<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PreventRequestsDuringMaintenance extends Middleware
{
    public function handle($request, Closure $next)
    {
        if (app()->isDownForMaintenance()) {
            throw new ServiceUnavailableHttpException(null, 'El sitio está en mantenimiento.');
        }

        return $next($request);
    }
}
