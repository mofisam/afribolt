<?php
namespace App\Controllers\Admin;

use App\Models\AdminUser;

/** Manage admin accounts. Super admins only. */
class AdminUserController extends BaseAdminController
{
    protected array $roles = []; // handled below: super_admin only

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        if (!\App\Core\Auth::adminHasRole('super_admin')) { $this->abort(403); }
    }

    private const ROLES = ['super_admin' => 'Super admin', 'admin' => 'Admin', 'content_manager' => 'Content manager', 'finance_manager' => 'Finance manager'];

    private function fields(bool $edit): array
    {
        return [
            ['name' => 'first_name', 'label' => 'First name', 'required' => true, 'half' => true],
            ['name' => 'last_name', 'label' => 'Last name', 'required' => true, 'half' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'admin_role', 'label' => 'Role', 'type' => 'select', 'options' => self::ROLES, 'half' => true, 'default' => 'admin',
                'help' => 'Content: courses, cohorts, sessions. Finance: payments, reports. Admin: everything except admin accounts.'],
            ['name' => 'account_status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'options' => ['active' => 'Active', 'suspended' => 'Suspended'], 'default' => 'active'],
            ['name' => 'password', 'label' => $edit ? 'New password (leave blank to keep)' : 'Password', 'type' => 'password', 'required' => !$edit],
        ];
    }

    private function rules(bool $edit): array
    {
        $min = max(10, (int) config('security.password_min_length', 8));
        return ['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'email' => 'required|email|max:191',
            'admin_role' => 'required|in:' . implode(',', array_keys(self::ROLES)), 'account_status' => 'required|in:active,suspended',
            'password' => ($edit ? '' : 'required|') . "min:{$min}"];
    }

    public function index(): void
    {
        [$rows, $pager] = $this->paged('admin_users a', [], 'a.*', 'a.first_name');
        $this->page('admin/crud/index', [
            'title' => 'Admin users', 'createUrl' => url('/admin/admin-users/create'), 'createLabel' => 'Add admin', 'rows' => $rows, 'pager' => $pager,
            'columns' => [
                ['label' => 'Name', 'render' => fn($r) => $r['first_name'] . ' ' . $r['last_name']],
                ['label' => 'Email', 'render' => fn($r) => $r['email']],
                ['label' => 'Role', 'render' => fn($r) => self::ROLES[$r['admin_role']] ?? $r['admin_role']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['account_status'])],
                ['label' => 'Last login', 'render' => fn($r) => $r['last_login_at'] ? local_date($r['last_login_at']) : 'Never'],
            ],
            'actions' => fn($r) => [['label' => 'Edit', 'url' => url('/admin/admin-users/' . $r['id'] . '/edit')]],
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'Add admin', 'action' => url('/admin/admin-users'), 'fields' => $this->fields(false), 'values' => [], 'cancelUrl' => url('/admin/admin-users')]);
    }

    public function store(): void
    {
        $d = $this->validate($this->rules(false), '/admin/admin-users/create');
        $email = strtolower($d['email']);
        if (AdminUser::findBy('email', $email)) {
            \App\Core\Session::flashInput($this->request->all(), ['email' => 'An admin with this email already exists.']);
            $this->redirect('/admin/admin-users/create');
        }
        $row = ['first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'admin_role' => $d['admin_role'],
            'account_status' => $d['account_status'], 'created_by_admin_id' => $this->adminId()];
        $id = AdminUser::create($row + ['password_hash' => password_hash($d['password'], PASSWORD_DEFAULT)]);
        $this->audit('admin_user.created', 'admin_user', $id, null, $row);
        $this->done('Admin added.', '/admin/admin-users');
    }

    public function edit(string $id): void
    {
        $a = AdminUser::find((int) $id) ?? $this->abort(404);
        $this->page('admin/crud/form', ['title' => 'Edit admin', 'action' => url('/admin/admin-users/' . $a['id']), 'fields' => $this->fields(true), 'values' => $a, 'cancelUrl' => url('/admin/admin-users')]);
    }

    public function update(string $id): void
    {
        $a = AdminUser::find((int) $id) ?? $this->abort(404);
        $d = $this->validate($this->rules(true), '/admin/admin-users/' . $a['id'] . '/edit');
        if ((int) $a['id'] === $this->adminId() && ($d['admin_role'] !== 'super_admin' || $d['account_status'] !== 'active')) {
            $this->done('You cannot demote or suspend your own account.', '/admin/admin-users/' . $a['id'] . '/edit', 'error');
        }
        $email = strtolower($d['email']);
        $other = AdminUser::findBy('email', $email);
        if ($other && (int) $other['id'] !== (int) $a['id']) {
            \App\Core\Session::flashInput($this->request->all(), ['email' => 'Another admin already uses this email.']);
            $this->redirect('/admin/admin-users/' . $a['id'] . '/edit');
        }
        $row = ['first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'admin_role' => $d['admin_role'], 'account_status' => $d['account_status']];
        $this->audit('admin_user.updated', 'admin_user', (int) $a['id'], $a, $row);
        if ($d['password']) { $row['password_hash'] = password_hash($d['password'], PASSWORD_DEFAULT); }
        AdminUser::update((int) $a['id'], $row);
        $this->done('Admin updated.', '/admin/admin-users');
    }
}
