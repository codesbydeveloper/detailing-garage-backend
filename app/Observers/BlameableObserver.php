<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class BlameableObserver
{
    public function creating(Model $model): void
    {
        $userId = auth()->id();

        if (! $userId) {
            return;
        }

        if ($model->isFillable('created_by') && empty($model->created_by)) {
            $model->setAttribute('created_by', $userId);
        }

        if ($model->isFillable('updated_by') && empty($model->updated_by)) {
            $model->setAttribute('updated_by', $userId);
        }
    }

    public function updating(Model $model): void
    {
        $userId = auth()->id();

        if ($userId && $model->isFillable('updated_by')) {
            $model->setAttribute('updated_by', $userId);
        }
    }
}
