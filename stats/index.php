<?php

// for debugging:
// 38559,38557 group id
// 209,212,224 task
// 32 contest id

// task partial: 209,214,215,216,222,233

//*****************************************************************

error_reporting(E_ALL);

include_once '../ext/highcharts-php/highcharts.php';
include_once '../shared/connect.php';

function db() {
   global $db;
   return $db;
}


//*****************************************************************
// Parse arguments

$arg_type = "";
$arg_contestIDs = "";
$arg_groupIDs = "";
$arg_taskIDs = "";

if (isset($_REQUEST['type'])) 
   $arg_type = $_REQUEST['type'];
if (isset($_REQUEST['contestIDs'])) 
   $arg_contestIDs = trim($_REQUEST['contestIDs']);
if (isset($_REQUEST['groupIDs'])) 
   $arg_groupIDs = trim($_REQUEST['groupIDs']);
if (isset($_REQUEST['taskIDs'])) 
   $arg_taskIDs = trim($_REQUEST['taskIDs']);
$arg_showIds = isset($_REQUEST['show_ids']);
$arg_hideBar = isset($_REQUEST['hide_bar']);
$arg_useFfscore = isset($_REQUEST['use_ffscore']);

$type = $arg_type;

if ($pos = strpos($arg_contestIDs, "-") !== FALSE) {
   $blocks = explode('-', $arg_contestIDs);
   $contestIDs = range($blocks[0], $blocks[1]);  
} else {
   $contestIDs = (empty($arg_contestIDs)) ? array() : explode(',', trim($arg_contestIDs));
}
$groupIDs = (empty($arg_groupIDs)) ? array() : explode(',', trim($arg_groupIDs));
$taskIDs = (empty($arg_taskIDs)) ? array() : explode(',', trim($arg_taskIDs));

function showID($ID) {
   global $arg_showIds;
   if (! $arg_showIds || $ID === FALSE)
      return "";
   return " (".$ID.")";
}

function useFfscore() {
   global $arg_useFfscore;
   return $arg_useFfscore;
}


// print_r($_REQUEST);


//*****************************************************************
// tools

function newobj($t) {
   $x = new stdClass;
   foreach ($t as $k => $v)
      $x->$k = $v;
   return $x;
}

function newerror($msg) {
   return newobj(array("error" => $msg));
}

function strip_underscore_fields($t) {
   $x = new stdClass;
   foreach ($t as $k => $v) {
      $newk = substr($k, 1);
      $x->$newk = $v;
   }
   return $x;
}

// TODO: replace with "IN (,,)" sql construct
function sql_or($key, $values) {
   $kvalues = array();
   foreach ($values as $value) {
      array_push($kvalues, $key." = ".addslashes($value));
   }
   return "(".implode(" OR ", $kvalues).")";
}

$chartCounter = 0;
function nextChartName() {
   global $chartCounter;
   $chartCounter++;
   return "chart_".$chartCounter;
}

function percentage($v, $precision = 1) {
   return sprintf("%.".$precision."f", 100 * $v) . "%";
}

function percentageSigned($v, $precision = 1) {
   return (($v>0) ? "+" : ""). percentage($v, $precision);
}

function roundScore($score) {
   return round($score, 1);
}

function roundScoreAndRelative($score, $base) {
   $relative = ($base == 0) ? "" : " (".percentageSigned(($score-$base)/$base).")";
   return roundScore($score) . $relative;
}


function getForeignSchoolConstraints() {
   return "
         ( `school`.country NOT LIKE 'France'
      AND `school`.country NOT LIKE 'Polynésie Française'
      AND `school`.country NOT LIKE 'Réunion'
      AND `school`.country NOT LIKE 'Mayotte'
      AND `school`.country NOT LIKE 'Martinique'
      AND `school`.country NOT LIKE 'Guadeloupe'
      AND `school`.country NOT LIKE 'Nouvelle-Calédonie'
      AND `school`.country NOT LIKE 'Guyane')
     ";
}

//*****************************************************************
// General display functions

function getHTMLPage($body) {
   return <<<EOF
   <!DOCTYPE html>
   <html>
   <head>
   <meta charset='utf-8'>
   <script src='../ext/jqueryUI/js/jquery-1.7.2.min.js'></script>
   <script src="../ext/highcharts/js/highcharts.js"></script>
   <title>Castor - Admin - Statistiques</title>
   <style>
   </style>
   </head><body>
   $body
   </body></html>
EOF;
}
//   @page { margin: 0cm }

