<?php

// Database
$config->db->use = 'mysql';
$config->db->testMode = false;
$config->db->dynamoSessions = false;

   // MySQL
   $config->db->mysql->host = '127.0.0.1';
   $config->db->mysql->database = 'beaver_contest';
   $config->db->mysql->user = '';
   $config->db->mysql->password = '';
   $config->db->mysql->logged = false;

// AWS, if relevant
$config->aws->key = '';
$config->aws->secret = '';
$config->aws->region = '';
$config->aws->bucketName = '';

// Emails
$config->email->bSendMailForReal = false;
$config->email->sEmailSender = '';
$config->email->sEmailInsriptionBCC = '';
$config->email->sGMailUsername = '';
$config->email->sGMailPassword = '';
$config->email->sInfoAddress = 'info@castor-informatique.fr';

$config->teacherInterface->sCoordinatorFolder = 'http://127.0.0.1/beaver_platform/teacherInterface/';
$config->teacherInterface->sAssetsStaticPath = 'http://127.0.0.1/beaver_platform/contestInterface/';
$config->teacherInterface->sAbsoluteStaticPath = 'http://127.0.0.1/beaver_platform/teacherInterface/';
$config->teacherInterface->genericPasswordMd5 = '';
$config->teacherInterface->countryCode = 'FR';
$config->teacherInterface->generationMode = 'local';
$config->teacherInterface->sAbsoluteStaticPathOldIE = 'http://127.0.0.1/beaver_platform/teacherInterface/';
$config->teacherInterface->sContestGenerationPath = '/../contestInterface/contests/';
$config->teacherInterface->baseUrl = 'http://coordinateur.castor-informatique.fr';

$config->contestInterface->baseUrl = 'http://concours.castor-informatique.fr';

$config->certificates->webServiceUrl = 'http://castor-informatique.fr.localhost/certificates/';

$config->timezone = 'Europe/Paris';
$config->defaultLanguage = 'fr';
$config->contestPresentationURL = 'http://castor-informatique.fr';
$config->contestOfficialURL = 'http://concours.castor-informatique.fr';
$config->contestBackupURL = '';
