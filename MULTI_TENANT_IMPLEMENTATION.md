# Implementação Multi-Empresa (Multi-Tenant)

## ✅ Progresso Atual

### Banco de Dados
- ✅ Migration `create_companies_table` - Criada
  - Tabela `companies` - Dados da empresa
  - Tabela `company_user` - Pivot usuários/empresas
  - Tabela `company_integrations` - Integrações por empresa
- ✅ Migration `add_company_id_to_tables` - Criada
  - Adicionado `company_id` em: supplier_imports, products, listings, orders, suppliers
  - Adicionado `current_company_id` em users

### Models
- ✅ `Company` model - Criado com relacionamentos
- ✅ `CompanyIntegration` model - Criado com criptografia
- ✅ `User` model - Atualizado com métodos de empresa

### Middleware
- ✅ `EnsureUserHasCompany` - Criado (garante empresa selecionada)

### Controllers
- ✅ `CompanyController` - Criado (vazio)
- ✅ `IntegrationController` - Criado (vazio)

## 📋 Próximos Passos

### 1. Rodar Migrations
```bash
php artisan migrate
```

### 2. Criar Seeder para Empresa Inicial
Criar `database/seeders/InitialCompanySeeder.php`:
- Criar empresa padrão
- Vincular usuário existente à empresa
- Migrar dados existentes para a empresa

### 3. Registrar Middleware
Em `bootstrap/app.php` ou `app/Http/Kernel.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', [
        \App\Http\Middleware\EnsureUserHasCompany::class,
    ]);
})
```

### 4. Implementar CompanyController
Métodos necessários:
- `index()` - Listar empresas do usuário
- `switch(Request $request)` - Trocar empresa atual
- `create()` - Formulário criar empresa
- `store()` - Salvar nova empresa
- `edit($id)` - Editar empresa
- `update($id)` - Atualizar empresa

### 5. Implementar IntegrationController
Métodos necessários:
- `index()` - Listar integrações da empresa
- `mercadoLivreConnect()` - Iniciar OAuth do ML
- `mercadoLivreCallback()` - Callback OAuth
- `mercadoLivreDisconnect()` - Desconectar ML
- `mercadoLivreReconnect()` - Reconectar conta ML

### 6. Criar Views
**Empresas:**
- `resources/views/panel/companies/index.blade.php`
- `resources/views/panel/companies/create.blade.php`
- `resources/views/panel/companies/edit.blade.php`
- `resources/views/panel/companies/setup.blade.php` (primeira vez)

**Integrações:**
- `resources/views/panel/integrations/index.blade.php`
- `resources/views/panel/integrations/mercado-livre.blade.php`

### 7. Atualizar Topbar
Em `resources/views/layouts/panel.blade.php`:
- Adicionar seletor de empresa (dropdown)
- Mostrar empresa atual
- Permitir troca rápida

### 8. Atualizar Controllers Existentes
Adicionar filtro por `company_id` em:
- `ImportUIController`
- `ProductUIController`
- `SupplierController`
- `ListingUIController`
- `OrderUIController`

Exemplo:
```php
// Antes
$imports = DB::table('supplier_imports')->get();

// Depois
$imports = DB::table('supplier_imports')
    ->where('company_id', auth()->user()->current_company_id)
    ->get();
```

### 9. Atualizar Jobs/Criar Registros
Ao criar registros, sempre incluir `company_id`:
```php
DB::table('supplier_imports')->insert([
    'company_id' => auth()->user()->current_company_id,
    // ... outros campos
]);
```

### 10. Migrar Controller do Mercado Livre
Atualizar `MercadoLivreController` para:
- Usar `company_integrations` ao invés de config global
- Armazenar tokens por empresa
- Filtrar por empresa atual

### 11. Adicionar Rotas
Em `routes/web.php`:
```php
Route::prefix('panel')->middleware('auth')->group(function () {
    // Empresas
    Route::get('/companies', [CompanyController::class, 'index'])->name('panel.companies.index');
    Route::post('/companies/switch', [CompanyController::class, 'switch'])->name('panel.companies.switch');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('panel.companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('panel.companies.store');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('panel.companies.edit');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('panel.companies.update');
    Route::get('/companies/setup', [CompanyController::class, 'setup'])->name('panel.companies.setup');

    // Integrações
    Route::get('/integrations', [IntegrationController::class, 'index'])->name('panel.integrations.index');
    Route::get('/integrations/mercado-livre/connect', [IntegrationController::class, 'mercadoLivreConnect'])->name('panel.integrations.ml.connect');
    Route::get('/integrations/mercado-livre/callback', [IntegrationController::class, 'mercadoLivreCallback'])->name('panel.integrations.ml.callback');
    Route::post('/integrations/mercado-livre/disconnect', [IntegrationController::class, 'mercadoLivreDisconnect'])->name('panel.integrations.ml.disconnect');
});
```

## 🎯 Fluxo de Uso

1. **Usuário faz login**
2. Middleware verifica se tem empresa selecionada
3. Se não tem, seleciona automaticamente a primeira
4. Se não tem nenhuma empresa, redireciona para setup
5. **Topbar mostra empresa atual com dropdown**
6. Usuário pode trocar de empresa a qualquer momento
7. **Todas as operações são filtradas pela empresa atual**

## 🔒 Segurança

- ✅ Credenciais de integração criptografadas
- ✅ Validação de acesso do usuário à empresa
- ✅ Isolamento de dados por empresa
- ✅ Verificação de permissões (is_admin)

## 📊 Schema Resumido

```
companies (id, name, document, email, settings)
  └─ company_user (pivot: user_id, company_id, is_admin)
  └─ company_integrations (integration_type, credentials, settings)
  └─ supplier_imports (company_id, ...)
  └─ products (company_id, ...)
  └─ suppliers (company_id, ...)
  └─ listings (company_id, ...)
  └─ orders (company_id, ...)

users (id, name, email, current_company_id)
```

## ⚠️ Atenção

Antes de rodar as migrations em produção:
1. Fazer backup do banco
2. Testar em ambiente de desenvolvimento
3. Criar seeder para migrar dados existentes
4. Validar que todos os dados têm `company_id` correto