function getPageForm() {
   global $arg_type, $arg_contestIDs, $arg_groupIDs, $arg_taskIDs, $arg_showIds, $arg_useFfscore;
   $checked0 = ($arg_type == "full_score_distrib" || $arg_type == "") ? "checked" : "";
   $checked1 = ($arg_type == "full_score_cumulative") ? "checked" : "";
   $checked2 = ($arg_type == "task_score_distrib") ? "checked" : "";
   $checked3 = ($arg_type == "resolution_stats") ? "checked" : "";
   $checked4 = ($arg_type == "participation_stats") ? "checked" : "";
   $checked_show_ids = ($arg_showIds) ? "checked" : "";
   $checked_use_ffscore = ($arg_useFfscore) ? "checked" : "";
   echo <<<EOF
   <div>
     <a href="index.php?prepare_scores=1">prepare scores</a> 
     <a href="index.php?prepare_genders=1">prepare genders</a>
     (run each only once)</div>
   <form action="index.php" method="get">
   
   <div>
      <input type="radio" id="type_full_score_distrib" name="type" value="full_score_distrib" $checked0>
      <label for="type_full_score_distrib">Full score distribution</label>

      <input type="radio" id="typefull_score_cumulative" name="type" value="full_score_cumulative" $checked1>
      <label for="type_full_score_cumulative">Full score cumulative</label>

      <input type="radio" id="type_task_score_distrib" name="type" value="task_score_distrib" $checked2>
      <label for="type_task_score_distrib">Task score distribution</label>

      <input type="radio" id="type_resolution_stats" name="type" value="resolution_stats" $checked3>
      <label for="type_resolution_stats">Resolution statistics</label>

      <input type="radio" id="type_participation_stats" name="type" value="participation_stats" $checked4>
      <label for="type_participation_stats">Participation statistics</label>

   </div>
   <div>
      Contest ids: <input name="contestIDs" type="text" size="30" value="$arg_contestIDs"> or
      group ids: <input name="groupIDs" type="text" size="30" value="$arg_groupIDs"> ; optional
      tasks ids: <input name="taskIDs" type="text" size="30" value="$arg_taskIDs"> (coma separated).
   </div>
   <div>
      <input type="submit" value="Show">

      <input type="checkbox" id="hide_bar" name="hide_bar">
      <label for="hide_bar">hide header</label>

      <input type="checkbox" id="show_ids" name="show_ids" $checked_show_ids>
      <label for="show_ids">show ids</label>

      <input type="checkbox" id="use_ffscore" name="use_ffscore" $checked_use_ffscore>
      <label for="use_ffscore">use ffscore</label>

   </div>
   </form>
   <hr />
EOF;
}


//*****************************************************************
// Chart display: common definitions

function createHighchart($options) {
   $chartName = nextChartName();
   $options['chart']['renderTo'] = $chartName;
   $chartdescr = Highcharts::create($chartName, $options);
   $chartdiv = '<div style="padding:0em" id="'.$chartName.'"> </div>';
   //min-width="800" height="300 margin: 0 auto"
   return $chartdescr.$chartdiv;
}

