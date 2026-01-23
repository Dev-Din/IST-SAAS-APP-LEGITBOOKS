<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    protected function log(Model $model, string $action, ?array $before = null, ?array $after = null): void
    {
        if (! property_exists($model, 'tenant_id') && ! $model->getAttribute('tenant_id')) {
            return;
        }

        AuditLog::create([
            'tenant_id' => $model->tenant_id,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'performed_by' => optional(Auth::user())->id,
            'action' => $action,
            'before' => $before,
            'after' => $after,
        ]);
    }

    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->log($model, 'updated', $model->getOriginal(), $model->getAttributes());
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getOriginal(), null);
    }
}
