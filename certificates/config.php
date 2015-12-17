<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../shared/connect.php';

$config->certificates_confs = [];

$config->certificates_confs[0] = [
	'logName' => 'Castor',
	'school_template' => 'school_template.html',
	'group_template' => "group_template.html",
	'certificate_template' => "certificate_template.html",
	'mysql_host' => $config->db->mysql->host,
	'mysql_user' => $config->db->mysql->user,
	'mysql_password' => $config->db->mysql->password,
	'mysql_database' => $config->db->mysql->database,
	'folder' => 'default',
	'contestIDs' => [54],
	'addAlgoreaCode' => true,
   'printNbContestants' => true,
   'rankNbContestants' => true,
   'rankGrades' => true,
	'grades' => [4,5,6,7,8,9,10,11,12,13,14,15],
	'nbContestantsMax' => 2,
	'groupListGradeNames' => array(
      -1 => "Professeur",
      4 => "CM1",
      5 => "CM2",
      6 => "6<sup>e</sup>",
      7 => "5<sup>e</sup>",
      8 => "4<sup>e</sup>",
      9 => "3<sup>e</sup>",
      10 => "Seconde",
      11 => "Première",
      12 => "Terminale",
      13 => "Seconde Pro.",
      14 => "Première Pro.",
      15 => "Terminale Pro.",
   ),
	'certifGradeNames' => array(
      -1 => "Professeur",
      4 => "Niveau CM1",
      5 => "Niveau CM2",
      6 => "Niveau 6<sup>e</sup>",
      7 => "Niveau 5<sup>e</sup>",
      8 => "Niveau 4<sup>e</sup>",
      9 => "Niveau 3<sup>e</sup>",
      10 => "Niveau Seconde",
      11 => "Niveau Première",
      12 => "Niveau Terminale",
      13 => "Niveau Seconde Pro.",
      14 => "Niveau Première Pro.",
      15 => "Niveau Terminale Pro.",
   ),
   'printCodeString' => function($contestant, $code) {
      return '<div style="height:0px; overflow:visible;font-size:20.8px;">
            Qualifié'.($contestant->genre == 1 ? 'e' : '')." pour le 1<sup>er</sup> tour du concours Algoréa.
            <br/>
            Validez votre qualification sur algorea.org avec le code : ".$code."
            </div>";
   }
];

if (is_readable(__DIR__.'/config_local.php')) {
   include_once __DIR__.'/config_local.php';
}

function connectWithConfig($thisConfig) {
	// creating a $config object useable by connect_pdo in connect.php
	$pdoConfig = (object) array();
	$pdoConfig->db = (object) array();
	$pdoConfig->db->mysql = (object) array();
	$pdoConfig->db->mysql->host = $thisConfig['mysql_host'];
	$pdoConfig->db->mysql->database = $thisConfig['mysql_database'];
	$pdoConfig->db->mysql->password = $thisConfig['mysql_password'];
	$pdoConfig->db->mysql->user = $thisConfig['mysql_user'];
	$pdoConfig->db->mysql->logged = false;
	return connect_pdo($pdoConfig);
}