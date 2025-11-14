<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int status
 */
class Hold extends Model
{
    protected function casts(): array
    {
        return [
            'status' => HoldStatus::class,
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(Slot::class);
    }
}
