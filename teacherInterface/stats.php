<?php 

require_once("../shared/common.php");
require_once("commonAdmin.php");
include('./config.php');

$groupTable = "group";
$teamTable = "team";
$contestantTable = "contestant";
$intervals = [
    300 => "5 minutes",
    900 => "15 minutes",
    3600 => "1 hour",
    3600 * 6 => "6 hours",
    3600 * 24 => "1 day",
    3600 * 24 * 7 => "1 week",
    3600 * 24 * 30 => "1 month",
];

function printColumns($columns, $data, $headers = false) {
    foreach($columns as $idx => $column) {
        $classes = '';
        if($headers) {
            echo "<th";
        } else {
            echo "<td";
            $classes = "right-align ";
        }
        if($idx == 0) {
            $classes .= " left-col";
        } else if($idx == count($columns) - 1) {
            $classes .= " right-col";
        }
        if($classes) {
            echo " class='".$classes."'";
        }
        echo ">";
        if($headers) {
            echo $column;
        } else {
            echo isset($data[$column]) ? $data[$column] : "-";
        }
        if($headers) {
            echo "</th>";
        } else {
            echo "</td>";
        }
    }
}

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<?php
script_tag('/bower_components/jquery/jquery.min.js');
?>
<link rel="stylesheet" href="admin.css">
<style>
body {
    margin: 16px;
}
table {
    border: 2px solid black;
    border-collapse: collapse;
    margin-top: 16px;
}
td, th {
    border: 1px solid black;
    padding: 4px;
}
.right-align {
    text-align: right;
}
.left-col {
    border-left: 2px solid black;
}
.right-col {
    border-right: 2px solid black;
}
.total-row {
    font-weight: bold;
}
.no-wrap {
    white-space: nowrap;
    word-break: keep-all;
}
</style>
</head>
<body>
<?php

if (!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
   echo translate("admin_restricted");
   exit;
}

if (!isset($_GET["contestID"])) {
   echo "contestID parameter is missing.";
   exit;
}
$contestID = $_GET["contestID"];

$contests = [$contestID];
$contestsWithTeams = [];
$contestInfos = [];
$stmt = $db->prepare("SELECT * FROM `contest` WHERE ID = :contestID OR parentContestID = :contestID ORDER BY name ASC");
$stmt->execute(['contestID' => $contestID]);
while($row = $stmt->fetchObject()) {
    $contestInfos[$row->ID] = $row;
    if($row->ID != $contestID) {
        $contests[] = $row->ID;
    }
}
if(!isset($contestInfos[$contestID])) {
    echo "Contest not found.";
    exit;
}
?>

<h1><?=$contestInfos[$contestID]->name ?></h1>
<p>as of <?=date("Y-m-d H:i:s")?></p>
<p>Legend :
    <ul>
        <li>G : number of groups (groups with at least one participation / total groups)</li>
        <li>T : number of teams</li>
        <li>C : number of contestants</li>
    </ul>
</p>

<?php
if(count($contestInfos) > 1) {
?>
<h2>Sub-contests</h2>
<ul>
<?php
foreach($contestInfos as $contest) {
    if($contest->ID == $contestID) {
        continue;
    }
    echo "<li>".$contest->name."</li>";
}
?>
</ul>
<?php
}
?>

<h2>Stats by participationType</h2>
<table>
    <thead>
        <tr>
            <th rowspan="2">Participation type</th>
