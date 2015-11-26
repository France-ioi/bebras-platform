<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

/* Contest generation has three modes, which you can set in config.json:
 *   - "local": the contest is generated in ../contestInterface/contests/
 *   - "aws": the contest is generated in the buckets set in config.json
 *   - "aws+local": both
 */
require_once('../shared/common.php');
$mode = $config->teacherInterface->generationMode;
$contestLocalDir = __DIR__.'/../contestInterface/contests/';
if (property_exists($config->teacherInterface, 'sContestGenerationPath')) {
   $contestLocalDir = __DIR__.$config->teacherInterface->sContestGenerationPath;
}

require_once('../vendor/autoload.php');

// use cannot be in an "if" block
use Aws\S3\S3Client;
$copy_file_func_name = "copy";
$put_contents_func_name = "file_put_contents";

if ($mode == "aws"|| $mode == "aws+local") {
   $publicClient = S3Client::factory(array(
      'credentials' => array(
           'key'    => $config->aws->key,
           'secret' => $config->aws->secret
       ),
      'region' => $config->aws->region,
      'version' => '2006-03-01'
   ));
   $publicBucket = $config->aws->bucketName;
   $copy_file_func_name = "awsCopyFile";
   $put_contents_func_name = "awsPutContents";
}


require_once('path.inc.php');
require_once('../tasks/bebras/Bebras.php');

if (!isset($_REQUEST["contestID"])) {
   throw new Exception('No contest ID provided.');
}

$contestID = $_REQUEST["contestID"];
$contestFolder = $_REQUEST["contestFolder"];

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
       $out['git'] = 'image/gif';
       $out['jpg'] = 'image/jpeg';
       $out['svg'] = 'image/svg+xml';
       $out['html'] = 'text/html';
       $out['ttf'] = 'application/octet-stream';
       $out['eot'] = 'application/octet-stream';
       $out['woff'] = 'application/octet-stream';
    }
    return $out;
}

function getMimeTypeOfFile($file) {
    static $types;
    if(!isset($types))
        $types = getMimeTypes();
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if(!$ext)
        $ext = $file;
    $ext = strtolower($ext);
    return isset($types[$ext]) ? $types[$ext] : null;
}

function getZippedStr($content) {
   $gzfile = '/tmp/tmp-'.rand(1000,9999).'.gz';
   $fp = gzopen($gzfile, 'w9');
   gzwrite ($fp, $content);
   gzclose($fp);
   return $gzfile;
}

function getZippedVersion($src) {
   $gzfile = $src.'.gz';
   $fp = gzopen($gzfile, 'w9');
   gzwrite ($fp, file_get_contents($src));
   gzclose($fp);
   return $gzfile;
}

// same signature as copy()
function awsCopyFile($src, $dst, $adminOnly = false) {
   global $publicClient, $publicBucket;
   $mime_type = getMimeTypeOfFile($dst);
   $args = array(
         'Bucket'     => $publicBucket,
         'SourceFile' => $src,
         'Key'        => $dst,
         'ContentType' => $mime_type,
   );
   $zipped = false;
   if ($mime_type == 'application/javascript' || $mime_type == 'text/html' || $mime_type == 'text/css' || $mime_type == 'text/plain') {
      $src = getZippedVersion($src);
      $args['SourceFile'] = $src;
      $zipped = true;
      $args['ContentEncoding'] = 'gzip';
   }
   if (!$adminOnly) {
      $args['ACL'] = 'public-read';
   }
   $publicClient->putObject($args);
   if ($zipped) {
      unlink($src);
   }
}

function awsPutContents($dst, $contents, $adminOnly = false) {
   global $publicClient, $publicBucket;
   $mime_type = getMimeTypeOfFile($dst);
   if ($mime_type == 'application/javascript' || $mime_type == 'text/html' || $mime_type == 'text/css' || $mime_type == 'text/plain') {
      $src = getZippedStr($contents);
      $args = array(
         'Bucket'     => $publicBucket,
         'SourceFile' => $src,
         'Key'        => $dst,
         'ContentType' => $mime_type,
         'ContentEncoding' => 'gzip',
      );
      if (!$adminOnly) {
         $args['ACL'] = 'public-read';
      }
      $publicClient->putObject($args);
      unlink($src);
   } else {
      $args = array(
         'Bucket' => $publicBucket,
         'Body'   => $contents,
         'Key'    => $dst,
         'ACL'    => 'public-read',
         'ContentType' => $mime_type,
      );
      if (!$adminOnly) {
         $args['ACL'] = 'public-read';
      }
      $publicClient->putObject($args);
   }
}

