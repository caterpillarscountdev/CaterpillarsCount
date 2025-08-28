<?php
require_once("orm/Publication.php");

$pubs = Publication::findAll();
$pubsArray = array();
for($i = 0; $i < count($pubs); $i++){
  $pubsArray[] = array(
    "id" => $pubs[$i]->getID(),
    "citation" => $pubs[$i]->getCitation(),
    "doi" => $pubs[$i]->getDOI(),
    "link" => $pubs[$i]->getLink(),
    "image" => $pubs[$i]->getImage()
    );
}

die(json_encode($pubsArray));
?>
b