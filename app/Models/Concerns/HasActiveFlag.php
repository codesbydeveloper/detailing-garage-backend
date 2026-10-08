<?php

namespace App\Models\Concerns;

trait HasActiveFlag
{
    public function initializeHasActiveFlag(): void
    {
        $this->append('is_active');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