function contestCopyFile($src, $dst) {
   global $mode, $contestLocalDir;
   if ($mode == "aws") {
      return awsCopyFile($src, 'contests/'.$dst);
   } else if ($mode == "aws+local") {
      copy($src, $contestLocalDir.$dst);
      return awsCopyFile($src, 'contests/'.$dst);
   } else {
      return copy($src, $contestLocalDir.$dst);
   }
}

function contestCopyFileSols($src, $dst) {
   global $mode, $contestLocalDir;
   if ($mode == "aws") {
      //return awsCopyFile($src, 'contests/'.$dst, true); //XXX
      return awsCopyFile($src, 'contests/'.$dst);
   } else if ($mode == "aws+local") {
      copy($src, $contestLocalDir.$dst);
      //return awsCopyFile($src, 'contests/'.$dst, true); // XXX
      return awsCopyFile($src, 'contests/'.$dst);
   } else {
      return copy($src, $contestLocalDir.$dst);
   }
}

// same signature as file_put_contents()
function contestPutContents($dst, $contents, $adminOnly = false) {
   global $mode, $contestLocalDir;
   if ($mode == "aws") {
      return awsPutContents('contests/'.$dst, $contents, $adminOnly);
   } else if ($mode == "aws+local") {
      file_put_contents($contestLocalDir.$dst, $contents);
      return awsPutContents('contests/'.$dst, $contents, $adminOnly);
   } else {
      return file_put_contents($contestLocalDir.$dst, $contents);
   }
}

function emptyContestDir($contestFolder) {
   global $mode, $contestLocalDir;
   global $publicClient, $publicBucket, $privateClient, $privateBucket;
   if ($mode == "aws") {
      $publicClient->deleteMatchingObjects($publicBucket, 'contests/'.$contestFolder);
   } else if ($mode == "aws+local") {
      deleteRecurse($contestLocalDir.$contestFolder);
      mkdir($contestLocalDir.$contestFolder, 0777, true);
      $publicClient->deleteMatchingObjects($publicBucket, 'contests/'.$contestFolder);
   } else {
      deleteRecurse($contestLocalDir.$contestFolder);
      if (!mkdir($contestLocalDir.$contestFolder, 0777, true)) {
         exit();
      }
   }
}

if (!isset($_REQUEST['tasks'])) {
   // Retrieve the question's list
   $questions = getQuestions($db, $contestID);
   
   $questionsUrl = array();
   foreach ($questions as $curQuestion) {
      $questionsUrl[] = $curQuestion->folder.'/'.$curQuestion->key.'/';
   }
   emptyContestDir($contestFolder);
   echo json_encode(array('questionsUrl' => $questionsUrl));
   exit;
}

if (isset($_REQUEST['tasks']) && $_REQUEST['tasks']) {
   $tasks = json_decode($_REQUEST['tasks'], true);
   emptyContestDir($contestFolder);
   generateContest($tasks, $contestID, $contestFolder, isset($_REQUEST['fullFeedback']) ? $_REQUEST['fullFeedback'] : false, isset($_REQUEST['status']) ? $_REQUEST['status'] : 'RunningContest');
}

function contestAddContent($contestFolder, $content, &$listParts, &$buffer, &$numPart, $isLast) {
   $buffer .= $content;
   if ((strlen($buffer) + strlen($content) > 200000) || ($isLast && (strlen($buffer) != 0))) {
      $part = "part_".$numPart.".html";
      contestPutContents($contestFolder."/".$part, "<!doctype html>\n".$buffer);
      $listParts .= $part." ";
      $buffer = "";
      $numPart++;
   }
}

