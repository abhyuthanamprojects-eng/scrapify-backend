<?php

return [
    /**
     * FCM Driver.
     *
     * Supported: "http_v1", "legacy"
     */
    'driver' => env('FCM_DRIVER', 'http_v1'),

    /**
     * FCM Credentials.
     */
    'credentials' => [
        'file' => base_path(env('FIREBASE_CREDENTIALS', 'storage/app/firebase-auth.json')),
    ],
];
