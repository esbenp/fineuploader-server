# Fineuploader Server

[![Build Status](https://travis-ci.org/esbenp/fineuploader-server.svg)](https://travis-ci.org/esbenp/fineuploader-server) [![Coverage Status](https://coveralls.io/repos/esbenp/fineuploader-server/badge.svg?branch=master)](https://coveralls.io/r/esbenp/fineuploader-server?branch=master)

## Installation

```bash
composer require optimus/fineuploader-server 0.3.*
```

## Usage

This is basically some wrapper classes around
[Fine Uploader's PHP server example](https://github.com/esbenp/fineuploader-server/blob/master/src/Vendor/FineUploader.php).

### 1. Publish/edit configuration

If you are using Laravel you can integrate the uploader server by adding the service provider to your `config/app.php`

`config/app.php`
```
// ... other service providers
Optimus\FineuploaderServer\Provider\LaravelServiceProvider::class
```

Then publish the configuration

`php artisan vendor:publish`

### 2. Create routes and controller methods

Somewhere in your routes file(s)

```
$router->post('/uploader/upload', '\Optimus\FineuploaderServer\Controller\LaravelController@upload');
$router->delete('/uploader/delete/{uuid}', '\Optimus\FineuploaderServer\Controller\LaravelController@delete');
$router->get('/uploader/session', '\Optimus\FineuploaderServer\Controller\LaravelController@session');
```

### 3. Upload

This works fairly straight forward out of the box with fineuploader.
I have not tried it with "vanilla" fineuploader but use the server with [fineuploader-client](https://github.com/esbenp/fineuploader-client)

Basically you send uploads to `POST /uploader/upload`. Delete using `DELETE /uploader/delete/{uuid}`.
And repopulate the uploader using `GET /uploader/session`.

## Configuration

When publishing assets you will publish `uploader.php` to your configs directory. It is already populated
with some sensible defaults. If you wish for the uploader to generate thumbnails using the
thumbnail creator middleware you have to install the package as well using.

```
composer require optimus/fineuploader-server-thumbnail-creator 0.1.*
```

You can also choose to use [Cloudinary](http://cloudinary.com/) as a storage backend. Here is an example of
how your configuration could look like using a Cloudinary backend. Notice the thumbnail middleware is missing
since the Cloudinary storage provider will add it automatically using Cloudinary.

`config/uploader.php`
```
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

    // Can be overridden by client
    'thumbnails' => [
        'height' => 100,
        'width' => 100,
        'crop' => 'fill'
    ],

    'storage' => 'cloudinary',

    'storage_url_resolver' => [
        'class' => Optimus\FineuploaderServer\Http\CloudinaryUrlResolver::class
    ],

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
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME', 'cloud_name'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET')
            ]
        ]

    ],

    'naming_strategy' => Optimus\FineuploaderServer\Naming\UniqidStrategy::class,

    'middleware' => [

    ]

];
```
