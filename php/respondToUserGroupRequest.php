<?php

require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/UserGroup.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$email = custgetparam("email");
$salt = custgetparam("salt");
$groupID = custgetparam("groupID");
$response = custgetparam("response");


$conn = (new Keychain)->getDatabaseConnection();

$user = User::findBySignInKey($email, $salt);

if(is_object($user) && get_class($user) == "User"){
  $groups = UserGroup::requestsForUser($user);
  $found = false;
  foreach($groups as $group) {
    if ($group->getID() == $groupID) {
      $found = true;
      if($response == "approve"){
        if($group->consentFromUser($user)){
          die("true|" . $group->getName());
        }
        die("false|Could not approve request.");
      }
      else if($response == "deny"){
        if($group->declineFromUser($user)){
          die("true|");
        }
        die("false|Could not deny request.");
      }
      die("false|Invalid response.");
    }
  }
  if (!$found) {
    die("false|Could not locate group request in order to respond to it.");
  }
  
}
die("false|Your log in dissolved. Maybe you logged in on another device.");

?>