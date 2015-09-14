 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data = json_decode($data, true);

	$did = $data['did'];
	$pid = $data['pid'];

	$sql = "UPDATE `driver` SET `finished` = '1' WHERE `aid` = '$did' AND `finished` = '0'";
	$result = mysql_query($sql);

	$sql = "UPDATE `passenger` SET `finished` = '1' WHERE `aid` = '$pid' AND `finished` = '0'";
	$result = mysql_query($sql);

?>
