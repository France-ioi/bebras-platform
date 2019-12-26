<?php
class FilesIndex {

    protected $files = [];
    protected $modules = [];


    public function addModules($modules) {
        foreach($modules as $module) {
            $this->modules[$module] = true;
        }
    }


    public function addFiles($files, $path_prefix = '') {
        $this->files = array_merge(
            $this->files,
            $this->addPrefix($files, $path_prefix)
        );
    }


    public function getModules() {
        return array_keys($this->modules);
    }


    public function getContents($path_prefix = '') {
        $res = array_merge(
            $this->files,
            $this->getModules()
        );
        $res = $this->addPrefix($res, $path_prefix);
        return json_encode($res);
    }


    private function addPrefix($arr, $prefix) {
        return array_map(function($v) use ($prefix) {
            return $prefix.$v;
        }, $arr);
    }

}