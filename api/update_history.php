<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once '../config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$did = $data['did'];
$pid = $data['pid'];
$finish = $data['finish'];

$sql = "UPDATE `history` SET `finished` = '$finish' WHERE `finished` = 1 AND `pid` = '$pid' AND `did` = '$did'";
$result = mysql_query($sql);

?>