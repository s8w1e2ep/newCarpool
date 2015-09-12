 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];
$role = $data['role'];

//reciever
if ($role == "driver") {
	$sql = "SELECT `dnum` FROM `driver` WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$dnum = $i[0];
	$sql = "SELECT `dnum` FROM `history` WHERE `dnum` = '$dnum' AND `time` = '0000-00-00 00:00:00'";

} else if ($role == "passenger") {
	$sql = "SELECT `pnum` FROM `passenger` WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$pnum = $i[0];
	$sql = "SELECT `pnum` FROM `history` WHERE `pnum` = '$pnum' AND `time` = '0000-00-00 00:00:00'";
	$arr = array();
	$result = mysql_query($sql);
	while ($i = mysql_fetch_array($result)) {
		$sql2 = "SELECT `aid` FROM `driver` WHERE `aid` = '$id' AND `finished` = '0'";
		$temp['id'] = $i[0];
		array_push($arr, $temp);
	}
}

$arr = array();
$result = mysql_query($sql);

while ($i = mysql_fetch_array($result)) {
	$sql2 = "SELECT `aid` FROM `driver` WHERE `aid` = '$id' AND `finished` = '0'";
	$temp['id'] = $i[0];
	array_push($arr, $temp);
}

echo json_encode($arr, true);
?>
