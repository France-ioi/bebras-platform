<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

include_once("config.php");
include_once("../shared/common.php");
include_once("../shared/tinyORM.php");
include_once("common_contest.php");
include_once("backend/Controller.php");

$controller = isset($_POST['controller']) ? $_POST['controller'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

$class_name = $controller . 'Controller';
$class_file = 'backend/controllers/' . $class_name . '.php';
if (!file_exists($class_file)) {
    exitWithJsonFailure('Controller file not found');
}

require_once($class_file);
if (!class_exists($class_name)) {
    exitWithJsonFailure('Controller class not found');
}

$controller = new $class_name;
if (!method_exists($controller, $action)) {
    exitWithJsonFailure('Action not found');
}

initSession(); // TODO: add option to controller?
call_user_func(array($controller, $action));
