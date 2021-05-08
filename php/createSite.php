<?php
	header('Access-Control-Allow-Origin: *');
	
	require_once('orm/User.php');
	
	$email = $_GET["email"];
	$salt = $_GET["salt"];
	$siteName = $_GET["siteName"];
	$description = $_GET["description"];
	$url = $_GET["url"];
	$latitude = $_GET["latitude"];
	$longitude = $_GET["longitude"];
	$zoom = $_GET["zoom"];
	$plantCount = intval($_GET["plantCount"]);
	$sitePassword = $_GET["sitePassword"];
	$public = $_GET["public"];
	
	$user = User::findBySignInKey($email, $salt);
	if(is_object($user) && get_class($user) == "User"){
		//make sure plant count is valid
		if($plantCount % 5 != 0){
			die("false|The number of plants you will survey must be a multiple of 5. ");
		}
		if($plantCount > 125){
			die("false|Woah! That's a lot of plants. The number of plants you will survey must be 125 at most.");
		}
		
		//get region from lat/long
		//Max of 2,500 free requests per day, calculated as the sum of client-side and server-side queries.
		//Max of 50 requests per second, calculated as the sum of client-side and server-side queries.
		$KEY = getenv("unrestrictedGoogleMapsGeocodeAPIKey");
		$arr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&key=" . $KEY), true);
		$country = "";
		$region = "";
		$addressComponents = $arr["results"][0]["address_components"];
		for($i = 0; $i < count($addressComponents); $i++){
			if(in_array("country", $addressComponents[$i]["types"])){
				$country = $addressComponents[$i]["short_name"];
			}
			else if(in_array("administrative_area_level_1", $addressComponents[$i]["types"])){
				$region = $addressComponents[$i]["short_name"];
			}
		}
		
		$finalRegion = $country;
		if($country == "US" || $country == "CA"){
			$finalRegion = $region;
		}
		
		//create site
		$site = $user->createSite($siteName, $description, $url, $latitude, $longitude, $zoom, $finalRegion, $sitePassword, $public);
		
		//output errors if there are any
		if(!is_object($site) || get_class($site) != "Site"){
			die("false|" . $site);
		}
		
		//if error free, create the plants for the site
		for($i = 0; $i < ($plantCount / 5); $i++){
			$site->addCircle();
		}
		//and email the creator
		$site->sendSignUpEmailToCreator();
		die("true");
		
	}
	die("false|Your log in dissolved. Maybe you logged in on another device.");
?>
