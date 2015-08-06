<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

// Disable for now, see later if this file is still usefull
exit;

require_once 'bebras/Bebras.php';
require_once 'lib/Route.php';
require_once 'lib/Task.php';

if (!isset($_GET['url'])) {
   throw new Exception('This file cannot be accessed directly.');
}

$route = new Route(Bebras::getQuestionsDirectory(), $_GET['url']);
if ($route::isImage($_GET['url'])) {
   $route->displayImage();
   exit;
}

if ($route::isFile($_GET['url'])) {
   // TODO: Maybe manage this with pub/private keys
   if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1'))) {
      echo 'Access denied';
      exit;
   }
   
   $route->displayFile();
   exit;
}

echo $route->getJsonPath() . '  ' . $route->getTaskPath() . '   ' . $route->getTaskDir();die;
$task = new Task($route->getJsonPath(), $route->getTaskPath(), $route->getTaskDir());

// Disable, see later if this file is still usefull
exit;

// Asking for the JSON
if (isset($_GET['json']) && $_GET['json']) {
   // TODO: Maybe manage this with pub/private keys
   if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1'))) {
      echo 'Access denied';
      exit;
   }
   else {
      header('Content-Type: application/json');
      echo $task->getBebrasJson();
      exit;
   }
}
?>
<!doctype html>
<html>
   <head>
      <meta charset="utf-8">
      <title><?php echo $task->getName(); ?></title>
      <?php echo $task->getStaticResourcesImportHtml(Task::MODULES | Task::CONTENT | Task::SOLUTION); ?>
   </head>
   <body <?php echo $task->getBodyParametersHtml(); ?>>
      <?php echo $task->getContent(Task::CONTENT | Task::SOLUTION); ?>
   </body>
</html>