<?php

// new function to replace filter_var(,FILTER_VALIDATE_BOOLEAN) which is returning empty strings in php 8
function custom_filter_var_bool($checkvar) {
		// as of php 8 filter_var is returning '' for empty values 
		if (filter_var($checkvar, FILTER_VALIDATE_BOOLEAN)===true) {
           return 1;
		} else {
           return 0;
		}			
	}



  //wrapper to allow getting items from array without checking for key existence each time 	  
  function getarrayitem($arr, $key_to_get) {
	 if (array_key_exists($key_to_get, $arr)) {
        return($arr[$key_to_get]);
	 } else {
        return(null);
	 }		 
  }


  //wrapper to allow getting params to check if they are present and setting to null otherwise 	  
  function custgetparam($paramname) {
	 if (isset($_POST[$paramname])) {
        return(trim($_POST[$paramname]));
	 }   	
	 if (isset($_GET[$paramname])) {
        return(trim($_GET[$paramname]));
	 } 
     return (null);
  }

function curlINatAPI($path, $data, $acessToken, $useGET) {
  return curlINat("https://api.inaturalist.org" . $path, $data, $accessToken, $useGET);
}
  
function curlINatOAuth($data) {
  $data["client_secret"] = getenv("iNaturalistAppSecret");
  $data["client_id"] = getenv("iNaturalistAppID");
  return curlINat("https://www.inaturalist.org/oauth/token", $data);
}
  
function curlINat($uri, $data, $accessToken = null, $useGET = false) {
  $ch = curl_init($uri);
  $headers = array("Accept: application/json", "Content-Type: application/json");
  if (!$useGET) {
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
  }
  if ($accessToken) {
    $headers[] = "Authorization: Bearer " . $accessToken;
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close ($ch);
  return json_decode($response, true);
}
	
?>