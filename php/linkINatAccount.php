<?php
require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$email = custgetparam("email");
$salt = custgetparam("salt");
$code = custgetparam("code");

$user = User::findBySignInKey($email, $salt);
if(is_object($user) && get_class($user) == "User"){
  $fields = array(
    "grant_type" => "authorization_code",
    "code" => $code,
    "redirect_uri" => (new Keychain)->getRoot() . "/settings"
    );

  $response = curlINatOAuth($fields);
  error_log(print_r($response, true));
  if ($response["error"])
  $details = $user->getINaturalistUserDetails($response["access_token"]);
  $user->setINaturalistLinked($details["login"], $response["access_token"], $response["refresh_token"]);
  
  $settings = array(
    "iNaturalistAccountName" => $user->getINaturalistAccountName(),
    );
  die("true|" . json_encode($settings));
}
die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
