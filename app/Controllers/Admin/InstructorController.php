<?php
namespace App\Controllers\Admin;

use App\Models\Instructor;

class InstructorController extends BaseAdminController
{
    protected array $roles = ['admin'];

    private function fields(bool $edit): array
    {
        return [
            ['name' => 'first_name', 'label' => 'First name', 'required' => true, 'half' => true],
            ['name' => 'last_name', 'label' => 'Last name', 'required' => true, 'half' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'half' => true],
            ['name' => 'phone', 'label' => 'Phone', 'half' => true],
            ['name' => 'biography', 'label' => 'Short biography', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'password', 'label' => $edit ? 'New password (leave blank to keep)' : 'Temporary password', 'type' => 'password', 'required' => !$edit, 'half' => true,
                'help' => 'Share it securely. The instructor signs in at /instructor/login.'],
            ['name' => 'account_status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'default' => 'active',
                'options' => ['active' => 'Active', 'invited' => 'Invited (cannot log in)', 'suspended' => 'Suspended']],
        ];
    }

    private function rules(bool $edit): array
    {
        $min = (int) config('security.password_min_length', 8);
        return ['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'email' => 'required|email|max:191', 'phone' => 'phone',
            'biography' => 'max:2000', 'password' => ($edit ? '' : 'required|') . "min:{$min}", 'account_status' => 'required|in:active,invited,suspended'];
    }

    public function index(): void
    {
        [$rows, $pager] = $this->paged(
            'instructors i', [], 'i.*, (SELECT COUNT(*) FROM cohort_instructors ci WHERE ci.instructor_id = i.id) AS cohort_count', 'i.last_name, i.first_name'
        );
        $this->page('admin/crud/index', [
            'title' => 'Instructors', 'createUrl' => url('/admin/instructors/create'), 'createLabel' => 'Add instructor', 'rows' => $rows, 'pager' => $pager,
            'columns' => [
                ['label' => 'Name', 'render' => fn($r) => $r['first_name'] . ' ' . $r['last_name']],
                ['label' => 'Email', 'render' => fn($r) => $r['email']],
                ['label' => 'Cohorts', 'render' => fn($r) => $r['cohort_count']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['account_status'])],
            ],
            'actions' => fn($r) => [['label' => 'Edit', 'url' => url('/admin/instructors/' . $r['id'] . '/edit')]],
            'intro' => 'Assign instructors to cohorts from the cohort page.',
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'Add instructor', 'action' => url('/admin/instructors'), 'fields' => $this->fields(false), 'values' => [], 'cancelUrl' => url('/admin/instructors')]);
    }

    public function store(): void
    {
        $d = $this->validate($this->rules(false), '/admin/instructors/create');
        $email = strtolower($d['email']);
        if (Instructor::findBy('email', $email)) {
            \App\Core\Session::flashInput($this->request->all(), ['email' => 'An instructor with this email already exists.']);
            $this->redirect('/admin/instructors/create');
        }
        $row = ['first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'phone' => $d['phone'], 'biography' => $d['biography'],
            'password_hash' => password_hash($d['password'], PASSWORD_DEFAULT), 'account_status' => $d['account_status']];
        $id = Instructor::create($row);
        unset($row['password_hash']);
        $this->audit('instructor.created', 'instructor', $id, null, $row);
        $this->done('Instructor added.', '/admin/instructors');
    }

    public function edit(string $id): void
    {
        $i = Instructor::find((int) $id) ?? $this->abort(404);
        $this->page('admin/crud/form', ['title' => 'Edit instructor', 'action' => url('/admin/instructors/' . $i['id']), 'fields' => $this->fields(true), 'values' => $i, 'cancelUrl' => url('/admin/instructors')]);
    }

    public function update(string $id): void
    {
        $i = Instructor::find((int) $id) ?? $this->abort(404);
        $d = $this->validate($this->rules(true), '/admin/instructors/' . $i['id'] . '/edit');
        $email = strtolower($d['email']);
        $other = Instructor::findBy('email', $email);
        if ($other && (int) $other['id'] !== (int) $i['id']) {
            \App\Core\Session::flashInput($this->request->all(), ['email' => 'Another instructor already uses this email.']);
            $this->redirect('/admin/instructors/' . $i['id'] . '/edit');
        }
        $row = ['first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'phone' => $d['phone'], 'biography' => $d['biography'], 'account_status' => $d['account_status']];
        $this->audit('instructor.updated', 'instructor', (int) $i['id'], $i, $row);
        if ($d['password']) { $row['password_hash'] = password_hash($d['password'], PASSWORD_DEFAULT); }
        Instructor::update((int) $i['id'], $row);
        $this->done('Instructor updated.', '/admin/instructors');
    }
}
