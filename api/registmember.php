 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['phone'];		//以電話號碼當作id
$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'];
$gender = $data['gender'];
$password = $data['password'];
$regid = $data['regid'];

$sql = "SELECT * FROM `account` WHERE `phone` = '$phone'";
$result = mysql_query($sql);
$num = mysql_num_rows($result);

//id = Cccddddddd，C=大寫英文字母，c=小寫英文字母，d=0~9
// $id = chr(rand(65, 90)).chr(rand(97, 122)).chr(rand(97, 122));
// for($i = 0; $i < 7; $i++){
// 	$id = $id."".rand(0, 9);
// }

if ($num == 0) {
	if (strlen($id) > 0 && strlen($gender) > 0) {
		$sql = "INSERT INTO `account`(`aid`, `name`, `gender`, `phone`, `regid`, `cid`, `friendlist`, `password`, `email`) VALUES ('$id','$name','$gender','$phone','$regid','null', '[]', '$password', '$email')";
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
	echo ("registered");
}

?>
