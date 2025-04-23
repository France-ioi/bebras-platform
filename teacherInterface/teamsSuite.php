<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

// Phase of the contest
// 0 : qualification, time-limited contest not open
// 1 : qualifying is possible, display passwords
// 2 : participation in the time-limited contest is possible, display as such
// 3 : display rankings from time-limited contest
$phase = 3;

// Text telling when the time-limited contest opens (displayed when $phase == 1)
$timeLimitedStart = "à partir du lundi 24 mars 2025";

// Qualification chapter ID (the one with the team)
$idTeamItem = "139860767650179314";
// Qualification tasks IDs
$idItems = [
"540235374245795712",
"737597811560821225",
"1697640084875933009",
"576733918320192006"
];
// Names of the tasks (qualification)
$itemNames = [
"Image mélangée 1",
"Image mélangée 2",
"Substitutions par colonnes",
"Réaction chimique"
];
// Score required to qualify 
$reqScores = [
   'fr' => 150,
   'ch' => 100
];

// Names of the tasks (time-limited contest)
$contestNames = $itemNames; // same as qualification


if (!isset($_SESSION["userID"])) {
   echo translate("session_expired");
   exit;
}

function formatContestant($code) {
   global $contestants, $userIds;
   $contestant = $contestants[$code];
   $str = $contestant['firstName'] . ' ' . $contestant['lastName'] . ' [' . $contestant['code'] . ']';
   if(!isset($userIds[$code])) {
      $str = '<i>' . $str . '</i>*';
   }
   return $str;
}

function formatScore($score) {
   return $score === null ? '-' : $score;
}

// Get all codes
$query = "SELECT ID, firstName, lastName, code FROM algorea_registration WHERE userID = :userID";
$stmt = $db->prepare($query);
$stmt->execute(['userID' => $_SESSION['userID']]);
$contestants = array();
$contestantCodes = array();
$unteamed = array();
while ($row = $stmt->fetch()) {
   $contestants[$row['code']] = $row;
   $contestantCodes[] = "'".$row['code']."'";
   $unteamed[] = $row['code'];
}
$strCodes = implode(",", $contestantCodes);

$db2 = new PDO($dbConnexionString2, $dbUser2, $dbPasswd2);


