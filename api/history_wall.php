 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	
	$data = $_GET['data'];
	$data = json_decode($data, true);
	
	$id = $data['id'];
		
	$sql = "SELECT * FROM `passenger` WHERE `finished`=1 and `aid` = '$id' ORDER BY `pnum` DESC";	
	$result = mysql_query($sql);
	
	$index = 0;
	date_default_timezone_set("Asia/Taipei");
	
	function getAddress($latlng){
		$latlng = json_decode($latlng);
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.($latlng->at).','.($latlng->ng).'&language=zh-TW';
		$response = json_decode(file_get_contents($url), true);
		if($response["status"] == "OK"){
			$n = count($response["results"][0]["address_components"]);
			$address = "";
			$ii = $n - 3;
			for($ii; $ii >= 0; $ii--){
				$address = $address.$response["results"][0]["address_components"][$ii]["long_name"];
			}
			return $address;
		}
	}
	
	while($index < 10 && $i = mysql_fetch_array($result))
	{
		$ssql = "SELECT * FROM `history` WHERE `pnum` = ".$i['pnum'];	
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$dnum = $j['dnum'];
		$dis = $j['distance'];
	
		$ssql = "SELECT `aid` FROM `driver` WHERE `dnum` = ".$dnum;	
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$did = $j[0];
		
		$ssql = "SELECT `name` FROM `account` WHERE `aid` = ".$did;	
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);
		$name = $j[0];
		
		echo '<div class="wallOut">';	
		echo '	<div class="wall-left">';
		echo '		<img id="image" src="http://graph.facebook.com/'.$did.'/picture?type=large" class="avatar" style="padding: 5px">';
		echo '	</div>';
		echo '	<div class="wall-right">';
		echo '		<div style="height: 50%; white-space: nowrap;">';
		echo '			<span style="padding-right: 5px; float: right; color: rgba(0,0,0,0.45);">';
		$d = substr($i['time'], 0, 10);
		if($d == date('Y-m-d'))
			echo ' 			今天 ';
		else if($d == date('Y-m-d', strtotime('yesterday')))
			echo ' 			昨天 ';
		else
			echo ' 			'.$d.' ';
		echo substr($i['time'], strrpos($i['time'],' '), 6);
		echo 			'</span>';
		echo '			<span style="float: left;"><b>司機: '.$name.'</b></span><br/>';
		echo '		</div>';
		echo '		<div style="height: 50%;">';
		echo '			<span style="float: left;">共乘距離: '.$dis.'公尺</span><br/>';
		echo '			<button class="mdl-button mdl-js-button mdl-button--icon" style="color: #00AAAA; float: right;"><i class="material-icons">info</i></button>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">起點:<br/>'.getAddress($i['start']).'號<br/></span>';
		echo '			<span style="text-align: left; color: rgba(0,0,0,0.75);">終點:<br/>'.getAddress($i['end']).'號</span>';
		echo '		</div>';
		echo '	</div>';
		echo '</div>';
		
		$index++;
	}
?>
