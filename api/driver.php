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
	
	$sql = "SELECT `aid` FROM `driver` WHERE `finished` = '0' and`aid` = '$id'";
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
		$path = json_encode($path);
		$sql = "UPDATE `driver` SET `path` = '$path', `seat` = '$seat', `time` = CURRENT_TIMESTAMP, `threshold` = '$threshold', `waiting` = '$waiting' WHERE `aid` = '$id'";
		$result = mysql_query($sql);
		echo "sussess1";
	}
	else
	{	
		//driver
		if(sizeof($path) > 2)	
		{			
			$path = json_encode($path);
			$sql = "INSERT INTO `driver`(`dnum`, `aid`, `path`, `seat`, `time`, `threshold`, `waiting`, `finished`) VALUES ('$max', '$id', '$path', '$seat', CURRENT_TIMESTAMP, '$threshold', '$waiting', '0')";
			$result = mysql_query($sql);
			echo "sussess2";
		}
	}
	/*
	$id = $data['id'];
	$phone = $data['phone'];
	$gender = $data['gender'];
	$name = $data['name'];
	
	if(strlen($id) > 0 && strlen($gender) > 0)
	{
		$sql = 'SELECT `aid` FROM `account` WHERE `aid` = '$id'';
		
		$result = mysql_query($sql);
		$num = mysql_num_rows($result);

		if($num > 0)
			echo('failed');
		else
		{
			$sql = 'INSERT INTO `account`(`aid`, `name`, `gender`, `phone`, `cid`) VALUES ('$id','$name','$gender','$phone','null')';		
			$result = mysql_query($sql);
			
			echo('success');
		}
	}
	else ('failed');
	*/
?>
