<?php
  $ch = curl_init('https://www.inaturalist.org/oauth/token');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "client_id=" . getenv("iNaturalistAppID") . "&client_secret=" . getenv("iNaturalistAppSecret") . "&grant_type=password&username=caterpillarscount&password=" . getenv("iNaturalistPassword"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$token = json_decode(curl_exec($ch), true)["access_token"];
		curl_close ($ch);

  $url = "http://www.inaturalist.org/observations.json?observation[species_guess]=Sternorrhyncha&observation[id_please]=1&observation[observed_on_string]=2021-05-31&observation[place_guess]=Somerville%20Street%20Trees&observation[latitude]=42.390056165293&observation[longitude]=-71.101489223272&observation[observation_field_values_attributes][0][observation_field_id]=9677&observation[observation_field_values_attributes][0][value]=8%20cm&observation[observation_field_values_attributes][1][observation_field_id]=2926&observation[observation_field_values_attributes][1][value]=50&observation[observation_field_values_attributes][2][observation_field_id]=9676&observation[observation_field_values_attributes][2][value]=No&observation[observation_field_values_attributes][3][observation_field_id]=3020&observation[observation_field_values_attributes][3][value]=Visual&observation[observation_field_values_attributes][4][observation_field_id]=9675&observation[observation_field_values_attributes][4][value]=None&observation[observation_field_values_attributes][5][observation_field_id]=9670&observation[observation_field_values_attributes][5][value]=3%20mm&observation[observation_field_values_attributes][6][observation_field_id]=1194&observation[observation_field_values_attributes][6][value]=Somerville%20Street%20Trees&observation[observation_field_values_attributes][7][observation_field_id]=9671&observation[observation_field_values_attributes][7][value]=1&observation[observation_field_values_attributes][8][observation_field_id]=1422&observation[observation_field_values_attributes][8][value]=GDH&observation[observation_field_values_attributes][9][observation_field_id]=6609&observation[observation_field_values_attributes][9][value]=Red%20maple&observation[observation_field_values_attributes][10][observation_field_id]=9672&observation[observation_field_values_attributes][10][value]=0-5%25&observation[observation_field_values_attributes][11][observation_field_id]=544&observation[observation_field_values_attributes][11][value]=3&observation[observation_field_values_attributes][12][observation_field_id]=9673&observation[observation_field_values_attributes][12][value]=amertl";
  
  $ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=" . $token);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		$response = curl_exec($ch);
		curl_close ($ch);
		
echo "[" . json_encode($response) . "]";
?>
