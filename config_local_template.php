<?php

// Database
$config->db->use = 'mysql';
$config->db->testMode = false;
$config->db->dynamoSessions = false; // true to use dynamoDB sessions, string to use a specific table
$config->db->dynamoDBPrefix = ''; // prefix for sessions, team and team_question

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
$config->aws->s3region = '';

// Emails
$config->email->bSendMailForReal = false;
$config->email->sEmailSender = '';
$config->email->sEmailInsriptionBCC = '';
$config->email->sGMailUsername = '';
$config->email->sGMailPassword = '';
$config->email->sInfoAddress = 'info@castor-informatique.fr';

// Localization
$config->timezone = 'Europe/Paris';
$config->defaultLanguage = 'fr';
$config->teacherInterface->countryCode = 'FR';

// Teacher interface settings
$config->teacherInterface->sCoordinatorFolder = 'http://127.0.0.1/beaver_platform/teacherInterface/';
$config->teacherInterface->sAssetsStaticPath = 'http://127.0.0.1/beaver_platform/contestInterface/';
$config->teacherInterface->sAbsoluteStaticPath = 'http://127.0.0.1/beaver_platform/contestInterface/';
$config->teacherInterface->sAbsoluteStaticPathOldIE = 'http://127.0.0.1/beaver_platform/contestInterface/';
$config->teacherInterface->genericPasswordMd5 = '';
$config->teacherInterface->generationMode = 'local';
$config->teacherInterface->sContestGenerationPath = '/../contestInterface/contests/'; // *MUST* be relative!
$config->teacherInterface->forceOfficialEmailDomain = false;
 // indicate the ID of the contest for which password will be automatically generated for teachers
$config->teacherInterface->teacherPersonalCodeContestID = 0;

$config->contestInterface->sAssetsStaticPathNoS3 = 'http://concours.castor-informatique.fr';
$config->contestInterface->sAbsoluteStaticPathNoS3 = 'http://concours.castor-informatique.fr';

$config->grades = [-1,3,4,5,6,7,8,9,10,11,12,-4];
$config->defaultCategory = '';
$config->defaultTeacherCategory = '';
$config->trainingContestID = '56';


// URLs
$config->teacherInterface->baseUrl = 'http://coordinateur.castor-informatique.fr/';
$config->contestInterface->baseUrl = 'http://concours.castor-informatique.fr/';
$config->certificates->webServiceUrl = 'http://castor-informatique.fr.localhost/certificates/';
$config->contestPresentationURL = 'http://castor-informatique.fr/';
$config->contestOfficialURL = 'http://concours.castor-informatique.fr/';
$config->contestBackupURL = '';
$config->useCustomStrings = false; // see README

// Timestamp for URLs
// If defined, ?v=[timestamp] will be added to all URLs.
$config->timestamp = 1000000000;

// Should we upgrade contest URLs (resources) to HTTPS client-side?
// If true, upgrade all URLs; if an array, upgrade all URLs from these domains
//$config->upgradeToHTTPS = false;

// Minimum common.js version
// Allows to avoid denying users with an old common.js version; however, it is
// preferable to not change this configuration except if you know what you're
// doing.
//$config->minimumCommonJsVersion = 2;


/*
$config->login_module_client = [
    'id' => '7',
    'secret' => '1AtKfSc7KbgIo8GDCI31pA9laP7pFoBqSg3RtVHq',
    'base_url' => 'http://login-module.dev',
    'redirect_uri' => $config->teacherInterface->baseUrl.'login_module/callback_oauth.php',
];
*/
