<?php

require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
$siteID = intval(custgetparam("siteID"));

$email = custgetparam("email");
$salt = custgetparam("salt");

$dbconn = (new Keychain)->getDatabaseConnection();


$user = User::findBySignInKey($email, $salt);
if(is_object($user) && get_class($user) == "User"){

  $results = array(
    "Users" => array()
  );

  $results["Users"][] = array(
    "ID" => 1,
    "Name" => "Luke Opperman",
    "QuizCount" => 0,
    "QuizMean" => 0,
    "QuizHigh" => 0,
    "GameCount" => 5,
    "GameMean" => 10,
    "GameHigh" => 20,
    "SurveysWeek" => 0,
    "SurveysMonth" => 0,
    "SurveysYear" => 0,
    "SurveysTotal" => 0,
    "SurveysCaterpillars" => 0,
    "ArthropodGroups" => array(),
  );
  

  die("true|" . json_encode($results));

}

die("false|Your log in dissolved. Maybe you logged in on another device.");
        
?>