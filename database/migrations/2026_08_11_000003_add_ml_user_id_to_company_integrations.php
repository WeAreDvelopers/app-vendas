<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->string('ml_user_id')->nullable()->index()->after('integration_type');
        });

        // Backfill: extrai credentials.user_id do JSON para a nova coluna (ML apenas).
        foreach (DB::table('company_integrations')
            ->where('integration_type', 'mercado_livre')
            ->whereNull('ml_user_id')->get() as $row) {
            $creds = json_decode($row->credentials ?? '{}', true);
            $mlUserId = $creds['user_id'] ?? null;
            if ($mlUserId) {
                DB::table('company_integrations')->where('id', $row->id)
                    ->update(['ml_user_id' => (string) $mlUserId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('company_integrations', function (Blueprint $table) {
            $table->dropIndex(['ml_user_id']);
            $table->dropColumn('ml_user_id');
        });
    }
};
