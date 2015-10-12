 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];
$fid = $data['fid'];

$sql = "SELECT `friendlist` FROM `account` WHERE `aid`='$id'";
$result = mysql_query($sql);
$i = mysql_fetch_array($result);
$friendlist = json_decode($i[0]);

array_push($friendlist, $fid);
$friendlist = json_encode($friendlist);

$sql = "UPDATE `account` SET `friendlist` = '$friendlist'  WHERE `aid`='$id'";
$result = mysql_query($sql);

if ($result) {
	echo 'success';
} else {
	echo 'failed';
}

?>
