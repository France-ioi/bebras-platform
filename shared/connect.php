<?php

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\SessionHandler;
require_once 'LoggedPDO.php';
require_once __DIR__.'/../config.php';

ini_set('session.gc_maxlifetime', $config->contestInterface->sessionLength);
session_set_cookie_params($config->contestInterface->sessionLength);

function connect_pdo($config) {
   // computing timezone difference with gmt:
   // http://www.sitepoint.com/synchronize-php-mysql-timezone-configuration/
   $now = new DateTime();
   $mins = $now->getOffset() / 60;
   $sgn = ($mins < 0 ? -1 : 1);
   $mins = abs($mins);
   $hrs = floor($mins / 60);
   $mins -= $hrs * 60;
   $offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
   try {
      $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
      $pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
      $connexionString = "mysql:host=".$config->db->mysql->host.";dbname=".$config->db->mysql->database.";charset=utf8";
      if (isset($config->db->mysql->port)) {
         $connexionString .= ";port=".$config->db->mysql->port;
      }
      if ($config->db->mysql->logged) {
         $db = new LoggedPDO($connexionString, $config->db->mysql->user, $config->db->mysql->password, $pdo_options);
      } else {
         $db = new PDO($connexionString, $config->db->mysql->user, $config->db->mysql->password, $pdo_options);
      }
      $db->exec("SET time_zone='".$offset."';");
   } catch (Exception $e) {
      die("Erreur : " . $e->getMessage());
   }
   return $db;
}

function connect_dynamoDB($config) {
   $client = DynamoDbClient::factory(array(
      'credentials' => array(
           'key'    => $config->aws->key,
           'secret' => $config->aws->secret
       ),
      'region' => $config->aws->region,
      'version' => '2012-08-10'
   ));
   return $client;
}

$dynamoDB = null;

if ($config->db->dynamoSessions) {
   require_once dirname(__FILE__).'/../vendor/autoload.php';
   $dynamoDB = connect_dynamoDB($config);
   // registering the dynamodb session handler performs some useless operations
   // in session!
   if (!isset($noSessions) || !$noSessions) {
      $table_name = $config->db->dynamoDBPrefix . 'sessions';
      if(gettype($config->db->dynamoSessions) == 'string') {
         $table_name = $config->db->dynamoSessions;
      }
      $sessionHandler = SessionHandler::fromClient($dynamoDB, array(
         'table_name'       => $config->db->dynamoDBPrefix . 'sessions',
      ));
      $sessionHandler->register();
   }
}

if ($config->db->use != "dynamoDB" || !isset($noSQL) || !$noSQL) {
   // mysql is almost always used   
   $db = connect_pdo($config);
}
