<?php
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json; charset=UTF-8');

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data = json_decode($data, true);

	$pid = $data['pid'];

	$sql = "DELETE FROM `history` WHERE `pid`='$pid' AND `did`='00000000' AND `time`='0000-00-00 00:00:00'";
	$result = mysql_query($sql);

	if($result)
		echo 'success';
	else
		echo 'failed';
?>