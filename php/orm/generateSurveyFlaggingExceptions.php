<?php                                           

$filename = './plantRules.csv';

$f = fopen($filename, 'r');
// Eat first row
fgetcsv($f);

$longLeaves = array();
$compounds = array();


while (($data = fgetcsv($f)) !== FALSE) {
  if ($data[9]) {
    $compounds[] = '"' . $data[0] . '" => ' . 1;
  }
  if ($data[10]) {
    $longLeaves[] = '"' . $data[0] . '" => ' . $data[10];
  }

}
print("<?php\n");
print("function SurveyFlaggingExceptions() {\nreturn array(\n");
print(join(",\n", $longLeaves));
print(");}\n\n");

print("function SurveyFlaggingCompoundLeaves() {\nreturn array(\n");
print(join(",\n", $compounds));
print(");}\n");


?>