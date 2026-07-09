<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/*',
        'api/login',
        'api/logout',
        'api/events',
        'api/events/*',
        'api/news',
        'api/news/*',
        'api/galleries',
        'api/galleries/*',
        'api/tourist-places',
        'api/tourist-places/*',
        'login',
        'logout',
    ];
}