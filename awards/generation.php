<?php

require_once("common.php");

// PDF génération
class Generation
{
   static $csvFile = null;
   public static function init()
   {
      self::$csvFile = fopen("schools.csv", "w");
      fputcsv(self::$csvFile, array("name", "address", "zipcode", "city", "country", 'firstName', 'lastName', 'nbAwards'), ";");
   }
   public static function write()
   {
      fclose(self::$csvFile);
   }

   public static function makeAll($aSchools)
   {
      global $levelStr;

      self::init();

      $i=0;
      tpl("templateHeader.html", array(), true);
      foreach ($aSchools as $School)
      {
         foreach ($School->aGroups as $idGroup => $Group)
         {
            self::addCsvRow($School, count($Group));
            //echo "COUNTRY: ".$School->country." ".$School->city."\n";
            $aData = array();
            // School data
            foreach (array('name', 'address', 'zipcode', 'city', 'country', 'firstName', 'lastName') as $field)
               $aData["school".ucfirst($field)] = $School->$field;
            $bestUser = $School->users[0];
            foreach ($School->users as $user) {
               if ($user->ownGroupsContestants > $bestUser->ownGroupsContestants) {
                  $bestUser = $user;
               }
            }
            $aData["userFirstName"] = $bestUser->firstName;
            $aData["userLastName"] = $bestUser->lastName;
            $aData["users"] = $School->users;
            
            // Users data

            // Group data
            $aData['nbTotal'] = $School->nbTot;
            $aData['nbInGroup'] = count($Group);
            $aData['nbGroups'] = $School->nbGroups;

            // Contestants data
            $aData['contestants'] = array();
            foreach ($Group as $Cont)
            {
                $aData['contestants'][] = (object)array(
                  'lastName' => $Cont->lastName,
                  'firstName' => $Cont->firstName,
                  'rank' => $Cont->rank,
                  'score' => $Cont->score,
                  'category' => $levelStr[$Cont->level],
                );
            }
            tpl("templateLetter.html", $aData , true);
         }   
      
         $i++;
      }
      tpl("templateFooter.html", array(), true);
      self::write();
   }

   public static function addCsvRow($School, $nbAwards)
   {
      $aData = array();
      foreach (array('name', 'address', 'zipcode', 'city', 'country', 'firstName', 'lastName') as $field)
         $aData[] = $School->$field;
      $aData[] = $nbAwards;
      fputcsv(self::$csvFile, $aData, ";");
   }
}

//updateNbAwarded(2014);

$aSchools = getAllData();
Generation::makeAll($aSchools);

/*

UPDATE `school` LEFT JOIN school_user ON (school.ID = school_user.schoolID AND school.userID = school_user.userID) SET school.userID = NULL WHERE school_user.ID IS NULL

SELECT school.* FROM school JOIN school_user ON school.ID = school_user.schoolID WHERE school.userID = 0 ORDER BY school.ID

// Mise à jour du champ school_user.ownGroupsContestants

UPDATE `school_user` JOIN SELECT SUM(`nbStudentsEffective`) as `nbContestants`, `schoolID`, `userID` FROM `group` WHERE `participationType` = 'Official' GROUP BY `schoolID`, `userID`) as `groups` ON (school_user.schoolID = `groupS`.`schoolID` AND school_user.userID = `groupS`.`userID`) SET school_user.ownGroupsContestants = `nbContestants`;


UPDATE school JOIN (SELECT schoolID, MAX(ownGroupsContestants) as maxStudents FROM school_user GROUP BY schoolID) as bestSchoolUser ON (bestSchoolUser.schoolID = school.ID) JOIN school_user ON (school.ID = school_user.schoolID AND school_user.ownGroupsContestants = bestSchoolUser.maxStudents) SET school.userID = school_user.userID WHERE school.userID = 0

*/