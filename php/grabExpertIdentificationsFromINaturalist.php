<?php
  /*
  require_once('/opt/app-root/src/php/orm/resources/Keychain.php');
  
  //TODO: get current iteration from database to use in cURL
  
  $ch = curl_init('https://api.inaturalist.org/v1/observations?project_id=caterpillars-count-foliage-arthropod-survey&user_login=caterpillarscount&quality_grade=research&page=1&per_page=50&order=desc&order_by=created_at');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = json_decode(curl_exec($ch), true);
	curl_close ($ch);
  
  $iNaturalistIDs = [];
  for($i = 0; i < count($data["results"]); $i++){
    $iNaturalistIDs[] = $data["results"][$i]["id"];
  }
  
  $iNaturalistIDTranslations = array();
  $query = mysqli_query($dbconn, "SELECT ID, INaturalistID FROM ArthropodSighting WHERE INaturalistID IN ('" . implode("', '", $iNaturalistIDs) . "')");
  while($row = mysqli_fetch_assoc($query)){
    $iNaturalistIDTranslations[$row["INaturalistID"]] = $row["ID"];
  }
  
  for($i = 0; i < count($data["results"]); $i++){
    $arthropodFK = $iNaturalistIDTranslations[$data["results"][$i]["id"]];
    $rank = $data["results"][$i]["taxon"]["rank"];
    $taxonName = $data["results"][$i]["taxon"]["name"];
    $standardGroup = ;//TODO
  }
  
  if(count($data["results"]) == 0){
    //mark finished in db and reset iteration
  }
  */
?>
