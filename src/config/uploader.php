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

    'storage' => 'cloudinary',

    'storages' => [

        'local' => [
            'class' => \Optimus\FineuploaderServer\Storage\LocalStorage::class,
            'config' => [
                'root_folder' => storage_path() . '/uploader'
            ]
        ],

        'cloudinary' => [
            'class' => \Optimus\FineuploaderServer\Storage\CloudinaryStorage::class,
            'config' => [
                'cloud_name' => 'traede',
                'api_key' => '753542315792455',
                'api_secret' => 'ycOrCejdXnst8jlfNdv-CSM9ROo'
            ]
        ]

    ],

    'naming_strategy' => Optimus\FineuploaderServer\Naming\NoRenameStrategy::class

];
