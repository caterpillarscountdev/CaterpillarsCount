<?php                                           

$filename = './plantRules.csv';

$plants = array();
$conifers = array();

$src = file_get_contents('../../js/plantSpecies.json');
$canon = json_decode($src, true);


$f = fopen($filename, 'r');
// Eat first row
fgetcsv($f);

while (($data = fgetcsv($f)) !== FALSE) {
  if ($data[8] == '1') {
    $conifers[] = $data[1];
    if (str_word_count($data[3]) > 1) {
      $conifers[] = $data[3];
    }
  }
  else {
    $plants[] = $data[1];
    if (str_word_count($data[3]) > 1) {
      $plants[] = $data[3];
    }
  }

}

$conifers = array_values(array_udiff($conifers, $canon["conifers"], 'strcasecmp'));
$plants = array_values(array_udiff($plants, $canon["plants"], 'strcasecmp'));

print(json_encode(array("plants" => $plants, "conifers" => $conifers), JSON_PRETTY_PRINT));


?>