<?php

namespace App\Repositories\Contracts;

use App\Models\Post;

/**
 * @method Post|null find(mixed $id)
 * @method Post|null first()
 */
interface PostRepositoryInterface extends RepositoryInterface
{
	//define set of methods that PostRepositoryInterface Repository must implement
}
