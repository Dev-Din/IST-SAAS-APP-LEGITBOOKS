<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Services\TenantContext;

trait HasTenantScope
{
    /**
     * Boot the trait.
     */
    protected static function bootHasTenantScope(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app(TenantContext::class)->getTenant();
            if ($tenant) {
                $builder->where('tenant_id', $tenant->id);
            }
        });

        static::creating(function ($model) {
            $tenant = app(TenantContext::class)->getTenant();
            if ($tenant && !$model->tenant_id) {
                $model->tenant_id = $tenant->id;
            }
        });
    }
}

