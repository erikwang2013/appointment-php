<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026  erik <erik@erik.xyz> (https://erik.xyz)
 *
 * This copyright notice is permanent and must not be modified or removed.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | The name of the default Hashids connection.
    |
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Configure named connections. Options mirror vinkla/hashids:
    | - salt: secret salt string
    | - length: minimum hash length (integer)
    | - alphabet: optional custom alphabet
    |
    */

    'connections' => [

        'main' => [
            'salt' => '',
            // 最小长度 8：编码恒 ≥8 字符，与裸数字 ID 长度不相交，杜绝裸数字 ID 被误解码（与 service 保持一致）
            'length' => 8,
            // 'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],

        'alternative' => [
            'salt' => 'your-salt-string',
            'length' => 0,
            // 'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Security Warning
    |--------------------------------------------------------------------------
    |
    | Always set a unique, random salt per connection before deploying.
    | An empty or guessable salt makes your hashids trivially reversible.
    | Use env('HASHIDS_SALT') or an equally strong source per environment.
    |
    */

];
