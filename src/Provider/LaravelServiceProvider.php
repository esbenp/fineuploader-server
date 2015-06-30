<?php

namespace Optimus\Uploader\Provider;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Optimus\Uploader\Uploader;

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

            $storage = new $config['storage_driver']();

            $uploader = new Uploader($storage, $config);

            return $uploader;
        });
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
