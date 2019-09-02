<?php
  $offset = intval($_GET["offset"]);
  $limit = intval($_GET["limit"]);
  
  $photoHashes = array();
  $photos = scandir("../images/arthropods");
  for($i = 0; $i < count($photos); $i++){
    $photoHashes[$photos[$i]] = hash_file("md5", "../images/arthropods/" . $photos[$i]);
  }

  for($i = 0; $i < count($photoHashes); $i++){
    for($j = 0; $j < count($photoHashes); $j++){
      if($i != $j && $photoHashes[$i] == $photoHashes[$j]){
        echo $photos[$i];
        break;
      }
    }
  }
?>
