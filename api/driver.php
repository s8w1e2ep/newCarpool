 <?php
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=UTF-8');
	
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	
	$data = $_POST['data'];
	$data = json_decode($data, true);
	
	$id = $data['id'];
	$condition = $data['condition'];
	$seat = $condition[0]['seat'];
	$threshold = $condition[0]['rating'];
	$waiting = $condition[0]['waiting'];
	
	$sql = "SELECT `dnum` FROM `driver` WHERE `finished` = '0' and `aid` = '$id'";
	$result = mysql_query($sql);
	$num = mysql_num_rows($result);
	$path = $data['path'];
	
	//取得最後編號
	$sql2 = "SELECT MAX(`dnum`) FROM `driver`";
	$result2 = mysql_query($sql2);
	$max = mysql_fetch_array($result2);
	$max = $max[0] + 1;
	
	if($num > 0)
	{
		$dnum = mysql_fetch_array($result);
		$dnum = $dnum[0];
		$path = json_encode($path);
		$sql = "UPDATE `driver` SET `path` = '$path', `seat` = '$seat', `threshold` = '$threshold', `waiting` = '$waiting', `time` = CURRENT_TIMESTAMP WHERE `dnum` = '$dnum'";
		$result = mysql_query($sql);
		echo "sussess1";
	}
	else{	
		//driver
		if(sizeof($path) > 2)	
		{			
			$path = json_encode($path);
			$sql = "INSERT INTO `driver`(`dnum`, `aid`, `path`, `seat`, `time`, `threshold`, `waiting`, `finished`) VALUES ('$max', '$id', '$path', '$seat', CURRENT_TIMESTAMP, '$threshold', '$waiting', '0')";
			$result = mysql_query($sql);
			echo "sussess2";
		}
	}
?>
