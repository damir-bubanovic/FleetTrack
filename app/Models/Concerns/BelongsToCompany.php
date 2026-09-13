<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Company that owns the model.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope the query to a specific company.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForCompany(
        Builder $query,
        int $companyId,
    ): Builder {
        return $query->where(
            $this->getTable().'.company_id',
            $companyId,
        );
    }

    /**
     * Scope records visible to the given user.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeVisibleTo(
        Builder $query,
        User $user,
    ): Builder {
        if ($user->hasRole(UserRole::SuperAdmin->value)) {
            return $query;
        }

        return $query->where(
            $this->getTable().'.company_id',
            $user->company_id,
        );
    }
}
