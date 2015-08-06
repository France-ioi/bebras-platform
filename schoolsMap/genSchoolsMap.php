<?php


/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

define('YEAR_CONTEST', '2014'); 
define('CERTIGEN_CONTESTS', '32, 33, 34, 35, 36, 37'); 
// 2013:  define('CERTIGEN_CONTESTS', '27, 30, 28, 29'); 
// 2012:  define('CERTIGEN_CONTESTS', '6, 7, 8, 9');

require_once("../shared/common.php");
require_once("googleMap.inc.php");

function getMap($src, $dst) {
   $data = array();
   list($gmap, $TOT) = getGoogleMap();
   $data["{MAP}"] = $gmap;
   $data["{YEAR}"] = YEAR_CONTEST;
   $s = file_get_contents($src);
   $s = str_replace(array_keys($data), array_values($data), $s);
   file_put_contents($dst, $s);
}

getMap("templateMap.inc.html", "map.html");
