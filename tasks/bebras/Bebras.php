<?php

class Bebras
{
   private $taskDirectory;
   private $question;

   /**
    * Path to questions directory
    * @return string
    */
   public static function getQuestionsDirectory()
   {
      return realpath(__DIR__ . '/../../teacherInterface/bebras-tasks');
   }

   /**
    * Constructor
    * 
    * @param array $question The question's data
    * @param string $taskDirectory The absolute task path
    */
   public function __construct($question, $taskDirectory)
   {
      $this->setQuestion($question);
      $this->setTaskDirectory($taskDirectory);
   }

   /**
    * Generate the bebras
    */
   public function generateJsonFile()
   {
      echo $this->taskDirectory;
      
      $htmlFileName = $this->taskDirectory . "/" . $this->question->key . ".html";
      $content = utf8_decode(file_get_contents($htmlFileName));
      
      $contentBodyRaw = $this->getTagContent($content, 'body', '');
      $contentBodyCommented = $this->extractTagContent($contentBodyRaw, 'solution');
      $contentBody = $this->removeHtmlComments($contentBodyCommented);
      $contentSolution = $this->getTagContent($content, 'solution', '');
      
      $jQueryModules = array(
          array('type' => 'javascript', 'url' => '../../modules/jquery-1.7.1.min.js', 'id' => 'http://code.jquery.com/jquery-1.7.1.min.js'),
      );
      if (strstr($content, 'jquery-ui-1.8.22.custom.min.js')) {
          $jQueryModules[] = array('type' => 'javascript', 'url' => '../../modules/jquery-ui-1.8.22.custom.min.js', 'id' => 'http://jqueryui.com');
      }
      
      // The Bebras JSON
      $bebras = array(
         'id' => '',
         'language' => 'fr',
         'version' => 'fr.01',
         'title' => utf8_encode($this->getTagContent($content, 'h1', '')),
         'authors' => '',
         'translators' => array(),
         'license' => '',
         'taskPathPrefix' => '',
         'modulesPathPrefix' => '',
         'browserSupport' => array(),
         'acceptedAnswers' => array($this->question->expectedAnswer),
         'task' => array(
            array('type' => 'html', 'url' => 'task.html'),
            array('type' => 'javascript', 'content' => 'var questionKey = "'.$this->question->key.'";'),
         ),
         'grader' => array(),
         'solution' => array(
            array('type' => 'html', 'url' => 'solution.html'),
         ),
         'task_modules' => array_merge($jQueryModules, array(
            array('type' => 'javascript', 'url' => '../../modules/common.js', 'id' => 'http://castor-informatique.fr/tasks/modules/common.js'),
            array('type' => 'javascript', 'url' => '../../modules/task.js', 'id' => 'http://castor-informatique.fr/tasks/modules/task.js'),
            array('type' => 'javascript', 'url' => '../../modules/tracker.js', 'id' => 'http://castor-informatique.fr/tasks/modules/tracker.js'),
            array('type' => 'javascript', 'url' => '../../modules/bebras.js', 'id' => 'http://castor-informatique.fr/tasks/modules/bebras.js', 'meta' => 'module remove'),
            array('type' => 'css', 'url' => '../../modules/styles.css', 'id' => 'http://castor-informatique.fr/tasks/modules/styles.css'),
         )),
         'grader_modules' => array(),
         'solution_modules' => array(),
      );
      
      // Add inline javascript in the JSON
      $inlineJs = $this->getTagContent($content, 'script', ' id');
      if ($inlineJs) {
         // Adapt Tracker
         // Tracker.trackData({dataType:"answer", teamID: teamID, questionKey: "2012_CZ_12", answer: nm_2012_CZ_12.get_cmd()});
         // => Remove 'teamID: teamID, questionKey: "2012_CZ_12", '
         $inlineJs = preg_replace('#(Tracker.trackData.*)teamID: teamID, questionKey:.*, (.*;)#isU', '$1$2', $inlineJs);
         
         // setSelectAnswer => Remove first parameter
         $inlineJs = preg_replace('#setSelectAnswer\(.*, #isU', 'setSelectAnswer(', $inlineJs);
         $inlineJs = preg_replace('#setSelectAnswer\(function\((questionKey|key), #isU', 'setSelectAnswer(function(', $inlineJs);
         
         $bebras['task'][] = array(
            'type' => 'javascript',
            'content' => utf8_encode($inlineJs),
         );
      }
      
      // Add inline css in the JSON
      $inlineCss = $this->getTagContent($content, 'style', '');
      if ($inlineCss) {
         $bebras['task'][] = array(
            'type' => 'css',
            'content' => utf8_encode($inlineCss),
         );
      }
      
      // Add task's images in the JSON
      $images = PEMTaskCompiler::findUsedFiles($inlineJs.$contentBody.$inlineCss, array('png', 'jpg', 'gif', 'PNG', 'JPG', 'GIF'));
      foreach ($images as $curImage) {
         $bebras['task'][] = array(
            'type' => 'image',
            'url' => $curImage,
         );
      }
      $videos = PEMTaskCompiler::findUsedFiles($inlineJs.$contentBody.$inlineCss, array('mp4', 'MP4'));
      foreach ($videos as $curVideo) {
         $bebras['task'][] = array(
            'type' => 'video',
            'url' => $curVideo,
         );
      }
      
      // Add solution's images in the JSON
      $imagesSolution = PEMTaskCompiler::findUsedFiles($contentSolution, array('png', 'jpg', 'gif', 'PNG', 'JPG', 'GIF'));
      foreach ($imagesSolution as $curImageSolution) {
         $bebras['solution'][] = array(
            'type' => 'image',
            'url' => $curImageSolution,
         );
      }
      
      // JS grader
      if (is_file($this->taskDirectory.'/grader.js')) {
         $bebras['grader'][] = array(
             'type' => 'javascript',
             'content' => file_get_contents($this->taskDirectory.'/grader.js'),
         );
      }
      
      // Disociate content and solution html files
      file_put_contents($this->taskDirectory.'/task.html', utf8_encode($contentBody));
      file_put_contents($this->taskDirectory.'/solution.html', utf8_encode($contentSolution));
      
      /*
      // Content parameter variant
      $bebras['task'][] = array(
         'type' => 'html',
         'content' => utf8_encode($contentBody),
      );
      $bebras['solution'][] = array(
         'type' => 'html',
         'content' => utf8_encode($contentSolution),
      );
       */
      
      // Write JSON and JS file
      file_put_contents($this->taskDirectory.'/bebras.json', str_replace('\\u00a0', ' ', json_encode($bebras, JSON_PRETTY_PRINT)));
      file_put_contents($this->taskDirectory.'/bebras.js', 'var json = '.json_encode($bebras, JSON_PRETTY_PRINT).';function getTaskResources() { return json; }');
      
      // TODO: Uncomment the following line when everything works, if needed
      //unlink($htmlFileName);
      
      echo '      <strong>[DONE]</strong><br />';
   }
   
