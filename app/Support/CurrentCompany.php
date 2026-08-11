<?php
// app/Support/CurrentCompany.php
namespace App\Support;

class CurrentCompany
{
    protected ?int $id = null;

    public function set(?int $companyId): void
    {
        $this->id = $companyId;
    }

    public function id(): ?int
    {
        if ($this->id !== null) {
            return $this->id;
        }

        return auth()->check() ? auth()->user()->current_company_id : null;
    }

    public function clear(): void
    {
        $this->id = null;
    }
}
