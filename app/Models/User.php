<?php

declare(strict_types=1);

namespace App\Models;

use App\Behavior\Filterable;
use App\Behavior\Sortable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * Relations
 * @property Collection|Subscription[] $subscriptions
 *
 * Magic methods
 * @method static Builder|self sortable()
 * @method static Builder|self filterable(array $except = [])
 * @method static User|null find(string|int $id, array $columns = ['*'])
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use Sortable;
    use Filterable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'id');
    }

    public function getPerPage(): int
    {
        $maxPerPage = 100;
        $perPage = (int) request('per-page', 50);

        return $perPage > $maxPerPage ? $maxPerPage : $perPage;
    }
}
