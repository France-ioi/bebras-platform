<?php

require_once __DIR__.'/../../../shared/simple_html_dom.php';

class TaskParser {

    private $options;

    public function __construct($task_path, $options) {
        $this->options = $options;
        $this->html = file_get_html($task_path);
    }


    public function parseModules($path_prefix) {
        $modules = [];
        $scripts = $this->html->find('script[src]');
        foreach($scripts as $script) {
            $path = $this->getNewModulePath($script->src);
            $script->src = $path_prefix.$path;
            $modules[] = $path;
        }
        $links = $this->html->find('link[href]');
        foreach($links as $link) {
            $path = $this->getNewModulePath($link->href);
            $link->href = $path_prefix.$path;
            $modules[] = $path;
        }
        return $modules;
    }


    private function getNewModulePath($path) {
        $root = 'modules/';
        $tmp = explode($root, $path, 2);
        return $root.$tmp[1];
    }


    public function getContents() {
        $this->parseSolution();
        return $this->html->save();
    }



    private function parseSolution() {
        if(!$this->options['solution']) {
            $this->html->find('#solution')[0]->innertext = '';
        }
    }

}