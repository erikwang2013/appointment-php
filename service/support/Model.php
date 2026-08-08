<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace support;

use Erikwang2013\Snowflake\Snowflake;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Model $model): void {
            if (empty($model->getKey())) {
                $model->setAttribute($model->getKeyName(), self::nextId());
            }
        });
    }

    public static function generateId(): string
    {
        return self::nextId();
    }

    private static function nextId(): string
    {
        $config = config('snowflake', []);
        $snowflake = new Snowflake(
            (int)($config['datacenter_id'] ?? 1),
            (int)($config['worker_id'] ?? 1)
        );
        return (string)$snowflake->id();
    }
}
