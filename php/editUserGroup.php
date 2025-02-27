<?php

require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/UserGroup.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$email = custgetparam("email");
$salt = custgetparam("salt");

$id = custgetparam("id");
$name = custgetparam("name");
$emails = custgetparam("emails");

$conn = (new Keychain)->getDatabaseConnection();

$user = User::findBySignInKey($email, $salt);

if(is_object($user) && get_class($user) == "User"){
  $group = UserGroup::findByID($id);
  if(is_object($group) && get_class($group) == "UserGroup") {
    $group->setName($name);
    $group->setEmails($emails);
    $group->requestUserConsents();
  } else {
    $group = UserGroup::create($user, $name, $emails);
    $group->requestUserConsents();
  }
  die("true|" . $group->getID());
}
die("false|Your log in dissolved. Maybe you logged in on another device.");

?>