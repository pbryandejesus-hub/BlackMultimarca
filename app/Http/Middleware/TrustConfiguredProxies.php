<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustConfiguredProxies extends Middleware
{
    protected function proxies(): array|string|null
    {
        return config('app.trusted_proxies');
    }

    protected function headers(): int
    {
        return Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO;
    }
}
