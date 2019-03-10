<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

require_once('../vendor/autoload.php');
require_once('../shared/common.php');
require_once("commonAdmin.php");

/* Contest generation has three modes, which you can set in config.json:
 *   - "local": the contest is generated in ../contestInterface/contests/
 *   - "aws": the contest is generated in the buckets set in config.json
 *   - "aws+local": both
 */
$mode = $config->teacherInterface->generationMode;
$doLocal = strpos($mode, 'local') !== false;
$doAws = strpos($mode, 'aws') !== false;

if (property_exists($config->teacherInterface, 'sContestGenerationPath')) {
   $contestLocalDir = realpath(__DIR__.$config->teacherInterface->sContestGenerationPath);
} else {
   $contestLocalDir = realpath(__DIR__.'/../contestInterface/contests');
}

// use cannot be in an "if" block
use Aws\S3\S3Client;
if ($doAws) {
   $publicClient = S3Client::factory(array(
      'credentials' => array(
           'key'    => $config->aws->key,
           'secret' => $config->aws->secret
       ),
      'region' => $config->aws->s3region,
      'version' => '2006-03-01'
   ));
   $publicBucket = $config->aws->bucketName;
}

require_once('path.inc.php');
require_once('../tasks/bebras/Bebras.php');

function getMimeTypes() {
    # Returns the system MIME type mapping of extensions to MIME types, as defined in /etc/mime.types.
    $out = array();
    if (file_exists('/etc/mime.types')) {
       $file = fopen('/etc/mime.types', 'r');
       while(($line = fgets($file)) !== false) {
           $line = trim(preg_replace('/#.*/', '', $line));
           if(!$line)
               continue;
           $parts = preg_split('/\s+/', $line);
           if(count($parts) == 1)
               continue;
           $type = array_shift($parts);
           foreach($parts as $part)
               $out[$part] = $type;
       }
       fclose($file);
    } else {
       $out['js'] = 'application/javascript';
       $out['png'] = 'image/png';
       $out['css'] = 'text/css';
       $out['txt'] = 'text/plain';
       $out['gif'] = 'image/gif';
       $out['jpg'] = 'image/jpeg';
       $out['svg'] = 'image/svg+xml';
       $out['mp4'] = 'video/mp4';
       $out['html'] = 'text/html';
       $out['ttf'] = 'application/octet-stream';
       $out['eot'] = 'application/octet-stream';
       $out['woff'] = 'application/octet-stream';
    }
    return $out;
}

function getMimeTypeOfFilename($filename) {
    static $types;
    if(!isset($types))
        $types = getMimeTypes();
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if(!$ext)
        $ext = $filename;
    $ext = strtolower($ext);
    return isset($types[$ext]) ? $types[$ext] : null;
}

function compressMimeType($mime_type) {
   return $mime_type == 'application/javascript' ||
          $mime_type == 'text/html' ||
          $mime_type == 'text/css' ||
          $mime_type == 'text/plain';
}

function getZippedVersion($src) {
   $gzfilename = $src.'.gz';
   $fp = gzopen($gzfilename, 'w9');
   gzwrite ($fp, file_get_contents($src));
   gzclose($fp);
   return $gzfilename;
}

/* generic bucket-scoped AWS filesystem operations */

function awsMkdir($path) {
   global $publicClient, $publicBucket;
   return $publicClient->putObject(array(
      'Bucket' => $publicBucket,
      'Key'    => rtrim($path, '/').'/',
      'Body'   => ""
   ));
}

function awsCopyFile($src, $dst, $adminOnly = false) {
   global $publicClient, $publicBucket;
   $mime_type = getMimeTypeOfFilename($dst);
   $args = array(
      'Bucket'     => $publicBucket,
      'SourceFile' => $src,
      'Key'        => $dst,
      'ContentType' => $mime_type,
      'CacheControl' => 'public, max-age=86400',
   );
   $zipped = false;
   if (compressMimeType($mime_type)) {
      $src = getZippedVersion($src);  // XXX missing error handling
      $args['SourceFile'] = $src;
      $zipped = true;
      $args['ContentEncoding'] = 'gzip';
   }
   if (!$adminOnly) {
      $args['ACL'] = 'public-read';
   }
   $result = $publicClient->putObject($args);
   if ($zipped) {
      unlink($src);
   }
   return !!$result;
}

