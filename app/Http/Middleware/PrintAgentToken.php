<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('printagent.token');
        $incoming = $request->header('X-PRINTAGENT-TOKEN') ?? $request->query('token');
        if (!$configured || $configured !== $incoming) {
            return response()->json(['error' => 'unauthorized'], 401);
        }
        return $next($request);
    }
}
