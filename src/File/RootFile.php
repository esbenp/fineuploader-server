<?php

namespace Optimus\FineuploaderServer\File;

use Optimus\FineuploaderServer\File\File;
use Optimus\FineuploaderServer\File\Edition;

class RootFile extends File {

    private $editions = [];

    private $name;

    private $originalName;

    private $type;

    public function getType()
    {
        // We set value lazily because some earlier editions of
        // the file do not have the full path and therefore exif_imagetype
        // will throw an error
        if ($this->type === null) {
            $this->type = $this->isImage() ? 'image' : 'file';
        }

        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function isImage()
    {
        return exif_imagetype($this->getPathname()) !== false;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function setOriginalName($name)
    {
        $this->originalName = $name;
    }

    public function addEdition(Edition $file)
    {
        $this->editions[$file->getKey()] = $file;
    }

    public function hasEdition($key)
    {
        return array_key_exists($key, $this->editions);
    }

    public function getEdition($key)
    {
        return $this->editions[$key];
    }

    public function getEditions()
    {
        return $this->editions;
    }

    public function generateEditionFilename($key)
    {
        $ext = $this->getExtension();
        $filename = $this->getBasename('.'.$ext);

        return sprintf('%s_%s.%s', $filename, $key, $ext);
    }

}
