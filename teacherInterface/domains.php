<?php

function getAllowedDomains() {
   global $config;

   $filename = __DIR__.'/regions/'.$config->teacherInterface->domainCountryCode.'/domains.json';
   if (is_readable($filename)) {
   	  $domains = json_decode(file_get_contents($filename));
   	  return $domains->domains;
   }
   else {
      return array();
   }
}

function getObsoleteDomains() {
   global $config;

   $filename = __DIR__.'/regions/'.$config->teacherInterface->domainCountryCode.'/domains.json';
   if (is_readable($filename)) {
   	  $domains = json_decode(file_get_contents($filename), true);
      $obsoleteDomains = isset($domains['obsoleteDomains']) ? $domains['obsoleteDomains'] : [];
      $obsoleteDomainsReplacements = isset($domains['obsoleteDomainsReplacements']) ? $domains['obsoleteDomainsReplacements'] : [];
   	  return ['obsolete' => $obsoleteDomains, 'replacements' => $obsoleteDomainsReplacements];
   }
   else {
      return ['obsolete' => [], 'replacements' => []];
   }
}
