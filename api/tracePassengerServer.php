<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// unit meter
define("CAR_DELTA", 25);
// define("DRIVER_ARRIVING", 50);

require_once '../config/db_connect.php';
$db = new DB_CONNECT();

//place this before any script you want to calculate time
$EXEC_START_TIME = microtime(true);

$data = $_GET['data'];
$data = json_decode($data, true);
// print_r($data);

if ($data['init']) {
	if ($data['role']) {
		// passenger initial info
		// SELECT `passenger`.`aid`, `account`.`name`, `passenger`.`curpoint`, `passenger`.`path` FROM`passenger`, `account` WHERE (NOT `passenger`.`finished`) AND `account`.`aid` = 1046779538684826 AND `passenger`.`aid` = 1046779538684826
		$passengerInfoSql = 'SELECT `passenger`.`aid`, `account`.`name`, `passenger`.`curpoint`, `passenger`.`path` FROM `passenger`, `account` WHERE (NOT `passenger`.`finished`) AND `account`.`aid` = ' . $data['pid'] . ' AND `passenger`.`aid` = ' . $data['pid'];
		$passengerInfo = mysql_query($passengerInfoSql);

		if (mysql_num_rows($passengerInfo) > 0) {
			$passengerInfo = mysql_fetch_array($passengerInfo, MYSQL_ASSOC);
			if ($data['pid'] == $passengerInfo['aid']) {
				// to client str
				// {"passenger":{"Name": "a", "CurPoint": 1, "Path": 1}}
				$clientStr = '{"passenger":{"Name": "' . $passengerInfo['name'] . '", "CurPoint": ' . $passengerInfo['curpoint'] . ', "Path": ' . $passengerInfo['path'] . '}}';
				// print_r($driverInfo);
				echo $clientStr;
			}
		}
	} else {
		// driver initial info
		// first select driver name curpoint
		// SELECT `driver`.`aid`, `account`.`name`, `driver`.`curpoint` FROM `account`, `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` = 1046779538684827 AND `account`.`aid` = 1046779538684827
		$driverDatas = array();
		$driverInfoSql = 'SELECT `driver`.`aid`, `account`.`name`, `driver`.`curpoint` FROM `account`, `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` = ' . $data['did'] . ' AND `account`.`aid` = ' . $data['did'];
		$driverInfo = mysql_query($driverInfoSql);
		if (mysql_num_rows($driverInfo) > 0) {
			$driverInfo = mysql_fetch_array($driverInfo, MYSQL_ASSOC);
			if ($data['did'] == $driverInfo['aid']) {
				$driverDatas['Name'] = $driverInfo['name'];
				$driverDatas['curpoint'] = $driverInfo['curpoint'];
			}
		}

		// seconde select passenger carpool path with the driver
		// SELECT `passenger`.`aid`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684831
		$driverCarpoolSql = 'SELECT `passenger`.`aid`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $data['pid'];
		$driverCarpool = mysql_query($driverCarpoolSql);
		if (mysql_num_rows($driverCarpool) > 0) {
			$driverCarpool = mysql_fetch_array($driverCarpool, MYSQL_ASSOC);
			if ($data['pid'] == $driverCarpool['aid']) {
				$driverDatas['Path'] = json_decode($driverCarpool['carpoolpath'], true)[$data['pathid']];
			}
		}

		// to client str
		// {"driver":{"Name": "a", "CurPoint": 1, "Path": 1}}
		$clientStr = '{"driver":{"Name": "' . $driverDatas['Name'] . '", "CurPoint": ' . $driverDatas['curpoint'] . ', "Path": ' . json_encode($driverDatas['Path']) . '}}';
		echo $clientStr;
	}
} else {
	if ($data['resetStatus']) {
		// update passenger get in and get out off car state
		// UPDATE `passenger` SET `passenger`.`getinStatus` = 0, `passenger`.`getoffStatus` = 0  WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684831
		$UpdatePassStatusSql = 'UPDATE `passenger` SET `passenger`.`getinStatus` = 0, `passenger`.`getoffStatus` = 0  WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $data['pid'];
		$UpdatePassStatusResult = mysql_query($UpdatePassStatusSql);
	} else {
// Get necessary data from db
		// function GetneceData($pid, $dids, $onlyPath)
		$neceData = GetneceData($data['pid'], $data['dids'], false);
		// print_r($neceData);

// first update driver current point
		UpdateCurrentPoint($data['pid'], $data['curpoint']);

// determine target point owner if get in car or get out off car
		$calResult = DetResult($neceData);
		// print_r($calResult);

		echo '{"calResult":' . $calResult . ', "driverCurpoints" : ' . json_encode($neceData['driversCurpoint']) . '}';
	}
}

