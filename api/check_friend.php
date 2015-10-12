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

	$index = 0;
	$fnum = count($friendlist);

	if($fnum == 0)
		echo "success";
	else{
		$check = true;
		for($ii = 0; $ii < $fnum; $ii++){
			if(strcmp($fid, $friendlist[$ii]) == 0)
				$check = false;
		}
		if($check)
			echo "success";
		else
			echo "failed";
	}

?>
