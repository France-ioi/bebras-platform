<?php

/* This file is a small script to transfer some databases from MySQL to
 * AWS DynamoDB. The most simple is to call it from command line.
 */

require_once(__DIR__.'/connect.php');
require_once(__DIR__.'/models.php');
require_once(__DIR__.'/tinyORM.php');

require_once dirname(__FILE__).'/../ext/autoload.php';
if (!$dynamoDB) {
   $dynamoDB = connect_dynamoDB($config);
}

$tinyOrm = isset($tinyOrm) ? $tinyOrm : new tinyOrm();

function transfer_table($table) {
   global $db, $tinyOrm, $tablesModels;
   $fields = $tablesModels[$table]['fields'];
   if ($table != 'team_question') {
      $fields['ID'] = array("type" => "int");
   }
   $fieldsStr = '';
   $first = true;
   foreach ($fields as $field => $infos) {
      if (!$first) {$fieldsStr .= ', ';}
      $fieldsStr .= "`$field`";
      $first = false;
   }
   $query = 'select '.$fieldsStr.' from `'.$table.'`;';
   $sth = $db->prepare($query);
   $sth->execute();
   $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
   foreach ($rows as $row) {
      $batchRows[] = $row;
   }
   $tinyOrm->batchWriteDynamoDB($table, $batchRows);
}

/*
foreach($tablesToSync as $table) {
   transfer_table($table);
}
*/
transfer_table('group');
