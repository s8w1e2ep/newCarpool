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
	
	
	
	usort($result, 'sort_by_percentage');
	
	$index = 0;
	while($index < sizeof($result))
	{		
		$class_n = 'child'.$index;
		if(sizeof($result[$index]) > 1){
			echo '<table id='.$class_n.' class="mdl-data-table mdl-js-data-table mdl-data-table--selectable mdl-shadow--2dp" style="display: none;">';
			echo '	<tr>';
			echo '		<td><b>司機</b></td>';
			echo '		<td><b>共乘比例</b></td>';
			echo '		<td><b>上車點距離</b></td>';
			echo '		<td><b>下車點距離</b></td>';
			echo '		<td><b>等待時間</b></td>';
			echo '		<td><b>資訊</b></td>';
			echo '	</tr>';
		}
		
		
		for($i = 1; $i < sizeof($result[$index]); $i++){
			$order = $result[$index][$i]['order'];
			$did = $result[$index][$i]['did'];
			$did2 = $result[$index][0]['did'];
			$percentage = $result[$index][$i]['percentage'];
			$on_d = $result[$index][$i]['on_d'];
			$off_d = $result[$index][$i]['off_d'];
			$wait = $result[$index][$i]['wait'];
			
			if($order == 2){
				if($i == sizeof($result[$index]) - 1){
					//get name;
					$sql = "SELECT `name` FROM `account` WHERE `aid` = '$did'";	
					$rresult = mysql_query($sql);
					$name = mysql_fetch_array($rresult);
					$name = $name[0];

					$imgsrc = 'http://graph.facebook.com/'.$did.'/picture?type=large';
					$infos = 'file:///android_asset/www/info.html?data='.$data_str;
					echo '<tr onclick="setDialog2('.$did2.','.$did.');">';// onclick="setDialog('.$did.');">';
					echo '	<td width="35%" align="center"><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
					echo '	<td width="15%" align="center">'.ceil(substr($percentage, 0, 2)).'%</td>';
					echo '	<td width="15%" align="center">'.$on_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.$off_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.round(($wait/60),0).'分鐘</td>';
					echo '	<td width="5%" align="center" onclick="cancel();"><a href='.$infos.'><i class="material-icons">info</i></a></td>';
					echo '</tr>';
				}else if($order = $result[$index][$i + 1]['order'] != 3){
					//get name;
					$sql = "SELECT `name` FROM `account` WHERE `aid` = '$did'";	
					$rresult = mysql_query($sql);
					$name = mysql_fetch_array($rresult);
					$name = $name[0];	
					
					$imgsrc = 'http://graph.facebook.com/'.$did.'/picture?type=large';
					$infos = 'file:///android_asset/www/info.html?data='.$data_str;
					echo '<tr onclick="setDialog2('.$did2.','.$did.');">';// onclick="setDialog('.$did.');">';
					echo '	<td width="35%" align="center"><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
					echo '	<td width="15%" align="center">'.ceil(substr($percentage, 0, 3)).'%</td>';
					echo '	<td width="15%" align="center">'.$on_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.$off_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.round(($wait/60),0).'分鐘</td>';
					echo '	<td width="5%" align="center" onclick="cancel();"><a href='.$infos.'><i class="material-icons">info</i></a></td>';
					echo '</tr>';
				}else{
					//get name;
					$sql = "SELECT `name` FROM `account` WHERE `aid` = '$did'";	
					$rresult = mysql_query($sql);
					$name = mysql_fetch_array($rresult);
					$name = $name[0];	
					
					$imgsrc = 'http://graph.facebook.com/'.$did.'/picture?type=large';
					$infos = 'file:///android_asset/www/info.html?data='.$data_str;
					echo '<tr onclick="showResult3('.$index.','.$i.');">';// onclick="setDialog('.$did.');">';
					echo '	<td width="35%" align="center"><img src="'.$imgsrc.'"alt="pic1" class="avatar"></td>';
					echo '	<td width="15%" align="center">'.ceil(substr($percentage, 0, 2)).'%</td>';
					echo '	<td width="15%" align="center">'.$on_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.$off_d.'公尺</td>';
					echo '	<td width="15%" align="center">'.round(($wait/60),0).'分鐘</td>';
					echo '	<td width="5%" align="center" onclick="cancel();"><a href='.$infos.'><i class="material-icons">info</i></a></td>';
					echo '</tr>';
				}
			}
			
		}
		echo '</table>';
		$index++;
	}

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
