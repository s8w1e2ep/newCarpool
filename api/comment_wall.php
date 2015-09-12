 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	
	$data = $_GET['data'];
	$data = json_decode($data, true);
	
	$id = $data['id'];
		
	$sql = "SELECT * FROM `wall` WHERE `wid` = '$id' ORDER BY `wnum` DESC";	
	$result = mysql_query($sql);
	
	$index = 0;
	date_default_timezone_set("Asia/Taipei");
	
	while($index < 10 && $i = mysql_fetch_array($result))
	{
		//friend name
		$ssql = "SELECT `name` FROM `account` WHERE `aid` = ".$i['uid'];	
		$rresult = mysql_query($ssql);		
		$j = mysql_fetch_array($rresult);
		$name = $j[0];
		
		if($i['rating'] == 0)
		{
			echo '			<div class="wallOut">';
			echo '				<div class="wall-left">';
			echo '					<img id="image" src="http://graph.facebook.com/'.$i['uid'].'/picture?type=large" class="avatar" style="padding: 5px;">';
			echo '				</div>';
			echo '				<div class="wall-right">';
			echo '					<span style="padding-right: 5px; float: right; color: rgba(0,0,0,0.45);">';
			$d = substr($i['time'], 0, 10);
			if($d == date('Y-m-d'))
				echo ' 					今天 ';
			else if($d == date('Y-m-d', strtotime('yesterday')))
				echo ' 					昨天 ';
			else
				echo ' 					'.$d.' ';
			echo substr($i['time'], strrpos($i['time'],' '), 6);
			echo 					'</span>';
			echo '					<span style="float: left;"><b>'.$name.':</b></span><br/>';
			echo '					<span style="text-align: left; color: rgba(0,0,0,0.75);">'.$i['comment'].'</span>';
			echo '				</div>';
			echo '			</div>';
		}
		
		$index++;
	}
?>
