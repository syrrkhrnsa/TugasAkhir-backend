<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipConvertEmptyStrings
{
    public function handle($request, Closure $next)
    {
        // Lewati ConvertEmptyStringsToNull untuk route ini
        return $next($request);
    }
}