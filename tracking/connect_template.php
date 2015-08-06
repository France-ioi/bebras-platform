<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

error_reporting(E_ALL);

function connect() {
   $host = "127.0.0.1";
   $database = "castor";
   $password = "";
   $user = "root";
   try {
      $pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
      $pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
      $db = new LoggedPDO("mysql:host=".$host.";dbname=".$database, $user, $password, $pdo_options);
   } catch (Exception $e) {
      die("Erreur : " . $e->getMessage());
   }
   return $db;
}

?>