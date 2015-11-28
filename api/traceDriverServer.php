<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// unit meter
define("CAR_DELTA", 25);

require_once '../config/db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

if ($data['init']) {
	if ($data['role']) {
		// driver initial info
		// SELECT `driver`.`aid`, `account`.`name`, `driver`.`curpoint`, `driver`.`path` FROM`driver`, `account` WHERE (NOT `driver`.`finished`) AND `account`.`aid` = 1046779538684826 AND `driver`.`aid` = 1046779538684826
		$driverInfoSql = 'SELECT `driver`.`aid`, `account`.`name`, `driver`.`curpoint`, `driver`.`path` FROM `driver`, `account` WHERE (NOT `driver`.`finished`) AND `account`.`aid` = ' . $data['did'] . ' AND `driver`.`aid` = ' . $data['did'];
		$driverInfo = mysql_query($driverInfoSql);

		if (mysql_num_rows($driverInfo) > 0) {
			$driverInfo = mysql_fetch_array($driverInfo, MYSQL_ASSOC);
			if ($data['did'] == $driverInfo['aid']) {
				// to client str
				// {"driver":{"Name": "a", "CurPoint": 1, "Path": 1}}
				$clientStr = '{"driver":{"Name": "' . $driverInfo['name'] . '", "CurPoint": ' . $driverInfo['curpoint'] . ', "Path": ' . $driverInfo['path'] . '}}';
				echo $clientStr;
			}
		}
	} else {
		// passenger initial info
		// SELECT `passenger`.`aid`, `account`.`name`, `passenger`.`curpoint`, `passenger`.`carpoolpath` FROM `account`, `passenger` WHERE (NOT `passenger`.`finished`) AND `account`.`aid` = 270371829840730  AND `passenger`.`aid` = 270371829840730
		$passInfoSql = 'SELECT `passenger`.`aid`, `account`.`name`, `passenger`.`curpoint`, `passenger`.`carpoolpath` FROM `account`, `passenger` WHERE (NOT `passenger`.`finished`) AND `account`.`aid` = ' . $data['pid'] . ' AND `passenger`.`aid` = ' . $data['pid'];
		$passInfo = mysql_query($passInfoSql);

		if (mysql_num_rows($passInfo) > 0) {
			$passInfo = mysql_fetch_array($passInfo, MYSQL_ASSOC);
			if ($data['pid'] == $passInfo['aid']) {
				$passInfo['carpoolpath'] = json_decode($passInfo['carpoolpath'], true);
				$reCarpoolPath = json_encode($passInfo['carpoolpath'][$data['carpoolidx']]);
				// to client str
				// {"passenger":{"Name": "a", "CurPoint": 1, "Path": 1}}
				$clientStr = '{"passenger":{"Name": "' . $passInfo['name'] . '", "CurPoint": ' . $passInfo['curpoint'] . ', "Path": ' . $reCarpoolPath . '}}';
				echo $clientStr;
			}
		}
	}
} else {
// Get necessary data from db
	// function GetneceData($did, $pids, $onlyPath)
	$neceData = GetneceData($data['did'], $data['pids'], $data['carpoolidx']);

// first update driver current point
	UpdateCurrentPoint($data['did'], $data['curpoint']);

// get target point
	$targetPoint = GetTargetPoint($neceData['driverPath'], $neceData['points']);

// determine target point owner if get in car or get out off car
	$calResult = DetResult($targetPoint);

	// get each passengers' curpoint
	// SELECT `passenger`.`aid`, `passenger`.`curpoint` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` =
	$passCurpoints = array();
	if (count($data['pids'])) {
		$passCurpoints = array();
		$passCurpointSql = 'SELECT `passenger`.`aid`, `passenger`.`curpoint` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` IN (' . join(",", $data['pids']) . ')';
		$passCurpoint = mysql_query($passCurpointSql);
		while ($lineData = mysql_fetch_array($passCurpoint, MYSQL_ASSOC)) {
			if (in_array($lineData['aid'], $data['pids']) && $lineData['aid'] != $calResult[0]['id']) {
				array_push($passCurpoints, array("id" => $lineData['aid'], "curpoint" => json_decode($lineData['curpoint'], true)));
			}
		}
	}
	echo '{"calResult":' . urldecode(json_encode($calResult)) . ', "passCurpoints" : ' . json_encode($passCurpoints) . '}';
}

