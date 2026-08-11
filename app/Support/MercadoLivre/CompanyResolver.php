<?php

namespace App\Support\MercadoLivre;

use App\Models\CompanyIntegration;

class CompanyResolver
{
    public function companyIdForMlUser(int|string $mlUserId): ?int
    {
        $integration = CompanyIntegration::query()
            ->where('integration_type', 'mercado_livre')
            ->where('ml_user_id', (string) $mlUserId)
            ->first();

        return $integration?->company_id;
    }
}
