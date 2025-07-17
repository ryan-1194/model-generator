<?php

namespace App\Cache;

use App\Cache\Traits\WithHelpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;

abstract class CacheBase
{
    use WithHelpers;

    protected $key;

    protected $ttl;

    protected $data;

    abstract protected function cacheMiss();

    abstract protected function errorModelName(): string;

    abstract protected function errorModelId(): mixed;

    public function __construct($key, $ttl)
    {
        $this->key = $key;
        $this->ttl = $ttl;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function invalidate(): bool
    {
        return Cache::forget($this->key);
    }

    public function fetch()
    {
        $this->data = Cache::remember($this->getKey(), $this->ttl, function () {
            return $this->cacheMiss();
        });

        // only store "non-null" data from cacheMiss()
        if (is_null($this->data)) {
            $this->invalidate();
        }

        return $this->data;
    }

    public function fetchOrFail()
    {
        $result = $this->fetch();
        if ($result == null) {
            throw new ModelNotFoundException('Unable to retrieve '.$this->errorModelName().($this->errorModelId() ? (' '.$this->errorModelId()) : ''));
        } else {
            return $result;
        }
    }

    public function store($data): bool
    {
        return Cache::put($this->getKey(), $data, $this->ttl);
    }

    public function exists(): bool
    {
        return Cache::has($this->getKey());
    }
}
