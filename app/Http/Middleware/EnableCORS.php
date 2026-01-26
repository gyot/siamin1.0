<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnableCORS
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->header("Access-Control-Allow-Origin", "*");
        $response->header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
        $response->header("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With");
        return $response;
    }
}