function getBarChartCommonOptions() {
   return array(
   'chart' => array(
      'defaultSeriesType' => 'column',
      ),
   'loading' => array(
      'hideDuration' => 0,
      ),
   'credits' => array(
      'text' => '',
      ),
   'tooltip' => array(
     'formatter' => new Javascript("
            function() {
              return '' + this.series.name +': '+ this.y + '%';
            }"),
      ),
    'plotOptions' => array(   
       'column' => array(
        // 'pointWidth' => 40,
        'pointPadding' => -0.1,
        'animation' => false,
        'stacking' => 'normal',
        'dataLabels' => array(
            'enabled' => true,
            'color' => 'white',
            'style' => array(
               'fontWeight' => 'bold'
               ),
            'formatter' => new Javascript("
                  function() {
                     return (this.y > 1) ? this.y + '%' : '';
                  }"),
              ),
         ),
      ),
   'xAxis' => array(
      /*                     
      'labels' => array(
         'align' => 'right',
         'rotation' => -45,
         'style' => '',
         //'align' => 'left',
         //'rotation' => 45,
         ),
      */
      ),
   'legend' => array(
        'reversed' => true,
       ),
   );

}


//*****************************************************************
// Query: prepare

/* reset cached_officialForContestID: 
   UPDATE `team` SET `team`.cached_officialForContestID = 0 WHERE 1
*/

function makePrepareScores() {
   $query = "
      ALTER TABLE  `team` ADD  `cached_officialForContestID` INT( 11 ) NOT NULL AFTER  `cached_contestants` ,
      ADD INDEX ( `cached_officialForContestID` );
     
      UPDATE `team`, `group` 
      SET `team`.cached_officialForContestID = `group`.contestID
      WHERE `team`.groupID = `group`.id
      AND `team`.endTime IS NOT NULL
      AND `group`.participationType = 'Official'
      AND `team`.participationType = 'Official';


      ALTER TABLE  `team_question` ADD INDEX (  `score` ) ;


      ALTER TABLE  `team` ADD  `cached_ffScore` INT( 11 ) NOT NULL AFTER  `cached_officialForContestID`,
      ADD INDEX ( `cached_ffScore` );

      UPDATE `team` 
      SET `team`.cached_ffScore = 
         (SELECT SUM(`team_question`.ffScore) FROM `team_question` WHERE `team_question`.teamID = `team`.ID)
      WHERE `team`.endTime IS NOT NULL;
      ";
   $stmt = db()->prepare($query);
   $stmt->execute();
}

function makePrepareGenders() {
   $query = "
      ALTER TABLE  `team` ADD `cached_nbBoy` INT( 11 ) NOT NULL AFTER  `cached_officialForContestID`;
      ALTER TABLE  `team` ADD `cached_nbGirl` INT( 11 ) NOT NULL AFTER  `cached_nbBoy`;

      UPDATE `team` 
      SET 
         `team`.cached_nbBoy = (
            SELECT COUNT(ID) 
            FROM `contestant` 
            WHERE `contestant`.teamID = `team`.ID
            AND `contestant`.genre = 2
            ),
        `team`.cached_nbGirl = (
            SELECT COUNT(ID) 
            FROM `contestant` 
            WHERE `contestant`.teamID = `team`.ID
            AND `contestant`.genre = 1
            );      
      ";
   $stmt = db()->prepare($query);
   $stmt->execute();
}


//*****************************************************************
// Query: common

function setGroupName(& $groupID, & $groupName, & $contestID) {
   if ($groupID !== FALSE) {
      $query = "SELECT name, contestID FROM `group` WHERE `group`.id = :groupID";
      $stmt = db()->prepare($query);
      $stmt->execute(array(":groupID" => $groupID));
      $results = $stmt->fetch(PDO::FETCH_OBJ);
      if ($results === FALSE) {
        return newerror("contest does not exist");
      }
      $groupName = $results->name;
      $contestID = $results->contestID;
   } 
}

function setContestName($contestID, & $contestName) {
   $query = "SELECT name FROM `contest` WHERE `contest`.id = :contestID";
   $stmt = db()->prepare($query);
   $stmt->execute(array(":contestID" => $contestID));
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results === FALSE) 
     return newerror("contest does not exist");
   $contestName = $results->name . showID($contestID);
};

function setNbTeams($contestID, $groupID, & $nbTeams) {
   $constraintGroup = ($groupID === FALSE) ? "" : "AND `team`.groupID = :groupID";
   $query = "
      SELECT COUNT(*) as nbTeams 
      FROM `team` 
      WHERE 
         `team`.cached_officialForContestID = :contestID
         $constraintGroup
      ";
   $options = array(":contestID" => $contestID);
   if ($groupID !== FALSE) {
      $options = array_merge($options, array(":groupID" => $groupID));
   }
   $stmt = db()->prepare($query);
   $stmt->execute($options);
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("no teams participated");
   }
   $nbTeams = $results->nbTeams;
}


//*****************************************************************
// Query: full score distribution

function getDataFullScores($contestID, $groupID) {

   // get group name
   $groupName = FALSE;
   setGroupName($groupID, $groupName, $contestID);
   
   // get contest name
   $contestName = FALSE;
   setContestName($contestID, $contestName);

   // get nb teams
   $nbTeams = FALSE;
   setNbTeams($contestID, $groupID, $nbTeams);

   // constraints
   $constraintGroup = ($groupID === FALSE) ? "" : "AND `team`.groupID = :groupID";

   // get scores
   $options = array(":contestID" => $contestID);
   if ($groupID !== FALSE) {
      $options = array_merge($options, array(":groupID" => $groupID));
   }
   $score = "score";
   if (useFfscore())
      $score = "cached_ffScore";
   $query = "
      SELECT 
         `team`.$score as score,
         COUNT(`team`.ID) as nbOcc
      FROM `team` 
      WHERE `team`.cached_officialForContestID = :contestID
        $constraintGroup
      GROUP BY `team`.$score
      ORDER BY `team`.$score
      ";
   $stmt = db()->prepare($query);
   $stmt->execute($options);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("failed to obtain data");
   }
   return newobj(array(
      'contestID' => $contestID,
      'contestName' => $contestName,
      'groupID' => $groupID,
      'groupName' => $groupName,
      'nbTeams' => intval($nbTeams),
      'scoreOccurences' => $results));
}



//*****************************************************************
// Query: score distribution per task

