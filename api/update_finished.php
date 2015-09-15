 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$did = -1;
$pid = -1;

if (isset($data['did'])) {
	$did = $data['did'];
	$sql = "UPDATE `driver` SET `finished` = '1' WHERE `aid` = '$did' AND `finished` = '0'";
	$result = mysql_query($sql);
} else if (isset($data['pid'])) {
	$pid = $data['pid'];
	$sql = "UPDATE `passenger` SET `finished` = '1' WHERE `aid` = '$pid' AND `finished` = '0'";
	$result = mysql_query($sql);
}

?>
