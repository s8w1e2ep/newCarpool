 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];
$comment = $data['comment'];
$rating = $data['rating'];
$uid = $data['uid'];
$role = $data['role'];

$sql = "SELECT MAX(`wnum`) FROM `wall`";
$result = mysql_query($sql);
$max = mysql_fetch_array($result);
$max = $max[0] + 1;

$sql = "INSERT INTO `wall`(`wnum`, `wid`, `comment`, `time`, `rating`, `uid`) VALUES ('$max','$uid','$comment',CURRENT_TIMESTAMP,'$rating','$id')";
$result = mysql_query($sql);

$sql = "SELECT AVG(`rating`) FROM `wall` WHERE `wid` = '$uid' and `rating` != 0";
$result = mysql_query($sql);
$avg = mysql_fetch_array($result)[0];

$sql = "UPDATE `account` SET `rating` = '$avg' WHERE `aid` = '$uid'";
$result = mysql_query($sql);



if (strcmp($role, "driver") == 0) {
	//get dnum
	$sql = "SELECT `dnum` FROM `driver` WHERE `finished` = 0 AND `aid` = '$id'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$dnum = $i[0];
	//get pnum
	$sql = "SELECT `pnum` FROM `passenger` WHERE `finished` = 0 AND `aid` = '$uid'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$pnum = $i[0];

	$sql = "UPDATE `history` SET `time` = CURRENT_TIMESTAMP WHERE `dnum` = '$dnum' AND `pnum` = '$pnum' AND `time` = '0000-00-00 00:00:00'";
} else if (strcmp($role, "passenger") == 0) {
	//get dnum
	$sql = "SELECT `dnum` FROM `driver` WHERE `finished` = 0 AND `aid` = '$uid'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$dnum = $i[0];
	//get pnum
	$sql = "SELECT `pnum` FROM `passenger` WHERE `finished` = 0 AND `aid` = '$id'";
	$result = mysql_query($sql);
	$i = mysql_fetch_array($result);
	$pnum = $i[0];

	$sql = "UPDATE `history` SET `time` = CURRENT_TIMESTAMP WHERE  `dnum` = '$dnum' AND `pnum` = '$pnum' AND `time` = '0000-00-00 00:00:00'";
}

$result = mysql_query($sql);

if ($result) {
	echo 'success';
} else {
	echo 'failed';
}

?>
