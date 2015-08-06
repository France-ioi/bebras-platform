<?php

/* This file is a tiny ORM to be able to have the same code for MySQL and
 * DynamoDB. It works only in the cases of Beaver Contest.
 *
 */

require_once('connect.php');
require_once('models.php');

use Aws\DynamoDb\Enum\Type;
use Aws\DynamoDb\Enum\ComparisonOperator;
use Aws\DynamoDb\Exception;

// list of all tables we want to sync
$tablesToSync = array(
   'group',
   'contestant',
   'team_question',
   'team',
   'contest',
   'contest_question',
   'question',
   'team_question'
);

class tinyOrm {
   private $db;
   private $dynamoDB;
   private $mode;//"mysql" or "dynamoDB"
   private $table_infos;
   private $hash_fields = array(
      'team_question' => array('teamID', 'questionID')
   );
   private $secondary_indexes = array(
      'team_question' => array('teamID'),
      'team' => array('password')
   );
   public function __construct() {
      global $tablesModels, $db, $dynamoDB, $config;
      $this->db = $db;
      $this->mode = $config->db->use;
      $this->dynamoDB = $dynamoDB;
      $this->table_infos = $tablesModels;
   }
   private $data_types;
   private function getRandomID() {
      return mt_rand()*mt_rand();
   }
   
   public function getHash($table, $fields) {
      if (!isset($this->hash_fields[$table])) {return false;}
      $hash_fields = $this->hash_fields[$table];
      /*
      $str = '';
      $first = true;
      foreach ($hash_fields as $hash_field) {
         if (!$first) {$str .= '-';}
         if (!isset($fields[$hash_field])) {return false;}
         $str .= $fields[$hash_field];
         $first = false;
      }*/
      // warning: According to 
      // http://php.net/manual/en/language.types.integer.php, windows version of
      // php doesn't support 64-bit integers, so we stick to 32 bit.
      // PHP max int is 2^63 so we can't take first 16 (hex) characters of md5
      // or we'll get collisions. Hence we have a 60-bit number.
      //return intval(substr(sha1($str), 0, ((PHP_INT_SIZE == 8) ? 15 : 7)), 16);
      $values = array();
      foreach($hash_fields as $hash_field) {
         if (!isset($fields[$hash_field])) {return false;}
         $values[] = $fields[$hash_field];
      }
      $res = 1000000000*intval($values[0]) + intval($values[1]);
      if ($res == PHP_INT_MAX) {
         die('cannot compute hash: value too large! 0: '.$values[0].', 1: '.$values[1]);
      }
      return $res;
   }
   
   public function normalizeField($table, $field, $value, $mode) {
      $fields_infos = $this->table_infos[$table]['fields'];
      if ($field == 'ID' || $field == 'iVersion') {return intval($value);}
      switch($fields_infos[$field]['type']) {
         case 'int':
            return ($mode == 'dynamoDB') ? intval($value) : $this->db->quote(intval($value));
            break;
         case 'string':
            return ($mode == 'dynamoDB') ? strval($value) : $this->db->quote($value);
            break;
         case 'date':
            if ($mode == 'dynamoDB') {
               $myDate = new DateTime($value);
               return $myDate->format('Y-m-d H:i:s');
            } else {
               return "'$value'";
            }
            break;
      }
   }
   
   public function normalizeFields($table, $fields, $mode) {
      $fields_infos = $this->table_infos[$table]['fields'];
      foreach ($fields as $key => $value) {
         // ignoring unknown fields and no empty attribute in dynamoDB (results in error)
         if (($key != 'ID' && $key != 'iVersion' && !isset($fields_infos[$key]) )|| (!$fields[$key] && $mode == 'dynamoDB')) {
            unset($fields[$key]); continue;
         }
         $fields[$key] = $this->normalizeField($table, $key, $value, $mode);
      }
      return $fields;
   }
   
   public function insertSQL($table, $fields, $options=null) {
      $fields = $this->normalizeFields($table, $fields, 'sql');
      $query = "insert into $table (";
      $first = true;
      foreach ($fields as $field => $value) {
         if (!$first) {$query .= ', '; $first = false;}
         $query .= '`'.$field.'`';
         $first = false;
      }
      $query .= ') values (';
      $first = true;
      foreach ($fields as $field => $value) {
         if (!$first) {$query .= ', ';}
         $query .= $value;
         $first = false;
      }
      $query .= ')';
      if ($options['on duplicate update']) {
         $query .= ' on duplicate key update ';
         $first = true;
         foreach ($options['on duplicate update'] as $field) {
            if (!$first) {$query .= ', ';}
            $query .= "`$field` = ".$fields[$field];
            $first = false;
         }
      }
      $query .= ';';
      return $this->db->exec($query);
   }
   
