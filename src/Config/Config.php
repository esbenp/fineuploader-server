<?php

namespace Optimus\FineuploaderServer\Config;

class Config {

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function merge(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function get($key, $default = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        $config = $this->config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($config) || !array_key_exists($segment, $config)) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    public function set($key, $value)
    {
       if (is_null($key)) {
           return $this->config = $value;
       }

       $keys = explode('.', $key);

       $config = &$this->config;
       while (count($keys) > 1) {
           $key = array_shift($keys);

           // If the key doesn't exist at this depth, we will just create an empty array
           // to hold the next value, allowing us to create the arrays to hold final
           // values at the correct depth. Then we'll keep digging into the array.
           if (!isset($config[$key]) || !is_array($config[$key])) {
               $config[$key] = [];
           }

           $config = &$config[$key];
       }

       $config[array_shift($keys)] = $value;

       return $config;
   }


}
