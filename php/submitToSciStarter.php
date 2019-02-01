<?php
	/*
	function submitToSciStarter($email, $type, $where = null, $when = null, $duration = null, $magnitude = null, $extra = null){
		$KEY = getenv("SciStarterKey");

		$ch = curl_init("https://scistarter.com/api/profile/id?hashed=" . hash("sha256", $email) . "&key=" . $KEY);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$profileID = json_decode(curl_exec($ch), true)["scistarter_profile_id"];
		curl_close($ch);

		$extraParams = "";
		if($where !== null){
			$extraParams .= "&where=" . $where;
		}
		if($when !== null){
			$extraParams .= "&when=" . $when;
		}
		if($duration !== null){
			$extraParams .= "&duration=" . $duration;
		}
		if($magnitude !== null){
			$extraParams .= "&magnitude=" . $magnitude;
		}
		if($extra !== null){
			$extraParams .= "&extra=" . $extra;
		}

		$ch = curl_init("https://scistarter.com/api/record_event?key=" . $KEY);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "profile_id=" . $profileID . "&project_id=" . getenv("SciStarterProjectID") . "&type=" . $type . $extraParams);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		curl_close ($ch);
	}
	*/
?>
