 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$sql = "SELECT `ticket`, `aid`, `description`, `created_time`, `edited_time`, `result` FROM `events` WHERE `edited_time` != '0000-00-00 00:00:00'";

	$arr = array();
	$result = mysql_query($sql);

	while ($i = mysql_fetch_array($result))
	{
		$temp['ticket'] = $i[0];
		$temp['aid'] = $i[1];
		$temp['description'] = $i[2];
		$temp['created_time'] = $i[3];
		$temp['edited_time'] = $i[4];
		$temp['result'] = $i[5];

		$ssql = "SELECT `name`, `gender`, `phone` FROM `account` WHERE `aid` = '$i[1]'";
		$rresult = mysql_query($ssql);
		$j = mysql_fetch_array($rresult);

		$temp['name'] = $j[0];
		$temp['gender'] = $j[1];
		$temp['phone'] = $j[2];

		array_push($arr, $temp);
	}

	echo json_encode($arr);
?>