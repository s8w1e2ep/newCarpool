 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];

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

	$ssql = "SELECT * FROM `passenger` WHERE `pnum` = " . $i['pnum'] and `finished` == 1;
	$rresult = mysql_query($ssql);
	$j = mysql_fetch_array($rresult);

	$ssql2 = "SELECT * FROM `driver` WHERE `dnum` = " . $i['dnum'] and `finished` == 1;
	$rresult2 = mysql_query($ssql2);
	$j2 = mysql_fetch_array($rresult2);

	if ($j['aid'] === $id) {
		//passenger
		$passenger = $j;
		$dnum = $i['dnum'];
		$dis = $i['distance'];
		//get driver id
		$ssql = "SELECT `aid` FROM `driver` WHERE `dnum` = " . $dnum;
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$did = $j[0];
		//get driver name
		$ssql = "SELECT `name` FROM `account` WHERE `aid` = " . $did;
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$name = $j[0];

		echo '<div class="wallOut">';
		echo '	<div class="wall-left">';
		echo '		<img id="image" src="http://graph.facebook.com/' . $did . '/picture?type=large" class="avatar" style="padding: 5px">';
		echo '	</div>';
		echo '	<div class="wall-right">';
		echo '		<div style="height: 50%; white-space: nowrap;">';
		echo '			<span style="padding-right: 5px; float: right; color: rgba(0,0,0,0.45);">';
		$d = substr($passenger['time'], 0, 10);
		if ($d == date('Y-m-d')) {
			echo ' 			今天 ';
		} else if ($d == date('Y-m-d', strtotime('yesterday'))) {
			echo ' 			昨天 ';
		} else {
			echo ' 			' . $d . ' ';
		}

		echo substr($passenger['time'], strrpos($passenger['time'], ' '), 6);
		echo '</span>';
		echo '			<span style="float: left;"><b>司機: ' . $name . '</b></span><br/>';
		echo '		</div>';
		echo '		<div style="height: 50%;">';
		echo '			<span style="float: left;">共乘距離: ' . $dis . '公尺</span><br/>';
		echo '			<button class="mdl-button mdl-js-button mdl-button--icon" style="color: #00AAAA; float: right;"><i class="material-icons">info</i></button>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">起點:<br/>' . getAddress($passenger['start']) . '號<br/></span>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">終點:<br/>' . getAddress($passenger['end']) . '號</span>';
		echo '		</div>';
		echo '	</div>';
		echo '</div>';

		$index++;

	} else if ($j2['aid'] === $id) {
		//driver
		$path = json_decode($j2['path']);
		$pnum = $i['pnum'];
		$dis = $i['distance'];
		//get passenger id
		$ssql = "SELECT `aid` FROM `passenger` WHERE `pnum` = " . $pnum;
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$pid = $j[0];
		//get passenger name
		$ssql = "SELECT `name` FROM `account` WHERE `aid` = " . $pid;
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$name = $j[0];

		echo '<div class="wallOut">';
		echo '	<div class="wall-left">';
		echo '		<img id="image" src="http://graph.facebook.com/' . $pid . '/picture?type=large" class="avatar" style="padding: 5px">';
		echo '	</div>';
		echo '	<div class="wall-right">';
		echo '		<div style="height: 50%; white-space: nowrap;">';
		echo '			<span style="padding-right: 5px; float: right; color: rgba(0,0,0,0.45);">';
		$d = substr($j2['time'], 0, 10);
		if ($d == date('Y-m-d')) {
			echo ' 			今天 ';
		} else if ($d == date('Y-m-d', strtotime('yesterday'))) {
			echo ' 			昨天 ';
		} else {
			echo ' 			' . $d . ' ';
		}

		echo substr($j2['time'], strrpos($j2['time'], ' '), 6);
		echo '</span>';
		echo '			<span style="float: left;"><b>乘客: ' . $name . '</b></span><br/>';
		echo '		</div>';
		echo '		<div style="height: 50%;">';
		echo '			<span style="float: left;">共乘距離: ' . $dis . '公尺</span><br/>';
		echo '			<button class="mdl-button mdl-js-button mdl-button--icon" style="color: #00AAAA; float: right;"><i class="material-icons">info</i></button>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">起點:<br/>' . getAddress(json_encode($path[0])) . '號<br/></span>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">終點:<br/>' . getAddress(json_encode($path[count($path) - 1])) . '號</span>';
		echo '		</div>';
		echo '	</div>';
		echo '</div>';

		$index++;

	}
}
?>
