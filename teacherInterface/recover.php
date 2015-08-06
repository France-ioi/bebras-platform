<?php 
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once("../shared/common.php");
require_once("commonAdmin.php");

function getUserFromEmail($db, $email) {
   $query = "SELECT * FROM `user` WHERE (`officialEmail` = ? OR `alternativeEmail` = ?)";
   $stmt = $db->prepare($query);
   $stmt->execute(array($email, $email));
   return $stmt->fetchObject();
}


function sendRecoverEmail($sEmail, $sRecoverCode) {
   global $config;
   sendMail($sEmail, $sTitle, $sBody, $sFrom, $config->email->sInfoAddress, $sInfos = '');
}


function recoverSendMail($db, $sEmail) {
   global $config;
   $row = getUserFromEmail($db, $sEmail);
   if (!$row) {
      echo json_encode(array("success" => false));
      return;
   }
   $sRecoverCode = generateSalt();
   $query = "UPDATE `user` SET `recoverCode` = ? WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $stmt->execute(array($sRecoverCode, $row->ID));

   if ($sEmail !== "")
   {
      $link = $config->teacherInterface->sCoordinatorFolder."/recover.php?action=recover&email=".urlencode($sEmail)."&recoverCode=".urlencode($sRecoverCode);
      $sBody = "Bonjour,\r\n\r\nPour définir un nouveau mot de passe, ouvrez le lien suivant dans votre navigateur  : \r\n\r\n".$link."\r\n\r\nN'hésitez pas à nous contacter si vous rencontrez des difficultés.\r\n\r\nCordialement,\r\n--\r\nL'équipe du Castor Informatique";

      $sTitle = "Réinitialisation de mot de passe Coordinateur Castor Informatique";
      sendMail($sEmail, $sTitle, $sBody, $config->email->sEmailSender);
      //$params = array('recoverCode' => $recoverCode, 'email' => $email);
      //http_post("eval01.france-ioi.org", 80, "/castor/sendMail2.php", $params);
   }
   echo json_encode(array("success" => true));
}

if (!isset($_REQUEST["action"])) {
   echo "Le lien est invalide.";
   exit;
} 

$action = $_REQUEST["action"];
$email = $_REQUEST["email"];
if ($action == "sendMail") {
   recoverSendMail($db, $email);
} else if ($action == "recover"){
   $recoverCode = $_REQUEST["recoverCode"];
   $row = getUserFromEmail($db, $email);
   if (!$row || $row->recoverCode != $recoverCode) {
      echo "Le lien est invalide.";
      return;
   }
   echo "
   <!DOCTYPE html>
   <html>
   <head>
      <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
      <link rel='stylesheet' href='jquery-ui-1.8.20.custom.css' />
      <link rel='stylesheet' href='admin.css' />
      <script src='jqGrid/js/jquery-1.7.2.min.js'></script> 
      <script src='jquery-ui-1.8.20.custom.min.js'></script>
      <script type='text/javascript'>
      var strings = {
         'unknown_email': 'Email inconnu',
         'recover_email_sent_1': 'Vous allez recevoir un email à l\'adresse ',
         'recover_email_sent_2': '. Cliquez sur le lien qu\'il contient pour définir un nouveau mot de passe',
         'password_changed': 'Votre mot de passe a été modifié',
         'option_no_filter': 'Pas de filtre',
         'index_url': 'index.html'
      }
      function getRegions() { return {} };
      </script>
      <script src='admin.js'></script>
   </head>
   <body>
   <div id='divHeader'>
        <table style='width:100%'><tr>
            <td style='width:20%'><img src='images/castor_small.png'/></td>
            <td><p class='headerH1'>Castor Informatique France</p>
            <p class='headerH2'> Plate-forme du concours Castor - <span style='color:red;font-weight:bold'>ACCES COORDINATEUR</span></p>
            </td>
            <td></td>
         </tr></table>
   </div>
   <div class='dialog'>
      Entrez votre nouveau mot de passe : <input type='password' id='newPassword1' /><br/>
      Entrez de nouveau pour le confirmer : <input type='password' id='newPassword2' /><br/>
      <input type='button' id='buttonChangePassword' value='Valider' onclick='changePassword(\"".$email."\", \"".$row->recoverCode."\")' />
   </div></html>
   ";
} else if ($action === "changePassword") {
   $recoverCode = $_REQUEST["recoverCode"];
   $password = $_REQUEST["password"];
   $row = getUserFromEmail($db, $email);
   if (!$row || $row->recoverCode != $recoverCode) {
      echo "Le lien est invalide.";
      return;
   }
   $query = "UPDATE `user` SET `passwordMd5` = ? WHERE `ID` = ?";
   $stmt = $db->prepare($query);
   $passwordMd5 = computePasswordMD5($password, $row->salt);   
   $stmt->execute(array($passwordMd5, $row->ID));
   echo json_encode(array("success" => true));
}
unset($db);

?>
