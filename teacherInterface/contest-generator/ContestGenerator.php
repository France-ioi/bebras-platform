<?php
require_once 'libs/Filesystem.php';
require_once 'ContestOld.php';
require_once 'ContestNew.php';


class ContestGenerator {

    public function __construct($config) {
        $this->filesystem = new Filesystem($config);
        $this->generator_old = new ContestOld($config, $this->filesystem);
        $this->generator_new = new ContestNew($config, $this->filesystem);
    }


    public function generate() {
        $tasks = json_decode($_REQUEST['tasks'], true);
        $fullFeedback = isset($_REQUEST['fullFeedback']) ? $_REQUEST['fullFeedback'] : false;
        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'RunningContest';

        $this->generator_old->generate(
            $tasks,
            $_REQUEST['contestID'],
            $_REQUEST['contestFolder'],
            $fullFeedback,
            $status
        );

        $this->generator_new->generate(
            $tasks,
            $_REQUEST['contestID'],
            $_REQUEST['contestFolder'],
            $fullFeedback,
            $status
        );
    }


    public function prepare($request) {
        $this->generator_old->prepare($request);
        $this->generator_new->prepare($request);
    }
}