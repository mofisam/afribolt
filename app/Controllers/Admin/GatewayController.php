<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\PaymentGateway;

class GatewayController extends BaseAdminController
{
    protected array $roles = ['finance_manager'];

    public function index(): void
    {
        $this->page('admin/gateways/index', [
            'title' => 'Payment gateways',
            'gateways' => Database::all('SELECT * FROM payment_gateways ORDER BY display_name'),
            'events' => Database::all('SELECT w.*, g.display_name FROM payment_webhook_events w JOIN payment_gateways g ON g.id = w.gateway_id ORDER BY w.id DESC LIMIT 20'),
            'webhookUrl' => url('/webhooks/paystack'),
        ]);
    }

    private function fields(): array
    {
        return [
            ['name' => 'display_name', 'label' => 'Display name', 'required' => true, 'half' => true],
            ['name' => 'public_key', 'label' => 'Public key', 'half' => true, 'help' => 'Secret and webhook keys are NOT stored here. Set them in the server .env file.'],
            ['name' => 'supported_currencies', 'label' => 'Supported currencies', 'help' => 'Comma separated, e.g. NGN, USD'],
            ['name' => 'is_enabled', 'label' => 'Enabled', 'type' => 'checkbox', 'help' => 'When off, students cannot start new payments.'],
            ['name' => 'is_test_mode', 'label' => 'Test mode', 'type' => 'checkbox', 'help' => 'Informational. The mode is decided by the keys in .env.'],
        ];
    }

    public function edit(string $id): void
    {
        $g = PaymentGateway::find((int) $id) ?? $this->abort(404);
        $g['supported_currencies'] = implode(', ', (array) json_decode((string) $g['supported_currencies'], true));
        $this->page('admin/crud/form', ['title' => 'Edit ' . $g['display_name'], 'action' => url('/admin/gateways/' . $g['id']), 'fields' => $this->fields(), 'values' => $g, 'cancelUrl' => url('/admin/gateways')]);
    }

    public function update(string $id): void
    {
        $g = PaymentGateway::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(['display_name' => 'required|max:100', 'public_key' => 'max:255', 'supported_currencies' => 'max:100'], '/admin/gateways/' . $g['id'] . '/edit');
        $cur = array_values(array_filter(array_map(fn($c) => strtoupper(trim($c)), explode(',', (string) $d['supported_currencies'])), fn($c) => preg_match('/^[A-Z]{3}$/', $c)));
        $row = ['display_name' => $d['display_name'], 'public_key' => $d['public_key'], 'supported_currencies' => json_encode($cur),
            'is_enabled' => $this->checkbox('is_enabled'), 'is_test_mode' => $this->checkbox('is_test_mode')];
        PaymentGateway::update((int) $g['id'], $row);
        $this->audit('gateway.updated', 'payment_gateway', (int) $g['id'], $g, $row);
        $this->done('Gateway updated.', '/admin/gateways');
    }
}
