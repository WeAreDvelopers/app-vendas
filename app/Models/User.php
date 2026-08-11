<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Empresas que este usuário tem acesso
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('is_admin')
            ->withTimestamps();
    }

    /**
     * Empresa atualmente selecionada
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * Obtém a empresa atual ou a primeira disponível
     */
    public function getCurrentCompany(): ?Company
    {
        if ($this->current_company_id) {
            return $this->currentCompany;
        }

        // Se não tem empresa selecionada, pega a primeira
        $firstCompany = $this->companies()->first();
        if ($firstCompany) {
            $this->switchCompany($firstCompany->id);
            return $firstCompany;
        }

        return null;
    }

    /**
     * Troca a empresa atual
     */
    public function switchCompany(int $companyId): bool
    {
        // Verifica se o usuário tem acesso a esta empresa
        if (!$this->companies()->where('companies.id', $companyId)->exists()) {
            return false;
        }

        $this->update(['current_company_id' => $companyId]);
        return true;
    }

    /**
     * Verifica se é admin de uma empresa
     */
    public function isAdminOf(int $companyId): bool
    {
        return $this->companies()
            ->where('companies.id', $companyId)
            ->wherePivot('is_admin', true)
            ->exists();
    }
}
