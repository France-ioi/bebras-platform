<?php

// Do not modify this file, but override the configuration
// in a config_local.php file based on config_local_template.php

global $config;
$config = (object) array();

$config->maintenanceUntil = null; // maintenance end time (null if no maintenance)

// Minimum common.js version required by the platform
// Increment this each time common.js has an important modification; modify the
// version number at the beginning of common.js too.
$config->minimumCommonJsVersion = 2;

$config->timestamp = false;
$config->faviconfile = 'favicon.ico';

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

// Badge interface
$config->badgeInterface = (object) array();
// customCodeQuery: allows to fetch more data in the query used by verifyCode
$config->badgeInterface->customCodeQuery = null;
// customDataFunction: allows to modify the data to be sent. Takes one
// argument, &$contestant, which is the array returned by the database query
// and which will be returned as the badge
$config->badgeInterface->customDataFunction = null;

$config->contestInterface = (object) array();
// Point contestInterface->baseUrl to an URL serving the contestInterface directory.
$config->contestInterface->baseUrl = 'http://concours.castor-informatique.fr';
$config->contestInterface->sAbsoluteStaticPathNoS3 = 'http://concours.castor-informatique.fr';
$config->contestInterface->sAssetsStaticPathNoS3 = 'http://concours.castor-informatique.fr';
$config->contestInterface->sessionLength = 3600;
$config->contestInterface->browserCheck = 'bebras-platform';
$config->contestInterface->logActivity = false;
$config->contestInterface->httpsTestUrl = null;
$config->contestInterface->downgradeToHTTP = false;
$config->contestInterface->redirectToHTTPS = false;
$config->contestInterface->srlModuleUrl = null;
$config->contestInterface->oldInterfaceScoreModifiersDisplay = true;
$config->contestInterface->confirmContestants = false;
$config->contestInterface->checkBrowserID = false;

$config->teacherInterface = (object) array();
$config->teacherInterface->sHotlineNumber = '';
$config->teacherInterface->sCoordinatorFolder = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->sAssetsStaticPath = 'http://castor.pem.dev/contestInterface/';
$config->teacherInterface->sAbsoluteStaticPath = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->genericPasswordMd5 = '';
$config->teacherInterface->countryCode = 'FR';
$config->teacherInterface->domainCountryCode = 'FR';
$config->teacherInterface->generationMode = 'local';
$config->teacherInterface->sAbsoluteStaticPathOldIE = 'http://coordinateur.castor-informatique.fr/';
$config->teacherInterface->sContestGenerationPath = '/../contestInterface/contests/'; // *MUST* be relative!
$config->teacherInterface->forceOfficialEmailDomain = false;
$config->teacherInterface->autoValidateOfficialEmail = false;
$config->teacherInterface->useAlgoreaCodes = false; // change if your award is an acess code for another contest
// Point teacherInterface->baseUrl to an URL serving the teacherInterface directory.
$config->teacherInterface->baseUrl = 'http://coordinateur.castor-informatique.fr';
$config->teacherInterface->teacherPersonalCodeContestID = 0;

$config->certificates = (object) array();
$config->certificates->webServiceUrl = 'http://castor-informatique.fr.localhost/certificates/';
$config->certificates->allow = false;
$config->certificates->confIndexForThisPlatform = 0; // index of the conf in certificates/ (you shouldn't need to change it)

$config->grades = [-1,4,5,6,16,7,17,8,18,9,19,10,13,11,14,12,15,20,-4];

$config->timezone = ini_get('date.timezone');
$config->defaultLanguage = 'fr';
$config->contestPresentationURL = '';
$config->contestOfficialURL = '';
$config->contestBackupURL = '';
$config->customStringsName = null; // see README
$config->readOnly = false;

// Preloaded image URLs manipulations
$config->imagesURLReplacements = array();
$config->imagesURLReplacementsNonStatic = array();
$config->upgradeToHTTPS = false;
$config->redirectToHTTPSIfError = false;

// Exclude extensions from preloading
$config->preloadExcludeExtensions = [];

// team_question transfer script
$config->transferTeamQuestion = (object) array(
    // Number of teams to load from SQL on each update chunk
    'nbTeamsPerChunk' => 2000,
    // Number of seconds to sleep between each update chunk
    'sleepSecs' => 15,
    // Minimum number of teams to trigger an update (to avoid infinite loops)
    'nbMinTeams' => 50,
    // startTime criteria to select a team for update
    'startTimeLimit' => "NOW() - INTERVAL 3 hour",
    // startTime source SQL table
    'dateTable' => 'team',
    // team_question source DynamoDB table; if null, will be replaced by
    // $config->db->dynamoDBPrefix.'team_question' at execution time
    'srcTable' => null,
    // team_question destination SQL table
    'dstTable' => 'team_question');

$config->validationMailBody = "Bonjour,\r\n\r\nPour valider votre inscription en tant que coordinateur pour le concours Castor, ouvrez le lien suivant dans votre navigateur  : \r\n\r\n%s\r\n\r\nN'hésitez pas à nous contacter si vous rencontrez des difficultés.\r\n\r\nCordialement,\r\n-- \r\nL'équipe du Castor Informatique";
$config->validationMailTitle = "Castor Informatique : validation d'inscription";

date_default_timezone_set($config->timezone);

if (is_readable(__DIR__.'/config_local.php')) {
   include_once __DIR__.'/config_local.php';
}
if (is_readable(__DIR__.'/config/index.php')) {
   include_once __DIR__.'/config/index.php';
}

// for dbv...
$config->db->host = $config->db->mysql->host;
$config->db->database = $config->db->mysql->database;
$config->db->password = $config->db->mysql->password;
$config->db->user = $config->db->mysql->user;
