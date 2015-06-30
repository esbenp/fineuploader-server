<?php

namespace Optimus\FineuploaderServer\Provider;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Optimus\FineuploaderServer\Uploader;

class LaravelServiceProvider extends BaseProvider {

    public function register()
    {
        $this->loadConfig();
        $this->registerAssets();
        // Must be called after config
        $this->bindInstance();
    }

    public function bindInstance()
    {
        $this->app->bindShared('uploader', function(){
            $config = $this->app['config']->get('uploader');

            $storage = $this->createStorage($config['storage'], $config['storages']);
            $namingStrategy = new $config['naming_strategy'];

            $uploader = new Uploader($storage, $namingStrategy, $config);

            return $uploader;
        });
    }

    private function createStorage($storageKey, array $storages)
    {
        if (!array_key_exists($storageKey, $storages)) {
            throw new \Exception("$storageKey is not a valid fineuploader server storage");
        }

        $storage = $storages[$storageKey];

        if (!array_key_exists('class', $storage)) {
            throw new \Exception("$storageKey does not have a valid storage class");
        }

        $config = array_key_exists('config', $storage) ? $storage['config'] : [];

        $storage = new $storage['class']($config);

        return $storage;
    }

    private function registerAssets()
    {
        $this->publishes([
            __DIR__.'/../config/uploader.php' => config_path('uploader.php')
        ]);
    }

    private function loadConfig()
    {
        if ($this->app['config']->get('uploader') === null) {
            $this->app['config']->set('uploader', require __DIR__.'/../config/uploader.php');
        }
    }

}
