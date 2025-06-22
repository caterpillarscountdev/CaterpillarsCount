<?php
require_once('orm/resources/Keychain.php');
require_once('orm/User.php');
require_once('orm/resources/Customfunctions.php'); // contains new function custgetparam() to simplify handling if param exists or not for php 8

$email = custgetparam("email");
$salt = custgetparam("salt");
$code = custgetparam("code");
$forget = custgetparam("forget");

$user = User::findBySignInKey($email, $salt);
if(is_object($user) && get_class($user) == "User"){
  if ($forget) {
    $user->setINaturalistLinked('', '');
    $user->setINaturalistJWToken('');
    die("true|Your iNaturalist account link is forgotten.");
  }
  $fields = array(
    "grant_type" => "authorization_code",
    "code" => $code,
    "redirect_uri" => (new Keychain)->getRoot() . "/settings/"
    );

  $response = curlINatOAuth($fields);
  if (array_key_exists("error", $response)) {
    $msg = $response["error"];
    if ($msg == "invalid_grant") {
      $msg = "invalid or expired request. Please try again";
    }
    die("false|Error linking iNaturalist account: " . $msg . ".");
  }
  $api_token = $user->refreshINaturalistJWToken($response["access_token"], true);
  //error_log(print_r($api_token, true));
  $details = $user->getINaturalistUserDetails();
  //error_log(print_r($details["results"][0], true));
  $user->setINaturalistLinked($details["results"][0]["login"], $response["access_token"]);
  
  $settings = array(
    "iNaturalistAccountName" => $user->getINaturalistAccountName(),
    );
  die("true|" . json_encode($settings));
}
die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
