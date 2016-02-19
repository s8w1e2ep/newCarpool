 <?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../config//db_connect.php';
include "PHPMailer-ML/_acp-ml/modules/phpmailer/class.phpmailer.php"; //匯入PHPMailer類別
mb_internal_encoding('UTF-8');

$db = new DB_CONNECT();

$data = $_GET['data'];
$data = json_decode($data, true);

$id = $data['id'];

$sql = "SELECT `email` FROM `account` WHERE `aid` = '$id'";

$result = mysql_query($sql);
$i = mysql_fetch_array($result);
$email = $i[0];

$verification = "s" . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true; // turn on SMTP authentication
$mail->CharSet = "utf8"; //設定郵件編碼

$mail->Username = "letscarpoolapp@gmail.com";
$mail->Password = "CarpoolRoot";

$mail->FromName = "Carpool";
// 寄件者名稱(你自己要顯示的名稱)
$webmaster_email = "letscarpoolapp@gmail.com";
//回覆信件至此信箱

$name = "user"; // 收件者的名稱or暱稱
$mail->From = $webmaster_email;
$mail->AddAddress($email, $name);
$mail->AddReplyTo($webmaster_email, "Squall.f");
$mail->WordWrap = 50; //每50行斷一次行

//$mail->AddAttachment("/XXX.rar");
// 附加檔案可以用這種語法(記得把上一行的//去掉)

$mail->IsHTML(true); // send as HTML
$mail->Subject = "Carpool驗證信"; // 信件標題
$mail->Body = "驗證碼:" . $verification; //信件內容(html版，就是可以有html標籤的如粗體、斜體之類)
//$mail->AltBody = "信件內容";
//信件內容(純文字版)

if (!$mail->Send()) {
	echo "寄信發生錯誤：" . $mail->ErrorInfo;
//如果有錯誤會印出原因
} else {
	echo $verification;
}

// else:
// 	echo "信件發送失敗！"; //寄信失敗顯示的錯誤訊息
// endif;

?>
