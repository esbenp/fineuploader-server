<?php

return [

    'uploader_folder' => storage_path() . '/uploader',

    'temp_folder' => '/temp',

    'fine_uploader' => [

        'allowed_extensions' => [],
        'size_limit'    => 20*1024*1024, // 20 Mb
        'input_name'    => 'qqfile',
        'chunks_folder' => '/chunks'

    ],

    'storage' => 'local',

    'storage_url_resolver' => function($file){
        return sprintf("http://laravel-packages.dev/images/%s/%s", $file->getUploaderPath(), $file->getFilename());
    },

    'success_response_class' => Optimus\FineuploaderServer\Response\OptimusResponse::class,

    'storages' => [

        'local' => [
            'class' => Optimus\FineuploaderServer\Storage\LocalStorage::class,
            'config' => [
                'root_folder' => storage_path() . '/uploader'
            ]
        ],

        'cloudinary' => [
            'class' => Optimus\FineuploaderServer\Storage\CloudinaryStorage::class,
            'config' => [
                'cloud_name' => 'traede',
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
                'editions' => [
                    'thumbnail' => ['crop' => 'fill', 'width' => 100, 'height' => 100]
                ]
            ]
        ]

    ],

    'naming_strategy' => Optimus\FineuploaderServer\Naming\NoRenameStrategy::class,

    'middleware' => [
        [
            'class' => Optimus\FineuploaderServer\Middleware\ThumbnailCreator::class,
            'config' => [

            ]
        ]
    ]

];
