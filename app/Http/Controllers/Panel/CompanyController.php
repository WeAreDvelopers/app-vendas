<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Troca a empresa atual do usuário
     */
    public function switch(Request $request)
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $user = $request->user();
        $companyId = $request->input('company_id');

        // Verifica se o usuário tem acesso a esta empresa
        if (!$user->switchCompany($companyId)) {
            return back()->with('error', 'Você não tem acesso a esta empresa.');
        }

        return back()->with('ok', 'Empresa alterada com sucesso!');
    }

    /**
     * Lista empresas do usuário
     */
    public function index(Request $request)
    {
        $companies = $request->user()->companies()->get();

        return view('panel.companies.index', compact('companies'));
    }

    /**
     * Formulário de criar empresa
     */
    public function create()
    {
        return view('panel.companies.create');
    }

    /**
     * Salvar nova empresa
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20'
        ]);

        $company = Company::create([
            'name' => $request->name,
            'document' => $request->document,
            'email' => $request->email,
            'phone' => $request->phone,
            'active' => true
        ]);

        // Vincula o usuário como admin
        $company->users()->attach($request->user()->id, [
            'is_admin' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Troca para a nova empresa
        $request->user()->switchCompany($company->id);

        return redirect()->route('panel.companies.index')
            ->with('ok', 'Empresa criada com sucesso!');
    }

    /**
     * Editar empresa
     */
    public function edit(int $id)
    {
        $company = Company::findOrFail($id);

        // Verifica se tem acesso
        if (!auth()->user()->companies()->where('companies.id', $id)->exists()) {
            abort(403, 'Você não tem acesso a esta empresa.');
        }

        return view('panel.companies.edit', compact('company'));
    }

    /**
     * Atualizar empresa
     */
    public function update(Request $request, int $id)
    {
        $company = Company::findOrFail($id);

        // Verifica se é admin
        if (!auth()->user()->isAdminOf($id)) {
            abort(403, 'Apenas administradores podem editar a empresa.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20'
        ]);

        $company->update($request->only(['name', 'document', 'email', 'phone']));

        return back()->with('ok', 'Empresa atualizada com sucesso!');
    }
}