function awsPutContents($dst, $content, $adminOnly = false) {
   $src_temp = tempnam('/tmp', 'bebras-platform');
   $success = false !== file_put_contents($src_temp, $content);
   if ($success) {
      $success = awsCopyFile($src_temp, $dst, $adminOnly);
   }
   unlink($src_temp);
   return $success;
}

/* path utilities */

function joinPaths($lhs, $rhs) {
   return rtrim($lhs, '/') . '/' . ltrim($rhs, '/');
}

function makeLocalPath($path) {
   global $contestLocalDir;
   return joinPaths($contestLocalDir, $path);
}

function makeAwsPath($path) {
   return joinPaths('contests', $path);
}

/* mode-aware contests-directory-scoped filesystem operations */

function myMkdir($path) {
   global $doLocal, $doAws;
   if ($doLocal) {
      $localPath = makeLocalPath($path);
      if (!mkdir(makeLocalPath($path))) {
         throw new Exception('local mkdir failed');
      }
   }
   if ($doAws) {
      // Creating a directory is a no-op for S3.
      /*
      if (!awsMkdir(makeAwsPath($path))) {
         throw new Exception('AWS mkdir failed');
      }
      */
   }
}

function myCopyFile($src, $dst, $adminOnly = false) {
   global $doLocal, $doAws;
   if ($doLocal) {
      if (!copy($src, makeLocalPath($dst))) {
         throw new Exception('local copyFile failed');
      }
   }
   if ($doAws) {
      if (!awsCopyFile($src, makeAwsPath($dst))) {
         throw new Exception('AWS copyFile failed');
      }
   }
}

function myPutContents($dst, $content, $adminOnly = false) {
   global $doLocal, $doAws;
   if ($doLocal) {
      if (!file_put_contents(makeLocalPath($dst), $content)) {
         throw new Exception('local putContent failed');
      }
   }
   if ($doAws) {
      if (!awsPutContents(makeAwsPath($dst), $content, $adminOnly)) {
         throw new Exception('AWS putContent failed');
      }
   }
}

/* contest-folder-scoped filesystem operations */

/* Create contest-relative directory $dir */
function contestMkdir($dir) {
   global $contestFolder;
   myMkdir(joinPaths($contestFolder, $dir));
}

/* Copy file at absolute path $src to public contest-relative path $dst */
function contestCopyFile($src, $dst) {
   global $contestFolder;
   myCopyFile($src, joinPaths($contestFolder, $dst));
}

/* Copy file at absolute path $src to non-public contest-relative path $dst */
function contestCopyFileSols($src, $dst) {
   global $contestFolder;
   // return myCopyFile($src, $dst, true); // XXX
   myCopyFile($src, joinPaths($contestFolder, $dst));
}

/* Write $content to contest-relative path $dst, public unless $adminOnly is true */
function contestPutContents($dst, $content, $adminOnly = false) {
   global $contestFolder;
   myPutContents(joinPaths($contestFolder, $dst), $content, $adminOnly);
}

/* Add $content fragment to contest part files. */
function contestAddContent($content, &$listParts, &$buffer, &$numPart, $isLast) {
   $buffer .= $content;
   if ((strlen($buffer) + strlen($content) > 200000) || ($isLast && (strlen($buffer) != 0))) {
      $part = "part_".$numPart.".html";
      contestPutContents($part, "<!doctype html>\n".$buffer);
      $listParts .= $part." ";
      $buffer = "";
      $numPart++;
   }
}

function removeFonts($images) {
   $imagesToPreload = [];
   foreach($images as $image) {
      $ext = pathinfo($image, PATHINFO_EXTENSION);
      if ($ext != 'eot' && $ext != 'woff' && $ext != 'ttf') {
         $imagesToPreload[] = $image;
      }
   }
   return $imagesToPreload;
}

