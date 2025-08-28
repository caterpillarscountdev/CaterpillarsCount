<?php                                           

require_once('Publication.php');

$pubs = './publications.csv';
$pubsSites = './publicationsSites.csv';
$pubsUsers = './publicationsUsers.csv';

$publications = array();

$f = fopen($pubs, 'r');
fgetcsv($f); // Eat first row

while (($data = fgetcsv($f)) !== FALSE) {
  $existing = Publication::findByID($data[0]);
  if ($existing && !$data[4]) {
    $data[4] = $existing->getImage();
  }
  if ($existing && !$data[5]) {
    $data[5] = $existing->getOrder();
  }
  $publications[$data[0]] = Publication::create($data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
}
fclose($f);
$f = fopen($pubsSites, 'r');
fgetcsv($f); // Eat first row

while (($data = fgetcsv($f)) !== FALSE) {
  // SiteFK, nSurveys, pubID
  if ($publications[$data[2]]) {
    $publications[$data[2]]->addSite($data[0], $data[1]);
  } else {
    print("No publication for site row: " . print_r($data, true));
  }
}
fclose($f);
$f = fopen($pubsUsers, 'r');
fgetcsv($f); // Eat first row

while (($data = fgetcsv($f)) !== FALSE) {
  // UserFK, nSurveys, pubID
  if ($publications[$data[2]]) {
    $publications[$data[2]]->addUser($data[0], $data[1]);
  } else {
    print("No publication for user row: " . print_r($data, true));
  }
}
fclose($f);


print("Updated publications tables.");


?>