/*
 **************************************************************************************************
 **************************************************************************************************
 *                       			 FUNCTION DEFINITION START        							  *
 **************************************************************************************************
 **************************************************************************************************
 */

// get necessary data
// driver path, passengers' get in and get out off point
function GetneceData($did, $pids, $carpoolPathIdx) {
	$neceData = array();

	// get driver data
	// SELECT `driver`.`aid`,  `driver`.`path` FROM `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` = 1046779538684826
	$getDriverPathSql = 'SELECT `driver`.`aid`, `driver`.`path` FROM `driver` WHERE (NOT `driver`.`finished`) AND `driver`.`aid` = ' . $did;
	$driverPathResult = mysql_query($getDriverPathSql);

	if (mysql_num_rows($driverPathResult) > 0) {
		$driverPath = mysql_fetch_array($driverPathResult);
		if ($driverPath['aid'] == $did) {
			$neceData['driverPath'] = json_decode($driverPath['path'], true);
		} else {
			$neceData['driverPath'] = null;
		}
	} else {
		$neceData['driverPath'] = null;
	}

	// get passengers' data
	// SELECT `passenger`.`aid`, `passenger`.`getinStatus`, `passenger`.`getoffStatus`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` IN (270371829840730, 1046779538684829, 1046779538684830, 1046779538684831)
	$neceData['points'] = array();
	if (count($pids) > 0) {
		$getPassPointsSql = 'SELECT `passenger`.`aid`, `passenger`.`curpoint`, `passenger`.`getinStatus`, `passenger`.`getoffStatus`, `passenger`.`carpoolpath` FROM `passenger` WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` IN (' . join(",", $pids) . ')';
		$passPointsResult = mysql_query($getPassPointsSql);

		$i = 0;
		while ($lineData = mysql_fetch_array($passPointsResult, MYSQL_ASSOC)) {
			if (in_array($lineData['aid'], $pids)) {
				$cpath = json_decode($lineData['carpoolpath'], true);
				// $carpoolPathCurrentIdx = array_search($lineData['aid'], $pids);
				$carpoolPathCurrentIdx = $carpoolPathIdx[$i];

				if (!$lineData['getinStatus']) {
					$pData = array();
					$pData['id'] = $lineData['aid'];
					$pData['type'] = 1;
					$pData['curpoint'] = json_decode($lineData['curpoint'], true);
					$pData['point'] = $cpath[$carpoolPathCurrentIdx][0];
					array_push($neceData['points'], $pData);
				}

				if (!$lineData['getoffStatus']) {
					$pData = array();
					$pData['id'] = $lineData['aid'];
					$pData['type'] = 2;
					$pData['curpoint'] = json_decode($lineData['curpoint'], true);
					$pData['point'] = end($cpath[$carpoolPathCurrentIdx]);
					array_push($neceData['points'], $pData);
				}
			}
		}
	}
	return $neceData;
}

// update current point
// UPDATE `driver` SET `driver`.`curpoint` = '{"at": 22.9667, "ng": 120.2288}' WHERE `driver`.`aid` = 1046779538684826
function UpdateCurrentPoint($id, $point) {
	$sql = 'UPDATE `driver` SET `driver`.`curpoint` = ' . '\'{"at":"' . $point['at'] . '","ng":"' . $point['ng'] . '"}\'' . ' WHERE `driver`.`aid` = ' . $id;
	$updateResult = mysql_query($sql);
}