function generateContest($tasks, $contestID, $contestFolder, $fullFeedback = false, $status = 'RunningContest') {
   global $doLocal, $contestLocalDir, $config;
   if ($status == 'RunningContest' || $status == 'FutureContest') {
      $generateSolutions = false;
   }
   $strQuestions = "<!doctype html>\n";
   $strQuestionsArr = array();
   $strSolutions = "<!doctype html>\n";
   $images = array();
   $imagesSols = array();
   $jsModulesRes = array();
   $cssModulesRes = array();
   $strGraders = "<!doctype html>\n";

   $tasks = json_decode($_REQUEST['tasks'], true);
   $numPart = 0;
   $nameParts = "";
   $buffer = "";
   foreach ($tasks as $curTask) {
      $strQuestion = "";;
      $jsQuestions = '';
      $cssQuestions = '';
      $cssSolutions = '';
      $jsModules = array();
      $jsCurrentModules = array();
      $cssCurrentModules = array();
      $cssModules = array();

      $curKey = $curTask['key'];
      $task = new PEMTaskCompiler($curTask['bebras'], $curTask['key'], __DIR__.'/bebras-tasks/'.$curTask['url'], true);

      // Create the task directory.
      contestMkdir($curKey);

      // Copy bebras.js
      $bebrasJsContent = 'var json = '.$task->getBebrasJson().'; function getTaskResources() { return json; }';
      $bebrasJsDstFile = $curKey.'/bebras.js';
      contestPutContents($bebrasJsDstFile, $bebrasJsContent);

      $curImages = $task->copyImages(PEMTaskCompiler::TASK, $curKey, 'contestCopyFile');
      $images = array_merge($images, Bebras::addAbsoluteStaticPath($curImages, $contestFolder.'/'.$curKey));
      $curImagesSols = $task->copyImages(PEMTaskCompiler::SOLUTION, $curKey, 'contestCopyFileSols');
      $imagesSols = array_merge($imagesSols, Bebras::addAbsoluteStaticPath($curImagesSols, $contestFolder.'/'.$curKey));

      // Convert JS and CSS image path
      $questionJs = $task->getJavascript(PEMTaskCompiler::TASK | PEMTaskCompiler::SAT | PEMTaskCompiler::DISPLAY | PEMTaskCompiler::PROXY);
      $solutionJs = $task->getJavascript(PEMTaskCompiler::SOLUTION);
      $questionCss = $task->getCss(PEMTaskCompiler::TASK | PEMTaskCompiler::SAT | PEMTaskCompiler::DISPLAY | PEMTaskCompiler::PROXY);
      $solutionCss = $task->getCss(PEMTaskCompiler::SOLUTION);

      // Javascript & css modules
      $modules = $task->getModules();
      $jsCurrentModules = $modules['jsModules']['ref'];
      $jsModulesRes = array_merge($jsModulesRes, $jsCurrentModules);
      $cssCurrentModules = $modules['cssModules']['ref'];
      $cssModulesRes = array_merge($cssModulesRes, $cssCurrentModules);

      // JS modules content
      foreach ($modules['jsModules']['inline'] as $curJsModuleContent) {
         $jsQuestions .= $curJsModuleContent;
      }
      // Css modules content
      foreach ($modules['cssModules']['inline'] as $curCssModuleContent) {
         $cssQuestions .= $curCssModuleContent;
      }

      // Javascript grader
      $strGraders .= '<div id="javascript-grader-'.$curKey.'" data-content="'.htmlspecialchars($task->getGrader(), ENT_COMPAT, 'UTF-8').'"></div>'."\r\n";

      $questionRelatedJs = Bebras::moveQuestionImagesSrc($questionJs, $curKey, $contestFolder);
      $solutionRelatedJs = Bebras::moveQuestionImagesSrc($solutionJs, $curKey, $contestFolder);
      $cssQuestions .= Bebras::moveQuestionImagesSrc($questionCss, $curKey, $contestFolder);
      $cssSolutions .= Bebras::moveQuestionImagesSrc($solutionCss, $curKey, $contestFolder);

      // Content
      $questionBody = $task->getContent(PEMTaskCompiler::TASK);
      $questionSolution = $task->getContent(PEMTaskCompiler::SOLUTION);

      // Remove absolute images
      $questionBody = preg_replace('#http\://.*\.(png|jpg|gif|mp4|jpeg)#isU', '', $questionBody);

      $strQuestion .= '<div id="question-'.$curKey.'" class="question"><div id="task" class="taskView">'."\r\n"
              .'<style>'.$cssQuestions.'</style>'
              .Bebras::moveQuestionImagesSrc($questionBody, $curKey, $contestFolder)
              .'</div></div>'."\r\n";

      $strQuestion .= '<div id="javascript-'.$curKey.'" data-content="'.htmlspecialchars($questionRelatedJs, ENT_COMPAT, 'UTF-8').'"></div>'."\r\n";

      foreach ($jsCurrentModules as $name => $content) {
         $strQuestion .= '<div class="js-module-'.$curKey.'" data-content="'.$name.'"></div>'."\n";
      }
      foreach ($cssCurrentModules as $name => $content) {
         $strQuestion .= '<div class="css-module-'.$curKey.'" data-content="'.$name.'"></div>'."\n";
      }
      $strSolutions .= '<div id="solution-'.$curKey.'" class="solution">'."\r\n"
              .'<style>'.$cssSolutions.'</style>'
              .Bebras::moveQuestionImagesSrc($questionSolution, $curKey, $contestFolder)
              .'</div>'."\r\n"
              .'<div id="javascript-solution-'.$curKey.'" data-content="'.htmlspecialchars($solutionRelatedJs, ENT_COMPAT, 'UTF-8').'"></div>'."\r\n";
      $strQuestions.= $strQuestion;
      contestAddContent($strQuestion, $nameParts, $buffer, $numPart, false);
   }
   contestCopyFile(__DIR__.'/bebras-tasks/_common/modules/img/castor.png', 'castor.png');
   contestCopyFile(__DIR__.'/bebras-tasks/_common/modules/img/laptop_success.png', 'laptop_success.png');
   contestCopyFile(__DIR__.'/bebras-tasks/_common/modules/img/laptop_warning.png', 'laptop_warning.png');
   contestCopyFile(__DIR__.'/bebras-tasks/_common/modules/img/laptop_error.png', 'laptop_error.png');
   contestCopyFile(__DIR__.'/bebras-tasks/_common/modules/img/fleche-bulle.png', 'fleche-bulle.png');
   $images[] = joinPaths($config->teacherInterface->sAbsoluteStaticPath, 'contests/'.$contestFolder.'/castor.png');
   $images[] = joinPaths($config->teacherInterface->sAbsoluteStaticPath, 'contests/'.$contestFolder.'/fleche-bulle.png');

   $jsPreload = "\r\n//ImagesLoader is injected by the platform just before the contest is loaded\r\n";

   // preloading fonts results in very strange bug with CORS headers...
   $imagesToPreload = removeFonts($images);
   $imagesToPreloadSols = removeFonts($imagesSols);

   $jsPreload .= "ImagesLoader.setImagesToPreload([\n'".
      implode("' ,\n'", $imagesToPreload).
      "']);\r\n";

   $jsPreload .= "function preloadSolImages() { var imagesToLoad = [\n'".
      implode("' ,\n'", $imagesToPreloadSols).
      "'];\r\n   ImagesLoader.addImagesToPreload(imagesToLoad);\r\n}\n";

   $htAccessContent =
       '<Files "contest_'.$contestID.'_sols.html">'."\n"
         ."\t".'Deny from all'."\n"
      .'</Files>'."\n"
      .'<Files "bebras.js">'."\n"
         ."\t".'Deny from all'."\n"
      .'</Files>'."\n";

   if (!$fullFeedback) {
      $htAccessContent .= '<Files "contest_'.$contestID.'_graders.html">'."\n"
         ."\t".'Deny from all'."\n"
         .'</Files>'."\n";
   }

   // Compile js modules
   $strQuestions .= "\n";

   foreach ($jsModulesRes as $name => $content) {
      // If the content is too long, split it in parts
      if(strlen($content) < 65000) {
         $strContent = htmlspecialchars($content, ENT_COMPAT, 'UTF-8');
         $strModule = '<div class="js-module" id="js-module-'.$name.'" data-content="'.$strContent.'"></div>'."\n";
         $strQuestions .= $strModule;
         contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
      } else {
         $strContentPart = 0;
         while(strlen($content) > 0) {
            $contentExcept = substr($content, 0, 65000);
            $content = substr($content, 65000);
            $strContent = htmlspecialchars($contentExcept, ENT_COMPAT, 'UTF-8');
            $strModule = '<div class="js-module" id="js-module-'.$name.'_'.$strContentPart.'" data-part="' . $strContentPart . '" data-content="'.$strContent.'"></div>'."\n";
            $strContentPart += 1;
            $strQuestions .= $strModule;
            contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
         }
      }
   }

   // Compile css modules
   $strQuestions .= "\n";
   foreach ($cssModulesRes as $name => $content) {
      $strModule .= '<div class="css-module" id="css-module-'.$name.'" data-content="'.htmlspecialchars($content, ENT_COMPAT, 'UTF-8').'"></div>'."\n";
      $strQuestions .= $strModule;
      contestAddContent($strModule, $nameParts, $buffer, $numPart, false);
   }
   contestAddContent("", $nameParts, $buffer, $numPart, true);
   contestPutContents('index.txt', trim($nameParts));
   // Preload
   //$strQuestions .= '<div id="preload-images-js" data-content="'.htmlspecialchars($jsPreload, ENT_COMPAT, 'UTF-8').'"></div>'."\n";

   // Create files
   contestPutContents('contest_'.$contestID.'.html', $strQuestions);
   contestPutContents('contest_'.$contestID.'_sols.html', $strSolutions, true);
   contestPutContents('contest_'.$contestID.'.js', $jsPreload);
   contestPutContents('contest_'.$contestID.'_graders.html', $strGraders, !$fullFeedback);
   contestPutContents('.htaccess', $htAccessContent, true);
}


