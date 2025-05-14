<?php                                           

$filename = './plantRules.csv';

$f = fopen($filename, 'r');
// Eat first row
fgetcsv($f);

$plants = array();
$conifers = array();


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
print(json_encode(array("plants" => $plants, "conifers" => $conifers), JSON_PRETTY_PRINT));


?>