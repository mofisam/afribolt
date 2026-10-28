<?php
namespace App\Middleware;

use App\Core\Request;

interface Middleware
{
    public function handle(Request $request): void;
}
