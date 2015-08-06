<?php

die('Should not be called anymore');

/**
 * Generate Bebras JSON for each tasks
 */
require_once("../../shared/common.php");
require_once 'Bebras.php';

function getAllQuestions($db) {
   $stmt = $db->prepare("SELECT `question`.`ID`, `question`.`key`, `question`.`folder`, `question`.`name`, `question`.`answerType`, `question`.`expectedAnswer` FROM `question`");
   $stmt->execute();
   $questionsData = array();
   while ($row = $stmt->fetchObject()) {
      $questionsData[$row->ID] = $row;
   }
   return $questionsData;
}

$questionsDir = Bebras::getQuestionsDirectory();
$questions = getAllQuestions($db);

foreach ($questions as $curQuestion) {
   $curQuestionDir = $questionsDir . '/' . $curQuestion->folder . '/' . $curQuestion->key;
   if (is_dir($curQuestionDir)) {
      $content = file_get_contents($curQuestionDir.'/index.html');
      
      // Add id
      //$content = preg_replace('#(var json = {.*\"id\": \")(\",)#isU', '$1http://castor-informatique.fr/tasks/'.$curQuestion->folder . '/' . $curQuestion->key.'/$2', $content);
      
      // Windows to UNIX conversion
      //$content = str_replace(chr(13).chr(10), chr(10), $content);
      //file_put_contents($curQuestionDir.'/index.html', $content);
      
      // Add postmessage
      $content = str_replace('<script class="remove" type="text/javascript" src="../../modules/gen_task_resources.js"></script>',
              '<script class="remove" type="text/javascript" src="../../modules/gen_task_resources.js"></script>'."\r\n"
              .'<script class="module beaver" type="text/javascript" src="../../modules/jquery.ba-postmessage.min.js" id="http://castor-informatique.fr/tasks/modules/jquery.ba-postmessage.min.js"></script>'."\r\n"
              .'<script class="module beaver" type="text/javascript" src="../../modules/pm_task_interface.js" id="http://castor-informatique.fr/tasks/modules/pm_task_interface.js"></script>'."\r\n",
              $content
      );
      file_put_contents($curQuestionDir.'/index.html', $content);
      
      echo $curQuestionDir.'<br />';
      /*$bebras = new Bebras($curQuestion, $curQuestionDir);
      $bebras->generateJsonFile();
      $bebras->generateTaskFile();
      
      unlink($curQuestionDir.'/bebras.js');
      unlink($curQuestionDir.'/bebras.json');
      unlink($curQuestionDir.'/task.html');
      unlink($curQuestionDir.'/solution.html');
      unlink($curQuestionDir.'/'.$curQuestion->key.'.html');*/
   }
}