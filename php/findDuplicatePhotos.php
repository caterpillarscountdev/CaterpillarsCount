<?php
  $offset = intval($_GET["offset"]);
  $limit = intval($_GET["limit"]);
  
  $usedPhotoHashes = array();
  $photoHashes = array();
  $photos = scandir("../images/arthropods");
  for($i = 0; $i < count($photos); $i++){
    $photoHashes[$photos[$i]] = hash_file("md5", "../images/arthropods/" . $photos[$i]);
  }

  for($i = 0; $i < count($photoHashes); $i++){
    $isDuplicate = false;
    $urls = array();
    for($j = 0; $j < count($photoHashes); $j++){
      if($i != $j && !in_array($photoHashes[$photos[$i]], $usedPhotoHashes) && $photoHashes[$photos[$i]] == $photoHashes[$photos[$j]]){
        if(!$isDuplicate){
          echo "<a href=\"https://caterpillarscount.unc.edu/images/arthropods/" . $photos[$i] . "\" target=\"_blank\">";
          $urls[] = "\"" . $photos[$i] . "\"";
        }
        $isDuplicate = true;
        $urls[] = "\"" . $photos[$j] . "\"";
      }
    }
    if($isDuplicate){
      $usedPhotoHashes[] = $photoHashes[$photos[$i]];
      echo "SELECT * FROM ArthropodSighting WHERE PhotoURL IN (" . implode(", ", $urls) . ")</a><br/>";
    }
  }
?>
