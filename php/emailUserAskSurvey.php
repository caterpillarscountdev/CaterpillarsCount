<?php
require_once('orm/User.php');
require_once('orm/Survey.php');
require_once('orm/resources/mailing.php');
require_once('orm/resources/Customlogging.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8
$email = custgetparam("email");
$salt = custgetparam("salt");
$surveyID = custgetparam("survey");
$emailmessage = custgetparam("emailmessage");

$user = User::findBySignInKey($email, $salt);

if(is_object($user) && get_class($user) == "User"){
  try {
    $survey = Survey::findByID($surveyID);
    if(is_object($survey) && get_class($survey) == "Survey") {
      $site = $survey->getPlant()->getSite();
      if(User::isSuperUser($user) || $site->isAuthority($user)){
        $emailto = $survey->getObserver()->getEmail();
        $ccs = $site->getAuthorityEmails();
        if (emailAsAndCCUs($user->getEmail(), $emailto, $ccs, "Data issue with your Survey",$emailmessage)) {
          die("true|");	 
        } else {
          die("false|Email failed to send.");		
        }
      }
    }
  } catch (Throwable $exception) {
    $subj = "Email User Error for " . $email;
    $body = $exception->getMessage();
    email("caterpillarscountdev@gmail.com", $subj, $body);
  }
  die("false|You do not have authority to approve this survey.");
}
die("false|Your log in dissolved. Maybe you logged in on another device.");

?>
