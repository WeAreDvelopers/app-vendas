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
        return $this->id;
    }

    public function clear(): void
    {
        $this->id = null;
    }
}
