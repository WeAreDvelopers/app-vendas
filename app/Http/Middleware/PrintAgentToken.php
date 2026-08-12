<?php
namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrintAgentToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-PRINTAGENT-TOKEN') ?? $request->query('token');

        if (empty($incoming)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        // Token mestre global (config): vê as filas de todas as empresas.
        $master = config('printagent.token');
        if ($master && hash_equals((string) $master, (string) $incoming)) {
            $request->attributes->set('print_company_id', null);
            return $next($request);
        }

        // Token por empresa: o agente só recebe as etiquetas da sua empresa.
        $company = Company::where('print_agent_token', $incoming)->first();
        if ($company) {
            $request->attributes->set('print_company_id', $company->id);
            return $next($request);
        }

        return response()->json(['error' => 'unauthorized'], 401);
    }
}