function getDataScoresDistrib($contestID, $groupID, $taskIDs) {

   // get group name
   $groupName = FALSE;
   setGroupName($groupID, $groupName, $contestID);

   // get contest name
   $contestName = FALSE;
   setContestName($contestID, $contestName);

   // get nb teams
   $nbTeams = FALSE;
   setNbTeams($contestID, $groupID, $nbTeams);

   // constraints
   $constraintGroup = ($groupID === FALSE) ? "" : "AND `team`.groupID = :groupID";
   $constraintTasks = (empty($taskIDs)) ? "" : "AND ".sql_or("`question`.ID", $taskIDs);

   // get question properties
   $query = "
      SELECT 
         `question`.ID as questionID, 
         `question`.name as questionName,
         `contest_question`.maxScore as maxScore
      FROM `contest_question`, `question`
      WHERE `contest_question`.questionID = `question`.ID
        AND `contest_question`.contestID = :contestID
        $constraintTasks
      ";
   $stmt = db()->prepare($query);
   $stmt->execute(array(":contestID" => $contestID));
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("failed to obtain properties");
   }
   $questionProperties = array();
   foreach ($results as $result) {
      $questionProperties[$result->questionID] = newobj(array(
         "name" => $result->questionName,
         "maxScore" => $result->maxScore));
   }

   // get scores (reusing $options)
   $score = "score";
   if (useFfscore())
      $score = "ffScore";
   $options = array(":contestID" => $contestID);
   if ($groupID !== FALSE) {
      $options = array_merge($options, array(":groupID" => $groupID));
   }
   $constraintTasks = (empty($taskIDs)) ? "" : "AND ".sql_or(" `team_question`.questionID", $taskIDs);
   $query = "
      SELECT 
         `team_question`.questionID as questionID, 
         `team_question`.$score as score,
         COUNT(`team_question`.teamID) as nbOcc
      FROM `team` 
      JOIN team_question ON (`team_question`.teamID = `team`.ID)
      WHERE `team`.cached_officialForContestID = :contestID
        $constraintGroup
        $constraintTasks
      GROUP BY `team_question`.$score, `team_question`.questionID
      ORDER BY `team_question`.questionID, `team_question`.$score
      ";
   $stmt = db()->prepare($query);
   $stmt->execute($options);
   $results = $stmt->fetchAll(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("failed to obtain data");
   }
   return newobj(array(
      'contestID' => $contestID,
      'contestName' => $contestName,
      'groupID' => $groupID,
      'groupName' => $groupName,
      'nbTeams' => intval($nbTeams),
      'questionProperties' => $questionProperties,
      'scoreOccurences' => $results));
}


//*****************************************************************
// Chart display: full scores

function getChartFullScores($descr, $cumulative) {
   //  $descr as returned by getDataFullScores
   $series = array();
   $nbCumul = $descr->nbTeams;
   foreach ($descr->scoreOccurences as $scoreAndNbOcc) {   
      $nbOcc = intval($scoreAndNbOcc->nbOcc);
      $series[] = array(
         intval($scoreAndNbOcc->score),
         ($cumulative) ? $nbCumul : $nbOcc);
      $nbCumul -= $nbOcc;
   }
   $ytitle = ($cumulative) ? 'Nb participants avec au moins ce score' : 'Nb participants avec ce score';
   $baseOptions =  array(
   'chart' => array(
      'defaultSeriesType' => 'scatter',
      ),
   'loading' => array(
      'hideDuration' => 0,
      ),
   'credits' => array(
      'text' => '',
      ),
    'plotOptions' => array(   
       'scatter' => array(
           'marker' => array(
               'radius' => 1,
               ),
           'lineWidth' => 1,
           'animation' => false,
           'color' => '#0000FF',
            ),
      ),
   'xAxis' => array(
      /*                     
      'labels' => array(
         'align' => 'right',
         'rotation' => -45,
         'style' => '',
         //'align' => 'left',
         //'rotation' => 45,
         ),
      */
      ),
   'legend' => array(
        'reversed' => true,
       ),
   );
   $options = array_merge_recursive($baseOptions, array(
   'chart' => array(
      'width' => 1000,
      'height' => 340,
      ),
   'title' => array(
      'text' => $ytitle,
      ),
   'xAxis' => array(
      'labels' => array(
         'style' => ''
         // 'style' => 'font-weight:bold',
         ),
      ),
   'yAxis' => array(
      'min' => 0,
      'title' => array(
         'text' => 'Occurences',
         ),
      ),
   'series' => array(
       array(
             'name' => 'Score ',
             'data' => $series,
             'color' => '#0000FF',
             ),
       ),
    'legend' => array(
        'enabled' => false,
       ),
   ));
   return createHighchart($options);
}

/* barplot:
function getChartFullScores($descr) {
   //  $descr as returned by getDataFullScores
   $series = array(
      'labels' => array(),
      'values' => array(),
      );
   foreach ($descr->scoreOccurences as $scoreAndNbOcc) {   
      $series['labels'][] = $scoreAndNbOcc->score;
      $series['values'][] = intval($scoreAndNbOcc->nbOcc); //  / $descr->nbTeams
   }
   $options = array_merge_recursive(getBarChartCommonOptions(), array(
   'chart' => array(
      'width' => 1000,
      'height' => 340,
      ),
   'title' => array(
      'text' => 'Scores sur '.$descr->contestName,
      ),
   'xAxis' => array(
      'categories' => $series['labels'],
      'labels' => array(
         'style' => ''
         // 'style' => 'font-weight:bold',
         ),
      ),
   'yAxis' => array(
      'title' => array(
         'text' => 'Occurences',
         ),
      ),
   'series' => array(
       array(
             'name' => 'Score ',
             'data' => $series['values'],
             'color' => '#0000FF',
             ),
       ),
    'legend' => array(
        'enabled' => false,
       ),
   ));
   return createHighchart($options);
}
*/

