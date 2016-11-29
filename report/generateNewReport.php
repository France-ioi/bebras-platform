<?php

require_once '../shared/common.php';

$stmt = $db->prepare('select school.name, school.country, school.region, school.ID, school.city, school.zipcode, count(contestant.ID) as contestantCount, contestant.grade from contestant
join team on team.ID = contestant.teamID
join `group` on `group`.ID = team.groupID
join school on school.ID = group.schoolID
where
team.participationType = \'Official\'
and `group`.contestID = :contestID
group by school.ID, contestant.grade');
$stmt->execute(['contestID' => '455965778962240640']);

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
	15 => 'lyceepro'
];

$finalRes = [];
$schoolIdToArrayIndex = [];

foreach($bigRes as $row) {
	if (!isset($finalRes[$row['region']])) {
		$finalRes[$row['region']] = [];
	}
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
	if (!isset($regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]])) {
		$regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]] = 0;
	}
	$regionData[$index]['contestantData'][$gradeCat[intval($row['grade'])]] += intval($row['contestantCount']);
}

echo 'var contestData = '.json_encode($finalRes, JSON_UNESCAPED_UNICODE).';';