   private function insertDynamoDB($table, $fields, $options) {
      $fields = $this->normalizeFields($table, $fields, 'dynamoDB');
      if (!isset($fields['ID'])) {$fields['ID'] = $this->getHash($table, $fields);}
      if (!isset($fields['ID'])) {unset($fields['ID']);}
      $query = array(
         'TableName' => $table,
         'Item' => $this->dynamoDB->formatAttributes($fields),
         'ReturnConsumedCapacity' => 'TOTAL'
      );
      $res = $this->dynamoDB->putItem($query);
      return $res;
   }
   
   public function batchWriteDynamoDB($table, $items) {
      $request = array(
         'RequestItems' => array($table => array()),
         'ReturnConsumedCapacity' => 'TOTAL'
      );
      // 25 items max per request
      $i= 0;
      $batchItems = array();
      foreach ($items as $item) {
         if ($i == 24) {
            $this->dynamoDB->batchWriteItem($request);
            $request = array(
               'RequestItems' => array($table => array()),
               'ReturnConsumedCapacity' => 'TOTAL'
            );
         }
         $i = $i + 1;
         if (!isset($item['ID'])) {
            $item['ID'] = $this->getHash($table, $item);
         }
         $itemRequest = $this->normalizeFields($table, $item, 'dynamoDB');
         $itemRequest = $this->dynamoDB->formatAttributes($itemRequest);
         $request['RequestItems'][$table][] = array('PutRequest' => array('Item' => $itemRequest));
      }
      return $this->dynamoDB->batchWriteItem($request);
   }
   
   public function batchWriteSQL($table, $items, $fields, $fieldsOnDuplicate) {
      $query = 'insert ignore into `'.$table.'` (`';
      $query .= implode('`, `', $fields);
      $query .= '`) values ';
      $first = true;
      foreach ($items as $item) {
         if (!$first) {$query .= ',';}
         $itemRequest = $this->normalizeFields($table, $item, 'sql');
         $query.= '('.implode(', ', $itemRequest).') ';
         $first = false;
      }
      if (count($fieldsOnDuplicate)) {
         $query .= 'on duplicate key update ';
         $first = true;
         foreach($fieldsOnDuplicate as $field) {
            if (!$first) {$query .= ', ';}
            $query .= '`'.$field.'` = values (`'.$field.'`) ';
            $first = false;
         }
      }
      $query .= ';';
      $sth = $this->db->exec($query);
   }
   
   // opposite of dynamoDB->formatAttributes();
   public function deformatAttributes($result) {
      $res = array();
      $newresult = array();
      foreach((array)$result as $field => $value) {
         foreach($value as $type => $val) {
            $newresult[$field] = ($type == Type::NUMBER) ? intval($val) : strval($val);
         }
      }
      return $newresult;
   }
   
   private function selectDynamoDB($table, $fields, $where, $options) {
      $where = $this->normalizeFields($table, $where, 'dynamoDB');
      $query = array(
         'ConsistentRead'  => true,
         'TableName'       => $table,
      );
      if (!isset($where['ID'])) {
         $where['ID'] = $this->getHash($table, $where);
         if (!$where['ID']) {
            unset($where['ID']);
         } else {
            foreach($this->hash_fields[$table] as $field) {
               unset($where[$field]);
            }
         }
      }
      $keyConditions = array();
      $queryFilter = array();
      foreach ($where as $field => $value) {
         $type = ($field == 'ID') ? 'int' : $this->table_infos[$table]['fields'][$field]['type'];
         $type = ($type == 'int') ? Type::NUMBER : Type::STRING;
         if ($field == 'ID') {
            $keyConditions[$field] = array(
               'ComparisonOperator' => ComparisonOperator::EQ,
               'AttributeValueList' => array(array($type => $value)),
               );
         } else if (isset($this->secondary_indexes[$table]) && in_array($field, $this->secondary_indexes[$table]) && !isset($where['ID'])) {
            $query['IndexName'] = $field.'-index'; // TODO: maybe a better system...
            $query['ConsistentRead'] = false; // No consistent read with secondary indexes
            $keyConditions[$field] = array(
               'ComparisonOperator' => ComparisonOperator::EQ,
               'AttributeValueList' => array(array($type => $value)),
               );
         } else {
            $queryFilter[$field] = array(
               'ComparisonOperator' => ComparisonOperator::EQ,
               'AttributeValueList' => array(array($type => $value)),
               );
         }
      }
      if (count($fields)) {
         $query['AttributesToGet'] = $fields;
      }
      if (count($keyConditions)) {
         $query['KeyConditions'] = $keyConditions;
      }
      if (count($queryFilter)) {
         $query['QueryFilter'] = $queryFilter;
      }
      $results = $this->dynamoDB->query($query);
      $results = $results['Items'];
      foreach ($results as $id => $result) {
         $results[$id] = $this->deformatAttributes($result);
      }
      return $results;
   }
   