// get target point to calculate some infomation
function GetTargetPoint($path, $points) {
	$found = 0;
	$tarPoints = array();

	if (count($points)) {
		foreach ($path as $j => $pathPoint) {
			foreach ($points as $i => $p) {
				if (number_format($pathPoint['at'], 5) == number_format($p['point']['at'], 5) && number_format($pathPoint['ng'], 5) == number_format($p['point']['ng'], 5)) {
					array_push($tarPoints, $p);
					$found++;
					break;
				}
			}
			if ($found == 2) {
				break;
			}
		}
	}

	return $tarPoints;
}

function DetResult($tp) {
	// to google distance matrix point
	// 0 is $tp first
	// 1 is $tp second
	// 2 is driver end point
	$whichPoint;
	$tpNum = count($tp);

	if ($tpNum) {
		if ($tp[0]['type'] == 1) {
			// get in condition
			// DetGetinCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetinPoint)
			if (DetGetinCar($tp[0]['curpoint'], $GLOBALS['data']['curpoint'], $tp[0]['point'])) {
				// this passenger get in car
				// update db infomation
				// UPDATE `passenger` SET `passenger`.`getinStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684829
				$UpdatePassStatusSql = 'UPDATE `passenger` SET `passenger`.`getinStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $tp[0]['id'];
				$UpdatePassStatusResult = mysql_query($UpdatePassStatusSql);
				$whichPoint = ($tpNum > 1) ? 1 : 2;
			} else {
				$whichPoint = 0;
			}
		} else {
			// get out off condition
			// DetGetoutoffCar($passengerCurrentPoint, $driverCurrentPoint, $passengerGetoutPoint)
			if (DetGetoutoffCar($tp[0]['curpoint'], $GLOBALS['data']['curpoint'], $tp[0]['point'])) {
				// this passenger get out off car
				// update db infomation
				// UPDATE `passenger` SET `passenger`.`getoffStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = 1046779538684829
				$UpdatePassStatusSql = 'UPDATE `passenger` SET `passenger`.`getoffStatus` = 1 WHERE (NOT `passenger`.`finished`) AND `passenger`.`aid` = ' . $tp[0]['id'];
				$UpdatePassStatusResult = mysql_query($UpdatePassStatusSql);
				$whichPoint = ($tpNum > 1) ? 1 : 2;
			} else {
				$whichPoint = 0;
			}
		}
	} else {
		$whichPoint = 2;
	}

	// google distance matrix api cal result
	// function getPathDistance($p1, $p2, $mode)
	// $p1 is driver curpoint in here
	// $p2 is destination mean $whichPoint in here
	// $mode could be driving (default), walking, bicycling, transit
	if ($whichPoint > 1) {
		$gdmResult = getPathDistance($GLOBALS['data']['curpoint'], end($GLOBALS['neceData']['driverPath']), "driving");
	} else {
		$gdmResult = getPathDistance($GLOBALS['data']['curpoint'], $tp[$whichPoint]['point'], "driving");
	}

	// $tp[0] point if get in or get out off
	// $whichPoint is driver page display infomation to driver by google distance matrix api
	$calResult = array();

	if (!$whichPoint) {
		// client page display point
		// one point
		// passenger state 0 is the passenger not get in or get out off
		array_push($calResult, array("id" => $tp[0]['id'], "type" => $tp[0]['type'], "curpoint" => $tp[0]['curpoint'], "gdm" => $gdmResult));
	} else {
		// two points
		// 0 is next point to show
		if ($whichPoint > 1) {
			array_push($calResult, array("id" => $GLOBALS['data']['did'], "type" => 0, "curpoint" => null, "passengerState" => null, "gdm" => $gdmResult));
		} else {
			array_push($calResult, array("id" => $tp[1]['id'], "type" => $tp[1]['type'], "curpoint" => $tp[1]['curpoint'], "gdm" => $gdmResult));
		}
		// $calResult[1] will be removed point on map
		if ($tpNum) {
			array_push($calResult, array("id" => $tp[0]['id'], "type" => $tp[0]['type'], "curpoint" => null, "gdm" => null));
		}
	}
	return $calResult;
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
