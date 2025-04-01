<?php

require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/UserGroup.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$email = custgetparam("email");
$salt = custgetparam("salt");

$conn = (new Keychain)->getDatabaseConnection();

$user = User::findBySignInKey($email, $salt);

if(is_object($user) && get_class($user) == "User"){
  $groupsArray = array();
  $groups = UserGroup::requestsForUser($user);
    for($i = 0; $i < count($groups); $i++){
      $groupsArray[$i] = array(
        "id" => $groups[$i]->getID(),
        "manager" => $groups[$i]->getManager()->getFullName(),
        "name" => $groups[$i]->getName()
        );
    }
  die("true|" . json_encode($groupsArray));
}
die("false|Your log in dissolved. Maybe you logged in on another device.");

?>