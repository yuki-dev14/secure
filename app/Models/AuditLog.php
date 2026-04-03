<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_type', 'event',
        'auditable_type', 'auditable_id',
        'old_values', 'new_values',
        'url', 'ip_address', 'user_agent',
        'tags', 'description', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function auditable()
    {
        return $this->morphTo();
    }

    public static function record(
        string $event,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $description = null,
        ?string $tags = null,
    ): self {
        return static::create([
            'user_id'        => auth()->id(),
            'user_type'      => auth()->user()?->role ?? 'guest',
            'event'          => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->getKey(),
            'old_values'     => $oldValues ?: null,
            'new_values'     => $newValues ?: null,
            'url'            => request()->fullUrl(),
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'tags'           => $tags,
            'description'    => $description,
            'created_at'     => now(),
        ]);
    }
}
