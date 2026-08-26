<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class ServiceCategory extends Model
{
    protected $table = 'erik_service_category';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'name', 'icon', 'parent_id', 'sort', 'status',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

}
