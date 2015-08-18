 <?php
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=UTF-8');
	
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	
	$data = $_GET['data'];
	$data = json_decode($data, true);
	
	$id = $data['id'];
	//var_dump($data['did']);
	//var_dump($data['dist']);
	
	//取得最後編號
	$sql = "SELECT MAX(`hid`) FROM `history`";
	$result = mysql_query($sql);
	$max = mysql_fetch_array($result);
	$max = $max[0] + 1;
	//新增
	$sql = "INSERT INTO `history` (`hid`, `did`, `pid`, `distance`, `time`) VALUES ('$max', '00000000', '$id', 0, '0000-00-00 00:00:00')";
	$result = mysql_query($sql);
?>