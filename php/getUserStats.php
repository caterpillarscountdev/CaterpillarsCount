<?php

require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$siteID = intval(custgetparam("siteID"));
$userID = intval(custgetparam("userID"));

$email = custgetparam("email");
$salt = custgetparam("salt");

$conn = (new Keychain)->getDatabaseConnection();


$user = User::findBySignInKey($email, $salt);
if(is_object($user) && get_class($user) == "User"){

  $users = array($user);

  $sites = $user->getSites();
  for($i = 0; $i < count($sites); $i++){
    $sites[$i] = $sites[$i]->getID();
  }
  $site = Site::findByID($siteID);
  if(is_object($site) && get_class($site) == "Site" && in_array($site->getID(), $sites)){
    // get site Users
    $users = array();
    $query = mysqli_query($conn, "SELECT DISTINCT Survey.UserFKOfObserver AS UserFK FROM Survey JOIN Plant ON Survey.PlantFK = Plant.ID WHERE Plant.SiteFK =" . $site->getID());

    while($row = mysqli_fetch_assoc($query)){
      $u = User::findByID($row["UserFK"]);
      if (is_object($u) && get_class($u) == "User") {
        // if Site and User params, just show that user
        if (!$userID || $u->getID() == $userID) {
          $users[] = $u;
        }
      }
    }

  };

  $getSightings = count($users) == 1;

  $results = array(
    "Users" => array()
  );

  $userIDs = array();

  uasort($users, function($a, $b) { return strcmp($a->getLastName(), $b->getLastName());});
  
  foreach($users as $u) {
    $userIDs[] = $u->getID();
    $results["Users"][strval($u->getID())] = array(
      "ID" => $u->getID(),
      "Name" => $u->getFullName(),
      "iNatObserverID" => $u->getINaturalistObserverID(),
      "QuizCount" => 0,
      "QuizMean" => 0,
      "QuizHigh" => 0,
      "GameCount" => 0,
      "GameScoreMean" => 0,
      "GameScoreHigh" => 0,
      "GameFoundMean" => 0,
      "GameFoundHigh" => 0,
      "GameIdentificationMean" => 0,
      "GameIdentificationHigh" => 0,
      "GameLengthMean" => 0,
      "GameLengthHigh" => 0,
      "SurveysWeek" => 0,
      "SurveysMonth" => 0,
      "SurveysYear" => 0,
      "SurveysTotal" => 0,
      "SurveysCaterpillars" => 0,
      "SurveysAccuracy" => 0,
      "ArthropodGroups" => array(),
      );
  }

  // Quiz

  $query = mysqli_query($conn, "SELECT UserFK, Count(ID) AS QuizCount, MAX(Score) AS QuizHigh, AVG(Score) AS QuizMean FROM QuizScore WHERE UserFK IN (" . implode(",", $userIDs) . ") GROUP BY UserFK");

  while($row = mysqli_fetch_assoc($query)){
    $results["Users"][strval($row["UserFK"])] = array_merge($results["Users"][strval($row["UserFK"])], $row);
  }
  
  // Virtual Survey

  $query = mysqli_query($conn, "SELECT UserFK, Count(ID) AS GameCount, MAX(Score) AS GameScoreHigh, AVG(Score) AS GameScoreMean, MAX(PercentFound) AS GameFoundHigh, AVG(PercentFound) AS GameFoundMean, MAX(IdentificationAccuracy) AS GameIdentificationHigh, AVG(IdentificationAccuracy) AS GameIdentificationMean, MAX(LengthAccuracy) AS GameLengthHigh, AVG(LengthAccuracy) AS GameLengthMean FROM VirtualSurveyScore WHERE UserFK IN (" . implode(",", $userIDs) . ") GROUP BY UserFK");

  while($row = mysqli_fetch_assoc($query)){
    $results["Users"][strval($row["UserFK"])] = array_merge($results["Users"][strval($row["UserFK"])], $row);
  }

  // Surveys

  $query = mysqli_query($conn, "SELECT User.ID as UserFK, SUM(CASE WHEN Survey.LocalDate >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) THEN 1 ELSE 0 END) AS SurveysWeek, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(DATE_FORMAT(CURDATE(),'%Y-%m'), '-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS SurveysMonth, SUM(CASE WHEN Survey.LocalDate >= STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '%Y-%m-%d %T') THEN 1 ELSE 0 END) AS SurveysYear, Count(*) AS SurveysTotal, COUNT(DISTINCT Survey.LocalDate) AS SurveysTotalUniqueDates FROM `Survey` JOIN User ON Survey.UserFKOfObserver=User.ID JOIN Plant ON Survey.PlantFK=Plant.ID WHERE User.ID IN (" . implode(",", $userIDs) . ") GROUP BY User.ID");

  while($row = mysqli_fetch_assoc($query)){
    $results["Users"][strval($row["UserFK"])] = array_merge($results["Users"][strval($row["UserFK"])], $row);
  }

  // Survey Caterpillars
  $query = mysqli_query($conn, "SELECT Survey.UserFKOfObserver AS UserFK, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS SurveysCaterpillars FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID AND ArthropodSighting.UpdatedGroup='caterpillar' AND Survey.UserFKOfObserver IN (" . implode(",", $userIDs) . ") GROUP BY Survey.UserFKOfObserver");

  while($row = mysqli_fetch_assoc($query)){
    $results["Users"][strval($row["UserFK"])]["SurveysCaterpillars"] = intval($results["Users"][strval($row["UserFK"])]["SurveysTotal"]) > 0 ? (round($row["SurveysCaterpillars"] / intval($results["Users"][strval($row["UserFK"])]["SurveysTotal"]) * 100, 2)) : 0;
  }

  if ($getSightings) {
    // Sightings

    $query = mysqli_query($conn, "SELECT Survey.UserFKOfObserver AS UserFK, UpdatedGroup, COUNT(DISTINCT ArthropodSighting.SurveyFK) AS Surveys, COUNT(IF(PhotoURL != \"\",1, NULL)) As WithPhotos, COUNT(ExpertIdentification.ID) AS WithIDs, COUNT(CASE WHEN ExpertIdentification.StandardGroup = ArthropodSighting.OriginalGroup THEN 1 ELSE NULL END) AS WithMatches FROM ArthropodSighting JOIN Survey ON ArthropodSighting.SurveyFK=Survey.ID LEFT JOIN ExpertIdentification ON ArthropodSighting.ID = ExpertIdentification.ArthropodSightingFK WHERE Survey.UserFKOfObserver IN (" . implode(",", $userIDs) . ") GROUP BY Survey.UserFKOfObserver, ArthropodSighting.UpdatedGroup;");

    while($row = mysqli_fetch_assoc($query)){
      $results["Users"][strval($row["UserFK"])]["ArthropodGroups"][$row["UpdatedGroup"]] = $row;
      $results["Users"][strval($row["UserFK"])]["ArthropodGroups"][$row["UpdatedGroup"]]["Accuracy"] = $row["WithIDs"] ? intval($row["WithMatches"] / $row["WithIDs"] * 100) . "%" : "N/A";
    }
  }
  

  // Format for response
  
  $results["Users"] = array_values($results["Users"]);

  die("true|" . json_encode($results));

}

die("false|Your log in dissolved. Maybe you logged in on another device.");
        
?>