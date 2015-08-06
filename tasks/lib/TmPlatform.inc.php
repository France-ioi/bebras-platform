<?php

require_once(__DIR__."/../../shared/common.php");

class TmPlatform
{
   /**
    * Get the platform id by its uri
    * 
    * @param string $platformUri
    * @return int
    */
   public static function getIdByUri($platformUri)
   {
      global $db;
      
      $query = '
         SELECT p.id
         FROM tm_platforms p
         WHERE p.uri = ?';
      
      $stmt = $db->prepare($query);
      $stmt->execute(array($platformUri));
      if (!($row = $stmt->fetchObject())) {
         echo 'Platform server with uri ' . $platformUri . ' does not exist.';
         exit;
      }
      
      return $row->id;
   }
   
   /**
    * Retrieve the private key
    * 
    * @param int $platformId
    * @return string
    */
   public static function getPrivateKeyById($platformId)
   {
      global $db;
      
      $query = '
         SELECT p.pv_key
         FROM tm_platforms p
         WHERE p.id = ?';
      
      $stmt = $db->prepare($query);
      $stmt->execute(array($platformId));
      if (!($row = $stmt->fetchObject())) {
         echo 'Platform server with id ' . $platformId . ' does not exist.';
         exit;
      }
      
      return $row->pv_key;
   }
   
   /**
    * Retrieve the private key
    * 
    * @param int $platformId
    * @return string
    */
   public static function getPublicKeyById($platformId)
   {
      global $db;
      
      $query = '
         SELECT p.pc_key
         FROM tm_platforms p
         WHERE p.id = ?';
      
      $stmt = $db->prepare($query);
      $stmt->execute(array($platformId));
      if (!($row = $stmt->fetchObject())) {
         echo 'Platform server with id ' . $platformId . ' does not exist.';
         exit;
      }
      
      return $row->pc_key;
   }
   
   /**
    * Decrypt the token in url
    * 
    * @param int $platformId
    * @param array $encryptedParams
    * @return array of parameters
    */
   public static function decryptToken($platformId, $encryptedParams)
   {
      $privatekey = self::getPrivateKeyById($platformId);
      
      $decryptedParams = '';
      $out = '';
      $maxlength = 128; // Decryption length limitation
      while ($encryptedParams) {
        $input = substr($encryptedParams, 0, $maxlength);
        $encryptedParams = substr($encryptedParams, $maxlength);
        openssl_private_decrypt($input, $out, $privatekey);

        $decryptedParams .= $out;
      }
      
      openssl_private_decrypt($encryptedParams, $decryptedParams, $privatekey);
      
      $params = json_decode($decryptedParams, true);
      
      $datetime = new DateTime();
      $datetime->modify('+1 day');
      $tomorrow = $datetime->format('d-m-Y');
      if (!isset($params['date'])) {
         if (!$decryptedParams) {
            fatalError('Token cannot be decrypted, please check your SSL keys and that the $_GET can handle the request size.');
         }
         else {
            fatalError('Invalid Task token, unable to decrypt: '.$decryptedParams.'; current: '.date('d-m-Y'));
         }
      }
      else if ($params['date'] != date('d-m-Y') && $params['date'] != $tomorrow) {
         fatalError('API token expired.');
      }
      
      return $params;
   }
   
   public static function generateToken($platformId, array $params)
   {
      $paramString = json_encode($params);
      $publickey = self::getPublicKeyById($platformId);
      
      $encryptedParams = '';
      $encrypted = '';
      $maxlength = 117; // Encryption length limitation
      while ($paramString) {
         $input = substr($paramString, 0, $maxlength);
         $paramString = substr($paramString, $maxlength);
         openssl_public_encrypt($input, $encrypted, $publickey);
         
         $encryptedParams .= $encrypted;
      }
      
      return $encryptedParams;
   }
   
   /**
    * Load the page crypted parameters in $_GET
    */
   public static function loadParams()
   {
      $platformUri = urldecode($_GET['sPlatform']);
      $platformId = self::getIdByUri($platformUri);
      
      $_GET['sToken'] = str_replace(' ', '+', $_GET['sToken']);
      $encryptedParams = base64_decode($_GET['sToken']);
      $params = self::decryptToken($platformId, $encryptedParams);
      
      // Map parameters in $_GET
      foreach (array_merge($params, array('bPrintable' => 1)) as $key => $value) {
         $_GET[$key] = $value;
      }
   }
   
   /**
    * Generation of submission token
    * 
    * @param type $user
    * @param type $chapter
    * @param type $task
    * @return type
    */
   public static function generateSubmissionToken($idPlatform, $submission, $idChapter)
   {
      $params = array(
          'date' => date('d-m-Y'),
          'exercice' => urlencode(sSelfDomain),
          'idUser' => $submission->idUser,
          'idTask' => $submission->idTask,
          'idChapter' => $idChapter,
          'sDate' => $submission->sDate,
          'iSuccess' => $submission->iSuccess,
          'iScore' => $submission->iScore,
          'sMode' => $submission->sMode,
          'idSubmission' => $submission->id,
      );
      
      $sToken = self::generateToken($idPlatform, $params);
      return $sToken;
   }
}

?>
