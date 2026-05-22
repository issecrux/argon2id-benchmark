<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, the bcrypt algorithm is
    | used; however, you remain free to modify this option if you wish.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => 'argon2id',

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Bcrypt algorithm. This will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that should be used when
    | passwords are hashed using the Argon algorithm. These will allow you
    | to control the amount of time it takes to hash the given password.
    |
    */

    // Argon2id options - default menggunakan RFC 9106 second recommended
    // (baseline penelitian untuk lingkungan memori terbatas)
    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),    // 64 MB - RFC 9106 second recommended
        'threads' => env('ARGON_THREADS', 4),       // parallelism - RFC 9106 second recommended
        'time' => env('ARGON_TIME', 3),             // time cost - RFC 9106 second recommended
        'verify' => env('HASH_VERIFY', true),
    ],

    'rehash_on_login' => env('HASH_REHASH_ON_LOGIN', false),

];
