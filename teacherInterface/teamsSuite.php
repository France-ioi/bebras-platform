<?php

require_once("../shared/common.php");
require_once("commonAdmin.php");

$idTeamItem = "260120724311792746";
$idItems = [
"532647690497199341",
"134681515031039304",
"1219260860211948277",
"36816274423673297",
"794469105323096133",
"72197693631116221",
"942219830652070444"
];

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
$stmt = $db2->prepare("
SELECT users.ID AS userId, groups.ID AS groupId, groups.sName, groups.iTeamParticipating, alkindi_teams.sPassword, alkindi_teams.idNewGroup, alkindi_teams.country,
alkindi_teams.thirdScore, alkindi_teams.thirdTime,
alkindi_teams.score1, alkindi_teams.time1,
alkindi_teams.score2, alkindi_teams.time2,
alkindi_teams.score3, alkindi_teams.time3,
alkindi_teams.score4, alkindi_teams.time4,
alkindi_teams.score5, alkindi_teams.time5,
alkindi_teams.score6, alkindi_teams.time6,
alkindi_teams.score7, alkindi_teams.time7
FROM pixal.groups
JOIN pixal.groups_groups ON groups_groups.idGroupParent = groups.ID
JOIN pixal.users ON groups_groups.idGroupChild = users.idGroupSelf
LEFT JOIN pixal.alkindi_teams ON alkindi_teams.idGroup = groups.ID
WHERE users.ID IN (".$strUserIds.")
AND groups.idTeamItem = :idTeamItem");
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
         'scores' => [],
         'times' => [],
         'members' => []
         ];
   }
   for($i = 1; $i <= 7; $i++) {
      $teams[$row['groupId']]['scores'][$i] = $row["score$i"];
      $teams[$row['groupId']]['times'][$i] = $row["time$i"];
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
      if($userId) {
         queryPlatform([
            'action' => 'createTeam',
            'idItem' => $idTeamItem,
            'user_id' => $userId,
            'name' => $post['name']
            ]);
      } else {
         $newTeamId = (string) mt_rand(100000, 999999999);
         $stmt = $db2->prepare("INSERT INTO pixal.teams_requests (code, team_id, name) VALUES(:code, :team_id, :name);");
         $stmt->execute(['code' => $code, 'team_id' => $newTeamId, 'name' => $post['name']]);
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
<p>Attention : Une astérique à côté du code d'un participant indique que cet élève ne s'est jamais connecté à la plateforme du 3e tour. Les participants ne font pas partie définitivement d'une équipe tant qu'ils ne se sont pas connectés.</p>
<?php
}
?>

<h2>Équipes enregistrées</h2>

<table class="resultats" cellspacing=0>
<tr>
   <td rowspan="2">Nom de l'équipe</td>
   <td rowspan="2">Membres</td>
   <td colspan="4">Scores (phase de qualification)</td>
   <td rowspan="2">Mot de passe<br>pour l'épreuve</td>
   <td colspan="4">Scores (épreuve)</td>
</tr>
<tr>
   <td>Mots<br>connus</td>
   <td>Image<br>brouillée</td>
   <td>Substitutions<br>composées</td>
   <td><b>Total</b></td>
   <td>Mots<br>connus</td>
   <td>Image<br>brouillée</td>
   <td>Substitutions<br>composées</td>
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
    if($data['participating']) {
       $teamScores = isset($scores[$groupId]) ? $scores[$groupId] : array_fill(0, count($idItems), null);
       echo "<td>" . formatScore($teamScores[0]) . ' | ' . formatScore($teamScores[1]) . "</td>";
       echo "<td>" . formatScore($teamScores[2]) . ' | ' . formatScore($teamScores[3]) . ' | ' . formatScore($teamScores[4]) . "</td>";
       echo "<td>" . formatScore($teamScores[5]) . ' | ' . formatScore($teamScores[6]) . "</td>";
       echo "<td><b>" . (isset($scoreTotals[$groupId]) ? $scoreTotals[$groupId] : '-') . "</b> / 700</td>";
       if($data['password']) {
          echo "<td><pre>" . $data['password'] . "</pre></td>";
          if($data['idNewGroup']) {
             if($data['thirdScore'] !== null) {
                echo "<td>" . $data['scores'][1] . ' | ' . $data['scores'][2] . "</td>";
                echo "<td>" . $data['scores'][3] . ' | ' . $data['scores'][4] . ' | ' . $data['scores'][5] . "</td>";
                echo "<td>" . $data['scores'][6] . ' | ' . $data['scores'][7] . "</td>";
                echo "<td><b>" . $data['thirdScore'] . "</b> / 700</td>";
             } else {
                echo "<td colspan=\"4\"><i>Scores à venir</i></td>";
             }
          } else {
             echo "<td colspan=\"4\"><i>N'a pas encore utilisé le mot de passe pour l'épreuve d'1h30 sous surveillance</i></td>";
          }
       } elseif($data['country'] == 'fr' || $data['country'] == 'ch') {
          $reqScore = $data['country'] == 'ch' ? 200 : 300;
          echo "<td colspan=\"5\"><i>N'est pas encore qualifiée pour l'épreuve (n'a pas atteint $reqScore points)</i></td>";
       } else {
          echo "<td colspan=\"5\"><i>Phase de qualification en cours</i></td>";
       }
    } else {
       echo "<td colspan=\"9\"><i>N'a pas commencé la phase de qualification</i></td>";
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
