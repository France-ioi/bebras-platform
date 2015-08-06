#!/usr/bin/env php
<?php

// replaces {{rand}} by a random number in some files
function makeRandomVersion() {
   $fileList = array(
      __DIR__.'/contestInterface/index.html',
      __DIR__.'/teacherInterface/index.html'
   );
   foreach ($fileList as $file) {
      $str=file_get_contents($file);
      $random = mt_rand(100000000, 999999999);
      $str=str_replace("{{rand}}", $random, $str);
      file_put_contents($file, $str);
   }
}

makeRandomVersion();