   /**
    * Generate the task file
    */
   public function generateTaskFile()
   {
      $task = new PEMTaskCompiler($this->taskDirectory.'/bebras.json', $this->taskDirectory);
      //$bebrasJson = file_get_contents($this->taskDirectory.'/bebras.json');
       
      $content = '<!doctype html>
<html>
   <head>
      <meta charset="utf-8">
      <title>'.$task->getTitle().'</title>
'.$task->getStaticResourcesImportHtml(PEMTaskCompiler::INCLUDE_MODULES | PEMTaskCompiler::TASK | PEMTaskCompiler::SOLUTION | PEMTaskCompiler::GRADER).'
      <script class="remove" type="text/javascript">var json = '.$task->getBebrasJsonForQuestion().';</script>
   </head>
   <body>
      <task>'."\r\n".$task->getContent(PEMTaskCompiler::TASK)."\n".self::getOtherImagesHtml($task->getContent(PEMTaskCompiler::Task))."\r\n".'</task>
      <solution>'."\r\n".$task->getContent(PEMTaskCompiler::SOLUTION)."\r\n".'</solution>
   </body>
</html>';
      
      file_put_contents($this->taskDirectory.'/index.html', $content);
   }

   /**
    * Check if the Json already exists
    */
   public function jsonExists()
   {
      return file_exists($this->taskDirectory . '/' . 'bebras.json');
   }

