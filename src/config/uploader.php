<?php

return [

    'storage_driver' => Optimus\Uploader\Storage\Cloudinary\CloudinaryStorage::class,

    'uploader_folder' => storage_path() . '/uploader',

    'temp_folder' => '/temp',

    'fine_uploader' => [

        'allowed_extensions' => [],
        'size_limit'    => 20*1024*1024, // 20 Mb
        'input_name'    => 'qqfile',
        'chunks_folder' => '/chunks'

    ]

];
