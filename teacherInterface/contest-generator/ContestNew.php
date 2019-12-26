<?php
require_once 'libs/TaskParser.php';
require_once 'libs/FilesIndex.php';

class ContestNew
{



    public function __construct($config, $filesystem)
    {
        $this->tasks_repo_path = __DIR__ . '/../bebras-tasks/';
        $this->config = $config;
        $this->filesystem = $filesystem;
    }


    public function contestCopyFile($src, $dst)
    {
        $this->filesystem->myCopyFile(
            $src,
            $this->filesystem->joinPaths($this->contestFolder, $dst)
        );
    }


    /* Write $content to contest-relative path $dst, public unless $adminOnly is true */
    public function contestPutContents($dst, $content, $adminOnly = false)
    {
        $this->filesystem->myPutContents(
            $this->filesystem->joinPaths($this->contestFolder, $dst),
            $content,
            $adminOnly
        );
    }



    public function generate($tasks, $contestID, $contestFolder, $fullFeedback = false, $status = 'RunningContest')
    {
        $this->contestFolder = $contestFolder.'.v2';
        $options = [
            'solution' => $fullFeedback || !($status == 'RunningContest' || $status == 'FutureContest')
        ];

        $index = new FilesIndex();
        foreach ($tasks as $task) {
            $task_parser = new TaskParser(
                $this->filesystem->joinPaths($this->tasks_repo_path, $task['url']),
                $options
            );

            $modules = $task_parser->parseModules('../');
            //$this->copyTaskModules($modules);
            $index->addModules($modules);

            $files = $this->copyTaskFiles($task);
            $index->addFiles($files, $task['key'].'/');

            $this->contestPutContents(
                $this->filesystem->joinPaths($task['key'], basename($task['url'])),
                $task_parser->getContents()
            );

            $task_parser = null;
        }
        $this->copyTaskModules($index->getModules());

        $this->contestPutContents(
            'index.json',
            $index->getContents($this->contestFolder.'/')
        );
    }



    public function copyTaskFiles($task) {
        $task_path = dirname($task['url']);
        $task_basename = basename($task['url']);

        $src_path = $this->filesystem->joinPaths(
            $this->tasks_repo_path,
            $task_path
        );
        $files = scandir($src_path);
        $res = [];
        foreach($files as $file) {
            if($file == '..' || $file == '.') continue;
            $res[] = $file;
            if($file == $task_basename) continue;
            $this->contestCopyFile(
                $this->filesystem->joinPaths($src_path, $file),
                $this->filesystem->joinPaths($task['key'], $file)
            );
        }
        return $res;
    }


    private function copyTaskModules($files) {
        foreach($files as $file) {
            $this->contestCopyFile(
                $this->filesystem->joinPaths($this->tasks_repo_path, $file),
                $file
            );
        }
    }

}