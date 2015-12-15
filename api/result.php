 <?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");

	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();

	$data = $_GET['data'];
	$data_str = $data;
	$data = json_decode($data, true);

	$id = $data['id'];
	$result = $data['result'];

	// echo '<table  class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">';
	// echo '	<tr>';
	// echo '		<th><b>司機</b></th>';
	// echo '		<th><b>共乘比例</b></th>';
	// echo '		<th><b>上車點距離</b></th>';
	// echo '		<th><b>下車點距離</b></th>';
	// echo '		<th><b>等待時間</b></th>';
	// echo '		<th><b>資訊</b></th>';
	// echo '	</tr>';
	// echo '<tbody>';

	usort($result, 'sort_by_percentage');

	$index = 0;
	while($index < sizeof($result))
	{
		$did = $result[$index][0]['did'];
		$percentage = $result[$index][0]['percentage'];
		$on_d = $result[$index][0]['on_d'];
		$off_d = $result[$index][0]['off_d'];
		$wait = $result[$index][0]['wait'];

		//get name;
		$sql = "SELECT `name` FROM `account` WHERE `aid` = '$did'";
		$rresult = mysql_query($sql);
		$name = mysql_fetch_array($rresult);
		$name = $name[0];

		$imgsrc = 'http://graph.facebook.com/'.$did.'/picture?type=large';
		$infos = 'file:///android_asset/www/info.html?data='.$data_str;

		if(sizeof($result[$index]) > 1){
			echo '<table  class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">';
			echo '<tbody value="'.$index.'" onclick="showResult2('.$index.');">';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">司機'.($index+1).'</td>';
			echo '<td><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">司機姓名</td>';
			echo '<td>'.$name.'</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">共乘比例</td>';
			echo '<td>'.ceil(substr($percentage, 0, 2)).'%</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">上車點距離</td>';
			echo '<td>'.$on_d.'公尺</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">下車點距離</td>';
			echo '<td>'.$off_d.'公尺</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">等待時間</td>';
			echo '<td>'.round(($wait/60),0).'分鐘</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			echo '<br/>';

			// echo '<tr value="'.$index.'" onclick="showResult2('.$index.');">';// onclick="setDialog('.$did.');">';
			// // echo '	<td width="35%" align="center"><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
			// echo '	<td ><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
			// echo '	<td >'.ceil(substr($percentage, 0, 2)).'%</td>';
			// echo '	<td >'.$on_d.'公尺</td>';
			// echo '	<td >'.$off_d.'公尺</td>';
			// echo '	<td >'.round(($wait/60),0).'分鐘</td>';
			// echo '	<td  onclick="cancel();"><a href='.$infos.'><i class="material-icons">info</i></a></td>';
			// echo '</tr>';
		}else{
			echo '<table  class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">';
			echo '<tbody value="'.$index.'" onclick="setDialog('.$did.','.$index.');">';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">司機'.($index+1).'</td>';
			echo '<td><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">司機姓名</td>';
			echo '<td>'.$name.'</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">共乘比例</td>';
			echo '<td>'.ceil(substr($percentage, 0, 2)).'%</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">上車點距離</td>';
			echo '<td>'.$on_d.'公尺</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">下車點距離</td>';
			echo '<td>'.$off_d.'公尺</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td class="mdl-data-table__cell--non-numeric">等待時間</td>';
			echo '<td>'.round(($wait/60),0).'分鐘</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			echo '<br/>';

			// echo '<tr value="'.$index.'" onclick="setDialog('.$did.','.$index.');">';// onclick="setDialog('.$did.');">';
			// echo '	<td ><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
			// echo '	<td >'.ceil(substr($percentage, 0, 3)).'%</td>';
			// echo '	<td >'.$on_d.'公尺</td>';
			// echo '	<td >'.$off_d.'公尺</td>';
			// echo '	<td >'.round(($wait/60),0).'分鐘</td>';
			// echo '	<td  onclick="cancel();"><a href='.$infos.'><i class="material-icons">info</i></a></td>';
			// echo '</tr>';
		}
		$index++;
	}
	// echo '</tbody>';
	// echo '</table>';

	function sort_by_percentage($a, $b)
	{
		if($b[0]['percentage'] - $a[0]['percentage'] != 0)
		{
			return $b[0]['percentage'] - $a[0]['percentage'];
		}
		else
		{
			if($a[0]['on_d'] - $b[0]['on_d'] != 0)
				return $a[0]['on_d'] - $b[0]['on_d'];
			else
			{
				if($a[0]['off_d'] - $b[0]['off_d'] != 0)
					return $a[0]['off_d'] - $b[0]['off_d'];
				else
				{
					if($a[0]['wait'] - $b[0]['wait'] != 0)
						return $a[0]['wait'] - $b[0]['wait'];
				}
			}
		}
	}
?>
