<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Laratips\Sortable\Sortable;

/**
 * Class Listing
 * @property int $id
 * @property string $url
 * @property string $types
 * @property string $address
 * @property string $city
 * @property string $county
 * @property string $state
 * @property string $zip
 * @property float|null $latitude
 * @property float|null $longitude
 * @property float $price
 * @property float $area
 * @property float|null $price_per_acre
 * @property float|null $local_avg_price_per_acre
 * @property float|null $local_median_price_per_acre
 * @property float|null $local_min_price_per_acre
 * @property float|null $price_to_local_avg
 * @property float|null $price_to_local_min
 * @property string|null $notes
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $checked_at
 *
 * Magic methods
 * @method static QueryBuilder getQuery()
 * @method static Builder|self sortable()
 * @method static Builder|self filterable(array $except = [])
 * @method static Builder select(mixed $columns = ['*'])
 * @method static Builder selectRaw(string $expression, array $bindings = [])
 * @method static Builder distinct()
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder whereBetween(string|Expression $column, array $values, string $boolean = 'and', bool $not = false)
 * @method static Builder whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static Builder from(Closure|QueryBuilder|string $table, string|null $as = null)
 * @method static Builder joinSub(Closure|QueryBuilder|Builder|string $query, string $as, Closure|string $first, string|null $operator = null, string|null $second = null, string $type = 'inner', bool $where = false)
 * @method static self findOrFail(mixed $id, array $columns = ['*'])
 * @method static int upsert(array $values, array|string $uniqueBy, array|null $update = null)
 * @method static int update(array $values)
 * @method static LengthAwarePaginator paginate(int|null $perPage = null, array $columns = ['*'], string $pageName = 'page', int|null $page = null)
 *
 */
class Listing extends Model
{
    use HasFactory;
    use Sortable;
    use \App\Behavior\Filterable;

    public const STATUS_AVAILABLE = 1;
    public const STATUS_UNDER_CONTRACT = 2;
    public const STATUS_OFF_MARKET = 3;
    public const STATUS_SOLD = 4;
    public const STATUS_STALE = 10;

    public array $defaultSort = ['sort' => 'updated_at', 'direction' => 'desc'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'area' => 'float',
        'price_per_acre' => 'float',
        'checked_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'url',
        'types',
        'address',
        'city',
        'county',
        'state',
        'zip',
        'latitude',
        'longitude',
        'price',
        'area',
        'notes',
        'status',
        'checked_at',
    ];

    #[ArrayShape([
        self::STATUS_AVAILABLE => "string",
        self::STATUS_UNDER_CONTRACT => "string",
        self::STATUS_OFF_MARKET => "string",
        self::STATUS_SOLD => "string",
        self::STATUS_STALE => "string"
    ])]
    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_UNDER_CONTRACT => 'Under Contract',
            self::STATUS_OFF_MARKET => 'Off Market',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_STALE => 'Stale',
        ];
    }

    #[Pure]
    public function getStatus(): string
    {
        return self::statuses()[$this->status];
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getPerPage(): int
    {
        $maxPerPage = 100;
        $perPage = (int) request('per-page', 50);

        return $perPage > $maxPerPage ? $maxPerPage : $perPage;
    }
}
