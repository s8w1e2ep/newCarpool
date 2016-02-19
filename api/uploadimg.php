 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'config//db_connect.php';
$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];

$picname = $_FILES['file']['name'];
//file => options.fileKey="file";
$picsize = $_FILES['file']['size'];
if ($picname != "") {
	if ($picsize > 2048000) {
		echo '圖片大小不能超過2M';
		exit;
	}
	$type = substr($picname, strrpos($picname, '.' ) + 1);

	if ($type != "gif" && $type != "jpg") {
		echo 'picname: ' . $type;
		echo '圖片格式錯誤!';
		exit;
	}

	$rand = rand(100, 999);
	$pics = date("YmdHis") . $rand . '.' . $type;
	//上傳路徑
	$pic_path = "usr_img/" . $pics;

	//var_dump($_FILES['file']['params']);

	if (move_uploaded_file($_FILES['file']['tmp_name'], $pic_path)) {
		// if (is_uploaded_file($_FILES['file']['tmp_name'])) {
		// 	echo 'failed';
		// } else {
			$sql = "INSERT INTO `userimg`(`aid`,`directory`, `time`) values('$id', '$pic_path', CURRENT_TIMESTAMP)";
			$result = mysql_query($sql);
			echo 'success';
		//}
	}
}
?>
