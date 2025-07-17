<?php

namespace App\Repositories\Contracts;

use App\Models\Blog;

/**
 * @method Blog|null find(mixed $id)
 * @method Blog|null first()
 */
interface BlogRepositoryInterface extends RepositoryInterface
{
	//define set of methods that BlogRepositoryInterface Repository must implement
}
