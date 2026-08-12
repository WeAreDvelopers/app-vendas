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
                if ($request->routeIs('panel.companies.create', 'panel.companies.store')) {
                    app(\App\Support\CurrentCompany::class)->set(null);

                    return $next($request);
                }

                return redirect()->route('panel.companies.create')
                    ->with('error', 'Você precisa criar/estar vinculado a uma empresa para acessar o sistema.');
            }

            // Seleciona automaticamente a primeira empresa
            $user->switchCompany($firstCompany->id);
        }

        app(\App\Support\CurrentCompany::class)->set($user->current_company_id);

        // Compartilha a empresa atual com todas as views
        view()->share('currentCompany', $user->getCurrentCompany());

        return $next($request);
    }
}
