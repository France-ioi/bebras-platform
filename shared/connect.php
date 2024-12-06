<?php

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\SessionHandler;
require_once 'LoggedPDO.php';
require_once __DIR__.'/../config.php';

ini_set('session.gc_maxlifetime', $config->contestInterface->sessionLength);
session_set_cookie_params($config->contestInterface->sessionLength);

function connect_pdo($dbConfig) {
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
      $connexionString = "mysql:host=".$dbConfig->mysql->host.";dbname=".$dbConfig->mysql->database.";charset=utf8";
      if (isset($dbConfig->mysql->port)) {
         $connexionString .= ";port=".$dbConfig->mysql->port;
      }
      if ($dbConfig->mysql->logged) {
         $db = new LoggedPDO($connexionString, $dbConfig->mysql->user, $dbConfig->mysql->password, $pdo_options);
      } else {
         $db = new PDO($connexionString, $dbConfig->mysql->user, $dbConfig->mysql->password, $pdo_options);
      }
      $db->exec("SET time_zone='".$offset."';");
   } catch (Exception $e) {
      die("Erreur : " . $e->getMessage());
   }
   return $db;
}

function connect_dynamoDB($awsConfig) {
   $client = DynamoDbClient::factory(array(
      'credentials' => array(
           'key'    => $awsConfig->key,
           'secret' => $awsConfig->secret
       ),
      'region' => $awsConfig->region,
      'version' => '2012-08-10'
   ));
   return $client;
}

$rodb = null;
function getRODB() {
   global $config, $db, $rodb;
   if($config->rodb->enable) {
      if(!$rodb) {
         $rodb = connect_pdo($config->rodb);
      }
      return $rodb;
   }
   return $db;
}

$dynamoDB = null;

if ($config->db->dynamoSessions) {
   require_once dirname(__FILE__).'/../vendor/autoload.php';
   $dynamoDB = connect_dynamoDB($config->aws);
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
   $db = connect_pdo($config->db);
}
