<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../shared/connect.php';

$config->certificates = [];

$config->certificates[0] = [
	'logName' => 'Alkindi',
	'school_template' => 'school_template.html',
	'group_template' => "group_template.html",
	'certificate_template' => "certificate_template.html",
	'mysql_host' => $config->db->mysql->host,
	'mysql_user' => $config->db->mysql->user,
	'mysql_password' => $config->db->mysql->password,
	'mysql_database' => $config->db->mysql->database,
	'folder' => 'default',
	'contestIDs' => [56],
	'addAlgoreaCode' => true
];

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