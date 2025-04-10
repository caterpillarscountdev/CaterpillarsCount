<?php                                           

$filename = './plantRules.csv';

$plants = array();
$conifers = array();

$src = file_get_contents('../../js/plantSpecies.json');
$canon = json_decode($src, true);


$f = fopen($filename, 'r');
// Eat first row
fgetcsv($f);

function canon_find($type, $val) {
  global $canon;

  return count(array_filter($canon[$type], function($match) use ($val) {
        $match = str_replace(" spp.", "", $match);
        return strcasecmp($match, $val) == 0;
      }));
}


while (($data = fgetcsv($f)) !== FALSE) {
  if ($data[8] == '1') {
    if (!canon_find("conifers", $data[0]) && !canon_find("conifers", $data[1])) {
      $conifers[] = $data[1];
    }
    if (str_word_count($data[3]) > 1 && !canon_find("conifers", $data[3])) {
      $conifers[] = $data[3];
    }
  }
  else {
    if (!canon_find("plants", $data[0]) && !canon_find("plants", $data[1])) {
      $plants[] = $data[1];
    }
    if (str_word_count($data[3]) > 1 && !canon_find("plants", $data[3])) {
      $plants[] = $data[3];
    }
  }

}

//$conifers = array_values(array_udiff($conifers, $canon["conifers"], 'strcasecmp'));
//$plants = array_values(array_udiff($plants, $canon["plants"], 'strcasecmp'));

print(json_encode(array("plants" => $plants, "conifers" => $conifers), JSON_PRETTY_PRINT));


?>