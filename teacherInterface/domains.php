<?php

function getAllowedDomains() {
   global $config;

   $filename = __DIR__.'/regions/'.$config->teacherInterface->countryCode.'/domains.json';
   if (is_readable($filename)) {
   	  $domains = json_decode(file_get_contents($filename));
   	  return $domains->domains;
   }
   else {
      return array();
   }
}
