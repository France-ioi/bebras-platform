<?php
require_once 'libs/TaskParser.php';

class ContestNew
{


    private $modules_copied;


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
        foreach ($tasks as $task) {
            $task_parser = new TaskParser(
                $this->filesystem->joinPaths($this->tasks_repo_path, $task['url']),
                $options
            );

            $modules = $task_parser->parseModules('../');
            $this->copyTaskModules($modules);

            $this->copyTaskFiles($task);

            $this->contestPutContents(
                $this->filesystem->joinPaths($task['key'], basename($task['url'])),
                $task_parser->getContents()
            );

            $task_parser = null;
        }
    }



    public function copyTaskFiles($task) {
        $task_path = dirname($task['url']);
        $task_basename = basename($task['url']);

        $src_path = $this->filesystem->joinPaths(
            $this->tasks_repo_path,
            $task_path
        );
        $files = scandir($src_path);
        foreach($files as $file) {
            if($file == '..' || $file == '.' || $file == $task_basename) continue;
            $this->contestCopyFile(
                $this->filesystem->joinPaths($src_path, $file),
                $this->filesystem->joinPaths($task['key'], $file)
            );
        }
    }


    private function copyTaskModules($files) {
        foreach($files as $file) {
            if(isset($this->modules_copied[$file])) {
                return;
            }
            $this->modules_copied[$file] = true;
            $this->contestCopyFile(
                $this->filesystem->joinPaths($this->tasks_repo_path, $file),
                $file
            );
        }
    }

}