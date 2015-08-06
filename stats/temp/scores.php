

 ===>
SELECT team_question.score, team_question.questionID, 
  team.cached_officialForContestID, count(*) as nb 
FROM team_question 
JOIN team ON (team_question.teamID = team.ID) 
WHERE cached_officialForContestID > 27 
GROUP BY team_question.score, team_question.questionID, team.cached_officialForContestID
ORDER BY team_question.questionID, team_question.score






/* demo for a single task:

SELECT 
   DISTINCT `team_question`.score as _score,
   COUNT(*) as _nb,
   `question`.name as _name
FROM 
   `team`, `team_question`
LEFT JOIN `question`
  ON `question`.ID = '197'
WHERE `team`.cached_officialForContestID >= '30'
   AND `team`.cached_officialForContestID <= '30'
   AND `team_question`.questionID = '197'
   AND `team_question`.teamID = `team`.id
GROUP BY _score
LIMIT 0,10

*/

/* demo for a given contest, collapsing all scores, ignoring official

SELECT 
   `question`.ID as _questionID,
   COUNT(*) as _nb
FROM 
   (`contest_question`, `team_question`)
LEFT JOIN `question`
  ON `question`.ID = `contest_question`.questionID
WHERE 
      `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
   AND `team_question`.questionID = `contest_question`.questionID
GROUP BY `contest_question`.questionID
LIMIT 0,100

*/

/* same as above, with officials only

SELECT 
   `question`.ID as _questionID,
   COUNT(*) as _nb
FROM 
   (`contest_question`, `team_question`)
INNER JOIN `team`
  ON `team`.ID = `team_question`.teamID
   AND `team`.cached_officialForContestID >= '30'
   AND `team`.cached_officialForContestID <= '30'
LEFT JOIN `question`
  ON `question`.ID = `contest_question`.questionID
WHERE 
    `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
   AND `team_question`.questionID = `contest_question`.questionID
GROUP BY `contest_question`.questionID
LIMIT 0,100

*/

/* same as above, with scores

SELECT 
   DISTINCT `team_question`.score as _score,
   `question`.ID as _questionID,
   COUNT(*) as _nb
FROM 
   (`contest_question`, `team_question`)
INNER JOIN `team`
  ON `team`.ID = `team_question`.teamID
   AND `team`.cached_officialForContestID >= '30'
   AND `team`.cached_officialForContestID <= '30'
       # debug: AND `team`.startTime <= '2013-11-13 09:00:30'
LEFT JOIN `question`
  ON `question`.ID = `contest_question`.questionID
WHERE 
    `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
   AND `team_question`.questionID = `contest_question`.questionID
      # debug: AND `team_question`.questionID <= '197' AND `team_question`.questionID >= '195'
GROUP BY `contest_question`.questionID, _score
LIMIT 0,100

*/

SELECT 
   DISTINCT `team_question`.score as _score,
   `contest_question`.questionID as _questionID
FROM 
   (`contest_question`, `team_question`)
WHERE 
    `team_question`.questionID <= '197' AND `team_question`.questionID >= '195' AND
    `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
   AND `team_question`.questionID = `contest_question`.questionID
GROUP BY `contest_question`.questionID, `team_question`.score 
LIMIT 0,100


SELECT 
   DISTINCT `team_question`.score as _score,
   `contest_question`.questionID as _questionID,
   COUNT(*) as _nb
FROM `team_question`
JOIN `contest_question` 
  ON (`contest_question`.questionID = `team_question`.questionID
   AND `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30')
WHERE 
      # debug: 
  `team_question`.questionID <= '197' AND `team_question`.questionID >= '195'
GROUP BY `contest_question`.questionID, `team_question`.score
LIMIT 0,100








SELECT 
   DISTINCT `team_question`.score as _score,
   COUNT(*) as _nb,
   `question`.ID as _questionID,
   `question`.name as _name
FROM 
   `team`, `team_question`, `contest_question`
LEFT JOIN `question`
  ON `question`.ID = `contest_question`.questionID
WHERE `team`.cached_officialForContestID >= '30'
   AND `team`.cached_officialForContestID <= '30'
   AND `team_question`.teamID = `team`.id
   AND `team_question`.questionID = _questionID
   AND `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
GROUP BY _question_id,_score
LIMIT 0,100


SELECT 
   COUNT(*) as _nb
FROM 
   `contest_question`, `team_question`
LEFT JOIN `question`
  ON `question`.ID = `contest_question`.questionID
WHERE 
      `contest_question`.contestID >= '30'
   AND `contest_question`.contestID <= '30'
   AND `team_question`.questionID = `contest_question`.questionID
GROUP BY `contest_question`.questionID
LIMIT 0,100





*/



SELECT 
   COUNT(*) as _number,
   `question`.ID as _questionID, 
   `question`.name as _name,
   DISTINCT `team_question`.score as _score
FROM 
   `team`, `team_question`, `contest_question`, `question`
WHERE `team`.cached_officialForContestID >= :idMin
   AND `team`.cached_officialForContestID <= :idMax
   AND `team_question`.questionID = _questionID
   AND `team_question`.teamID = `team`.id
