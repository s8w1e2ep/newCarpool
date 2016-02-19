 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$sql = "SELECT * FROM `history` ORDER BY `hid` DESC";
$result = mysql_query($sql);

$index = 0;
date_default_timezone_set("Asia/Taipei");

function getAddress($latlng) {
	$latlng = json_decode($latlng);
	$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . ($latlng->at) . ',' . ($latlng->ng) . '&language=zh-TW';
	$response = json_decode(file_get_contents($url), true);
	if ($response["status"] == "OK") {
		$n = count($response["results"][0]["address_components"]);
		$address = "";
		$ii = $n - 3;
		for ($ii; $ii >= 0; $ii--) {
			$address = $address . $response["results"][0]["address_components"][$ii]["long_name"];
		}
		return $address;
	}
}

while ($index < 10 && $i = mysql_fetch_array($result)) {

	$ssql = "SELECT `aid` FROM `passenger` WHERE `pnum` = " . $i['pnum'];
	$rresult = mysql_query($ssql);
	$j = mysql_fetch_array($rresult);
	$pid = $j[0];

	$ssql2 = "SELECT `aid` FROM `driver` WHERE `dnum` = " . $i['dnum'];
	$rresult2 = mysql_query($ssql2);
	$j2 = mysql_fetch_array($rresult2);
	$did = $j2[0];

	//get distance
	$finished = $i["finished"];
	$dis = $i['distance'];
	//get driver name
	$ssql = "SELECT `name` FROM `account` WHERE `aid` = " . $did;
	$rresult = mysql_query($ssql);
	$j = mysql_fetch_array($rresult);
	$dname = $j[0];
	//get passenger name
	$ssql = "SELECT `name` FROM `account` WHERE `aid` = " . $pid;
	$rresult = mysql_query($ssql);
	$j = mysql_fetch_array($rresult);
	$pname = $j[0];

	echo '<br/>';
	echo '<div class="card-event mdl-card mdl-shadow--2dp" style="background: #0088A8; color: #fff;">';
	echo '<br/><div style="float: right;">';
	$d = substr($i['time'], 0, 10);
	if ($d == date('Y-m-d')) {
		echo ' 			今天 ';
	} else if ($d == date('Y-m-d', strtotime('yesterday'))) {
		echo ' 			昨天 ';
	} else {
		echo ' 			' . $d . ' ';
	}
	echo substr($i['time'], strrpos($i['time'], ' '), 6);
	echo '</div>';
	echo '<span><i class="material-icons">&#xE531;</i>' . $dname . '</span>';
	echo '<span><i class="material-icons">&#xE536;</i>' . $pname . '</span/><br/>';
	echo '<span>共乘距離:' . $dis . ' 公尺</span><br/>';
	if ($finished == 1) {
		echo '共乘配對成功';
	} else if ($finished == 2) {
		echo '司機取消共乘';
	} else if ($finished == 3) {
		echo '乘客取消共乘';
	}

	echo '</div><br/>';

	$index++;
}
?>
