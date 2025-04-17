<?php                                           

require("PlantSpecies.php");

$plants = array();
$conifers = array();


foreach (PlantSpeciesList() as $species) {
  if ($species[2]) {
    $conifers[] = $species[0];
    if ($species[0] != $species[1]) {
      $conifers[] = $species[1];
    }
  }
  else {
    $plants[] = $species[0];
    if ($species[0] != $species[1]) {
      $plants[] = $species[1];
    }
  }

}
print(json_encode(array("plants" => $plants, "conifers" => $conifers), JSON_PRETTY_PRINT));


?>