//*****************************************************************
// Pre-processing: zero/full/overHalf/belowHalf score distribution

function compareTaskRatio($a, $b) {
   $d = $b->full - $a->full;
   if ($d == 0)
      return $b->overHalf - $a->overHalf;
   else
      return $d;
}

function getDescrResolution($data) {
   // $data : as returned by getDataScoresDistrib

   $nbTeams = $data->nbTeams;

   $scoreOccurencesByTask = array();
   foreach ($data->scoreOccurences as $row) {
      $questionID = $row->questionID;
      if (! isset($scoreOccurencesByTask[$questionID]))
         $scoreOccurencesByTask[$questionID] = array();
      array_push($scoreOccurencesByTask[$questionID], array($row->score, $row->nbOcc));
   }
   $series = array();
   foreach ($scoreOccurencesByTask as $questionID => $scoreAndNbOccs) {
      $properties = $data->questionProperties[$questionID];
      $maxScore = $properties->maxScore;
      $nbBelowHalf = 0;
      $nbOverHalf = 0;
      $nbFull = 0;
      $nbZero = 0;
      foreach ($scoreAndNbOccs as $scoreAndNbOcc) {
         $score = $scoreAndNbOcc[0];
         $nbOcc = $scoreAndNbOcc[1];
         if ($score == $maxScore) {
            $nbFull += $nbOcc;
         } else if ($score == 0) {
            $nbZero += $nbOcc;
         } else if ($score < $maxScore / 2) {
            $nbBelowHalf += $nbOcc;
         } else {
            $nbOverHalf += $nbOcc;
         }
      }
      array_push($series, newobj(array(
         'label' => $properties->name . showID($questionID), 
         'zero' => round(100 * $nbZero / $nbTeams),
         'full' => round(100 * $nbFull / $nbTeams),
         'overHalf' => round(100 * $nbOverHalf / $nbTeams),
         'belowHalf' => round(100 * $nbBelowHalf / $nbTeams))));
   }
   usort($series, "compareTaskRatio");
   return newobj(array(
      "groupName" => ($data->groupID == FALSE) ? FALSE : ($data->groupName . showID($data->groupID)),
      "contestID" => $data->contestID,
      "contestName" => $data->contestName . showID($data->contestID),
      "series" => $series));
}

function fix2014DescrResolution(& $descr) {
   $id = $descr->contestID;
   foreach ($descr->series as $serie) {
      $y = "";
      $x = $serie->label;
      if (($x == "Boulier") || ($x == "Retourner les crêpes")) {
         $y = ($id <= 32) ? "a" : "b";
      } else if (($x == "Terminal") 
              || ($x == "Les amis") 
              || ($x == "Parapente") 
              || ($x == "Traverser le pont") 
              || ($x == "Position secrète")) {
         $y = ($id <= 33) ? "a" : "b";
      } 
      if ($y != "")
         $serie->label = $x . " ($y)";
   }
}


//*****************************************************************
// Chart display: resolution stats

function getChartResolutionStats($descr, $showLegend) {
   $showZero = false;

   //  $descr->contestName : string
   //  $descr->series : list of
   //    - belowHalf/overHalf/full as float in range [0,1]
   //    - label as string

   $series = array(
      'labels' => array(),
      'zero' => array(),
      'full' => array(),
      'overHalf' => array(),
      'belowHalf' => array(),
      );
   foreach ($descr->series as $serie) {   
      $series['labels'][] = addslashes($serie->label);
      $series['zero'][] = $serie->zero;
      $series['full'][] = $serie->full;
      $series['overHalf'][] = $serie->overHalf;
      $series['belowHalf'][] = $serie->belowHalf;
   }

   $width = '1000';
   $height = ($showLegend) ? '370' : '340';
   if ($descr->groupName !== FALSE) {
      $title = "Réussite de ".$descr->groupName;
   } else {
      $title = 'Réussite sur '.$descr->contestName;
   }
   $allSeries = array(
       array(
          'name' => '>0% et <50% pts',
          'data' => $series['belowHalf'],
          'color' => '#FFA619', // # B10562
          ),
       array(
          'name' => '>=50% et <100% pts',
          'data' => $series['overHalf'],
          'color' => '#5BC7FF',
          ),
       array(
          'name' => '100% pts',
          'data' => $series['full'],
          'color' => '#0000FF',
      ));
   if ($showZero) {
      array_unshift($allSeries,
        array(
          'name' => '0% pts',
          'data' => $series['zero'],
          'color' => '#FF0000',
          ));
   }
   $options = array_merge_recursive(getBarChartCommonOptions(), array(
   'chart' => array(
      'width' => $width,
      'height' => $height,
      ),
   'title' => array(
      'text' => $title
      ),
   'xAxis' => array(
      'categories' => $series['labels'],
      'labels' => array(
         'align' => 'right',
         'rotation' => -45,
         ),
      ),
   'yAxis' => array(
      'min' => 0,
      'max' => 100,
      'title' => array(
         'text' => 'Pourcentage des équipes',
         ),
      ),
    'series' => $allSeries,
    'legend' => array(
        'enabled' => $showLegend,
       ),
   ));
   return createHighchart($options);
}


