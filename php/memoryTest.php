<?php
  ini_set('memory_limit', '-1');
  
  $arr = array();
  for($i = 0; $i < 96000; $i++){
    $arr[] = array(
      "one" => "skjd flskjd lskdj fs",
      "two" => "sdjflskd flsdfl sndf",
      "three" => "sldkflskm lkmsd lwkd",
      "four" => "oiwdnc lsc  sdlmc sdm sc",
      "five" => "skjncd ienjdn xmnc edc",
      "six" => "wdcwdverb erervedce",
      "seven" => true,
      "eight" => false,
      "nine" => 5,
      "ten" => 2,
      "eleven" => 9,
      "twelve" => "kjdnfkwndcwdnlcwnlkdn lwdc lw lkd cwlkdmc lkwmd clkemdlk vmeldv",
      "thirteen" => "cjs bxkcn oenv icjnvodkcnv kdncv dvdv",
      "fourteen" => "eoidjv oedmcoe djvneoi nveond veodnvoedcmendckled",
      "fifteen" => false,
      "sixteen" => true,
      "seventeen" => true,
      "eightteen" => true,
    );
  }
  die("[" . count($arr) . "]")
?>
