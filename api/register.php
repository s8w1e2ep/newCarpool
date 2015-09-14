 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];
$name = $data['name'];
$phone = $data['phone'];
$gender = $data['gender'];
$regid = $data['regid'];

$sql = "SELECT * FROM `account` WHERE `phone` = '$phone'";
$result = mysql_query($sql);
$num = mysql_num_rows($result);

if ($num == 0) {
	if (strlen($id) > 0 && strlen($gender) > 0) {
		$sql = "INSERT INTO `account`(`aid`, `name`, `gender`, `phone`, `regid`, `cid`) VALUES ('$id','$name','$gender','$phone','$regid','null')";
		$result = mysql_query($sql);

		if ($result) {
			//取得最後編號
			$sql2 = "SELECT MAX(`ticket`) FROM `events`";
			$result2 = mysql_query($sql2);
			$max = mysql_fetch_array($result2);
			$max = $max[0] + 1;

			$sql = "INSERT INTO `events`(`ticket`, `aid`, `description`, `created_time`, `edited_time`, `result`) VALUES ('$max','$id','','CURRENT_TIMESTAMP','0000-00-00 00:00:00','0')";
			$result = mysql_query($sql);
			echo ("success");
		} else {
			echo ("failed");
		}

	} else {
		echo ("invalid");
	}

} else {
	echo ("registed");
}

?>
