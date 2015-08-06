<?php
/* Copyright (c) 2012 Association France-ioi, MIT License http://opensource.org/licenses/MIT */

$apiDir = dirname(__FILE__).'/../ext/pi-google-maps-api/';
require($apiDir.'GoogleMapsAPI.class.php');
require($apiDir.'/res/france/info.php');

function getCoordinatesSchool($record)
{
   if (preg_match('/.*_.*/', $record['name']))
      return array(0, 0, "Dummy data");
   $address = $record['name'].",".$record['address'].",".$record['zipcode'].",".$record['city'].",".$record['country'];
   list($lat, $lng, $msg) = getCoordinates($address);
   if (preg_match('/.*No Map Results.*/', $msg))
   {
      $address = $record['city'].",".$record['country'];
      list($lat, $lng, $msg) = getCoordinates($address);
      if ($msg == "")
      {
         $msg = "Approximated address";
         $lat += rand(-1000, 1000) / (1000 * 1000);
         $lng += rand(-1000, 1000) / (1000 * 1000);
      }         
   }
   else if ($msg == "")
   {
      $lat += rand(-1000, 1000) / (1000 * 1000 * 3);
      $lng += rand(-1000, 1000) / (1000 * 1000 * 3);
   }
   return array($lat, $lng, $msg);
}

function getCoordinates($address)
{
   $mapURL = "http://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&sensor=true";
   $json = file_get_contents($mapURL);
   //$json = file_get_contents("json.ex");
   $data = json_decode($json, true);
   if (count($data['results']) == 0)
   {
      return array(0, 0, "No Map Results : $address\n");
   }
   if (count($data['results']) > 1)
   {
      return array(0, 0, "Too Many Map Results : $address");
   }
   $coords = $data['results'][0]['geometry']['location']; 
   if (isset($coords['lat']))
   {
      $lat = $coords['lat'];
      $lng = $coords['lng'];
   }
   return array($lat, $lng, "");
}

function fullAcademieName($name)
{
   if (preg_match("/^[aeiouy]/i", $name[0]))
      $name = "d'".$name;
   else
      $name = "de ".$name;
   return "Académie ".$name;
}

function getAcademiesStats()
{
   global $db;
   $query = "
      SELECT 
         school.region, 
         COUNT(*) AS nbStudents,
         COUNT(DISTINCT(`school`.ID)) AS nbSchools
      FROM `contestant`, `team`,  `group`, `school`
      WHERE 
         `contestant`.teamID = `team`.ID AND
         `team`.groupID = `group`.ID AND
         `group`.schoolID = `school`.ID AND
         `team`.`participationType` = 'Official' AND
         `group`.contestID IN (".CERTIGEN_CONTESTS.")
      GROUP BY school.region
      ORDER BY school.region
      ";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $ACC = array();
   $TOT = (object)array("nbStudents"=>0, "nbSchools"=>0);
   while($row = $stmt->fetchObject())
   {
      $ACC[$row->region] = (object)array(
         'nbStudents' => $row->nbStudents,
         'nbSchools' => $row->nbSchools,
         );
      $TOT->nbStudents += $row->nbStudents;
      $TOT->nbSchools  += $row->nbSchools;
   }
   return array($ACC, $TOT);
}

function getSchoolCoordinates()
{
   global $db;
   $query = "
      SELECT 
         `school`.*
      FROM `team`, `group`, `school`
      WHERE 
         `team`.groupID = `group`.ID AND
         `group`.`schoolID` = `school`.`ID`  AND
         `team`.`participationType` = 'Official' AND
         `group`.`contestID` IN (".CERTIGEN_CONTESTS.")
      GROUP BY school.ID
      LIMIT 0,3000
      ";
   $stmt = $db->prepare($query);
   $stmt->execute();
   $coordtab = array();
   while($row = $stmt->fetchObject())
   {
      if ($row->coords == "0,0,0")
         continue;
      list($lat, $lng, $trash) = explode(",", $row->coords);
      $coordtab[] = array($lng, $lat,addslashes("{$row->name}, {$row->city}"));
   }
   return $coordtab;
}

function getGoogleMap()
{
   global $academies, $apiDir;
   list($ACC, $TOT) = getAcademiesStats();
   $coordtab = getSchoolCoordinates();

   $gmap = new GoogleMapsAPI('AIzaSyCVU9GK-iNFOJquZ3tz0oj9dlfztnqMI1E');
   $gmap->setDivId('Map1');
   $gmap->setDirectionDivId('route');
   //$gmap->setCenterByAddress('France');
   $gmap->setCenterByCoods(46.34692761055676, 2.373046875);
   $gmap->setDisplayDirectionFields(false);
   $gmap->setSize(680,680);
   $gmap->setZoom(6);
   $gmap->setDefaultHideMarker(false);

   $gmap->addArrayMarkerByCoords($coordtab,'cat1', 'circle-blue.png');

   // Add polygons for academies
   foreach ($academies as $code => $name ) 
   {
      if(!isset($ACC[$code]))
         continue;
      $file =  $apiDir.'res/france/academies/'.$code.'/contour-simple.php';
      if (!file_exists($file))
         continue;

      include($file);
      $tooltip = "<strong>".fullAcademieName($name).", {$ACC[$code]->nbSchools} établissements, {$ACC[$code]->nbStudents} élèves</strong>";

      $gmap->addPolygonByCoords($coords,'polygon-'.$code, TRUE, '{color:\'#FFAA88\',opacity:0.2}', '{color:\'#000000\',opacity:0.5,weight:1}', '
         GEvent.addListener(THEPOLYGON,"click",function(){
            // Onclick : centered on the academy
            var code = "polygon-'.$code.'";
            var mini = [100,100];
            var maxi = [-100,-100];
            for (var i = 0; i < polygons[code].coords.length; i++)
            {
               for (var c = 0; c < 2; c++)
               {
                  mini[c] = Math.min(mini[c], polygons[code].coords[i][c]);
                  maxi[c] = Math.max(maxi[c], polygons[code].coords[i][c]);
               }
            }
            var bounds = new GLatLngBounds(new GLatLng(mini[0],mini[1]),new GLatLng(maxi[0],maxi[1])); 
            map.setCenter(bounds.getCenter(), map.getBoundsZoomLevel(bounds));
         });
         GEvent.addListener(THEPOLYGON,"mouseover",function(){
            // Show academy name
            toolTipContent = "'.$tooltip.'";
            THEPOLYGON.setFillStyle({color:\'#FF0000\'});
            tooltip.show(toolTipContent);
         });
         GEvent.addListener(THEPOLYGON,"mouseout",function(){
            // Hide academy name
            toolTipContent = "";
            THEPOLYGON.setFillStyle({color:\'#FFAA88\'});
            tooltip.hide();
         });
      ');
   }
   $gmap->generate();
   return array($gmap->getGoogleMap(), $TOT);
}

?>