<?php

namespace Optimus\FineuploaderServer\Controller;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Controller;

class LaravelController extends Controller {

    private $app;

    private $manager;

    private $request;

    public function __construct(Application $application)
    {
        $this->app = $application;

        $this->manager = $this->app['uploader'];
        $this->request = $this->app['request']->instance();
    }

    public function upload()
    {
        $data = $this->request->all();
        return response()->json($this->manager->upload($data));
    }

    public function delete()
    {
        $upload_path = $this->request->get('upload_path');
        return response()->json($this->manager->delete($upload_path));
    }

    public function session()
    {
        $input = $this->request->all();
        return response()->json($this->manager->session($input));
    }

}
