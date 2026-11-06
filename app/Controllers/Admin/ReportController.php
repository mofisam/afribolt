<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class ReportController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager'];

    private function range(): array
    {
        $from = (string) $this->request->query('from', gmdate('Y-m-d', strtotime('-90 days')));
        $to = (string) $this->request->query('to', gmdate('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = gmdate('Y-m-d', strtotime('-90 days')); }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) { $to = gmdate('Y-m-d'); }
        return [$from, $to];
    }

    public function index(): void
    {
        [$from, $to] = $this->range();
        $fromDt = $from . ' 00:00:00'; $toDt = $to . ' 23:59:59';
        $this->page('admin/reports/index', [
            'title' => 'Reports', 'from' => $from, 'to' => $to,
            'byStatus' => Database::all('SELECT status, COUNT(*) AS n FROM enrolments WHERE registered_at BETWEEN ? AND ? GROUP BY status ORDER BY n DESC', [$fromDt, $toDt]),
            'revenue' => Database::all("SELECT DATE_FORMAT(paid_at, '%Y-%m') AS month, currency, COUNT(*) AS payments, SUM(amount) AS gross, SUM(refunded_amount) AS refunded
                                        FROM payments WHERE status IN ('successful','partially_refunded','refunded') AND paid_at BETWEEN ? AND ? GROUP BY month, currency ORDER BY month DESC", [$fromDt, $toDt]),
            'cohorts' => Database::all("SELECT h.cohort_name, h.cohort_code, h.status, h.capacity, h.start_at, h.timezone,
                    SUM(e.status = 'confirmed') AS confirmed, SUM(e.status IN ('pending_payment','pending_review')) AS pending, SUM(e.status = 'waitlisted') AS waitlisted
                    FROM cohorts h LEFT JOIN enrolments e ON e.cohort_id = h.id WHERE h.deleted_at IS NULL AND h.start_at >= ? - INTERVAL 180 DAY GROUP BY h.id ORDER BY h.start_at DESC LIMIT 50", [$fromDt]),
        ]);
    }

    public function export(): void
    {
        [$from, $to] = $this->range();
        $type = (string) $this->request->query('type', 'enrolments');
        if ($type === 'payments') {
            $head = ['Reference', 'Student', 'Email', 'Cohort / course', 'Amount', 'Currency', 'Status', 'Refunded', 'Method', 'Paid at (UTC)', 'Created (UTC)'];
            $rows = Database::all("SELECT p.internal_reference, CONCAT(u.first_name,' ',u.last_name) n, u.email, COALESCE(h.cohort_name, CONCAT('Self-paced: ', c.title)) AS cohort_name, p.amount, p.currency, p.status, p.refunded_amount, p.payment_method, p.paid_at, p.created_at
                FROM payments p LEFT JOIN enrolments e ON e.id = p.enrolment_id LEFT JOIN cohorts h ON h.id = e.cohort_id LEFT JOIN course_access ca ON ca.id = p.course_access_id LEFT JOIN courses c ON c.id = ca.course_id
                JOIN users u ON u.id = COALESCE(e.student_id, ca.user_id)
                WHERE p.created_at BETWEEN ? AND ? ORDER BY p.created_at DESC", [$from . ' 00:00:00', $to . ' 23:59:59']);
        } else {
            $head = ['Enrolment', 'Student', 'Email', 'Phone', 'Cohort', 'Status', 'Fee', 'Paid', 'Currency', 'Registered (UTC)'];
            $rows = Database::all("SELECT e.enrolment_number, CONCAT(u.first_name,' ',u.last_name) n, u.email, u.phone, h.cohort_name, e.status, e.fee_amount, e.amount_paid, e.currency, e.registered_at
                FROM enrolments e JOIN users u ON u.id = e.student_id JOIN cohorts h ON h.id = e.cohort_id
                WHERE e.registered_at BETWEEN ? AND ? ORDER BY e.registered_at DESC", [$from . ' 00:00:00', $to . ' 23:59:59']);
        }
        $this->audit('report.exported', 'report', null, null, ['type' => $type, 'from' => $from, 'to' => $to]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $type . '_' . $from . '_to_' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
        fputcsv($out, $head);
        foreach ($rows as $r) {
            // Neutralise spreadsheet formulas in user-supplied text
            fputcsv($out, array_map(fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false ? "'" . $v : $v, array_values($r)));
        }
        fclose($out);
        exit;
    }
}
