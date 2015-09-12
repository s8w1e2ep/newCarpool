 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data = json_decode($data, true);

	$id = $data['id'];
	$curpoint = $data['curpoint'][0];
	$role = $data['role'];

	$curpoint = json_encode($curpoint, true);
	echo $curpoint;

	if ($role == 1) {
		$sql = "SELECT `pnum` FROM `passenger` WHERE `finished` = '0' and `aid` = '$id'";
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);
		
		$pnum = mysql_fetch_array($result);
		$pnum = $pnum[0];
		
		if($num > 0)
		{
			$sql = "UPDATE `passenger` SET `curpoint`= '$curpoint' WHERE `pnum` = '$pnum'";
			$result = mysql_query($sql);
		}

	} else if ($role == 2) {
		$sql = "SELECT `dnum` FROM `driver` WHERE `finished` = '0' and `aid` = '$id'";
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);

		if($num > 0)
		{
			$dnum = mysql_fetch_array($result);
			$dnum = $dnum[0];
		
			$sql = "UPDATE `driver` SET `curpoint`= '$curpoint' WHERE `dnum` = '$dnum'";
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