$EXEC_END_TIME = microtime(true);
// echo "\nExecution time : " . ($EXEC_END_TIME - $EXEC_START_TIME) . " sec";

/*
 **************************************************************************************************
 **************************************************************************************************
 *                                   FUNCTION DEFINITION START                                    *
 **************************************************************************************************
 **************************************************************************************************
 */

// get necessary data
// carpool path , driver curpoint
function GetneceData($pid, $dids) {
	$neceData = array();

	// get passenger data
	// SELECT `passenger`.`aid`, `passenger`.`getinStatus`, `passenger`.`getoffStatus`, `passenger`.`end`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684831
	$passInfoSql = 'SELECT `passenger`.`aid`, `passenger`.`getinStatus`, `passenger`.`getoffStatus`, `passenger`.`end`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $pid;
	$passInfo = mysql_query($passInfoSql);

	if (mysql_num_rows($passInfo) > 0) {
		$passInfo = mysql_fetch_array($passInfo, MYSQL_ASSOC);
		if ($passInfo['aid'] == $pid) {
			if (!$passInfo['getinStatus']) {
				$neceData['targetPoint'] = json_decode($passInfo['carpoolpath'], true)[0][0];
				$neceData['type'] = 0;
			} else if (!$passInfo['getoffStatus']) {
				$neceData['targetPoint'] = end(json_decode($passInfo['carpoolpath'], true)[0]);
				$neceData['type'] = 1;
			}
			$neceData['passEndpoint'] = json_decode($passInfo['end'], true);
		}
	}

	// get drivers' curpoint
	// SELECT `driver`.`aid`, `driver`.`curpoint` FROM `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` IN (1046779538684827,1046779538684828)
	$neceData['driversCurpoint'] = array();
	if (count($dids) > 0) {
		$driverCurpointSql = 'SELECT `driver`.`aid`, `driver`.`curpoint` FROM `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` IN (' . join(",", $dids) . ')';
		$driverCurpoint = mysql_query($driverCurpointSql);

		while ($lineData = mysql_fetch_array($driverCurpoint, MYSQL_ASSOC)) {
			if (in_array($lineData['aid'], $dids)) {
				$dData = array();
				$dData['did'] = $lineData['aid'];
				$dData['curpoint'] = json_decode($lineData['curpoint'], true);
				array_push($neceData['driversCurpoint'], $dData);
			}
		}
	}
	return $neceData;
}

// update current point
// UPDATE `passenger` SET `passenger`.`curpoint` = '{"at": 22.97371, "ng": 120.21737}' WHERE `passenger`.`aid` = 1046779538684831
function UpdateCurrentPoint($id, $point) {
	$sql = 'UPDATE `passenger` SET `passenger`.`curpoint` = ' . '\'{"at":"' . $point['at'] . '","ng":"' . $point['ng'] . '"}\'' . ' WHERE `passenger`.`aid` = ' . $id;
	$updateResult = mysql_query($sql);
}

