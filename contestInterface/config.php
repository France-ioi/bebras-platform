<?php

require_once __DIR__.'/../config.php';

header('Content-type: text/javascript');

$configArray = array(
   'defaultLanguage' => $config->defaultLanguage,
   'sAssetsStaticPath' => $config->teacherInterface->sAssetsStaticPath,
   'sAbsoluteStaticPath' => $config->teacherInterface->sAbsoluteStaticPath
);

echo 'var config = '.json_encode($configArray).';';
