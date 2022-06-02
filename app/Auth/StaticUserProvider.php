<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use JetBrains\PhpStorm\Pure;

use function array_column;
use function array_key_exists;
use function array_search;
use function count;
use function hash_equals;

class StaticUserProvider implements UserProvider
{
    public function __construct(private array $credentials_store)
    {
        //
    }

    #[Pure]
    public function retrieveById($identifier): ?Authenticatable
    {
        $key = array_search((int) $identifier, array_column($this->credentials_store, 'id'), true);
        return $key === false ? null : $this->getGenericUser($this->credentials_store[$key]);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        //
    }

    #[Pure]
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                array_key_exists('password', $credentials))) {
            return null;
        }

        if (!isset($credentials['email'])) {
            return null;
        }

        $key = array_search($credentials['email'], array_column($this->credentials_store, 'email'), true);
        return $key === false ? null : $this->getGenericUser($this->credentials_store[$key]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return hash_equals($user->getAuthPassword(), $credentials['password']);
    }

    #[Pure]
    protected function getGenericUser(?array $user = null): GenericUser|null
    {
        return $user !== null ? new GenericUser($user) : null;
    }
}
