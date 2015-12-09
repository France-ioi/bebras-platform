<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("certiCommon.inc.php");

// Defines some global parameters
ini_set('display_errors',1); 
error_reporting(E_ALL);
ini_set('max_execution_time', -1);

$db = null;

foreach ($config->certificates_confs as $thisConfig) {
	echo "Processing config ".$thisConfig['logName']."\n";
	$db = connectWithConfig($thisConfig);
	// Get next request
	$request = CertiGen::queueGetNext();
	if (!$request)
	{
	  echo "No request to process.\n";
	  exit(0);
	}

	// Let's process the school
	echo "Treating request {$request->ID} for school {$request->schoolID} ({$request->nbStudents} students).\n";

	if (!CertiGen::queueStarted($request->ID))
	{
	   echo "Not starting\n";
	   exit(0);
	}

	$time = microtime(true);
	$nbStudents = genSchoolCertificates($request->schoolID, $thisConfig);
	$time = microtime(true) - $time;
	echo "$nbStudents in $time seconds for school ".$request->schoolID."\n";
	if ($nbStudents > 0)
	   echo ($time/$nbStudents)."s / student\n";

	if (!CertiGen::queueFinished($request->ID))
	{
	   cleanSchool($request->schoolID);       
	   echo "Not finishing\n";
	   exit(0);
	}
}
