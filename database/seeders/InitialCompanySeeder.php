<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::transaction(function () {
            // 1. Criar empresa inicial
            $company = \App\Models\Company::create([
                'name' => 'Empresa Padrão',
                'document' => null,
                'email' => null,
                'phone' => null,
                'active' => true,
                'settings' => []
            ]);

            $this->command->info("✓ Empresa '{$company->name}' criada (ID: {$company->id})");

            // 2. Vincular todos os usuários existentes à empresa
            $users = \App\Models\User::all();
            foreach ($users as $user) {
                $company->users()->attach($user->id, [
                    'is_admin' => true, // Todos usuários existentes viram admins
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Define como empresa atual
                $user->update(['current_company_id' => $company->id]);

                $this->command->info("  ✓ Usuário '{$user->name}' vinculado à empresa");
            }

            // 3. Atualizar registros existentes com company_id
            $this->migrateData($company->id);

            $this->command->info("\n✅ Migração concluída com sucesso!");
            $this->command->info("   - Empresa ID: {$company->id}");
            $this->command->info("   - Usuários vinculados: " . $users->count());
        });
    }

    private function migrateData(int $companyId): void
    {
        // Atualiza supplier_imports
        $importsCount = \DB::table('supplier_imports')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
        $this->command->info("  ✓ {$importsCount} importações migradas");

        // Atualiza products
        $productsCount = \DB::table('products')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
        $this->command->info("  ✓ {$productsCount} produtos migrados");

        // Atualiza listings
        $listingsCount = \DB::table('listings')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
        $this->command->info("  ✓ {$listingsCount} listings migrados");

        // Atualiza orders
        $ordersCount = \DB::table('orders')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
        $this->command->info("  ✓ {$ordersCount} pedidos migrados");

        // Atualiza suppliers
        $suppliersCount = \DB::table('suppliers')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);
        $this->command->info("  ✓ {$suppliersCount} fornecedores migrados");

        $this->command->info("");
        $this->command->info("📊 Total de registros migrados: " .
            ($importsCount + $productsCount + $listingsCount + $ordersCount + $suppliersCount));
    }
}
