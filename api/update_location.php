 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data = json_decode($data, true);

	$id = $data['id'];
	$curpoint = $data['curpoint'][0];
	$role = $data['role'];

	$curpoint = json_encode($curpoint, true);
	echo $curpoint;

	if ($role == 1) {
		$sql = "SELECT `aid` FROM `requester` WHERE `aid` = '$id'";
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);
		if($num > 0)
		{
			$sql = "UPDATE `passenger` SET `curpoint`= '$curpoint' WHERE `aid` = '$id' and `finished` = '0'";
			$result = mysql_query($sql);
		}
		// else
		// {
		// 	$sql = "INSERT INTO `requester` (`aid`, `curpoint`) VALUES ('$id', '$curpoint')";
		// 	$result = mysql_query($sql);
		// }
	} else if ($role == 2) {
		$sql = "SELECT `aid` FROM `receiver` WHERE `aid` = '$id'";
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);

		if($num > 0)
		{
			$sql = "UPDATE `driver` SET `curpoint`= '$curpoint' WHERE `aid` = '$id' and `finished` = '0'";
			$result = mysql_query($sql);
			$success = true;
		}
		else
		{
			//取得最後編號
			$sql2 = "SELECT MAX(`dnum`) FROM `driver`";
			$result2 = mysql_query($sql2);
			$max = mysql_fetch_array($result2);
			$max = $max[0] + 1;

			$sql = "INSERT INTO `driver` (`dnum`, `aid`, `curpoint`) VALUES ('$max', '$id', '$curpoint')";
			$result = mysql_query($sql);
			$success = true;
		}
	}
?>
