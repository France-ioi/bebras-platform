
http://localhost/beaver_platform/stats/stats.php?prepare=1
http://localhost/beaver_platform/stats/stats.php?type=resolution_stats&contestIDs=32-37







================================
OLD














execute on table:

   ALTER TABLE  `team` ADD  `cached_officialForContestID` INT( 11 ) NOT NULL AFTER  `cached_contestants` ,
   ADD INDEX ( `cached_officialForContestID` );

   ALTER TABLE  `team_question` ADD INDEX (  `score` ) ;

   UPDATE `team`, `group` 
   SET `team`.cached_officialForContestID = `group`.contestID
   WHERE `team`.groupID = `group`.id
   AND `team`.endTime IS NOT NULL
   AND `group`.participationType = 'Official';


http://localhost/beaver_platform/stats/stats.php?contests=32-37

if changes to stats.php, run:
http://localhost/beaver_platform/stats/stats.php?contests=32-37&reset=1







* Install a webserver (apache, mysql, php)
  so as to be able to load the plateform

* Edit php.ini so as to allow the script to take more time an memory, updateing to:
      max_execution_time = 1000     
      memory_limit = 1280M

* Make sure you configure connect.php properly at the root of the plateform

* Obtain a dump containing the appropriate tables (structure and data):
  contest, contest_question, group, question, team, team_question

* Load this dump:
    - clear the beaver table
    - run (with appropriate user/password, and sql filename)
         mysql -u root --password="" -e "source dump.sql" beaver

  Content of batch file to execute on windows (adjust path and username/password):
      echo drop database beaver; > command.txt
      echo CREATE DATABASE `beaver` >> command.txt
      PATH=%PATH%;C:\programs\wamp\bin\mysql\mysql5.6.12\bin
      mysql -u root --password="" < command.txt
      mysql -u root --password="" -e "source dump.sql" beaver
      pause

* Execute the following queries, which avoids complicated joins in the processing of team results
  (you might need to adjust the data, which corresponds to the date of end of the official contest),
  and the queries to add indexes.



todo check if conditions are enough:



---reset:

   UPDATE `team`
   SET `team`.cached_officialForContestID = 0


   UPDATE `team`, `group` 
   SET `team`.cached_officialForContestID = `group`.contestID
   WHERE `team`.groupID = `group`.id
   AND `team`.endTime < '2014-11-18 00:00:00'
   AND `group`.participationType = 'Official';


     -----    depreacted:
            UPDATE `team`, `group` 
            SET `team`.cached_officialForContestID = `group`.contestID
            WHERE `team`.groupID = `group`.id
            AND `team`.endTime < '2013-11-19 00:00:00'
            AND `group`.participationType = 'Official';
-----------------



---deprecated
   ALTER TABLE  `team_question` ADD INDEX (  `questionID` ) ;
   ALTER TABLE  `team` ADD INDEX (  `ID` ) ;
   ALTER TABLE  `team` ADD INDEX (  `participationType` ) ;
   ALTER TABLE  `team` ADD INDEX (  `cached_officialForContestID` ) ;
   ALTER TABLE  `contest_question` ADD INDEX (  `contestID` ) ;



* Open in a browser: stats/stats.php?contests=27,30,28,29
   (where the id are those of the contests of interest)
   (update=1 indicates that data from database should be cached for next loading of the page)

  To force clearing the cache and reloading all data from the database, open:
     stats/stats.php?contest=27,30,28,29&reset=1   
  You can also control whether the cached data file should not be modify using the "update" argumemt, e.g.
     stats/stats.php?contest=27,30,28,29&reset=1&update=0




     stats/stats.php?contest=32

2014:  32 à 37 inclus
