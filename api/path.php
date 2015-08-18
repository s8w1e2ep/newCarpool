<?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	//connection
	require_once 'pathcompare.php';
	require_once '../../config//db_connect.php';
	$db = new DB_CONNECT();
	//get data
	$data = $_GET['data'];
	$data = json_decode($data, true);

	$id = $data['id'];							//乘客id
	$condition = $data['condition'];			//條件
	$percentage = $condition[0]['percentage'];	//乘客共乘比例門檻
	$distance = $condition[0]['distance'];		//乘客上車距離門檻
	$waiting = $condition[0]['waiting'];		//乘客等待時間門檻
	$threshold = $condition[0]['rating'];		//乘客評價門檻
	$start = '{"latitude":"'.$data['start']['latitude'].'","longitude":"'.$data['start']['longitude'].'"}';	//乘客起點
	$end = '{"latitude":"'.$data['end']['latitude'].'","longitude":"'.$data['end']['longitude'].'"}';		//乘客終點
	$path = $data['path'];						//乘客路徑json
	$passengerPath = array($path);				//乘客路徑陣列
	$totalDistance = $data['total'];			//乘客路徑長

	$_SAFE = 100;								//安全區間(m)
	$_END = 100;								//距離終點距離(m)
	//取乘客自身評價
	function getRating($fid){
		$sql = "SELECT `rating` FROM `account` WHERE `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//取司機門檻
	function getDriverThreshold($fid){
		$sql = "SELECT `threshold` FROM `driver` WHERE `finished` = '0' and `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//計算路徑距離與時間
	function getPathDistance($p1, $p2, $mode){
		if(strcmp($p1["at"], $p2["at"]) != 0 &&  strcmp($p1["ng"], $p2["ng"]) != 0){
			$origin = $p1["at"].','.$p1["ng"];
			$destination = $p2["at"].','.$p2["ng"];
			
			$url = 'http://maps.googleapis.com/maps/api/distancematrix/json?origins='.$origin.'&destinations='.$destination.'&mode='.$mode.'&language=zh-TW';
			//echo $url;
			$response = json_decode(file_get_contents($url), true);
			$result = array();
			if($response["status"] == "OK"){
				if($response["rows"][0]["elements"][0]["status"] == "OK"){
					$distance = $response["rows"][0]["elements"][0]["distance"]["value"];
					$duration = $response["rows"][0]["elements"][0]["duration"]["value"];
					array_push($result, $distance, $duration);
				}else{
					//echo 'GG1';
					array_push($result, 1000, 1800);
				}
			}else{
				//echo 'GG2';
				array_push($result, 1000, 1800);
			}
		}
		else{
			$result = array();
			array_push($result, 0, 0);
		}
		return $result;
	}
	//計算直接距離
	function getDirectDistance($origin, $destination){
		$radLat1 = deg2rad($origin["at"]);
		$radLat2 = deg2rad($origin["ng"]);
		$radLng1 = deg2rad($destination["at"]);
		$radLng2 = deg2rad($destination["ng"]);
		$a = $radLat1 - $radLat2;//緯度差, 緯度 < 90
		$b = $radLng1 - $radLng2;//經度差，緯度 < 180
		$dis = 2*asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137;
		return round($dis);
	}
	//計算路徑直接距離
	function getDistance($path){
		$R = 6378137;
		$l = count($path);
		$sum = 0;

		for ($i = 0; $i < $l - 1; $i++) {
			$pt1 = $path[$i];
			$pt2 = $path[$i + 1];

			$dLat = ($pt2["at"] - $pt1["at"]) * M_PI / 180;
			$dLong = ($pt2["ng"] - $pt1["ng"]) * M_PI / 180;
			$a = sin($dLat / 2) * sin($dLat / 2) +
				 cos(($pt1["at"] * M_PI / 180)) * cos(($pt2["at"] * M_PI / 180)) * sin($dLong / 2) * sin($dLong / 2);
			$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
			$sum += $R * $c;
		}
		return round($sum);
	}
	//比對媒合
	function matchPath($dpath, $ppath, $dwait, $did){
		
	}
	
	
	//司機評價符合乘客門檻
	$sql = "SELECT `driver`.`aid` FROM `driver`,`account` WHERE `finished` = '0' and `account`.`rating` >= '$threshold' and `account`.`aid`=`driver`.`aid` and `seat` != '0'";//篩選乘客評價門檻
	$result = mysql_query($sql);
	$driver_num = mysql_num_rows($result);

	$driverId = array();	//紀錄評價篩選後的司機id
	$count = 0;
	//乘客評價符合司機門檻
	for($i=0; $i < $driver_num; $i++){
		$res = mysql_result($result, $i);
		if(getDriverThreshold($res) <= getRating($id)){
			$driverId[$count] = $res;
			$count++;
		}
	}

	$driverPath = array();		//紀錄評價篩選後的司機path
	$driverPos = array();		//紀錄評價篩選後的司機目前位置
	$driverWait = array();		//紀錄評價篩選後的司機等待時間
	$matchResult = array();		//紀錄媒合成功單段共乘路徑結果
	$matchResult2 = array();	//紀錄媒合成功二段共乘路徑結果
	$matchResult3 = array();	//紀錄媒合成功三段共乘路徑結果
	$list1 = array();			//紀錄媒合成功單段共乘路徑索引
	$list2 = array();			//紀錄媒合成功二段共乘路徑索引
	$list3 = array();			//紀錄媒合成功三段共乘路徑索引
	$matchJson = "";			//紀錄單段共乘媒合成功的json string
	$matchJson2 = "";			//紀錄二段共乘媒合成功的json string
	$matchJson3 = "";			//紀錄三段共乘媒合成功的json string
	$result_n = 0;
	$index = 0;
	//如果有司機id結果
	if($count != 0){
		for($i = 0; $i < $count; $i++){
			$key = $driverId[$i];
			//取得司機路徑、司機目前位置、司機等待時間門檻
			$sql = "SELECT `path`, `curpoint`, `waiting` FROM `driver` WHERE `finished` = '0' and `aid` = '$key'";
			$result = mysql_query($sql);
			$num = mysql_num_rows($result);
			if($num == 1){
				$res = mysql_fetch_array($result);
				array_push($driverPath, $res[0]);
				array_push($driverWait, $res[2]);
				$res = json_decode($res[1], true);
				$res = '{"at":'.(float)($res["latitude"]).',"ng":'.(float)($res['longitude']).'}';
				array_push($driverPos, json_decode($res, true));
			}
		}
		//比對路徑與媒合
		$compare_n = count($driverPath);
		$count_failed = 0;
		for($i = 0; $i < $compare_n; $i++){
			$overlap = PathCompare(json_encode($path), $driverPath[$i], true);
			//有重疊
			if($overlap != null){
				$carpoolDistance;	//共乘距離
				$match = true;		//確認第一段是否通過篩選
				$match2 = true;		//確認第二段是否通過篩選
				$match3 = true;		//確認第三段是否通過篩選
				$safeDistance;		//安全距離
				$onDistance;		//上車點與乘客起點距離(m)
				$offDistance;		//下車點與乘客終點距離(m)
				$passengerTime;		//乘客走路時間(s)
				$driverTime;		//司機行駛時間(s)
				$carpoolTime;		//計算共乘路徑1的行駛時間
				$carpoolTime2;		//計算共乘路徑2的行駛時間
				//計算共乘距離
				//$distRes = getPathDistance($overlap[0][0], $overlap[0][count($overlap[0]) - 1], 'driving');
				$carpoolDistance = getDistance($overlap);
				//計算上車點與乘客起點距離(m)與乘客走路時間(s)
				$distRes = getPathDistance($passengerPath[0][0], $overlap[0], 'walking');
				$onDistance = $distRes[0];
				$passengerTime = $distRes[1];
				//計算下車點與乘客終點距離(m)
				$offn = count($passengerPath[0]) - 1;
				$distArr = array();
				array_push($distArr, $passengerPath[0][$offn], $overlap[count($overlap)-1]);
				$offDistance = getDistance($distArr);
				//計算司機行駛時間(s)
				$distRes = getPathDistance($driverPos[$i], $overlap[0], 'driving');
				$driverTime = $distRes[1];
				//計算司機行駛共乘路徑1時間
				$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
				$carpoolTime = $distRes[1];
				//計算安全距離
				$safeArr = array();
				array_push($safeArr, $driverPos[$i], $passengerPath[0][0]);
				$safeDistance = getDistance($safeArr);
				//計算共乘比例
				$per = round($carpoolDistance / $totalDistance * 100);
				//因為誤差，有可能超過100%
				if($per > 100)
					$per = 100;
				
				//篩選安全區間、共乘比例、上車點與乘客起點距離、司機願意等待時間與乘客願意等待時間
				if($safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance
				||($driverTime + $driverWait[$i]*60) < $passengerTime ||($passengerTime + $waiting*60) < $driverTime){
					$match = false;
				}
				$matchResult2[$i][] = array();
				$matchResult3[$i][] = array();
				//更新matchJson字串與matchResult
				if($match){
					array_push($list1, $i);
					array_push($matchResult, $overlap);
					$result_n++;
					$waitMax = ($driverTime > $passengerTime) ? $driverTime : $passengerTime;
					if($matchJson != ""){
						$matchJson = $matchJson.',[{"order":"'.'1'.
												 '","key":"'.$i.
												 '","did":"'.$driverId[$i].
												 '","percentage":"'.$per.
												 '","on_d":"'.$onDistance.
												 '","off_d":"'.$offDistance.
												 '","wait":"'.$waitMax.'"}';
					}else{
						$matchJson = '[[{"order":"'.'1'.
									  '","key":"'.$i.
									  '","did":"'.$driverId[$i].
									  '","percentage":"'.$per.
									  '","on_d":"'.$onDistance.
									  '","off_d":"'.$offDistance.
									  '","wait":"'.$waitMax.'"}';
					}				
					
					//第二次媒合
					$newOri = $overlap[count($overlap)-1];		//新起點	
					$check = false;
					$passenger_n = count($passengerPath[0]);
					$newPassengerPath = array();			//新乘客陣列
					for($j = 0; $j < $passenger_n; $j++){
						//用第一次媒合的下車點當第二次媒合上車點
						if(!$check && !strcmp($newOri['at'], $passengerPath[0][$j]['at']) && !strcmp($newOri['ng'], $passengerPath[0][$j]['ng'])){
							$check = true;
						}
						//紀錄乘客下車點到終點的路徑
						if($check){
							array_push($newPassengerPath, $passengerPath[0][$j]);
						}
					}
					
					
					
					//判斷是否需要繼續共乘
					if($offDistance > $_END && count($newPassengerPath) != 0){
						//print "match2 start<br>";
						$compare_n = count($driverPath);
						for($j = 0; $j < $compare_n; $j++){
							$overlap = PathCompare(json_encode($newPassengerPath), $driverPath[$j], true);
							if($overlap != null){
								//計算共乘距離
								$carpoolDistance = getDistance($overlap);
								//計算上車點與乘客起點距離(m)與乘客走路時間(s)
								$distRes = getPathDistance($newPassengerPath[0], $overlap[0], 'walking');
								$onDistance = $distRes[0];
								$passengerTime2 = $distRes[1];
								//計算下車點與乘客終點距離(m)
								$offn = count($newPassengerPath) - 1;
								$distArr = array();
								array_push($distArr, $newPassengerPath[$offn], $overlap[count($overlap)-1]);
								$offDistance = getDistance($distArr);
								//計算司機行駛時間(s)
								$distRes = getPathDistance($driverPos[$j], $overlap[0], 'driving');
								$driverTime2 = $distRes[1];
								//計算司機行駛共乘路徑2時間
								$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
								$carpoolTime2 = $distRes[1];
								//計算安全距離
								$safeArr = array();
								array_push($safeArr, $driverPos[$i], $newPassengerPath[0]);
								$safeDistance = getDistance($safeArr);
								//計算共乘比例
								$per = round($carpoolDistance / $totalDistance * 100);
								//因為誤差，有可能超過100%
								if($per > 100)
									$per = 100;
								
								//篩選司機願意等待時間與乘客願意等待時間
								if(	$safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
									$driverTime2 > $passengerTime + $carpoolTime + $passengerTime2 + $waiting*60 ||
								    $passengerTime + $carpoolTime + $passengerTime2 > $driverTime2 + $driverWait[$j]*60){
									$match2 = false;
								}
								//print ("offd:".$offDistance);
								//更新matchJson字串與matchResult
								$matchResult3[$i][$j][] = array();
								if($match2){
									if(!array_key_exists($i, $list2))
										$list2[$i] = array();
									array_push($list2[$i], $j);
									$matchResult2[$i][$j] = $overlap;
									$waitMax = ($driverTime2 > $passengerTime2) ? $driverTime2 : $passengerTime2;
									
									$matchJson = $matchJson.',{"order":"'.'2'.
															'","key":"'.$j.
															'","did":"'.$driverId[$j].
															'","percentage":"'.$per.
															'","on_d":"'.$onDistance.
															'","off_d":"'.$offDistance.
															'","wait":"'.$waitMax.'"}';
									
									
									//第三次媒合
									$newOri = $overlap[count($overlap)-1];		//新起點	
									$check = false;
									$passenger_n = count($newPassengerPath[0]);
									$newPassengerPath2 = array();			//新乘客陣列
									for($k = 0; $k < $passenger_n; $k++){
										//用第一次媒合的下車點當第二次媒合上車點
										if(!$check && !strcmp($newOri['at'], $newPassengerPath[$j]['at']) && !strcmp($newOri['ng'], $newPassengerPath[$j]['ng'])){
											$check = true;
										}
										//紀錄乘客下車點到終點的路徑
										if($check){
											array_push($newPassengerPath2, $newPassengerPath[$k]);
										}
									}

									//判斷是否需要繼續共乘
									if($offDistance > $_END && count($newPassengerPath2) != 0){
										//print "match3 start<br>";
										$compare_n = count($driverPath);
										for($k = 0; $k < $compare_n; $k++){
											$overlap = PathCompare(json_encode($newPassengerPath2), $driverPath[$k], true);
											if($overlap != null){
												//計算共乘距離
												$carpoolDistance = getDistance($overlap[0]);
												//計算上車點與乘客起點距離(m)與乘客走路時間(s)
												$distRes = getPathDistance($newPassengerPath2[0], $overlap[0], 'walking');
												$onDistance = $distRes[0];
												$passengerTime3 = $distRes[1];
												//計算下車點與乘客終點距離(m)
												$offn = count($newPassengerPath2) - 1;
												$distArr = array();
												array_push($distArr, $newPassengerPath2[$offn], $overlap[count($overlap)-1]);
												$offDistance = getDistance($distArr);
												//計算司機行駛時間(s)
												$distRes = getPathDistance($driverPos[$k], $overlap[0], 'driving');
												$driverTime3 = $distRes[1];
												//計算安全距離
												$safeArr = array();
												array_push($safeArr, $driverPos[$i], $newPassengerPath2[0]);
												$safeDistance = getDistance($safeArr);
												//計算共乘比例
												$per = round($carpoolDistance / $totalDistance * 100);
												//因為誤差，有可能超過100%
												if($per > 100)
													$per = 100;
												//篩選司機願意等待時間與乘客願意等待時間
												if( $safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
													$driverTime3 > $passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 + $waiting*60 ||
													$passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 > $driverTime3 + $driverWait[$j]*60){
													$match3 = false;
												}
												//更新matchJson字串與matchResult
												if($match3){
													if(!array_key_exists($j, $list3))
														$list3[$j] = array();
													array_push($list3[$j], $k);
													$matchResult3[$i][$j][$k] = $overlap;
													$waitMax = ($driverTime2 > $passengerTime2) ? $driverTime2 : $passengerTime2;
													
													$matchJson = $matchJson.',{"order":"'.'3'.
																			'","key":"'.$k.
																			'","did":"'.$driverId[$j].
																			'","percentage":"'.$per.
																			'","on_d":"'.$onDistance.
																			'","off_d":"'.$offDistance.
																			'","wait":"'.$waitMax.'"}';
												}
											}
										}
										//print "match3 end<br>";
										if(!$match2)
											$matchJson = $matchJson.']';
									}
								}	
							}
						}
						//print "match2 end<br>";
						$matchJson = $matchJson.']';
					}else{
						$matchJson = $matchJson.']';
					}
				}			
			}else{
				$count_failed++;
			}
		}
		//print "match1 end<br>";
		//var_dump($list1);
		//var_dump($list2);
		//var_dump($list3);
		//var_dump($matchResult3);
		//print(count($matchResult));
		//var_dump($matchResult2);
		//print(count($matchResult3));
		
		//儲存與回傳結果
		if($count_failed == $compare_n){
			echo "NoOverlap";
		}else if(count($matchResult) != 0){
			//轉換共乘路徑
			$carstr = "[";
			$carstr1 = "";
			$carstr2 = "";
			$carstr3 = "";
			$nn = count($matchResult);						//match1有幾條路徑

			for($i = 0; $i < $nn; $i++){
				//match1 path
				$n = count($matchResult[$i]);				//match1第i條path的經緯度數量
				$carstr1 = $carstr1."[";
				for($j = 0; $j < $n; $j++){
					if($j != $n - 1)
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'},';
					else
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'}]';
				}
				
				//match2 path
				$i1 = $list1[$i];											//get match1 index
				
				if(count($list2) != 0 && array_key_exists($i1, $list2)){	//判斷是否有match2
					$n2 = count($list2[$i1]);								//match2 index 數量
					for($j = 0; $j < $n2; $j++){				
						$carstr2 = $carstr2."[";
						$j1 = $list2[$i1][$j];								//get match2 index
						$n3 = count($matchResult2[$i1][$j1]);				//match2第i1中的j1條path的經緯度數量	
						for($k = 0; $k < $n3; $k++){
							if($k != $n3 - 1)
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'},';
							else
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'}]';
						}
						
						if(count($list3) != 0 && array_key_exists($j1, $list3)){	//判斷是否有match3					
							$n4 = count($list3[$j1]);								//match3 index 數量	
							for($k = 0; $k < $n4; $k++){
								$carstr3 = $carstr3."[";
								$k1 = $list3[$j1][$k];								//get match3 index
								$n5 = count($matchResult3[$i1][$j1][$k1]);			//match3第i1裡的j1中的k1條path的經緯度數量
								for($l = 0; $l < $n5; $l++){
									if($l != $n5 - 1)
										$carstr3 = $carstr3.'{"at":'.$matchResult2[$i1][$j1][$k1][$l]["at"].',"ng":'.$matchResult2[$i1][$j1][$k1][$l]["ng"].'},';
									else
										$carstr3 = $carstr3.'{"at":'.$matchResult2[$i1][$j1][$k1][$l]["at"].',"ng":'.$matchResult2[$i1][$j1][$k1][$l]["ng"].'}]';
								}
								if($i != $nn - 1 && $j == $n2 - 1 && $k == $n4 - 1)
									$carstr = $carstr."[".$carstr1.",".$carstr2.",".$carstr3."]]";
								else
									$carstr = $carstr."[".$carstr1.",".$carstr2.",".$carstr3."],";
								
								$carstr3 = "";
							}
						}else{
							if($i == $nn - 1 && $j == $n2 - 1)
								$carstr = $carstr."[".$carstr1.",".$carstr2."]]";
							else
								$carstr = $carstr."[".$carstr1.",".$carstr2."],";
						}
						$carstr2 = "";
					}
				}else{
					if($i != $nn - 1)
						$carstr = $carstr."[".$carstr1."],";
					else
						$carstr = $carstr."[".$carstr1."]]";
				}
				$carstr1 = "";
			}
			
			print $carstr;
			
			//更新requester資料
			$sql = "SELECT `aid` FROM `passenger` WHERE `finished` = '0' and `aid` = '$id'";
			$result = mysql_query($sql);
			$num = mysql_num_rows($result);
			$path = json_encode($path);
			
			//取得最後編號
			$sql2 = "SELECT MAX(`pnum`) FROM `passenger`";
			$result2 = mysql_query($sql);
			$max = mysql_fetch_array($result);
			$max = $max[0] + 1;
			if($num == 1){
				$sql = "UPDATE `passenger` SET `path`='$path', `start`='$start', `end`='$end', `time`=CURRENT_TIMESTAMP, `carpoolpath`='$carstr' WHERE `aid` = '$id'";
				$result = mysql_query($sql);
			}else{
				$sql = "INSERT INTO `passenger`(`pnum`, `aid`, `path`, `start`, `end`, `curpoint`, `time`, `carpoolpath`, `finished`, `getinStatus`, `getoffStatus`) VALUES ('$max', '$id', '$path', '$start', '$end', '$start', CURRENT_TIMESTAMP, '$carstr', '0', '0', '0')";
				$result = mysql_query($sql);
			}
			//回傳match結果
			$matchJson = '{"id":"'.$id.'","result":'.$matchJson.']}';
			echo $matchJson;
		}else{
			echo "NoMatch";
		}
	}else{
		echo "NoDriver";
	}
?>