//*****************************************************************
// Pre-processing: score distribution per task

function getDescrScoreDistrib($data) {
   // $data : as returned by getDataScoresDistrib

   $nbTeams = $data->nbTeams;

   $descrByTask = array();
   foreach ($data->scoreOccurences as $row) {
      $questionID = $row->questionID;
      if (! isset($descrByTask[$questionID]))
         $descrByTask[$questionID] = newobj(array('series' => array()));
      array_push($descrByTask[$questionID]->series, newobj(array(
         'score' => $row->score, 
         'percentage' => round(100 * $row->nbOcc / $nbTeams))));
   }

   foreach ($descrByTask as $questionID => $descr) {
      $properties = $data->questionProperties[$questionID];
      $descr->taskName = $properties->name . showID($questionID);
      $descr->contestName = $data->contestName . showID($data->contestID);
   }
   return $descrByTask;
}

//*****************************************************************
// Chart display: resolution stats

function getChartScores($descr) {
   //  $descr->taskName : string
   //  $descr->contestName : string
   //  $descr->series : list of ->score, and ->percentage
   $series = array(
      'labels' => array(),
      'values' => array(),
      );
   foreach ($descr->series as $serie) {   
      $series['labels'][] = $serie->score;
      $series['values'][] = $serie->percentage;
   }
   $options = array_merge_recursive(getBarChartCommonOptions(), array(
   'chart' => array(
      'width' => 500,
      'height' => 300,
      ),
   'title' => array(
      'text' => '<span style="font-size: small">Scores sur '.$descr->taskName.' ('.$descr->contestName.')</span>',
      ),
   'xAxis' => array(
      'categories' => $series['labels'],
      'labels' => array(
         'style' => ''
         // 'style' => 'font-weight:bold',
         ),
      ),
   'yAxis' => array(
      'min' => 0,
      'title' => array(
         'text' => 'Pourcentage des équipes',
         ),
      ),
   'series' => array(
       array(
             'name' => 'Score ',
             'data' => $series['values'],
             'color' => '#0000FF',
             ),
       ),
    'legend' => array(
        'enabled' => false,
       ),
   ));
   return createHighchart($options);
}

//*****************************************************************
// Query: participation stats

function getDataParticipation($contestID) {

   // get contest name
   $contestName = FALSE;
   setContestName($contestID, $contestName);

   $score = "score";
   if (useFfscore())
      $score = "cached_ffScore";
   $foreignSchool = getForeignSchoolConstraints();
   $query = "
    SELECT 
         (SELECT COUNT(`team`.ID) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID)
      as nbTeamsAll,
         (SELECT COUNT(`team`.ID) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy + `team`.cached_nbGirl = 1)
      as nbTeamsSingle,
         (SELECT COUNT(`team`.ID) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 0
         AND `team`.cached_nbGirl = 1)
      as nbTeamsGirl,
         (SELECT COUNT(`team`.ID) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 1
         AND `team`.cached_nbGirl = 0)
      as nbTeamsBoy,
         (SELECT COUNT(`team`.ID) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 1
         AND `team`.cached_nbGirl = 1)
      as nbTeamsMixed,
         (SELECT COUNT(`contestant`.ID) 
         FROM `contestant`, `team` 
         WHERE `contestant`.teamID = `team`.ID
         AND `team`.cached_officialForContestID = :contestID)
      as nbParticipants,
         (SELECT COUNT(`contestant`.ID) 
         FROM `contestant`, `team` 
         WHERE `contestant`.teamID = `team`.ID
         AND `team`.cached_officialForContestID = :contestID
         AND `contestant`.genre = 1)
      as nbParticipantsGirl,
         (SELECT SUM(`team`.$score) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID)
      as sumScoreAll,
         (SELECT SUM(`team`.$score) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy + `team`.cached_nbGirl = 1)
      as sumScoreSingle,
         (SELECT SUM(`team`.$score) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 0
         AND `team`.cached_nbGirl = 1)
      as sumScoreGirl,
         (SELECT SUM(`team`.$score) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 1
         AND `team`.cached_nbGirl = 0)
      as sumScoreBoy,
         (SELECT SUM(`team`.$score) 
         FROM `team` 
         WHERE `team`.cached_officialForContestID = :contestID
         AND `team`.cached_nbBoy = 1
         AND `team`.cached_nbGirl = 1)
      as sumScoreMixed,
         (SELECT COUNT(`school`.ID) 
         FROM `school`  
         WHERE EXISTS (
            SELECT * FROM `group` 
            WHERE `group`.schoolID = `school`.ID
            AND EXISTS (
               SELECT * FROM `team`
               WHERE `team`.groupID = `group`.ID
               AND `team`.cached_officialForContestID = :contestID
               )))
      as nbSchools
      ";
   $options = array(":contestID" => $contestID);
   $stmt = db()->prepare($query);
   $stmt->execute($options);
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("no teams participated");
   }
   $descr = newobj(array(
      "contestID" => $contestID,
      "contestName" => $contestName));
   foreach ($results as $key => $value) {
      $descr->$key = $value;
   }
   return $descr;
}