<?php
$ptNumbers = [];
$totalPtNumbers = [];
$stmt = $db->prepare("
    SELECT
    COUNT(DISTINCT `group`.ID) AS GT,
    COUNT(DISTINCT IF(`group`.nbTeamsEffective > 0, `group`.ID, NULL)) AS GP,
    COUNT(DISTINCT `team`.ID) AS T,
    COUNT(DISTINCT `contestant`.ID) AS C,
    `group`.participationType,
    `group`.contestID
    FROM `$groupTable` AS `group`
    JOIN `contest` ON `group`.contestID = `contest`.ID
    LEFT JOIN `$teamTable` AS team ON `group`.ID = `team`.groupID
    LEFT JOIN `$contestantTable` AS contestant ON `team`.ID = `contestant`.teamID
    WHERE `group`.contestID = :contestID OR `contest`.parentContestID = :contestID
    GROUP BY `group`.participationType, `group`.contestID;");
$stmt->execute(['contestID' => $contestID]);
while ($row = $stmt->fetchObject()) {
    if(!isset($ptNumbers[$row->participationType])) {
        $ptNumbers[$row->participationType] = [];
    }
    $ptNumbers[$row->participationType][$row->contestID] = (array) $row;
    $ptNumbers[$row->participationType][$row->contestID]["G"] = $row->GP . "&nbsp;/&nbsp;" . $row->GT;
    if(!isset($totalPtNumbers[$row->contestID])) {
        $totalPtNumbers[$row->contestID] = ["GP" => 0, "GT" => 0, "T" => 0, "C" => 0];
    }
    $totalPtNumbers[$row->contestID]["GP"] += $row->GP;
    $totalPtNumbers[$row->contestID]["GT"] += $row->GT;
    $totalPtNumbers[$row->contestID]["T"] += $row->T;
    $totalPtNumbers[$row->contestID]["C"] += $row->C;
    if($row->T > 0 && !in_array($row->contestID, $contestsWithTeams)) {
        $contestsWithTeams[] = $row->contestID;
    }
}
$contestColumns = [];
foreach($contests as $idx => $contest) {
    $columns = [];
    if($idx == 0) {
        $columns[] = 'G';
    }
    if(in_array($contest, $contestsWithTeams)) {
        $columns[] = 'T';
        if($contestInfos[$contest]->allowTeamsOfTwo == 1) {
            $columns[] = 'C';
        }
    }
    $contestColumns[$contest] = $columns;
}
foreach($contests as $idx => $contest) {
    echo "<th colspan='".count($contestColumns[$contest])."' class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
    $totalPtNumbers[$contest]["G"] = $totalPtNumbers[$contest]["GP"] . "&nbsp;/&nbsp;" . $totalPtNumbers[$contest]["GT"];
}
echo "</tr><tr>";
foreach($contests as $idx => $contest) {
    printColumns($contestColumns[$contest], null, true);
}
?>
        </tr>
    </thead>
    <tbody>
<?php
foreach($ptNumbers as $pt => $numbers) {
    echo "<tr><td>".$pt."</td>";
    foreach($contests as $contest) {
        printColumns($contestColumns[$contest], isset($numbers[$contest]) ? $numbers[$contest] : null);
    }
    echo "</tr>";

}
echo "<tr class='total-row'><td>Total</td>";
foreach($contests as $idx => $contest) {
    printColumns($contestColumns[$contest], $totalPtNumbers[$contest]);
}
echo "</tbody></table>";
flush();
?>

<h2>Teams by participation status</h2>
<table>
    <thead>
        <tr>
            <th rowspan="2">Participation status</th>
<?php
foreach($contestsWithTeams as $contest) {
    echo "<th colspan='".count($contestColumns[$contest])."' class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
}
echo "</tr><tr>";
foreach($contestsWithTeams as $contest) {
    printColumns($contestColumns[$contest], null, true);
}
?>
        </tr>
    </thead>
    <tbody>
<?php
$statusNumbers = ["Not started" => [], "Started" => [], "Ended" => []];
$stmt = $db->prepare("
    SELECT
    COUNT(DISTINCT `team`.ID) AS T,
    COUNT(DISTINCT `contestant`.ID) AS C,
    `team`.startTime IS NOT NULL as started,
    (`team`.startTime IS NOT NULL AND (`team`.endTime IS NOT NULL OR `team`.startTime + INTERVAL `team`.nbMinutes MINUTE < NOW())) AS ended,
    `team`.contestID
    FROM `$teamTable` AS `team`
    JOIN `contest` ON `team`.contestID = `contest`.ID
    LEFT JOIN `$contestantTable` AS `contestant` ON `team`.ID = `contestant`.teamID
    WHERE (`team`.contestID = :contestID OR `contest`.parentContestID = :contestID)
    GROUP BY started, ended, `team`.contestID;");
$stmt->execute(['contestID' => $contestID]);
while($row = $stmt->fetchObject()) {
    $status = $row->ended ? "Ended" : ($row->started ? "Started" : "Not started");
    $statusNumbers[$status][$row->contestID] = (array) $row;
}
foreach($statusNumbers as $status => $numbers) {
    echo "<tr><td>".$status."</td>";
    foreach($contestsWithTeams as $contest) {
        printColumns($contestColumns[$contest], isset($numbers[$contest]) ? $numbers[$contest] : null);
    }
    echo "</tr>";
}
echo "</tbody></table>";
flush();
?>

<h2>Contestants by genre and grade</h2>
<?php
$genreNumbers = [];
$gradeNumbers = [];
$stmt = $db->prepare("
    SELECT
    COUNT(DISTINCT `contestant`.ID) AS nbContestants,
    `contestant`.grade,
    `contestant`.genre,
    `team`.contestID
    FROM `$contestantTable` AS `contestant`
    JOIN `$teamTable` AS `team` ON `contestant`.teamID = `team`.ID
    JOIN `contest` ON `team`.contestID = `contest`.ID
    WHERE (`team`.contestID = :contestID OR `contest`.parentContestID = :contestID)
    GROUP BY `contestant`.grade, `contestant`.genre, `team`.contestID
    ORDER BY `contestant`.grade ASC, `contestant`.genre ASC;");
$stmt->execute(['contestID' => $contestID]);
while($row = $stmt->fetchObject()) {
    if(!isset($genreNumbers[$row->genre])) {
        $genreNumbers[$row->genre] = ["total" => 0];
    }
    if(!isset($genreNumbers[$row->genre][$row->contestID])) {
        $genreNumbers[$row->genre][$row->contestID] = 0;
    }
    $genreNumbers[$row->genre][$row->contestID] += $row->nbContestants;
    $genreNumbers[$row->genre]["total"] += $row->nbContestants;

    if(!isset($gradeNumbers[$row->grade])) {
        $gradeNumbers[$row->grade] = ["total" => 0];
    }
    if(!isset($gradeNumbers[$row->grade][$row->contestID])) {
        $gradeNumbers[$row->grade][$row->contestID] = 0;
    }
    $gradeNumbers[$row->grade][$row->contestID] += $row->nbContestants;
    $gradeNumbers[$row->grade]["total"] += $row->nbContestants;
}

echo "<table><thead><tr><th>Genre</th><th>Total</th>";
foreach($contestsWithTeams as $contest) {
    echo "<th class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
}
echo "</tr></thead><tbody>";
foreach($genreNumbers as $genre => $numbers) {
    echo "<tr><td>".($genre == 0 ? "-" : translate($genre == 1 ? "option_female" : "option_male"))."</td><td class='right-align'>".$numbers["total"]."</td>";
    foreach($contestsWithTeams as $contest) {
        if(isset($numbers[$contest])) {
            echo "<td class='right-align left-col'>".$numbers[$contest]."</td>";
        } else {
            echo "<td class='right-align left-col'>-</td>";
        }
    }
    echo "</tr>";
}
echo "</tbody></table>";

echo "<table><thead><tr><th>Grade</th><th>Total</th>";
foreach($contestsWithTeams as $contest) {
    echo "<th class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
}
echo "</tr></thead><tbody>";
foreach($gradeNumbers as $grade => $numbers) {
    echo "<tr><td>".translate("grade_".$grade)."</td><td class='right-align'>".$numbers["total"]."</td>";
    foreach($contestsWithTeams as $contest) {
        if(isset($numbers[$contest])) {
            echo "<td class='right-align left-col'>".$numbers[$contest]."</td>";
        } else {
            echo "<td class='right-align left-col'>-</td>";
        }
    }
    echo "</tr>";
}
echo "</tbody></table>";
flush();
?>

<h2>Participation statistics by time</h2>
<?php
if(!isset($_GET["showDates"]) || $_GET["showDates"] != "1") {
    echo "<p><a href='?contestID=".$contestID."&showDates=1'>Show teams by time created (slower)</a></p>";
    die();
}
?>
<p>Set time interval to :
<?php
if(isset($_GET["interval"])) {
    $interval = intval($_GET["interval"]);
} else {
    $interval = 3600 * 24;
}

foreach($intervals as $newInterval => $label) {
    if($newInterval == $interval) {
        echo $label." / ";
    } else {
        echo "<a href='?contestID=".$contestID."&showDates=1&interval=".$newInterval."'>".$label."</a> / ";
    }
}
?>
<a href="?contestID=<?=$contestID?>">Hide dates</a></p>
<table>
    <thead>
        <tr>
            <th rowspan="2">Date created</th>
<?php
$totalColumns = ["T"];
if(count($contestInfos) > 1) {
    $hasContestantsColumn = false;
    foreach($contestColumns as $columns) {
        $hasContestantsColumn = $hasContestantsColumn || in_array("C", $columns);
    }
    if($hasContestantsColumn) {
        $totalColumns[] = "C";
    }
}
if(count($contestInfos) > 1) {
    echo "<th colspan='".count($totalColumns)."' class='left-col right-col'>Total</th>";
}
foreach($contestsWithTeams as $contest) {
    echo "<th colspan='".count($contestColumns[$contest])."' class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
}
echo "</tr><tr>";
if(count($contestInfos) > 1) {
    printColumns($totalColumns, null, true);
}
foreach($contestsWithTeams as $contest) {
    printColumns($contestColumns[$contest], null, true);
}
?>
        </tr>
    </thead>
    <tbody>
<?php
$dateNumbers = [];
$limit = 100 * count($contestsWithTeams);
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT `team`.ID) AS T,
    COUNT(DISTINCT `contestant`.ID) AS C,
    ROUND(UNIX_TIMESTAMP(createTime)/$interval) AS intervalCreated,
    `team`.contestID
    FROM `$teamTable` AS `team`
    JOIN `contest` ON `team`.contestID = `contest`.ID
    LEFT JOIN `$contestantTable` AS `contestant` ON `team`.ID = `contestant`.teamID
    WHERE createTime IS NOT NULL AND (`team`.contestID = :contestID OR `contest`.parentContestID = :contestID)
    GROUP BY intervalCreated, `team`.contestID
    ORDER BY intervalCreated DESC
    LIMIT $limit;");
$stmt->execute(['contestID' => $contestID]);
while($row = $stmt->fetchObject()) {
    if(!isset($dateNumbers[$row->intervalCreated])) {
        $dateNumbers[$row->intervalCreated] = ["total" => ["T" => 0, "C" => 0]];
    }
    $dateNumbers[$row->intervalCreated][$row->contestID] = (array) $row;
    $dateNumbers[$row->intervalCreated]["total"]["T"] += $row->T;
    $dateNumbers[$row->intervalCreated]["total"]["C"] += $row->C;
    $limit -= 1;
}

$lastDate = null;
foreach($dateNumbers as $date => $numbers) {
    if($lastDate !== null && $lastDate != $date + 1) {
        echo "<tr><td colspan='".(count($contestsWithTeams) * 2 + 3)."'></td></tr>";
    }
    $lastDate = $date;
    echo "<tr><td class='no-wrap'>";
    echo date("Y-m-d H:i", $date * $interval) . " &mdash; " . date($interval >= 3600 * 24 ? "Y-m-d H:i" : "H:i", ($date + 1) * $interval);
    echo "</td>";
    if(count($contestInfos) > 1) {
        printColumns($hasContestantsColumn ? ["T", "C"] : ["T"], $numbers["total"]);
    }
    foreach($contestsWithTeams as $contest) {
        printColumns($contestColumns[$contest], isset($numbers[$contest]) ? $numbers[$contest] : null);
    }
    echo "</tr>";
}
?>
    </tbody>
</table>
<?php
if($limit <= 0) {
    echo "<p><i>Note : row limit reached, use a smaller interval to go back further in time.</i></p>";
}
?>
</body>