<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Feedback extends Model
{
    protected $table = 'appointment_feedback';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'content', 'images',
        'handler_reply', 'status', 'handled_by', 'handled_at',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