function generateContest($tasks, $contestID, $contestFolder, $fullFeedback = false, $status = 'RunningContest') {
   global $mode, $contestLocalDir, $config;
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
      
      list($curFolder, $curKey) = explode('/', $curTask['url']);
      $taskUrl = $curTask['url'].'/';
      $task = new PEMTaskCompiler($curTask['bebras'], __DIR__.'/bebras-tasks/'.$curFolder.'/'.$curKey.'/', true);

      // Task directory
      if ($mode != "aws") {
         $dstDir = $contestLocalDir.$contestFolder.'/'.$curKey;
         if (!is_dir($dstDir)) {
            mkdir($dstDir);
         }
      }

      // Copy bebras.js
      $bebrasJsContent = 'var json = '.$task->getBebrasJson().'; function getTaskResources() { return json; }';
      $bebrasJsDstFile = $contestFolder.'/'.$curKey.'/bebras.js';
      contestPutContents($bebrasJsDstFile, $bebrasJsContent);
      
      $curImages = $task->copyImages(PEMTaskCompiler::TASK, $contestFolder.'/'.$curKey, 'contestCopyFile');
      $images = array_merge($images, Bebras::addAbsoluteStaticPath($curImages, $contestFolder.'/'.$curKey));
      $curImagesSols = $task->copyImages(PEMTaskCompiler::SOLUTION, $contestFolder.'/'.$curKey, 'contestCopyFileSols');
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
      $questionBody = preg_replace('#http\://.*\.(png|jpg|gif|jpeg)#isU', '', $questionBody);

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
      contestAddContent($contestFolder, $strQuestion, $nameParts, $buffer, $numPart, false);
   }
   contestCopyFile(__DIR__.'/bebras-tasks/modules/img/castor.png', $contestFolder.'/castor.png');
   contestCopyFile(__DIR__.'/bebras-tasks/modules/img/fleche-bulle.png', $contestFolder.'/fleche-bulle.png');
   $images[] = $config->teacherInterface->sAbsoluteStaticPath.'/contests/'.$contestFolder.'/castor.png';
   $images[] = $config->teacherInterface->sAbsoluteStaticPath.'/contests/'.$contestFolder.'/fleche-bulle.png';

   $jsPreload = "\r\n//ImagesLoader is injected by the platform just before the contest is loaded\r\n";
   
   $jsPreload .= "ImagesLoader.setImagesToPreload([\n'".
      implode("' ,\n'", $images).
      "']);\r\n";

   $jsPreload .= "function preloadSolImages() { var imagesToLoad = [\n'".
      implode("' ,\n'", $imagesSols).
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
      $strModule = '<div class="js-module" id="js-module-'.$name.'" data-content="'.htmlspecialchars($content, ENT_COMPAT, 'UTF-8').'"></div>'."\n";
      $strQuestions .= $strModule;
      contestAddContent($contestFolder, $strModule, $nameParts, $buffer, $numPart, false);
   }

   // Compile css modules
   $strQuestions .= "\n";
   foreach ($cssModulesRes as $name => $content) {
      $strModule .= '<div class="css-module" id="css-module-'.$name.'" data-content="'.htmlspecialchars($content, ENT_COMPAT, 'UTF-8').'"></div>'."\n";
      $strQuestions .= $strModule;
      contestAddContent($contestFolder, $strModule, $nameParts, $buffer, $numPart, false);
   }
   contestAddContent($contestFolder, "", $nameParts, $buffer, $numPart, true);
   contestPutContents($contestFolder.'/index.txt', trim($nameParts));
   // Preload
   //$strQuestions .= '<div id="preload-images-js" data-content="'.htmlspecialchars($jsPreload, ENT_COMPAT, 'UTF-8').'"></div>'."\n";
   
   // Create files
   contestPutContents($contestFolder.'/contest_'.$contestID.'.html', $strQuestions);
   contestPutContents($contestFolder.'/contest_'.$contestID.'_sols.html', $strSolutions, true);
   contestPutContents($contestFolder.'/contest_'.$contestID.'.js', $jsPreload);
   contestPutContents($contestFolder.'/contest_'.$contestID.'_graders.html', $strGraders, !$fullFeedback);
   contestPutContents($contestFolder.'/.htaccess', $htAccessContent, true);
}