if (!array_key_exists("action", $_REQUEST)) {
   echo json_encode(['success' => false, 'message' => 'no action provided']);
   exit;
}
if (!array_key_exists("contestID", $_REQUEST)) {
   echo json_encode(['success' => false, 'no contest ID provided']);
   exit;
}
if (!array_key_exists("contestFolder", $_REQUEST)) {
   echo json_encode(['success' => false, 'no contest folder provided']);
   exit;
}

$action = $_REQUEST["action"];
$contestID = $_REQUEST["contestID"];
$contestFolder = $_REQUEST["contestFolder"];

if ($action === "prepare") {
   /* Create a fresh contestFolder by replacing the timestamp suffix. */
   $timestamp = time();
   if (array_key_exists("newFolder", $_REQUEST) && $_REQUEST["newFolder"] === "true") {
      $contestFolder = preg_replace("/(\.[0-9]+)+$/", "", $contestFolder) . "." . $timestamp;
   }
   try {
      contestMkdir('');
      // Retrieve the question's list
      $questions = getQuestions($db, $contestID);
      $questionsUrl = array();
      $questionsKey = array();
      foreach ($questions as $curQuestion) {
         $questionsUrl[] = $curQuestion->path;
         $questionsKey[] = $curQuestion->key;
      }
      echo json_encode(array(
         'success' => true,
         'questionsUrl' => $questionsUrl,
         'questionsKey' => $questionsKey,
         'contestFolder' => $contestFolder
      ));
   } catch (Exception $e) {
      echo json_encode(['success' => false, ]);
      echo json_encode([
         'success' => false,
         'message' => $e->getMessage(),
         'contestFolder' => $contestFolder
      ]);
   }
   exit;
}

if ($action === "generate") {
   $tasks = json_decode($_REQUEST['tasks'], true);
   // TODO: fail unless the contest folder is empty
   try {
      generateContest($tasks, $contestID, $contestFolder,
         isset($_REQUEST['fullFeedback']) ? $_REQUEST['fullFeedback'] : false,
         isset($_REQUEST['status']) ? $_REQUEST['status'] : 'RunningContest');
      echo json_encode(['success' => true]);
   } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
   }
   exit;
}

if ($action === "setFolder") {
   $roles = getRoles();
   $request = [
      "modelName" => "contest",
      "model" => getViewModel("contest"),
      "filters" => [],
      "fields" => ["folder"],
      "records" => [
         [
            "ID" => $contestID,
            "values" => [
               "folder" => $contestFolder
            ]
         ]
      ]
   ];
   $success = true == updateRows($db, $request, $roles);
   echo json_encode(['success' => $success]);
   exit;
}

echo json_encode(['success' => false, 'message' => 'unknown action']);
exit;
