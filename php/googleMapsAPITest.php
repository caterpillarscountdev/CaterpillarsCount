<?php
		$arr = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?latlng=38.867053137076,-77.262822964995&key=" . getenv("unrestrictedGoogleMapsGeocodeAPIKey")), true);
  var_dump($arr);
?>
