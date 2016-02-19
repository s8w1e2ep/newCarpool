 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];

$sql = "SELECT `gender` FROM `account` WHERE `aid` = '$id'";

$result = mysql_query($sql);
$num = mysql_num_rows($result);
$i = mysql_fetch_array($result);
if (strcmp($i[0], "m") == 0) {
	echo '男';
} else if (strcmp($i[0], "f") == 0) {
	echo '女';
}
?>
