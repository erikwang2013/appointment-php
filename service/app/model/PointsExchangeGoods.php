<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class PointsExchangeGoods extends Model
{
    protected $table = 'erik_points_exchange_goods';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'type', 'points_cost', 'value',
        'stock', 'status', 'sort',
    ];

}
