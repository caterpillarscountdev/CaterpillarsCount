<?php

function SurveyFlaggingRules () {
  return array(
    "minSafeLeaves" => 5,
    "maxSafeLeaves" => 400,
    "maxSafeLeafLength" => 30,
    "arthropodGroupFlaggingRules" => array(
      "ant" => array(
        "maxSafeLength" => 17,
        "maxSafeQuantity" => 50
        ),
      "aphid" => array(
        "maxSafeLength" => 10,
        "maxSafeQuantity" => 50
        ),
      "bee" =>  array(
        "maxSafeLength" => 25,
        "maxSafeQuantity" => 6
        ),
      "sawfly" => array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 20
        ),
      "beetle" =>  array(
        "maxSafeLength" => 20,
        "maxSafeQuantity" => 10
        ),
      "caterpillar" =>  array(
        "maxSafeLength" => 50,
        "maxSafeQuantity" => 6
        ),
      "daddylonglegs" =>  array(
        "maxSafeLength" => 15,
        "maxSafeQuantity" => 6
        ),
      "fly" =>  array(
        "maxSafeLength" => 20,
        "maxSafeQuantity" => 6
        ),
      "grasshopper" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 6
        ),
      "leafhopper" =>  array(
        "maxSafeLength" => 20,
        "maxSafeQuantity" => 6
        ),
      "moths" =>  array(
        "maxSafeLength" => 30,
        "maxSafeQuantity" => 6
        ),
      "spider" =>  array(
        "maxSafeLength" => 20,
        "maxSafeQuantity" => 6
        ),
      "truebugs" =>  array(
        "maxSafeLength" => 25,
        "maxSafeQuantity" => 6
        ),
      "other" =>  array(
        "maxSafeLength" => 25,
        "maxSafeQuantity" => 6
        ),
      "unidentified" =>  array(
        "maxSafeLength" => 25,
        "maxSafeQuantity" => 6
        )
      )
    );
}
?>