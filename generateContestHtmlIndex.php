<?php

if (isset($_SERVER['HTTP_HOST'])) {
  die('you must use this script on the command line');
}

if (isset($argv) && isset($argv[1])) {
  $_SERVER['HTTP_HOST'] = $argv[1];
}

include __DIR__.'/contestInterface/index.php';
