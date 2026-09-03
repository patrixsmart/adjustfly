<?php

declare(strict_types=1);

namespace Patrixsmart\Adjustfly\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Patrixsmart\Adjustfly\Database\Factories\AdjustmentFactory;

/**
 * @property int $id
 * @property string $adjustable_type
 * @property int|string $adjustable_id
 * @property string|null $event
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class Adjustment extends Model
{
    /** @use HasFactory<AdjustmentFactory> */
    use HasFactory;

    use MassPrunable;
    use SoftDeletes;

    protected $fillable = [
        'adjustable_label',
        'event',
        'before',
        'after',
        'ip_address',
        'user_agent',
    ];

    /**
     * The adjustment payload is written by the package, never by user input,
     * so the user foreign key is added to the fillable list at runtime.
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable[] = $this->userForeignKey();

        parent::__construct($attributes);
    }

    public function getTable(): string
    {
        return $this->table ?? config('adjustfly.table', 'adjustments');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /**
     * The model that was adjusted.
     *
     * @return MorphTo<Model, $this>
     */
    public function adjustable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user credited with the adjustment.
     *
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo($this->userModel(), $this->userForeignKey());
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForUser(Builder $query, Model|int|string|null $user): Builder
    {
        return $query->where(
            $this->userForeignKey(),
            $user instanceof Model ? $user->getKey() : $user
        );
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForAdjustable(Builder $query, Model $adjustable): Builder
    {
        return $query
            ->where('adjustable_type', $adjustable->getMorphClass())
            ->where('adjustable_id', $adjustable->getKey());
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('adjustable_type', $type);
    }

    /**
     * The attributes that were touched by this adjustment.
     *
     * @return array<int, string>
     */
    public function changedAttributes(): array
    {
        return array_keys($this->after ?? []);
    }

    /**
     * @return Builder<$this>
     */
    public function prunable(): Builder
    {
        $days = config('adjustfly.prune_after_days');

        if ($days === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<=', now()->subDays((int) $days));
    }

    protected static function newFactory(): Factory
    {
        return AdjustmentFactory::new();
    }

    public function userForeignKey(): string
    {
        return config('adjustfly.user.foreign_key', 'user_id');
    }

    /**
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        return config('adjustfly.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\Models\User';
    }
}
