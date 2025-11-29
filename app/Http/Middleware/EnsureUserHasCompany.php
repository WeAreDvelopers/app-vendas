<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Se não tem empresa selecionada, tenta selecionar automaticamente
        if (!$user->current_company_id) {
            $firstCompany = $user->companies()->first();

            if (!$firstCompany) {
                // Usuário não tem acesso a nenhuma empresa
                return redirect()->route('panel.companies.setup')
                    ->with('error', 'Você precisa estar vinculado a uma empresa para acessar o sistema.');
            }

            // Seleciona automaticamente a primeira empresa
            $user->switchCompany($firstCompany->id);
        }

        // Compartilha a empresa atual com todas as views
        view()->share('currentCompany', $user->getCurrentCompany());

        return $next($request);
    }
}
