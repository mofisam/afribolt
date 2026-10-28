<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class AuditLogController extends BaseAdminController
{
    protected array $roles = ['admin'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($a = trim((string) $this->request->query('action', ''))) { $where[] = 'l.action LIKE ?'; $p[] = "%{$a}%"; }
        if ($t = $this->request->query('entity')) { $where[] = 'l.entity_type = ?'; $p[] = $t; }
        if ($f = $this->request->query('from')) { $where[] = 'l.created_at >= ?'; $p[] = $f . ' 00:00:00'; }
        if ($to = $this->request->query('to')) { $where[] = 'l.created_at <= ?'; $p[] = $to . ' 23:59:59'; }
        $entities = array_column(Database::all('SELECT DISTINCT entity_type FROM audit_logs ORDER BY entity_type'), 'entity_type', 'entity_type');
        [$rows, $pager] = $this->paged('audit_logs l LEFT JOIN admin_users a ON a.id = l.admin_user_id LEFT JOIN instructors i ON i.id = l.instructor_id WHERE ' . implode(' AND ', $where), $p,
            "l.*, CONCAT(a.first_name, ' ', a.last_name) AS admin_name, CONCAT(i.first_name, ' ', i.last_name) AS instructor_name", 'l.id DESC', 30);
        $this->page('admin/crud/index', [
            'title' => 'Audit log', 'rows' => $rows, 'pager' => $pager,
            'intro' => 'Read-only. Records changes to fees, dates, meeting links, registration settings, payments and more.',
            'filters' => [['name' => 'action', 'label' => 'Action contains'], ['name' => 'entity', 'label' => 'Record type', 'type' => 'select', 'options' => $entities],
                ['name' => 'from', 'label' => 'From', 'type' => 'date'], ['name' => 'to', 'label' => 'To', 'type' => 'date']],
            'columns' => [
                ['label' => 'When', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y, g:i:s A')],
                ['label' => 'Who', 'render' => fn($r) => $r['admin_name'] ?: ($r['instructor_name'] ?: 'System / student')],
                ['label' => 'Action', 'render' => fn($r) => $r['action']],
                ['label' => 'Record', 'render' => fn($r) => $r['entity_type'] . ($r['entity_id'] ? ' #' . $r['entity_id'] : '')],
                ['label' => 'Details', 'html' => true, 'render' => function ($r) {
                    if (!$r['old_values'] && !$r['new_values']) { return ''; }
                    $fmt = fn($j) => $j ? e(json_encode(json_decode($j), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : '';
                    return '<details><summary class="cursor-pointer underline text-bolt-dark">View</summary>'
                        . ($r['old_values'] ? '<p class="mt-2 font-bold">Before</p><pre class="text-xs whitespace-pre-wrap">' . $fmt($r['old_values']) . '</pre>' : '')
                        . ($r['new_values'] ? '<p class="mt-2 font-bold">After</p><pre class="text-xs whitespace-pre-wrap">' . $fmt($r['new_values']) . '</pre>' : '') . '</details>';
                }],
                ['label' => 'IP', 'render' => fn($r) => $r['ip_address'] ?? ''],
            ],
        ]);
    }
}
