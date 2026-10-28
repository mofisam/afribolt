<?php
namespace App\Controllers\Admin;

use App\Core\{Database, Session};
use App\Models\PlatformSetting;

class SettingsController extends BaseAdminController
{
    protected array $roles = ['admin'];

    public function index(): void
    {
        $this->page('admin/settings/index', ['title' => 'Settings', 'settings' => Database::all('SELECT * FROM platform_settings ORDER BY setting_key'), 'timezones' => $this->timezones()]);
    }

    public function save(): void
    {
        $posted = (array) ($_POST['s'] ?? []);
        $errors = [];
        $changed = 0;
        foreach (Database::all('SELECT * FROM platform_settings') as $s) {
            $id = (int) $s['id'];
            if (!array_key_exists($id, $posted) && $s['value_type'] !== 'boolean') { continue; }
            $raw = is_string($posted[$id] ?? null) ? trim($posted[$id]) : '';
            if ($s['value_type'] === 'boolean') { $raw = ($posted[$id] ?? '0') === '1' ? '1' : '0'; }
            if ($s['value_type'] === 'number' && $raw !== '' && !is_numeric($raw)) { $errors[] = "{$s['setting_key']} must be a number."; continue; }
            if ($s['value_type'] === 'json' && $raw !== '' && json_decode($raw) === null) { $errors[] = "{$s['setting_key']} must be valid JSON."; continue; }
            if ($s['setting_key'] === 'support_email' && $raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Support email is not valid.'; continue; }
            if ($s['setting_key'] === 'default_timezone' && !in_array($raw, timezone_identifiers_list(), true)) { $errors[] = 'Unknown timezone.'; continue; }
            if ($s['setting_key'] === 'default_currency' && !preg_match('/^[A-Za-z]{3}$/', $raw)) { $errors[] = 'Currency must be a 3-letter code.'; continue; }
            if ($raw !== (string) $s['setting_value']) {
                PlatformSetting::update($id, ['setting_value' => $raw === '' ? null : $raw, 'updated_by_admin_id' => $this->adminId()]);
                $this->audit('setting.updated', 'platform_setting', $id, ['value' => $s['setting_value']], ['key' => $s['setting_key'], 'value' => $raw]);
                $changed++;
            }
        }
        if ($errors) { Session::flash('error', implode(' ', $errors)); }
        $this->done($changed ? "{$changed} setting(s) saved." : 'No changes.', '/admin/settings', $errors && !$changed ? 'error' : 'success');
    }

    public function add(): void
    {
        $d = $this->validate(['setting_key' => 'required|max:150', 'setting_value' => 'max:2000', 'value_type' => 'required|in:string,number,boolean,json', 'description' => 'max:500'], '/admin/settings');
        $key = strtolower($d['setting_key']);
        if (!preg_match('/^[a-z0-9_.]+$/', $key)) { $this->done('Key may only contain letters, numbers, dots and underscores.', '/admin/settings', 'error'); }
        if (PlatformSetting::findBy('setting_key', $key)) { $this->done('That key already exists.', '/admin/settings', 'error'); }
        $id = PlatformSetting::create(['setting_key' => $key, 'setting_value' => $d['setting_value'], 'value_type' => $d['value_type'], 'description' => $d['description'],
            'is_public' => $this->checkbox('is_public'), 'updated_by_admin_id' => $this->adminId()]);
        $this->audit('setting.created', 'platform_setting', $id, null, ['key' => $key]);
        $this->done('Setting added.', '/admin/settings');
    }
}
