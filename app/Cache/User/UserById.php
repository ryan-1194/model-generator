<?php

namespace App\Cache\User;

use App\Cache\CacheBase;
use App\Models\User as Model;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * @method Model|null fetch()
 * @method Model fetchOrFail()
 * @method static Model get()
 */
class UserById extends CacheBase
{
    public function __construct(protected int $id)
    {
        parent::__construct("users.{$id}", now()->addHour());
    }

    protected function cacheMiss()
    {
        return app(UserRepositoryInterface::class)->find($this->id);
    }

    protected function errorModelName(): string
    {
        return 'User';
    }

    protected function errorModelId(): int
    {
        return $this->id;
    }
}
