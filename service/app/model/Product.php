<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\WebmanScout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

class Product extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'erik_product';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'category_id', 'name', 'cover_image', 'images',
        'price', 'original_price', 'stock',
        'sales_volume', 'type', 'sort', 'status',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'float',
        'original_price' => 'float',
        'stock' => 'integer',
        'sales_volume' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * 搜索引擎索引名称
     */
    public function searchableAs(): string
    {
        return 'erik_products';
    }

    /**
     * 获取索引数据数组
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'stock' => $this->stock,
            'sales_volume' => $this->sales_volume,
            'type' => $this->type,
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }

}
