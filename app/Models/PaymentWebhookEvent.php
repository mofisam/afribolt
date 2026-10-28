<?php
namespace App\Models;

use App\Core\Model;

class PaymentWebhookEvent extends Model
{
    protected static string $table = 'payment_webhook_events';
    protected static bool $softDeletes = false;
}
