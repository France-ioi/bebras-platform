
# ajouter de la ligne de cache montrant le contest id associé

ALTER TABLE  `team` ADD  `cached_officialForContestID` INT( 11 ) NOT NULL AFTER  `cached_contestants` ,
ADD INDEX ( `cached_officialForContestID` );

UPDATE `team`, `group` 
SET `team`.cached_officialForContestID = `group`.contestID
WHERE `team`.groupID = `group`.id
AND `team`.startTime IS NOT NULL
AND `team`.endTime < '2012-11-18 00:00:00'
AND `group`.isUnofficial = '2' ;

# (105833 row(s)affected)


#----
# pourquoi la requete suivante donne-t-elle des réultats avec start time null ?
#SELECT * FROM `team`, `group` 
#WHERE `team`.groupID = `group`.id
#AND `team`.startTime IS NULL
#AND `team`.endTime < '2012-11-18 00:00:00'
#AND `group`.isUnofficial = '2' ;
#----



# TODO: add team rank
#
#UPDATE `contestant` as `c1`, (SELECT `contestant2`.ID,
#@curRank := IF(@prevVal=`contestant2`.score, @curRank, @studentNumber) AS rank,
#@studentNumber := @studentNumber + 1 as studentNumber,
#@prevVal:=score
#FROM (select `contestant`.ID, `contestant`.`firstName`,
#`contestant`.`lastName`, `team`.`score`
#FROM `contestant` left join team on (contestant.teamID = team.ID) left
#join `group` ON (`team`.`groupID` = `group`.`ID`)
#WHERE `team`.`isUnofficial` = 2 AND `group`.`contestID` = 9 ORDER BY
#`team`.`score` DESC) `contestant2`, (
#SELECT @curRank :=0, @prevVal:=null, @studentNumber:=1) r
#ORDER BY score DESC) as `c2` SET `c1`.rank = `c2`.rank WHERE `c1`.ID = `c2`.ID
