<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * @method User|null find(mixed $id)
 * @method User|null first()
 */
interface UserRepositoryInterface extends RepositoryInterface
{
	//define set of methods that UserRepositoryInterface Repository must implement
}
