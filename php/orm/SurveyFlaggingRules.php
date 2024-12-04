<?php

function SurveyFlaggingRules () {
  return array(
    "minSafeLeaves" => 5,
    "maxSafeLeaves" => 600,
    "maxSafeLeafLength" => 30,
    "arthropodGroupFlaggingRules" => array(
      "ant" => array(
        "maxSafeLength" => 20,
        "maxSafeQuantity" => 200
        ),
      "aphid" => array(
        "maxSafeLength" => 10,
        "maxSafeQuantity" => 400
        ),
      "bee" =>  array(
        "maxSafeLength" => 40,
        "maxSafeQuantity" => 9
        ),
      "sawfly" => array(
        "maxSafeLength" => 60,
        "maxSafeQuantity" => 30
        ),
      "beetle" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 20
        ),
      "caterpillar" =>  array(
        "maxSafeLength" => 60,
        "maxSafeQuantity" => 9
        ),
      "daddylonglegs" =>  array(
        "maxSafeLength" => 15,
        "maxSafeQuantity" => 9
        ),
      "fly" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 9
        ),
      "grasshopper" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 9
        ),
      "leafhopper" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 9
        ),
      "moths" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 9
        ),
      "spider" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 9
        ),
      "truebugs" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 9
        ),
      "other" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 100
        ),
      "unidentified" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 100
        )
      )
    );
}
?>
