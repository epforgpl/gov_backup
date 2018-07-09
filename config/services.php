<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'elastic' => [
        'endpoint' => env('ELASTIC_ENDPOINT')
    ],

    'storage' => [
        'bucket' => 'govbackup-public',
        'default' => env('STORAGE_PROFILE', 'openstack-ovh'),

        'openstack-ovh' => [
            'type' => 'openstack',
            'username' => env('OPENSTACK_USERNAME'),
            'password' => env('OPENSTACK_PASSWORD'),
            'tenantId' => env('OPENSTACK_TENANT_ID'),
            'authUrl'  => env('OPENSTACK_AUTH_URL', 'https://auth.cloud.ovh.net/v2.0'),
            'publicUrlRoot' => env('OPENSTACK_PUBLIC_URL_ROOT', 'https://storage.waw1.cloud.ovh.net/'),
            'region'   => env('OPENSTACK_REGION', 'WAW1'),
            'keystoneAuth' => env('OPENSTACK_KEYSTONE_AUTH', 'v2'),
        ],

        's3' => [
            'type' => 's3',
            'endpoint' => env('S3_ENDPOINT'),
            'accessKey' => env('S3_ACCESS_KEY'),
            'secretKey' => env('S3_SECRET_KEY'),
        ]
    ],
];
