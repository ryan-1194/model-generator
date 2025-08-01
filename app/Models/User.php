<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	//

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var list<string>
	 */
	protected $fillable = [
		'name',
		'email',
		'email_verified_at',
		'password',
		'remember_token',
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var list<string>
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'name' => 'string',
			'email' => 'string',
			'email_verified_at' => 'datetime',
			'password' => 'string',
			'remember_token' => 'string',
		];
	}

}