   public function getTagContent($text, $tagName, $openExtra)
   {
      $tagStart = strpos($text, "<" . $tagName . $openExtra);
      if ($tagStart === FALSE) {
         return "";
      }
      
      $tagStartEnd = strpos($text, ">", $tagStart + 1 + strlen($tagName)) + 1;
      $tagEnd = strpos($text, "</" . $tagName . ">", $tagStartEnd);
      
      return trim(substr($text, $tagStartEnd, $tagEnd - $tagStartEnd));
   }
   
   public function extractTagContent($text, $tagName)
   {
      $tagStart = strpos($text, "<".$tagName.">");
      if ($tagStart === FALSE) {
         return $text;
      }
      
      $tagStartEnd = strpos($text, ">", $tagStart + 1 + strlen($tagName)) + 1;
      $tagEnd = strpos($text, "</".$tagName.">", $tagStartEnd);
      $tagEndEnd = strpos($text, ">", $tagEnd + 2 + strlen($tagName)) + 1;
      
      return substr($text, 0, $tagStart).substr($text, $tagEndEnd);
   }
   
   public static function getOtherImagesHtml($html)
   {
      $htmlContent = '';
      
      $images = PEMTaskCompiler::findUsedFiles($html, array('png', 'jpg', 'gif', 'PNG', 'JPG', 'GIF'), true);
      foreach ($images as $curImage) {
         if ($curImage[0] == '/') {
            $curImage = substr($curImage, 1);
         }
         $htmlContent .= '<img style="display: none;" src="'.$curImage.'" />'."\n";
      }
      $videos = PEMTaskCompiler::findUsedFiles($html, array('mp4', 'MP4'), true);
      foreach ($videos as $curVideo) {
         if ($curVideo[0] == '/') {
            $curVideo = substr($curVideo, 1);
         }
         $htmlContent .= '<video style="display: none;" src="'.$curVideo.'" />'."\n";
      }
      
      return $htmlContent;
   }
   
   public static function moveQuestionImagesSrc($text, $questionKey, $contestFolder) {
      $absolutePath = (self::getAbsoluteStaticPath()).'/contests/'.$contestFolder.'/'.$questionKey;
      return PEMTaskCompiler::moveQuestionImagesSrc($absolutePath, $text);
   }
   
   /**
    * Get the absolute static path for a file
    * 
    * @return string
    */
   public static function getAbsoluteStaticPath() {
      static $absolutePath = null;
      if ($absolutePath) {
         return $absolutePath;
      }
      global $config;
      if ($config->teacherInterface->sAbsoluteStaticPath) {
         $absolutePath = $config->teacherInterface->sAbsoluteStaticPath;
         return $absolutePath;
      }
      $currentDomain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
      if ($_SERVER['SERVER_PORT'] != '80') {
             $currentDomain .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
      }
      else {
          $currentDomain .= $_SERVER['SERVER_NAME'];
      }
      $absolutePath = $currentDomain.'/';
      $phpSelf = $_SERVER['PHP_SELF'];
      $rootIndexEnd = strpos($phpSelf, '/teacherInterface/');
      $absolutePath = $absolutePath.substr($phpSelf, 1, $rootIndexEnd)."contestInterface";

      return $absolutePath;
   }
      
   public static function addAbsoluteStaticPath($images, $contestFolder) {
      $absolutePath = self::getAbsoluteStaticPath();
      return PEMTaskCompiler::addAbsoluteStaticPath($absolutePath."/contests/".$contestFolder, $images);
   }
   
   public function removeHtmlComments($str)
   {
      return preg_replace('#<!--.*?-->#s', '', $str);
   }

   /**
    * Set the task directory, if it exists
    * 
    * @param string $taskDirectory
    * @throws Exception
    */
   public function setTaskDirectory($taskDirectory)
   {
      if (!is_dir($taskDirectory)) {
         throw new Exception('The task directory ' . $taskDirectory . ' doesn\'t exists.');
      }

      $this->taskDirectory = $taskDirectory;
   }

   public function getTaskDirectory()
   {
      return $this->taskDirectory;
   }

   public function setQuestion($question)
   {
      $this->question = $question;
   }

   public function getQuestion()
   {
      return $this->question;
   }
}
