<?php

require_once __DIR__.'/../config.php';

header('Content-type: text/javascript');

$configArray = array(
   'defaultLanguage' => $config->defaultLanguage,
   'countryCode' => $config->teacherInterface->countryCode,
   'infoEmail' => $config->email->sInfoAddress,
   'forceOfficialEmailDomain' => $config->teacherInterface->forceOfficialEmailDomain,
   'contestPresentationURL' => $config->contestPresentationURL,
);

echo 'var config = '.json_encode($configArray).';';
