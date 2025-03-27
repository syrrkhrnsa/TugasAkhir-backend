<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertToPut
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        // Hanya konversi jika ada header/parameter khusus
        if ($request->isMethod('post') && $request->route()->getName() == 'sertifikat.update') {
            $request->setMethod('PUT');
        }
        return $next($request);
    }
}
