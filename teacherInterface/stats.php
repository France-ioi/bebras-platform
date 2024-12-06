<?php 

require_once("../shared/common.php");
require_once("commonAdmin.php");
include('./config.php');

$db2 = getRODB();

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

function printColumns($columns, $data, $headers = false, $bold = false) {
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
        if($bold) { echo "<b>"; }
        if($headers) {
            echo $column;
        } else {
            echo isset($data[$column]) ? $data[$column] : "-";
        }
        if($bold) { echo "</b>"; }
        if($headers) {
            echo "</th>";
        } else {
            echo "</td>";
        }
    }
}

function makeTable($name, $contests, $columns, $data) {
    global $contestInfos;

    $colTotals = [];
    $totals = [];
    $fullTotals = [];
    $allColumns = [];
    foreach($contests as $contest) {
        $allColumns = array_unique(array_merge($allColumns, $columns[$contest]));
        $colTotals[$contest] = [];
    }
    $headerHasSecondRow = count($allColumns) > 1;

    echo "<table><thead><tr>";
    echo "<th".($headerHasSecondRow ? " rowspan='2'" : '').">".$name."</th>";
    foreach($contests as $contest) {
        echo "<th colspan='".count($columns[$contest])."' class='left-col right-col'>".$contestInfos[$contest]->name."</th>";
    }
    if(count($contests) > 1) {
        echo "<th colspan='".count($allColumns)."' class='left-col right-col'><b>Total</b></th>";
    }
    if($headerHasSecondRow) {
        echo "</tr><tr>";
        foreach($contests as $contest) {
            printColumns($columns[$contest], null, true);
        }
    }
    echo "</tr></thead>";
    echo "<tbody>";
    foreach($data as $type => $numbers) {
        if(substr($type, 0, 1) == "_") {
            $totalColSpan = count($allColumns) + 1;
            foreach($contests as $contest) {
                $totalColSpan += count($columns[$contest]);
            }
            echo "<tr><td colspan='$totalColSpan'></td></tr>";
            continue;
        }

        $rowTotals = [];
        echo "<tr><td>".$type."</td>";
        foreach($contests as $contest) {
            printColumns($columns[$contest], isset($numbers[$contest]) ? $numbers[$contest] : null);
            foreach($allColumns as $col) {
                if(isset($numbers[$contest][$col])) {
                    $rowTotals[$col] = (isset($rowTotals[$col]) ? $rowTotals[$col] : 0) + $numbers[$contest][$col];
                    $colTotals[$contest][$col] = (isset($colTotals[$contest][$col]) ? $colTotals[$contest][$col] : 0) + $numbers[$contest][$col];
                    $totals[$col] = (isset($totals[$col]) ? $totals[$col] : 0) + $numbers[$contest][$col];
                }
            }
        }
        if(count($contests) > 1) {
            printColumns($allColumns, $rowTotals, false, true);
            foreach($allColumns as $col) {
                $fullTotals[$col] = (isset($fullTotals[$col]) ? $fullTotals[$col] : 0) + $rowTotals[$col];
            }
        }
        echo "</tr>";
    }
    echo "<tr class='total-row'><td><b>Total</b></td>";
    foreach($contests as $contest) {
        printColumns($columns[$contest], isset($colTotals[$contest]) ? $colTotals[$contest] : null);
    }
    if(count($contests) > 1) {
        printColumns($allColumns, $fullTotals);
    }
    echo "</tr>";
    echo "</tbody></table>";
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
thead {
    border-bottom: 2px solid black;
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


$contestID = null;
if(isset($_GET["contestID"])) {
    if(!isset($_SESSION["isAdmin"]) || !$_SESSION["isAdmin"]) {
        echo translate("admin_restricted");
        echo "</body></html>";
        exit;
    }
    $contestID = $_GET["contestID"];
    $url = "?contestID=".$contestID;
} elseif(isset($_GET["password"]) && isset($config->teacherInterface->statsPasswords[$_GET["password"]])) {
    $contestID = $config->teacherInterface->statsPasswords[$_GET["password"]];
    $url = "?password=".$_GET["password"];
} else {
    echo "Missing parameters.";
    echo "</body></html>";
    exit;
}



$contests = [$contestID];
$contestsWithTeams = [];
$contestInfos = [];
$stmt = $db2->prepare("SELECT * FROM `contest` WHERE ID = :contestID OR parentContestID = :contestID ORDER BY name ASC");
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
<!--<p>Legend :
    <ul>
        <li>G : number of groups (groups with at least one participation / total groups)</li>
        <li>T : number of teams</li>
        <li>C : number of contestants</li>
    </ul>
</p>-->

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

<?php
$ptNumbers = [];
$totalPtNumbers = [];
$stmt = $db2->prepare("
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
    if($row->T > 0 && !in_array($row->contestID, $contestsWithTeams)) {
        $contestsWithTeams[] = $row->contestID;
    }
}
$contestColumns = [];
$contestHasColumns = false;
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
?>
<h2>Participation type</h2>
<?php
makeTable("Participation type", $contestsWithTeams, $contestColumns, $ptNumbers);
flush();
?>

<h2>Participation status</h2>
<?php
$statusNumbers = ["Not started" => [], "Started" => [], "Ended" => []];
$stmt = $db2->prepare("
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
makeTable("Participation status", $contestsWithTeams, $contestColumns, $statusNumbers);
flush();

$hasGenre = $contestInfos->askGenre == '1';
$genreNumbers = [];
$gradeNumbers = [];
$query = "
    SELECT
    COUNT(DISTINCT `contestant`.ID) AS nbContestants,
    `contestant`.grade, ";
$query .= $hasGenre ? "`contestant`.genre," : "";
$query .= "
    `team`.contestID
    FROM `$contestantTable` AS `contestant`
    JOIN `$teamTable` AS `team` ON `contestant`.teamID = `team`.ID
    JOIN `contest` ON `team`.contestID = `contest`.ID
    WHERE (`team`.contestID = :contestID OR `contest`.parentContestID = :contestID)
    GROUP BY `contestant`.grade,";
$query .= $hasGenre ? "`contestant`.genre," : "";
$query .= "
    `team`.contestID
    ORDER BY `contestant`.grade ASC";
$query .= $hasGenre ? ", `contestant`.genre ASC;" : "";
$stmt = $db2->prepare($query);
$stmt->execute(['contestID' => $contestID]);
$gcolumns = [];
while($row = $stmt->fetchObject()) {
    if($hasGenre) {
        if(!isset($genreNumbers[$row->genre])) {
            $genreNumbers[$row->genre] = [];
        }
        if(!isset($genreNumbers[$row->genre][$row->contestID])) {
            $genreNumbers[$row->genre][$row->contestID] = ["N" => 0];
        }
        $genreNumbers[$row->genre][$row->contestID]["N"] += $row->nbContestants;
    }

    $grade = translate("grade_".$row->grade);
    if(!isset($gradeNumbers[$grade])) {
        $gradeNumbers[$grade] = [];
    }
    if(!isset($gradeNumbers[$grade][$row->contestID])) {
        $gradeNumbers[$grade][$row->contestID] = ["N" => 0];
    }
    $gradeNumbers[$grade][$row->contestID]["N"] += $row->nbContestants;
    $gcolumns[$row->contestID] = ["N"];
}

if($hasGenre) {
    echo "<h2>Genre</h2>";
    makeTable("Genre", $contestsWithTeams, $gcolumns, $genreNumbers);
}

echo "<h2>Grade</h2>";
makeTable("Grade", $contestsWithTeams, $gcolumns, $gradeNumbers);
flush();
?>

<h2>Time</h2>
<?php
if(!isset($_GET["showDates"]) || $_GET["showDates"] != "1") {
    echo "<p><a href='$url&showDates=1'>Show teams by time created (slower)</a></p>";
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
        echo "<a href='$url&showDates=1&interval=".$newInterval."'>".$label."</a> / ";
    }
}
?>
<a href="<?=$url ?>">Hide dates</a></p>
<?php
$dateNumbers = [];
$limit = 100 * count($contestsWithTeams);
$stmt = $db2->prepare("
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

$lastDate = null;
while($row = $stmt->fetchObject()) {
    $curDate = $row->intervalCreated;
    $label = date("Y-m-d H:i", $curDate * $interval) . " &mdash; " . date($interval >= 3600 * 24 ? "Y-m-d H:i" : "H:i", ($curDate + 1) * $interval);
    if($lastDate !== null && $curDate < $lastDate - 1) {
        // Add extra row
        $dateNumbers["_".$curDate] = [];
    }
    if(!isset($dateNumbers[$label])) {
        $dateNumbers[$label] = [];
    }
    $dateNumbers[$label][$row->contestID] = (array) $row;
    $lastDate = $curDate;
    $limit -= 1;
}

makeTable("Date created", $contestsWithTeams, $contestColumns, $dateNumbers);
?>
    </tbody>
</table>
<?php
if($limit <= 0) {
    echo "<p><i>Note : row limit reached, use a smaller interval to go back further in time.</i></p>";
}
?>
</body>