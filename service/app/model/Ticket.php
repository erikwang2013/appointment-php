<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\model;

use support\Model;

class Ticket extends Model
{
    protected $table = 'appointment_ticket';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'user_id', 'category', 'description', 'images',
        'status', 'admin_id', 'reply_content', 'replied_at',
        'rating', 'rated_at',
    ];

    protected $casts = [
        'user_id'    => 'string',
        'images'     => 'array',
        'admin_id'   => 'string',
        'replied_at' => 'datetime',
        'rating'     => 'integer',
        'rated_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