// Get all users associated with codes
$userIds = [];
$codes = [];
$stmt = $db2->prepare("
SELECT users.ID, badges.code
FROM `login-module`.`badges` AS badges
JOIN pixal.users AS users ON users.loginId = badges.user_id
WHERE badges.code IN (".$strCodes.")
");
$stmt->execute();
while($row = $stmt->fetch()) {
   $userIds[$row['code']] = $row['ID'];
   $codes[$row['ID']] = $row['code'];
}
$strUserIds = implode(",", $userIds);

// Get all teams associated with those users
$teams = [];
$query = "
SELECT users.ID AS userId, `groups`.ID AS groupId, `groups`.sName, `groups`.iTeamParticipating,
alkindi_teams.sPassword, alkindi_teams.idNewGroup, alkindi_teams.country,
alkindi_teams.rank, alkindi_teams.rankBigRegion, alkindi_teams.rankRegion,
alkindi_teams.thirdScore, alkindi_teams.thirdTime,";
foreach($contestNames as $idx => $name) {
   $query .= "alkindi_teams.score" . ($idx + 1) . ", alkindi_teams.time" . ($idx + 1) . ", ";
}
$query .= "alkindi_teams.rank, alkindi_teams.rankBigRegion, alkindi_teams.rankRegion, alkindi_teams.qualifiedFinal, alkindi_teams.qualifiedFinalMaybe
FROM pixal.`groups`
JOIN pixal.groups_groups ON groups_groups.idGroupParent = `groups`.ID
JOIN pixal.users ON groups_groups.idGroupChild = users.idGroupSelf
LEFT JOIN pixal.alkindi_teams ON alkindi_teams.idGroup = `groups`.ID
WHERE users.ID IN (".$strUserIds.")
AND `groups`.idTeamItem = :idTeamItem";
$stmt = $db2->prepare($query);
$stmt->execute(['idTeamItem' => $idTeamItem]);
while($row = $stmt->fetch()) {
   if(!isset($teams[$row['groupId']])) {
      $teams[$row['groupId']] = [
         'name' => $row['sName'],
         'real' => true,
         'participating' => $row['iTeamParticipating'] == '1',
         'password' => $row['sPassword'],
         'idNewGroup' => $row['idNewGroup'],
         'country' => $row['country'],
         'thirdScore' => $row['thirdScore'],
         'thirdTime' => $row['thirdTime'],
         'rank' => $row['rank'],
         'rankBigRegion' => $row['rankBigRegion'],
         'rankRegion' => $row['rankRegion'],
         'qualifiedFinal' => $row['qualifiedFinal'],
         'qualifiedFinalMaybe' => $row['qualifiedFinalMaybe'],
         'scores' => [],
         'times' => [],
         'members' => []
         ];
   }
   for($i = 0; $i < count($contestNames); $i++) {
      $teams[$row['groupId']]['scores'][$i] = $row["score".($i+1)];
      $teams[$row['groupId']]['times'][$i] = $row["time".($i+1)];
   }
   $code = $codes[$row['userId']];
   $teams[$row['groupId']]['members'][] = $code;
   array_splice($unteamed, array_search($code, $unteamed), 1);
}


// Get teams requests for contestants with no user on AlgoreaPlatform
$stmt = $db2->prepare("
SELECT *
FROM pixal.teams_requests
WHERE code IN (".$strCodes.")");
$stmt->execute();
while($row = $stmt->fetch()) {
   if(!isset($teams[$row['team_id']])) {
      $teams[$row['team_id']] = ['name' => $row['name'], 'real' => false, 'participating' => false, 'password' => null, 'idNewGroup' => null, 'country' => null, 'thirdScore' => null, 'members' => []];
   }
   $teams[$row['team_id']]['members'][] = $row['code'];
   array_splice($unteamed, array_search($row['code'], $unteamed), 1);
}


// Process request if any
function queryPlatform($request) {
   global $config;

   $request['password'] = $config->teamsPassword;

   $fields = http_build_query($request);
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $config->teamsUrl);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   $res = curl_exec($ch);
   try {
      $res = json_decode($res, true);
   } catch(Exception $e) {}
   return $res;
}

function processRequest($post) {
   global $contestants, $db2, $idTeamItem, $teams, $userIds;

   // Check teacher has rights over that contestant
   if(!isset($contestants[$post['code']])) { return; }

   $code = $post['code'];
   $userId = isset($userIds[$code]) ? $userIds[$code] : false;

   if($post['action'] == 'create') {
      $name = $post['name'] ? $post['name'] : '.';
      if($userId) {
         queryPlatform([
            'action' => 'createTeam',
            'idItem' => $idTeamItem,
            'user_id' => $userId,
            'name' => $name
            ]);
      } else {
         $newTeamId = (string) mt_rand(100000, 999999999);
         $stmt = $db2->prepare("INSERT INTO pixal.teams_requests (code, team_id, name) VALUES(:code, :team_id, :name);");
         $stmt->execute(['code' => $code, 'team_id' => $newTeamId, 'name' => $name]);
      }
   } elseif($post['action'] == 'add') {
      // Check teacher has rights over that team
      $groupId = $post['groupId'];
      if(!isset($teams[$groupId])) { return; }

      if($userId) {
         if($teams[$groupId]['real']) {
            queryPlatform([
               'action' => 'joinTeam',
               'idItem' => $idTeamItem,
               'user_id' => $userId,
               'team_id' => $groupId
               ]);
         } else {
            // Create the actual team
            $res = queryPlatform([
               'action' => 'createTeam',
               'idItem' => $idTeamItem,
               'user_id' => $userId,
               'name' => $teams[$groupId]['name']
               ]);
            if($res['result']) {
               $stmt = $db2->prepare("UPDATE pixal.teams_requests SET team_id = :new_id WHERE team_id = :old_id;");
               $stmt->execute(['old_id' => $groupId, 'new_id' => $res['team']['ID']]);
            }
         }
      } else {
         $stmt = $db2->prepare("INSERT INTO pixal.teams_requests (code, team_id) VALUES(:code, :team_id);");
         $stmt->execute(['code' => $code, 'team_id' => $post['groupId']]);
      }
   } elseif($post['action'] == 'remove') {
      if($userId) {
         queryPlatform([
            'action' => 'leaveTeam',
            'idItem' => $idTeamItem,
            'user_id' => $userId
            ]);
      } else {
         $stmt = $db2->prepare("DELETE FROM pixal.teams_requests WHERE code = :code;");
         $stmt->execute(['code' => $code]);
      }
   }

   // Refresh the page
   Header("Location: teamsSuite.php");
   die();
}

if(isset($_POST['action'])) {
   processRequest($_POST);
}


// Get score information
$scores = [];
$scoreTotals = [];

$stmt = $db2->prepare("
SELECT idGroup, idItem, MAX(iScore) AS maxScore
FROM pixal.groups_attempts
WHERE idItem IN (" . implode(',', $idItems) . ")
AND idGroup IN (" . implode(',', array_keys($teams)) . ")
GROUP BY idGroup, idItem;");
$stmt->execute();
while($row = $stmt->fetch()) {
   if(!isset($scores[$row['idGroup']])) {
      $scores[$row['idGroup']] = array_fill(0, count($idItems), null);
      $scoreTotals[$row['idGroup']] = 0;
   }
   $scores[$row['idGroup']][array_search($row['idItem'], $idItems)] = $row['maxScore'];
   $scoreTotals[$row['idGroup']] += $row['maxScore'];
}

?>
<html>
<head>
<meta charset='utf-8'>
</head>
<style>
form {
   display: inline-block;
   margin: 0px;
}
.resultats tr:first-child td {
   font-weight: bold;
   background-color: lightgray;
}

.resultats tr td {
   border: solid black 1px;
   padding: 5px;
}
</style>
<body>
<h1>Équipes pour le 3e tour du Concours Alkindi</h1>

<h2>Notice</h2>

<p>
   Vos élèves peuvent créer leur propre équipe en se connectant sur la plateforme du 3e tour avec leur code de participant, puis en créant ou rejoignant une équipe. les explications sont fournies dans l'introduction de votre interface coordinateur.
</p>
<p>
   Vous pouvez aussi choisir, sur cette page, de préparer vous-mêmes les équipes. Les élèves rejoindront alors automatiquement l'équipe ainsi créée, sauf si celle-ci est déjà pleine.
</p>
<p>
   Pour cela, créez une équipe en choisissant son nom et un premier membre, puis ajoutez des membres à l'équipe, parmi ceux listés sur cette page.
</p>
<p>
   Si des élèves souhaitent participer au 3e tour sans avoir participé aux précédents, vous pouvez créer des <a href="extraQualificationCode.php">codes de participants</a> supplémentaires.
</p>
<p>
   <b>Participation à l'épreuve</b> : Si une équipe se qualifie pour l'épreuve, vous trouverez dans le tableau ci-dessous un mot de passe. Cette épreuve doit se faire sous votre supervision, et dure 1h30. Lorsque vos élèves sont prêts à participer à l'épreuve en temps limité, fournissez-leur ce mot de passe, à l'aide duquel ils pourront ouvrir l'accès en l'utilisant depuis la page d'accueil du concours.
</p>

<?php
// Display info
if(count($userIds) < count($contestants)) {
?>
<p>Attention : Une astérisque à côté du code d'un participant indique que cet élève ne s'est jamais connecté à la plateforme du 3e tour. Les participants ne font pas partie définitivement d'une équipe tant qu'ils ne se sont pas connectés.</p>
<?php
}
?>

<h2>Équipes enregistrées</h2>

<table class="resultats" cellspacing=0>
<tr>
   <td rowspan="2">Nom de l'équipe</td>
   <td rowspan="2">Membres</td>
   <td colspan="<?=count($itemNames) + 1 ?>">Scores (phase de qualification)</td>
   <td rowspan="2">Mot de passe<br>pour l'épreuve</td>
   <td colspan="<?=count($contestNames) + 1 ?>">Scores (épreuve)</td>
   <td rowspan="2">Classement (épreuve)</td>
</tr>
<tr>
<?php
foreach($itemNames as $name) {
   echo "<td>$name</td>";
}
?>
   <td><b>Total</b></td>
<?php
   foreach($contestNames as $name) {
      echo "<td>$name</td>";
   }
?>
   <td><b>Total</b></td>
</tr>
<?php
foreach($teams as $groupId => $data) {
    echo "<tr>";
    echo "<td>" . $data['name'] . "</td>";
    echo "<td>";
    $membersReg = [];
    $membersPrereg = [];

    foreach($data['members'] as $idx => $code) {
        $memberStr = formatContestant($code);
        if(!$data['idNewGroup']) {
            $memberStr .= ' <form method="post" action="teamsSuite.php">';
            $memberStr .= '<input type="hidden" name="action" value="remove"><input type="hidden" name="groupId" value="' . $groupId . '"><input type="hidden" name="code" value="' . $code . '">';
            if(count($data['members']) > 1) {
                $memberStr .= '<input type="submit" value="Retirer de l\'équipe"></form>';
            } else {
                $memberStr .= '<input type="submit" value="Supprimer l\'équipe"></form>';
            }
        }
        if(isset($userIds[$code])) {
           $membersReg[] = $memberStr;
        } else {
           $membersPrereg[] = $memberStr;
        }
    }

    if(count($membersReg) > 0) {
       echo "<b>Membres de l'équipe :</b><br>";
       echo implode($membersReg, '<br>');
    }

    if(count($membersPrereg) > 0) {
       if(count($membersReg) > 0) {
          echo "<hr>";
       }
       echo "<b>Participants pré-inscrits (non membres jusqu'à leur connexion) :</b><br>";
       echo implode($membersPrereg, '<br>');
    }

    if(count($data['members']) < 4 && count($unteamed) > 0 && !$data['idNewGroup']) {
        echo '<hr><form method="post" action="teamsSuite.php">';
        echo '<input type="hidden" name="action" value="add"><input type="hidden" name="groupId" value="' . $groupId . '">';
        echo '<select name="code">';
        foreach($unteamed as $code) {
            echo '<option value="' . $code . '">' . formatContestant($code) . '</option>';
        }
        echo '</select>';
        echo ' <input type="submit" value="Ajouter à l\'équipe"></form>';
    }
    echo "</td>";

    if(isset($scores[$groupId])) {
       // Team has participated in the qualification
       for($i = 0; $i < count($idItems); $i++) {
          echo "<td>" . formatScore($scores[$groupId][$i]) . "</td>";
       }
       echo "<td><b>" . (isset($scoreTotals[$groupId]) ? $scoreTotals[$groupId] : '-') . "</b> / 400</td>";

       if($phase > 0 && $data['password']) {
          // Team is qualified
          echo "<td><pre>" . $data['password'] . "</pre></td>";

          if($data['idNewGroup']) {
             // Team has participated in the time-limited contest
             if($data['thirdScore'] !== null) {
                // Scores have been calculated
                for($i = 0; $i < count($contestNames); $i++) {
                   echo "<td>" . formatScore($data['scores'][$i]) . "</td>";
                }
                echo "<td><b>" . $data['thirdScore'] . "</b> / 400</td>";

                if($phase > 2 && $data['rank'] != 0) {
                    // Rankings have been calculated
                    if($data['qualifiedFinal'] != '1') {
                        // Qualified to the final round
                        echo "<td>";
                        if($data['qualifiedFinalMaybe'] != '1') {
                           echo "<i>Équipe non qualifiée pour la finale</i><br>";
                        } else {
                           echo "<i>Équipe non qualifiée pour la finale (sauf en cas de désistements)</i><br>";
                        }
                        echo "Rang national : " . $data['rank'] . '<br>';
                        echo "Rang académie : " . $data['rankRegion'];
                        echo "</td>";
                    } else {
                        // Not qualified to the final round
                        echo "<td><i>Résultat en attente de validation, coordinateur contacté.</i></td>";
                    }
                } else {
                    // Rankings have not been calculated, or are still hidden through $phase
                    echo "<td><i>Classements à venir</i></td>";
                }
             } else {
                // Scores have not been calculated
                echo "<td colspan=\"".(count($contestNames) + 2)."\"><i>Scores à venir</i></td>";
             }
          } elseif($phase <= 1) {
             echo "<td colspan=\"".(count($contestNames) + 2)."\"><i>La participation à l'épreuve sera possible $timeLimitedStart</i></td>";
          } else {
             // Team hasn't done the time-limiteed contest
             echo "<td colspan=\"".(count($contestNames) + 2)."\"><i>N'a pas encore utilisé le mot de passe pour l'épreuve d'1h30 sous surveillance</i></td>";
          }
       } elseif($phase > 0 && isset($reqScores[$data['country']])) {
          // Team is not qualified yet
          $reqScore = $reqScores[$data['country']];
          echo "<td colspan=\"".(count($contestNames) + 3)."\"><i>N'est pas encore qualifiée pour l'épreuve (n'a pas atteint $reqScore points)</i></td>";
       } else {
          // Still phase 0
          echo "<td colspan=\"".(count($contestNames) + 3)."\"><i>Phase de qualification en cours</i></td>";
       }
    } else {
       // Team does not have any scores yet
       echo "<td colspan=\"".(count($itemNames) + count($contestNames) + 4)."\"><i>N'a pas commencé la phase de qualification</i></td>";
    }
    echo "</tr>";
}

?>
</table>
<?php
if(count($unteamed) > 0) {
?>
<h2>Créer une nouvelle équipe</h2>
<form method="post" action="teamsSuite.php">
<input type="hidden" name="action" value="create">
Nom de l'équipe : <input type="text" name="name" size="40"><br>
Premier membre : <select name="code">
<?php
foreach($unteamed as $code) {
    echo '<option value="' . $code . '">' . formatContestant($code) . '</option>';
}
?>
</select><br>
<input type="submit" value="Créer l'équipe"></form>


<?php
}
?>

<h2>Participants sans équipe</h2>
<?php
if(count($unteamed) == 0) {
?>
   <p><b>Tous les participants sont dans une équipe.</b></p>
<?php
} else {
?>
   <p>Chaque participant doit faire partie d'une équipe (même composée d'un seul membre) pour participer au 3e tour.</p>
   <ul>
<?php
   
   foreach($unteamed as $code) {
      echo "<li>" . formatContestant($code) . "</li>";
   }
   echo "</ul>";
}
?>

</body>
</html>
