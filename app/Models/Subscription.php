<?php

declare(strict_types=1);

namespace App\Models;

use App\Behavior\Filterable;
use App\Behavior\Sortable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Subscription
 * @property int $id
 * @property string|null $name
 * @property array $filters
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * Relations
 * @property User $user
 *
 * Magic methods
 * @method static Builder|self sortable()
 * @method static Builder|self filterable(array $except = [])
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find(string|int $id, array $columns = ['*'])
 * @method static static|static[]|Collection findOrFail(mixed $id, array $columns = ['*'])
 */
class Subscription extends Model
{
    use HasFactory;
    use Sortable;
    use Filterable;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getPerPage(): int
    {
        $maxPerPage = 100;
        $perPage = (int) request('per-page', 50);

        return $perPage > $maxPerPage ? $maxPerPage : $perPage;
    }
}
