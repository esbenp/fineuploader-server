<?php

namespace Optimus\FineuploaderServer\Provider;

use InvalidArgumentException;
use Illuminate\Support\ServiceProvider as BaseProvider;
use Optimus\FineuploaderServer\Uploader;
use Optimus\FineuploaderServer\Http\UrlResolverInterface;
use Optimus\Onion\LayerInterface;
use Optimus\Onion\Onion;

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

            $storage = $this->createStorage(
                $config['storage'],
                $config['storages'],
                $config['storage_url_resolver']
            );
            $namingStrategy = new $config['naming_strategy'];

            $middleware = $this->createMiddleware($config['middleware']);

            $uploader = new Uploader($storage, $namingStrategy, $config, $middleware);

            return $uploader;
        });
    }

    private function createMiddleware(array $middleware)
    {
        $mappedMiddleware = array_map(function($layer){
            if (!isset($layer['class'])) {
                throw new InvalidArgumentException('No class is specified for middleware');
            }

            $config = array_key_exists('config', $layer) ? $layer['config'] : [];
            $middleware = new $layer['class']($config);

            if (!($middleware instanceof LayerInterface)) {
                throw new InvalidArgumentException($layer['class'] .
                    " should implement Optimus\Onion\LayerInterface to be a valid middleware.");
            }

            return $middleware;
        }, $middleware);

        return (new Onion)->layer($mappedMiddleware);
    }

    private function createStorage($storageKey, array $storages, $urlResolver)
    {
        if (!array_key_exists($storageKey, $storages)) {
            throw new InvalidArgumentException("$storageKey is not a valid fineuploader server storage");
        }

        $storage = $storages[$storageKey];

        if (!array_key_exists('class', $storage)) {
            throw new InvalidArgumentException("$storageKey does not have a valid storage class");
        }

        $config = array_key_exists('config', $storage) ? $storage['config'] : [];

        if (is_array($urlResolver)) {
            if (!array_key_exists('class', $urlResolver)) {
                throw new InvalidArgumentException("urlResolver needs a class key");
            }

            $resolverConfig = array_key_exists('config', $urlResolver) ?
                                $urlResolver['config'] : [];

            $urlResolver = new $urlResolver['class']($resolverConfig);

            if (!($urlResolver instanceof UrlResolverInterface)) {
                throw new InvalidArgumentException(get_class($urlResolver) . " does not implement " .
                                        "Optimus\Http\UrlResolverInterface");
            }
        } elseif (!is_callable($urlResolver)) {
            throw new InvalidArgumentException("Url resolver is not a method.");
        }

        $storage = new $storage['class']($config, $urlResolver);

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
