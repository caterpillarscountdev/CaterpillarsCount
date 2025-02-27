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
  $canAdd = false;
  if (count($user->getSites()) > 0) {
    $canAdd = true;
    $groups = UserGroup::findByManager($user);
    for($i = 0; $i < count($groups); $i++){
      $groupsArray[$i] = array(
        "id" => $groups[$i]->getID(),
        "name" => $groups[$i]->getName(),
        "emails" => implode("\n", $groups[$i]->getEmails())
        );
    }
  }
  die("true|" . json_encode(array("groups" => $groupsArray, "allowed" => $canAdd)));
}
die("false|Your log in dissolved. Maybe you logged in on another device.");

?>