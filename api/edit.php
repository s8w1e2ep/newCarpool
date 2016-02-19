 <?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];
$name = $data['name'];
$phone = $data['phone'];
$gender = $data['gender'];

$sql = "SELECT `aid` FROM `account` WHERE `aid` = '$id'";
$result = mysql_query($sql);
$num = mysql_num_rows($result);

if ($num == 1) {
	$sql = "UPDATE `account` SET `name` = '$name', `phone` = '$phone' , `gender` = '$gender' WHERE `aid` = '$id'";
	$result = mysql_query($sql);
}

?>
