<?php
/**
* Extends PDO and logs all queries that are executed and how long
* they take, including queries issued via prepared statements
*/

$printRequests = false;

class LoggedPDO extends PDO
{
    public static $log = array();
    
    public function __construct($dsn, $username, $password, $pdo_options) {
        parent::__construct($dsn, $username, $password, $pdo_options);
    }
    
    /**
     * Print out the log when we're destructed. I'm assuming this will
     * be at the end of the page. If not you might want to remove this
     * destructor and manually call LoggedPDO::printLog();
     */
    public function __destruct() {
        self::printLog();
    }
    
    public function query($query) {
        $start = microtime(true);
        $result = parent::query($query);
        $time = microtime(true) - $start;
        LoggedPDO::$log[] = array('query' => $query,
                                  'params' => array(),
                                  'time' => round($time * 1000, 3));
        return $result;
    }

    /**
     * @return LoggedPDOStatement
     */
    public function prepare($query, $options=NULL) {
        return new LoggedPDOStatement(parent::prepare($query), $query);
    }
    
    public static function printLog() {
        if (!array_key_exists("REQUEST_URI", $_SERVER))
           return;
        $domain = preg_match('|coordinateur|', $_SERVER["REQUEST_URI"])?"coordinateur":"concours";
        $totalTime = 0;
        $s = "";
        foreach(self::$log as $entry) {
            $totalTime += $entry['time'];
            $s .= $domain ."\t". str_pad($entry['time'], 9,  " ", STR_PAD_LEFT) . "\t" . md5($entry['query']) ."\t".  date('Y/m/d-H:i') ."\t". $entry['query'].  "\t" .  $_SERVER["REQUEST_URI"] . "\n";
        }
        file_put_contents(realpath(dirname(__FILE__))."/../logs/pdo.log", $s, FILE_APPEND);
    }
}

/**
* PDOStatement decorator that logs when a PDOStatement is
* executed, and the time it took to run
* @see LoggedPDO
*/
class LoggedPDOStatement {
    /**
     * The PDOStatement we decorate
     */
    private $statement;
    private $query;

    public function __construct(PDOStatement $statement, $query) {
       $this->statement = $statement;
       $this->query = $query;
    }

   public function reconstituteFinalQuery($query, $values) {
      global $db;
      $res = $query;
      foreach ($values as $valueName => $value) {
         $res = str_replace(':'.$valueName, $db->quote($value), $res);
      }
      return $res;
   }

    /**
    * When execute is called record the time it takes and
    * then log the query
    * @return PDO result set
    */
    public function execute($params = array()) {
       global $printRequests;
       if ($printRequests) {
          print($this->reconstituteFinalQuery($this->query, $params)."\n");
       }
       $start = microtime(true);
       try {
          $result = $this->statement->execute($params);
          if ($printRequests) {
             print($this->statement->rowCount()." rows affected\n");
          }
       } catch (Exception $e) {
          if ($printRequests) {
             print("failed!\n");
          }
          file_put_contents(realpath(dirname(__FILE__))."/../logs/errors-pdo.log", "\n\n".date(DATE_RFC822).json_encode($_SESSION)."\n".$e."\n".$this->statement->queryString."\n".json_encode($params)."\n", FILE_APPEND);
          throw $e;
       }
       $time = microtime(true) - $start;
       LoggedPDO::$log[] = array('query' => '[PS] ' . $this->statement->queryString,
                                  'params' => $params,
                                  'time' => round($time * 1000, 3));
       return $result;
    }
    /**
    * Other than execute pass all other calls to the PDOStatement object
    * @param string $function_name
    * @param array $parameters arguments
    */
    public function __call($function_name, $parameters) {
       return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}
?>
