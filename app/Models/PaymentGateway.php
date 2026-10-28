<?php
namespace App\Models;

use App\Core\Model;

class PaymentGateway extends Model
{
    protected static string $table = 'payment_gateways';
    protected static bool $softDeletes = false;
}
