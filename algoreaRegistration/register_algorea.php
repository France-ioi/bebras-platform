<?php

require_once __DIR__.'/../teacherInterface/commonAdmin.php';

$action = $_POST["action"];
if (!$action) {
   echo json_encode(array('success' => false, 'message' => 'missing action'));
   exit();
}

function getInfos($code, $db) {
   $query = "select * from algorea_registration where code= ?;";
   $sth = $db->prepare($query);
   $sth->execute(array($code));
   $res = $sth->fetch();
   if ($res && $res['mailValidated']) {
      echo json_encode(array('success' => false, 'message' => 'La validation pour ce compte est déjà effectuée, impossible de recommencer.'));
      return;
   }
   if ($res) {
      echo json_encode(array('success' => true, 'infos' => $res));
      return;
   }
   $query = "select * from contestant where algoreaCode=?";
   $sth = $db->prepare($query);
   $sth->execute(array($code));
   $res = $sth->fetch();
   if ($res) {
      echo json_encode(array('success' => true, 'infos' => array('firstName' => $res['firstName'], 'lastName' => $res['lastName'])));
   } else {
      echo json_encode(array('success' => false, 'message' => 'Le code n\'est pas valide'));
   }
}

function registerInfos($infos, $db) {
   $query = "select * from algorea_registration where code= ?;";
   $sth = $db->prepare($query);
   $sth->execute(array($infos['code']));
   $res = $sth->fetch();
   if (!$res) {
      $query = "select * from contestant where algoreaCode=?";
      $sth = $db->prepare($query);
      $sth->execute(array($infos['code']));
      $res2 = $sth->fetch();
      if (!$res2) {
         echo json_encode(array('success' => false, 'message' => 'Le code n\'est pas valide'));
         return;
      }
   }
   if ($res && $res['mailValidated']) {
      echo json_encode(array('success' => false, 'message' => 'la validation pour ce compte est déjà effectuée, impossible de recommencer.'));
      error_log('tentative de triche sur les codes Algorea? registerInfos: '.json_encode($infos));
      return;
   }
   $mailValidationHash = md5(uniqid(mt_rand(), true));
   if (!$res) {
      $query = "insert into algorea_registration (code, firstName, lastName, mailValidationHash, email, algoreaAccount, mailValidated) values (?, ?, ?, ?, ?, ?, 0)";
      $args = array($infos['code'], $infos['firstName'], $infos['lastName'], $mailValidationHash, $infos['email'], $infos['algoreaAccount']);
   } else {
      if ($res['email'] == $infos['email']) {
         $mailValidationHash = $res['mailValidationHash'];
      }
      $query = "update algorea_registration set firstName = ?, lastName = ?, email = ?, algoreaAccount = ?, mailValidationHash = ? where code= ?;";
      $args = array($infos['firstName'], $infos['lastName'], $infos['email'], $infos['algoreaAccount'], $mailValidationHash, $infos['code']);
   }
   $sth = $db->prepare($query);
   $sth->execute($args);
   if (!$res || $res['email'] != $infos['email']) {
      sendVerifyEmail($infos['email'], $mailValidationHash, $infos);
   }
   echo json_encode(array('success' => true));
}

function sendVerifyEmail($mail, $mailValidationHash, $infos) {
   global $config;
   if (!$mail) {
      return;
   }
   $baseUrl = 'http://enregistrement.algorea.org/';
   $link = $baseUrl.'?hash='.urlencode($mailValidationHash);
   $sBody = "Bonjour,\n\nPour valider votre enregistrement pour le premier tour du concours Algoréa, ouvrez le lien suivant dans votre navigateur  : \n\n".$link."\n\nPour rappel, votre code est algorea-".$infos['code']."\n\nN'hésitez pas à nous contacter si vous rencontrez des difficultés.\n\nCordialement,\n-- \nL'équipe du Castor Informatique";
   $sTitle = "Concours Algoréa : pré-inscription";
   sendMail($mail, $sTitle, $sBody, $config->email->sEmailSender, $config->email->sEmailInsriptionBCC);
   file_put_contents('/tmp/mail.txt', $sBody);
}

function verifyEmail($hash, $db) {
   $query = "select * from algorea_registration where mailValidationHash= ?;";
   $sth = $db->prepare($query);
   $sth->execute(array($hash));
   $res = $sth->fetch();
   if (!$res) {
      echo json_encode(array('success' => false, 'message' => 'Impossible de vérifier l\'email'));
      return;
   }
   if ($res['mailValidated'] == 1) {
      echo json_encode(array('success' => false, 'message' => 'Email déjà vérifié !'));
      return;
   }
   $query = "update algorea_registration set mailValidated = 1 where mailValidationHash = ?;";
   $sth = $db->prepare($query);
   $sth->execute(array($hash));
   echo json_encode(array('success' => true));
}

if ($action === 'getInfos') {
   getInfos($_POST['code'], $db);
} else if ($action === 'registerInfos') {
   registerInfos($_POST['infos'], $db);
} else if ($action === 'verifyEmail') {
   verifyEmail($_POST['hash'], $db);
} else {
   echo json_encode(array('success' => false, 'message' => 'Action inconnue'));
}
