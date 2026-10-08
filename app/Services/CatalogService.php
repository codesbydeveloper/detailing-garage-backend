<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function __construct(private ActivityLogService $activity) {}

    public function remove(
        Model $model,
        string $relation,
        string $module,
        string $updateAction,
        string $deleteAction,
        string $label,
        User $actor,
        ?string $protectedSlug = null,
    ): string {
        if ($protectedSlug !== null && ($model->slug ?? null) === $protectedSlug) {
            throw ValidationException::withMessages([
                'status' => ["{$label} is required by the system and cannot be deleted."],
            ]);
        }

        $query = $model->{$relation}();

        if ($query instanceof HasMany) {
            $inUse = $query->withTrashed()->exists();
        } else {
            $inUse = $query->exists();
        }

        if ($inUse) {
            $old = ['status' => $model->status];
            $model->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);
            $this->activity->log(
                $updateAction,
                $module,
                "{$label} deactivated because it is already in use",
                $model,
                $old,
                ['status' => 'inactive'],
                'success',
                $actor,
            );

            return 'deactivated';
        }

        $snapshot = $this->activity->snapshot($model);
        $model->delete();
        $this->activity->log($deleteAction, $module, "{$label} deleted", $model, $snapshot, null, 'success', $actor);

        return 'deleted';
    }
}
