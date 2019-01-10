<?php

require_once '../shared/common.php';

if (!isset($_GET["contestID"])) {
   echo "contestID param missing";
   exit;
}

$contestID = $_GET["contestID"];

$stmt = $db->prepare('select school.name, school.country, school.region, school.ID, school.city, school.zipcode, count(contestant.ID) as contestantCount, contestant.grade from contestant
join team on team.ID = contestant.teamID
join `group` on `group`.ID = team.groupID
join school on school.ID = group.schoolID
where
team.participationType = \'Official\'
and `group`.contestID = :contestID
group by school.ID, contestant.grade');
$stmt->execute(['contestID' => $contestID]);

$bigRes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gradeCat = [
	4  => 'primaire',
	5  => 'primaire',
	6  => 'college',
	7  => 'college',
	8  => 'college',
	9  => 'college',
	10 => 'lycee',
	11 => 'lycee',
	12 => 'lycee',
	13 => 'lyceepro',
	14 => 'lyceepro',
	15 => 'lyceepro',
	16  => 'college',
	17  => 'college',
	18  => 'college',
	19  => 'college'
];

$regionNames = [
    "foreign"=> "Hors France",
    "aix-marseille"=> "Aix-Marseille",
    "amiens"=> "Amiens",
    "besancon"=> "Besançon",
    "bordeaux"=> "Bordeaux",
    "caen"=> "Caen",
    "clermont"=> "Clermont-Ferrand",
    "corse"=> "Corse",
    "creteil"=> "Créteil",
    "dijon"=> "Dijon",
    "grenoble"=> "Grenoble",
    "guadeloupe"=> "Guadeloupe",
    "guyane"=> "Guyane",
    "lille"=> "Lille",
    "limoges"=> "Limoges",
    "lyon"=> "Lyon",
    "martinique"=> "Martinique",
    "mayotte"=> "Mayotte",
    "montpellier"=> "Montpellier",
    "nancy-metz"=> "Nancy-Metz",
    "nantes"=> "Nantes",
    "nice"=> "Nice",
    "noumea"=> "Nouméa",
    "orleans-tours"=> "Orléans-Tours",
    "paris"=> "Paris",
    "poitiers"=> "Poitiers",
    "rouen"=> "Rouen",
    "reims"=> "Reims",
    "rennes"=> "Rennes",
    "reunion"=> "La Réunion",
    "strasbourg"=> "Strasbourg",
    "toulouse"=> "Toulouse",
    "versailles"=> "Versailles",
    "polynesie"=> "Polynésie française",
    "spm" => "Saint-Pierre et Miquelon",
    "wf" => "Wallis et Futuna"    
];

$finalRes = ['all' => []];
$schoolIdToArrayIndex = [];
$regionToArrayIndex = [];

foreach($bigRes as $row) {
	if (!isset($finalRes[$row['region']])) {
		$finalRes[$row['region']] = [];
		$regionToArrayIndex[$row['region']] = count($finalRes['all']);
		$finalRes['all'][$regionToArrayIndex[$row['region']]] = [
			'name' => $regionNames[$row['region']],
			'country' => '',
			'city' => '',
			'zipcode' => '',
			'contestantData' => ['total' => 0],
		];
	}
	$regionIndexInAll = $regionToArrayIndex[$row['region']];
	$regionData = &$finalRes[$row['region']];
	if (!isset($schoolIdToArrayIndex[$row['ID']])) {
		$index = count($regionData);
		$schoolIdToArrayIndex[$row['ID']] = $index;
		$regionData[$index] = [
			'name' => $row['name'],
			'country' => $row['country'],
			'city' => $row['city'],
			'zipcode' => $row['zipcode'],
			'contestantData' => ['total' => 0],
		];
	} else {
		$index = $schoolIdToArrayIndex[$row['ID']];
	}
	$regionData[$index]['contestantData'][intval($row['grade'])] = intval($row['contestantCount']);
	$regionData[$index]['contestantData']['total'] += intval($row['contestantCount']);
   $grade = intval($row['grade']);
   if (!isset($gradeCat[$grade])) {
      echo "Grade ".$grade." is not valid for report.<br/>";
      continue;
   }
	if (!isset($regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]])) {
		$regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]] = 0;
	}
	$regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]] += intval($row['contestantCount']);
	$finalRes['all'][$regionIndexInAll]['contestantData']['total'] += intval($row['contestantCount']);
	if (!isset($finalRes['all'][$regionIndexInAll]['contestantData'][$gradeCat[intval($row['grade'])]])) {
		$finalRes['all'][$regionIndexInAll]['contestantData'][$gradeCat[intval($row['grade'])]] = 0;
	}
	if (!isset($finalRes['all'][$regionIndexInAll]['contestantData'][intval($row['grade'])])) {
		$finalRes['all'][$regionIndexInAll]['contestantData'][intval($row['grade'])] = 0;
	}
	$finalRes['all'][$regionIndexInAll]['contestantData'][intval($row['grade'])] += intval($row['contestantCount']);
	$finalRes['all'][$regionIndexInAll]['contestantData'][$gradeCat[intval($row['grade'])]] += intval($row['contestantCount']);
}

echo 'var contestData = '.json_encode($finalRes, JSON_UNESCAPED_UNICODE).';';
