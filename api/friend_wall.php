 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	
	$data = $_GET['data'];
	$data = json_decode($data, true);
	
	$id = $data['id'];
	
	$sql = "SELECT `friendlist` FROM `account` WHERE `aid` = '$id'";	
	$result = mysql_query($sql);		
	$i = mysql_fetch_array($result);
	$friendlist = json_decode($i[0]);
	
	$index = 0;
	$fnum = count($friendlist);
	
	if($fnum != 0)
		echo '<table class="mdl-data-table mdl-js-data-table mdl-data-table--selectable mdl-shadow--2dp">';
	
	while($index < $fnum)
	{		
		$fid = $friendlist[$index];
		$ssql = "SELECT `name` FROM `account` WHERE `aid` = '$fid'";
		$rresult = mysql_query($ssql);		
		$j = mysql_fetch_array($rresult);
		$name = $j[0];
		
		$temp = 'data={"id":"'.$fid.'"}'
		$wall_html = 'file:///android_asset/www/wall.html?'.$temp;
	
		echo '			<tr>';
		echo '				<td><a href='.$wall_html.'><img id="image" src="http://graph.facebook.com/'.$fid.'/picture" alt="pic1" class="img-circle"></td>';
		echo '				<tr><h3 id="name">'.$name.'</h3></tr>';
		echo '			</tr>';
		
		$index++;
	}
	if($fnum != 0)
		echo '</table>';
?>
