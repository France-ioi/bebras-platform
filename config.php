<?php

// Do not modify this file, but override the configuration
// in a config_local.php file based on config_local_template.php

global $config;
$config = (object) array();

$config->maintenanceUntil = null; // maintenance end time (null if no maintenance)

$config->db = (object) array();
$config->db->use = 'mysql';
$config->db->dynamoSessions = false;
$config->db->dynamoDBPrefix = ''; // prefix for team and team_question
$config->db->testMode = false;

// MySQL
$config->db->mysql = (object) array();
$config->db->mysql->host = 'localhost';
$config->db->mysql->database = 'castor';
$config->db->mysql->password = 'castor';
$config->db->mysql->user = 'castor';
$config->db->mysql->logged = false;

// Emails
$config->email = (object) array();
$config->email->sFileStoringSentMails = 'logs/mails.txt';
$config->email->bSendMailForReal = false;
$config->email->sEmailSender = '';
$config->email->sEmailInsriptionBCC = null;
$config->email->smtpHost = '';
$config->email->smtpPort = '';
$config->email->smtpSecurity = ''; // to fill PHPMailer->SMTPSecure, "tls" or "ssl"
$config->email->smtpUsername = '';
$config->email->smtpPassword = 'PASSWORD';
$config->email->sInfoAddress = 'info@castor-informatique.fr';

$config->aws = (object) array();
$config->aws->key = '';
$config->aws->secret = '';
$config->aws->region = '';
$config->aws->bucketName = '';
$config->aws->s3region = '';

$config->contestInterface = (object) array();
// Point contestInterface->baseUrl to an URL serving the contestInterface directory.
$config->contestInterface->baseUrl = 'http://concours.castor-informatique.fr';
$config->contestInterface->sAbsoluteStaticPathNoS3 = 'http://concours.castor-informatique.fr';
$config->contestInterface->sAssetsStaticPathNoS3 = 'http://concours.castor-informatique.fr';
$config->contestInterface->sessionLength = 3600;

$config->teacherInterface = (object) array();
$config->teacherInterface->sHotlineNumber = '';
$config->teacherInterface->sCoordinatorFolder = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->sAssetsStaticPath = 'http://castor.pem.dev/contestInterface/';
$config->teacherInterface->sAbsoluteStaticPath = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->genericPasswordMd5 = '';
$config->teacherInterface->countryCode = 'FR';
$config->teacherInterface->generationMode = 'local';
$config->teacherInterface->sAbsoluteStaticPathOldIE = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->sContestGenerationPath = '/../contestInterface/contests/'; // *MUST* be relative!
$config->teacherInterface->forceOfficialEmailDomain = false;
$config->teacherInterface->useAlgoreaCodes = false; // change if your award is an acess code for another contest
// Point teacherInterface->baseUrl to an URL serving the teacherInterface directory.
$config->teacherInterface->baseUrl = 'http://coordinateur.castor-informatique.fr';
$config->teacherInterface->teacherPersonalCodeContestID = 0;

$config->certificates = (object) array();
$config->certificates->webServiceUrl = 'http://castor-informatique.fr.localhost/certificates/';
$config->certificates->allow = false;
$config->certificates->confIndexForThisPlatform = 0; // index of the conf in certificates/ (you shouldn't need to change it)

$config->timezone = ini_get('date.timezone');
$config->defaultLanguage = 'fr';
$config->contestPresentationURL = '';
$config->contestOfficialURL = '';
$config->contestBackupURL = '';
$config->customStringsName = null; // see README

if (is_readable(__DIR__.'/config_local.php')) {
   include_once __DIR__.'/config_local.php';
}

date_default_timezone_set($config->timezone);

// for dbv...
$config->db->host = $config->db->mysql->host;
$config->db->database = $config->db->mysql->database;
$config->db->password = $config->db->mysql->password;
$config->db->user = $config->db->mysql->user;