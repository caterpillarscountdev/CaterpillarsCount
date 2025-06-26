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

function curlINatAPI($path, $data, $accessToken, $options = array()) {
  return curlINat("https://api.inaturalist.org" . $path, $data, $accessToken, $options);
}
  
function curlINatOAuth($data) {
  $data["client_secret"] = getenv("iNaturalistAppSecret");
  $data["client_id"] = getenv("iNaturalistAppID");
  $data = http_build_query($data);
  return curlINat("https://www.inaturalist.org/oauth/token", $data);
}

function curlINatJWT($access_token) {
  return curlINat("https://www.inaturalist.org/users/api_token", null, $access_token, array("GET" => 1, "bearer" => 1));
}


function curlINat($uri, $data, $accessToken = null, $options = array()) {
  $ch = curl_init($uri);
  $headers = array("Accept: application/json");
  if (!(array_key_exists("GET", $options) && $options["GET"])) {
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($data) {
      if (is_array($data)) {
        if (array_key_exists("multipart", $options) && $options["multipart"]) {
          $headers[] = "Content-Type: multipart/form-data";
        } else {
          $data = json_encode($data);
          $headers[] = "Content-Type: application/json";
        }
      } 
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
  }
  if ($accessToken) {
    if(array_key_exists("bearer", $options) && $options["bearer"]) {
      $headers[] = "Authorization: Bearer " . $accessToken;
    } else {
      $headers[] = "Authorization: " . $accessToken;
    }
  }
  //error_log("curl " . print_r($headers, true) . "\n" . $accessToken . "\n" . print_r($options, true) );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close ($ch);
  return json_decode($response, true);
}
	
?>