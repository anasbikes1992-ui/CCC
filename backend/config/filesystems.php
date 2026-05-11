<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        // Supabase Storage is S3-compatible.
        'supabase' => [
            'driver' => 's3',
            'key' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'secret' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'region' => 'us-east-1',
            'bucket' => env('SUPABASE_BUCKET_PROOFS', 'ccc-proofs'),
            'endpoint' => env('SUPABASE_URL').'/storage/v1/s3',
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
