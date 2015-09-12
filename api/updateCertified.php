 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data = json_decode($data, true);

	$aid = $data['aid'];
	$description = $data['description'];

	$result = 0;

	if(strlen($description) == 0)
	{
		$result = 1;

		$sql = "UPDATE `account` SET `status`= '1' WHERE `aid` = '$aid'";
		$result = mysql_query($sql);
	}

	$ssql = "UPDATE `events` SET `description`= '$description', `result` = '$result', `edited_time` = CURRENT_TIMESTAMP WHERE `aid` = '$aid'";
	$rresult = mysql_query($ssql);
?>