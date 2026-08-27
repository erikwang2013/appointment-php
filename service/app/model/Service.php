<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use Erikwang2013\WebmanScout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use support\Model;

class Service extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'appointment_service';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'category_id', 'name', 'description', 'cover_image', 'images',
        'price', 'original_price', 'duration', 'specs',
        'sales_volume', 'sort', 'status',
    ];

    protected $casts = [
        'images' => 'array',
        'specs' => 'array',
        'price' => 'float',
        'original_price' => 'float',
        'duration' => 'integer',
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
        return 'appointment_services';
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
            'description' => $this->description,
            'price' => $this->price,
            'original_price' => $this->original_price,
            'duration' => $this->duration,
            'sales_volume' => $this->sales_volume,
            'sort' => $this->sort,
            'status' => $this->status,
        ];
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function reviews()
    {
        return $this->hasMany(OrderReview::class, 'service_id');
    }

}