   private function getDynamoDB($table, $fields, $where, $options) {
      $where = $this->normalizeFields($table, $where, 'dynamoDB');
      if (!isset($where['ID'])) {
         $where['ID'] = $this->getHash($table, $where);
         if (!$where['ID']) {
            unset($where['ID']);
         } else {
            foreach($this->hash_fields[$table] as $field) {
               unset($where[$field]);
            }
         }
      }
      $keyConditions = array();
      foreach ($where as $field => $value) {
         $type = ($field == 'ID') ? 'int' : $this->table_infos[$table]['fields'][$field]['type'];
         $type = ($type == 'int') ? Type::NUMBER : Type::STRING;
         if ($field == 'ID') {
            $keyConditions[$field] = array($type => $value);
         }
      }
      $query = array(
         'ConsistentRead'  => true,
         'TableName'       => $table,
      );
      if (count($fields)) {
         $query['AttributesToGet'] = $fields;
      }
      if (count($keyConditions)) {
         $query['Key'] = $keyConditions;
      } else {
         error_log("tinyOrm: no KeyCondition given in get()!");
         error_log($where);
         return false;
      }
      $result = $this->dynamoDB->GetItem($query);
      if ($result && count($result)) {
         $result = $result['Item'];
         $result = $this->deformatAttributes($result);
      }
      return $result;
   }
   
   // common code to select and get
   private function getSelectSQL($table, $fields, $where, $options = null, $get) {
      $fieldsStr = '';
      if (!count($fields)) {
         $fieldsStr = '*';
      } else {
         $first = true;
         foreach ($fields as $field) {
            if (!$first) {$fieldsStr .= ', ';}
            $fieldsStr .= "`$field`";
            $first = false;
         }
      }
      $query = 'select '.$fieldsStr.' from `'.$table.'` where ';
      $where = $this->normalizeFields($table, $where, 'sql');
      $first = true;
      foreach ((array)$where as $field => $value) {
         if (!$first) {$query .= ' and ';}
         $query .= '`'.$field.'` = '.$value;
         $first = false;
      }
      $query .= ';';
      $sth = $this->db->prepare($query);
      $sth->execute();
      if ($get) {
         return $sth->fetch(PDO::FETCH_ASSOC);
      } else {
         return $sth->fetchAll(PDO::FETCH_ASSOC);
      }
   }
   
   public function selectSQL($table, $fields, $where, $options = null) {
      return $this->getSelectSQL($table, $fields, $where, $options = null, false);
   }
   
   public function getSQL($table, $fields, $where, $options = null) {
      return $this->getSelectSQL($table, $fields, $where, $options = null, true);
   }
   
   // works only if ID is in $where // TODO: make the SQL equivalent
   private function updateDynamoDB($table, $fields, $where, $options = null) {
      $request = array(
         'TableName' => $table,
         'Key' => array()
      );
      $keyArray = array('ID' => intval($where['ID']));
      if (!isset($keyArray['ID'])) {
         $keyArray['ID'] = $this->getHash($table, $where);
         if (!isset($keyArray['ID'])) {
            // TODO: in this case, make a query, but this is costly...
            error_log('DynamoDB: cannot compute ID field in delete request');
            return false;
         }
      }
      unset($where['ID']);
      if (count($where)) {
         $request['Expected'] = array();
         foreach ($where as $field => $value) {
            $request['Expected'][$field] = array('AttributeValueList' => array(), 'ComparisonOperator' => array());
            if (!isset($value)) {
               $request['Expected'][$field]['ComparisonOperator'] = 'NULL';
            } else {
               $request['Expected'][$field]['ComparisonOperator'] = 'EQ';
               $value = $this->normalizeField($table, $field, $value, 'dynamoDB');
               $type = (gettype($value) == 'integer') ? Type::NUMBER : Type::STRING;
               $request['Expected'][$field]['AttributeValueList'] = array(array($type => $value));
            }
         }
      }
      $request['Key'] = $this->dynamoDB->formatAttributes($keyArray);
      $request['AttributeUpdates'] = array();
      foreach ($fields as $field => $value) {
         $request['AttributeUpdates'][$field] = array('Action' => 'PUT', 'Value' => array());
         $value = $this->normalizeField($table, $field, $value, 'dynamoDB');
         $type = (gettype($value) == 'integer') ? Type::NUMBER : Type::STRING;
         $request['AttributeUpdates'][$field]['Value'] = array($type => $value);
      }
      try {
         $res = $this->dynamoDB->updateItem($request);
         return true;
         // ignoring if no item corresponds (like in mysql)
      } catch (Aws\DynamoDb\Exception\ConditionalCheckFailedException $e) {
         return false;
      }
   }
   