function DetResult($data) {
	// cal distance and determine if passenger ge in or get out off car
	$getcarStatus;
	if (array_key_exists('type', $data)) {
		if (!$data['type']) {
			// get in condition
			// DetGetinCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetinPoint)
			if (DetGetinCar($GLOBALS['data']['curpoint'], $data['driversCurpoint'][0]['curpoint'], $data['targetPoint'])) {
				// this passenger get in car
				// update db infomation
				// UPDATE `passenger` SET `passenger`.`getinStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684829
				$UpdatePassStatusSql = 'UPDATE `passenger` SET `passenger`.`getinStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $GLOBALS['data']['pid'];
				$UpdatePassStatusResult = mysql_query($UpdatePassStatusSql);
				$getcarStatus = 0;
			} else {
				$getcarStatus = 2;
			}
		} else {
			// get out off condition
			// DetGetoutoffCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetoutPoint)
			if (DetGetoutoffCar($GLOBALS['data']['curpoint'], $data['driversCurpoint'][0]['curpoint'], $data['targetPoint'])) {
				// this passenger get out off car
				// update db infomation
				// UPDATE `passenger` SET `passenger`.`getoffStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684829
				$UpdatePassStatusSql = 'UPDATE `passenger` SET `passenger`.`getoffStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $GLOBALS['data']['pid'];
				$UpdatePassStatusResult = mysql_query($UpdatePassStatusSql);
				$getcarStatus = 1;
			} else {
				$getcarStatus = 2;
			}
		}
	} else {
		$getcarStatus = 2;
	}

	return $getcarStatus;
}

// cal distance : 1. passenger current point to driver current point
// 2. passenger current point to passenger get in car point
function DetGetinCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetinPoint) {
	$dis1 = getDirectDistance($passengerCurrentPoint, $driverCurrentPoint);
	$dis2 = getDirectDistance($passengerCurrentPoint, $passengerGetinPoint);

	if ($dis1 <= constant("CAR_DELTA") && $dis2 <= constant("CAR_DELTA")) {
		return 1;
	}
	return 0;
}

// cal distance : 1. passenger current point to passenger get out off car point
// 2. driver current point to passenger get out off car point
function DetGetoutoffCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetoutPoint) {
	$dis1 = getDirectDistance($passengerCurrentPoint, $passengerGetoutPoint);
	$dis2 = getDirectDistance($driverCurrentPoint, $passengerGetoutPoint);

	if ($dis1 <= constant("CAR_DELTA") && $dis2 <= constant("CAR_DELTA")) {
		return 1;
	}
	return 0;
}

//計算路徑距離與時間
function getPathDistance($p1, $p2, $mode) {
	$origin = $p1["at"] . ',' . $p1["ng"];
	$destination = $p2["at"] . ',' . $p2["ng"];
	$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $origin . '&destinations=' . $destination . '&mode=' . $mode . '&language=zh-TW';
	$response = json_decode(file_get_contents($url), true);
	$result = array();
	if ($response["status"] == "OK") {
		if ($response["rows"][0]["elements"][0]["status"] == "OK") {
			$distanceText = $response["rows"][0]["elements"][0]["distance"]["text"];
			$distanceVal = $response["rows"][0]["elements"][0]["distance"]["value"];
			$durationText = $response["rows"][0]["elements"][0]["duration"]["text"];
			$durationVal = $response["rows"][0]["elements"][0]["duration"]["value"];
			// array_push($result, $distance, $duration);
			$result['distance'] = array("text" => urlencode($distanceText), "val" => $distanceVal);
			$result['time'] = array("text" => urlencode($durationText), "val" => $durationVal);
		}
	}
	return $result;
}

//計算直接距離
function getDirectDistance($ori, $des) {
	$R = 6378137; // Earth’s mean radius in meter
	$dLat = ($des['at'] - $ori['at']) * M_PI / 180;
	$dLong = ($des['ng'] - $ori['ng']) * M_PI / 180;
	$a = sin($dLat / 2) * sin($dLat / 2) + cos(($ori['at'] * M_PI / 180)) * cos(($des['at'] * M_PI / 180)) * sin($dLong / 2) * sin($dLong / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$d = $R * $c;
	return $d;
}

?>