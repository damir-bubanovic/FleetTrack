<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    /**
     * Scope the query to a specific company.
     */
    public function scopeForCompany(
        Builder $query,
        int $companyId
    ): Builder {
        return $query->where(
            $this->getTable().'.company_id',
            $companyId
        );
    }
}