<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");

// server should keep session data for at least one hour
ini_set('session.gc_maxlifetime', 60*60);
// client should remember their session id for one hour
session_set_cookie_params(60*60);

session_name('coordinateur2');
session_start();

include_once("../shared/models.php");
include_once("../commonFramework/modelsManager/modelsManager.php");
require_once("../commonFramework/modelsManager/csvExport.php");
require_once("../vendor/autoload.php");

function generateSalt() {
   return  md5(uniqid(rand(), true));
}

function computePasswordMD5($sPassword, $sSalt) {
   return md5($sPassword.$sSalt);
}

function sendMail($sTo, $sTitle, $sBody, $sFrom, $sBCC = NULL)
{
   global $config;
   $mail             = new PHPMailer();
   $mail->IsSMTP(); // telling the class to use SMTP
   $mail->SMTPDebug  = 0; // SMTP debug information 0, 1 (errors and messages), 2 (messages only)
   $mail->SMTPAuth   = true;
   $mail->SMTPSecure = $config->email->smtpSecurity;
   $mail->Host       = $config->email->smtpHost;
   $mail->Port       = $config->email->smtpPort;
   $mail->Username   = $config->email->smtpUsername;
   $mail->Password   = $config->email->smtpPassword;
   // General configuration
   $mail->CharSet = 'UTF-8';
   
   // Content
   $mail->Subject    = $sTitle;
   $mail->Body       = $sBody; 
   //$mail->MsgHTML($sBody);
   
   // Emails
   $mail->SetFrom($sFrom, translate('mail_from_name'));
   $mail->AddReplyTo($sFrom, translate('mail_from_name'));
   $mail->AddAddress($sTo);
   
   if (!is_null($sBCC))
   {
      $mail->AddBCC($sBCC);
   }


   $bSent = $mail->Send();

   return ['success' => $bSent, 'error' => $mail->ErrorInfo];
}

// performs the url encoding of arguments
function http_post($server, $port, $url, $vars)
{
   // TODO: nettoyer ce code
   // Exemple:
   // http_post(
   // "www.fat.com",
   // 80, 
   // "/weightloss.pl", 
   // array("name" => "obese bob", "age" => "20")
   // );
   
   $user_agent = "Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)";
   $urlencoded = "";
   
   while (list($key,$value) = each($vars))
   {
      if (!is_array($value))
         $urlencoded.= urlencode($key) . "=" . urlencode($value) . "&";
      else
         foreach ($value as $val)
            $urlencoded.= urlencode($key) . "[]=" . urlencode($val) . "&";               
   }
   
   $urlencoded = substr($urlencoded,0,-1);	
   $content_length = strlen($urlencoded);
// TODO: si on fait get à la place de POST on récupère du code source...
// ATTENTION A NE PAS TABULER CE CODE 
      $headers = "POST $url HTTP/1.0
Accept: */*
Accept-Language: en-au
Content-Type: application/x-www-form-urlencoded
User-Agent: $user_agent
Host: $server
Connection: close
Cache-Control: no-cache
Content-Length: $content_length

"; 
   $fp = @fsockopen($server, $port, $errno, $errstr, 300);
   
   if (!$fp)
      return false;
   
   fputs($fp, $headers);
   fputs($fp, $urlencoded);
   fflush($fp);
   
   $ret = "";
   while (!feof($fp))
      $ret.= fgets($fp, 1024);
   
   fclose($fp);
   return $ret;
}

function displayRowsAsXml($rows, $viewModel, $page, $total_pages, $count) {
   // we should set the appropriate header information. Do not forget this.
   header("Content-type: text/xml;charset=utf-8");
    
   $s = "<?xml version='1.0' encoding='utf-8'?>";
   $s .=  "<rows>";
   $s .= "<page>".$page."</page>";
   $s .= "<total>".$total_pages."</total>";
   $s .= "<records>".$count."</records>";
    
   foreach ($rows as $row) {
   // be sure to put text data in CDATA
       $s .= "<row id='".$row->ID."'>";
      foreach ($viewModel["fields"] as $fieldName => $fieldInfos) {
         $s .= "<cell>";
         if (getFieldType($viewModel, $fieldName) == "string") {
//            $s .= "<![CDATA[".utf8_decode($row->$fieldName)."]]>";//"<![CDATA[".$row->$fieldName."]]>";
            $s .= "<![CDATA[".($row->$fieldName)."]]>";//"<![CDATA[".$row->$fieldName."]]>";
            } else {
            $s .= $row->$fieldName;
         }
         $s .= "</cell>";
      }
       $s .= "</row>";
   }
   $s .= "</rows>"; 
    
   echo $s;
}

function getRoles() {
   $roles = array();
   $role = $_SESSION["userType"];
   $roles[] = $role;
   if ($role === "admin") {
      $roles[] = "user";
   }
   return $roles;
}

function getTeamQuestionTableForGrading() {
   return "team_question";
}

if (!isset($_SESSION["userType"])) {
   $_SESSION["userType"] = "user";
}
if (!isset($_SESSION["isAdmin"])) {
   $_SESSION["isAdmin"] = 0;
}
?>