function addDataParticipation(& $dataTotal, & $dataToAdd) {
   foreach ($dataToAdd as $key => $value) {
      if (! isset($dataTotal->$key)) {
         $dataTotal->$key = 0;
      }
      if ($key == "contestName") {
         $dataTotal->$key .= $dataToAdd->contestID . ", " ;
      } else if ($key == "contestID") {
         $dataTotal->$key .= $dataToAdd->$key . ", " ;
      } else {
         $dataTotal->$key += $dataToAdd->$key;
      } 
   }
}

function getDataSchoolsTotal($contestIDs) {
   $foreignSchool = getForeignSchoolConstraints();
   $constraintContest = sql_or("`team`.cached_officialForContestID", $contestIDs);
   $constraintHasOfficial = "
     EXISTS (SELECT * FROM `group` 
      WHERE `group`.schoolID = `school`.ID
      AND EXISTS (
         SELECT * FROM `team`
         WHERE `team`.groupID = `group`.ID
         AND $constraintContest
         ))";
   $query = "
     SELECT
         (SELECT COUNT(`school`.ID)
         FROM `school`  
         WHERE $constraintHasOfficial)
      as nbSchools,
         (SELECT COUNT(`school`.ID) 
         FROM `school`  
         WHERE $foreignSchool
         AND $constraintHasOfficial)
     as nbSchoolsForeign,
         (SELECT COUNT(`contestant`.ID) 
         FROM `contestant`, `team`, `school` 
         WHERE `contestant`.cached_schoolID = `school`.ID 
         AND `contestant`.teamID = `team`.ID
         AND $constraintContest
         AND $foreignSchool)
     as nbParticipantsForeign
     ";
     // TODO: mettre un join pour récupére la school ci dessus.

   $stmt = db()->prepare($query);
   $stmt->execute();
   $results = $stmt->fetch(PDO::FETCH_OBJ);
   if ($results === FALSE) {
     return newerror("getDataSchoolsTotal error");
   }
   return $results;
}



//*****************************************************************
// Chart display: participation stats

function getChartParticipation($data) {
   
   $nbTeams = $data->nbTeamsAll;
   if ($nbTeams == 0) {
      return "<div>No participants for $data->contestName ($data->contestID)</div>";
   }

   $ratioGirls = percentage($data->nbParticipantsGirl / $data->nbParticipants);

   $ratioMixed = percentage($data->nbTeamsMixed / $nbTeams);

   $data->nbTeamsDouble = $data->nbTeamsAll - $data->nbTeamsSingle;
   $ratioDoubles = percentage($data->nbTeamsDouble / $nbTeams);
   // $ratioDoubles2 = round(100 * ($data->nbParticipants - $nbTeams) / $nbTeams) . "%";

   $data->sumScoreDouble = $data->sumScoreAll - $data->sumScoreSingle;

   // $data->nbTeamsBoy = $data->nbTeamsSingle - $data->nbTeamsGirl;
   // $data->sumScoreBoy = $data->sumScoreSingle - $data->sumScoreGirl;

   $avgScoreTeams = roundScore($data->sumScoreAll / $data->nbTeamsAll);
   $avgScoreSingle = roundScoreAndRelative($data->sumScoreSingle / $data->nbTeamsSingle, $avgScoreTeams);
   $avgScoreDouble = roundScoreAndRelative($data->sumScoreDouble / $data->nbTeamsDouble, $avgScoreTeams);
   $avgScoreBoy = roundScoreAndRelative($data->sumScoreBoy / $data->nbTeamsBoy, $avgScoreTeams);
   $avgScoreGirl = roundScoreAndRelative($data->sumScoreGirl / $data->nbTeamsGirl, $avgScoreTeams);
   $avgScoreMixed = roundScoreAndRelative($data->sumScoreMixed / $data->nbTeamsMixed, $avgScoreTeams);

   $extra = "";
   if (! empty($data->nbSchoolsForeign)) {
      $extra .= "<tr><td>nbSchoolsForeign</td><td>$data->nbSchoolsForeign</td></tr>";
      $extra .= "<tr><td>nbParticipantsForeign</td><td>$data->nbParticipantsForeign</td></tr>";
   }

   return "
      <style>
      .participation {
         margin: 1em;
         border-collapse: collapse;
      }
      .participation td {
         border: 1px solid black;
         padding: 0.2em;
      }
      </style>
      <div>
      <table class='participation'>
      <tr><td>name</td><td style='width: 20em'>$data->contestName</td></tr>
      <tr><td>nbParticipants</td><td>$data->nbParticipants</td></tr>
      <tr><td>ratioGirls</td><td>$ratioGirls</td></tr>
      <tr><td>nbTeamsTotal</td><td>$data->nbTeamsAll</td></tr>
      <tr><td>ratioDoubles</td><td>$ratioDoubles</td></tr>
      <tr><td>ratioMixed</td><td>$ratioMixed</td></tr>
      <tr><td>avgScoreTeams</td><td>$avgScoreTeams</td></tr>
      <tr><td>avgScoreSingle</td><td>$avgScoreSingle</td></tr>
      <tr><td>avgScoreDouble</td><td>$avgScoreDouble</td></tr>
      <tr><td>avgScoreSingleBoy</td><td>$avgScoreBoy</td></tr>
      <tr><td>avgScoreSingleGirl</td><td>$avgScoreGirl</td></tr>
      <tr><td>avgScoreMixed</td><td>$avgScoreMixed</td></tr>
      <tr><td>nbSchools</td><td>$data->nbSchools</td></tr>
      $extra
      </table>
      </div>";
}


