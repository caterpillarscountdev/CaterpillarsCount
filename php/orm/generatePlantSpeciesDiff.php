<?php                                           

require("PlantSpecies.php");

$filename = './plantRules.csv';

$plants = array();

$canon = PlantSpeciesList();

$f = fopen($filename, 'r');
// Eat first row
fgetcsv($f);


function canon_find($common, $sciName) {
  global $canon;

  return count(array_filter($canon, function($match) use ($common, $sciName) {
        return strcasecmp($match[0], $common) == 0 || strcasecmp($match[1], $sciName) == 0;
      }));
}

function spp($name, $data) {
  if (strcasecmp($data[6], "genus") == 0) {
    if (!strpos($name, 'spp')) {
      $name = $name . " spp.";
    }
  }
  return $name;
}

while (($data = fgetcsv($f)) !== FALSE) {
  if ($data[1] == 'NA' || $data[2] == 'NA' || strcasecmp($data[1],'Unknown') == 0 || strcasecmp($data[2],'Unknown') == 0) {
    continue;
  }
  $common = spp($data[1], $data);
  $sciName = spp($data[2], $data);
  if (!canon_find($common, $sciName)) {
    $plants[] = array($common, $sciName, ($data[8] == '1' ? 1 : 0 ));
  }
}

foreach($plants as $plant) {
  echo("array(\"$plant[0]\", \"$plant[1]\", $plant[2]),\n");
}

?>