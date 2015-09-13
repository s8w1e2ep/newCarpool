 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);
$id = $data['id'];

$index1 = -1;
$index2 = -1;
$index3 = -1;

if (isset($data['index1'])) {
	$index1 = $data['index1'];
} else if (isset($data['index2'])) {
	$index2 = $data['index2'];
} else if (isset($data['index3'])) {
	$index3 = $data['index3'];
}

if ($index1 != -1 && $index2 != -1 && $index3 != -1) {
	$sql = "SELECT `carpoolpath` FROM `passenger` WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$i = json_decode($i[0]);
	var_dump($i);

} else if ($index1 != -1 && $index2 != -1) {
	$sql = "SELECT `carpoolpath` FROM `passenger` WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$i = json_decode($i[0]);

} else {
	$sql = "SELECT `carpoolpath` FROM `passenger` WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$i = json_decode($i[0]);
	$order1 = $i->order1;
	$order1 = json_encode($order1[$index1]);
	$carpool = '['.$order1.',[],[]]';

	$sql = "UPDATE `passenger` SET `carpoolpath` = '$carpool' WHERE `aid` = '$id' AND `finished` = '0'";
	$result = mysql_query($sql);
}

?>