   private function updateSQL($table, $fields, $where, $options = null) {
      $fields = $this->normalizeFields($table, $fields, 'sql');
      $query = "update $table set ";
      $first = true;
      foreach ($fields as $field => $value) {
         if (!$first) {$query .= ', '; $first = false;}
         $query .= '`'.$field.'` = '.$value;
         $first = false;
      }
      $query .= ' where ';
      $where = $this->normalizeFields($table, $where, 'sql');
      $first = true;
      foreach ((array)$where as $field => $value) {
         if (!$first) {$query .= ' and ';}
         $query .= '`'.$field.'` = '.$value;
         $first = false;
      }
      $query .= '';
      $query .= ';';
      return $this->db->exec($query);
   }
   
   // works only if ID is in $where // TODO: make the SQL equivalent
   public function deleteDynamoDB($table, $where, $options = null) {
      $request = array(
         'TableName' => $table,
         'Key' => array()
      );
      $keyArray = array('ID' => intval($where['ID']));
      if (!isset($keyArray['ID'])) {
         $keyArray['ID'] = $this->getHash($table, $where);
         if (!isset($keyArray['ID'])) {
            error_log('DynamoDB: cannot compute ID field in delete request');
            return false;
         }
      }
      $request['Key'] = $this->dynamoDB->formatAttributes($keyArray);
      return $this->dynamoDB->deleteItem($request);
   }
   
   public function insert($table, $fields, $options = null) {
      if ($this->mode == 'dynamoDB') {
         return $this->insertDynamoDB($table, $fields, $options);
      } else {
         return $this->insertSQL($table, $fields, $options);
      }
   }
   
   public function select($table, $fields = null, $where, $options = null) {
      if ($this->mode == 'dynamoDB') {
         return $this->selectDynamoDB($table, $fields, $where, $options);
      } else {
         return $this->selectSQL($table, $fields, $where, $options);
      }
   }
   
   // get 1 record from a unique ID
   public function get($table, $fields = null, $where, $options = null) {
      if ($this->mode == 'dynamoDB') {
         return $this->getDynamoDB($table, $fields, $where, $options);
      } else {
         return $this->getSQL($table, $fields, $where, $options);
      }
   }
   
   // write several values (on duplicate key update)
   public function batchWrite($table, $items, $fields, $fieldsOnDuplicate) {
      if ($this->mode == 'dynamoDB') {
         return $this->batchWriteDynamoDB($table, $items); // no need of $fields here
      } else {
         return $this->batchWriteSQL($table, $items, $fields, $fieldsOnDuplicate);
      }
   }
   
   // updates an item. Warning: where must contain a key value for dynamodb!
   public function update($table, $fields, $where) {
      if ($this->mode == 'dynamoDB') {
         return $this->updateDynamoDB($table, $fields, $where);
      } else {
         return $this->updateSQL($table, $fields, $where);
      }
   }
}


$tinyOrm = new tinyOrm();

/*
$answer = "171";
$teamID = 148;
$questionID = 889;

$tinyOrm->insert('team_question', array(
   'teamID' => $teamID,
   'questionID' => $questionID,
   'answer'  => $answer,
   'date'   => '2014-09-18 17:13:45'
), array(
   'on duplicate update' => array('date', 'answer')
)
);
*/
/*
$tinyOrm->batchWrite('team_question', array(array(
   'teamID' => $teamID,
   'questionID' => $questionID,
   'answer'  => $answer,
   'date'   => '2014-09-18 17:13:45'
), array(
   'teamID' => $teamID,
   'questionID' => 884,
   'answer'  => $answer,
   'date'   => '2014-09-18 17:13:48'
)
), array('teamID', 'questionID', 'answer', 'date'), array('date', 'answer'));
*/
/*
$res = $tinyOrm->select('team_question', array('teamID', 'ID', 'date'), array(
   'teamID' => $teamID,
   'questionID' => 884,
   ));

print_r($res);
*/

/*
$tinyOrm = new tinyOrm();

$tinyOrm->batchWrite('team_question', array(array(
   'teamID' => 148,
   'questionID' => 112,
   'answer'  => "171",
   'date'   => '2014-09-18 17:13:45'
), array(
   'teamID' => 148,
   'questionID' => 884,
   'answer'  => "171",
   'date'   => '2014-09-18 17:13:48'
)
), array('teamID', 'questionID', 'answer', 'date'), array('date', 'answer'));

//$res = $tinyOrm->update('team_question', array('teamID' => 151), array('ID' => 1004819233354470956, 'questionID' => null));
$res = $tinyOrm->select('team_question', array('questionID', 'answer'), array('teamID' => 148));
print_r($res);
*/