//*****************************************************************
// Main

if (isset($_REQUEST["prepare_scores"])) {
   makePrepareScores();
   echo "prepare_scores completed";
   exit();
}

if (isset($_REQUEST["prepare_genders"])) {
   makePrepareGenders();
   echo "prepare_genders completed";
   exit();
}



$cacheParams = array($type, $contestIDs, $groupIDs, $taskIDs);
$cacheFile = 'stats_cache.php';

$data = FALSE;
$message = "";
$output = "";


$contestGroupIDs = array();
if (count($groupIDs) > 0) {
   if (count($contestIDs) > 0) {
      $message = "cannot specify both groups and contest";
   }
   foreach ($groupIDs as $groupID) {
      $contestGroupIDs[] = newobj(array("contestID" => FALSE, "groupID" => $groupID));
   }
} else if (count($contestIDs) > 0) {
   foreach ($contestIDs as $contestID) {
      $contestGroupIDs[] = newobj(array("contestID" => $contestID, "groupID" => FALSE));
   }
}

if ($type == "full_score_distrib" || $type == "full_score_cumulative") {
   if (! empty($taskIDs)) {
      $message = "cannot specify taskIDs when asking for full score distribution";
   } else {
      $cumulative = ($type == "full_score_cumulative");
      foreach ($contestGroupIDs as $contestGroupID) {
         $descr = getDataFullScores($contestGroupID->contestID, $contestGroupID->groupID);
         $output .= getChartFullScores($descr , $cumulative);
      }
   }

} else if ($type == "task_score_distrib") {
   $dataForTask = array();
   foreach ($contestGroupIDs as $contestGroupID) {
      $data = getDataScoresDistrib($contestGroupID->contestID, $contestGroupID->groupID, $taskIDs);
      $descrScoreDistribPerTask = getDescrScoreDistrib($data);
      foreach ($descrScoreDistribPerTask as $taskID => $descr) {
         if (! isset($dataFor[$taskID]))
            $dataFor[$taskID] = array();
         $dataForTask[$taskID][$contestGroupID->contestID] = $descr;
      }
   }
   foreach ($dataForTask as $taskID => $descrForContestID) {
      foreach ($descrForContestID as $contestID => $descr) {
         $output .= getChartScores($descr);
      }
   }

} else if ($type == "resolution_stats") {
   foreach ($contestGroupIDs as $i => $contestGroupID) {
      $data = getDataScoresDistrib($contestGroupID->contestID, $contestGroupID->groupID, $taskIDs);
      $descr = getDescrResolution($data);
      fix2014DescrResolution($descr);
      $showLegend = ($i == 0);
      $output .= getChartResolutionStats($descr, $showLegend);
   }

} else if ($type == "participation_stats") {
   $dataTotal = newobj(array(
      "contestID" => FALSE,
      "contestName" => "Total for "));
   foreach ($contestIDs as $contestID) {
      $data = getDataParticipation($contestID);
      addDataParticipation($dataTotal, $data);
      $output .= getChartParticipation($data);
   }
   if (count($contestIDs) > 1) {
      $extra = getDataSchoolsTotal($contestIDs);
      $dataTotal->nbSchools = $extra->nbSchools;
      $dataTotal->nbSchoolsForeign = $extra->nbSchoolsForeign;
      $dataTotal->nbParticipantsForeign = $extra->nbParticipantsForeign;
      $output .= getChartParticipation($dataTotal);
   }
} 



$body = "";
if (!$arg_hideBar) {
   $body .= getPageForm();
}
$body .= $message;
$body .= $output;
echo getHTMLPage($body);
exit();




?>