<?php
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
	//connection
	require_once 'pathcompare.php';
	require_once '../config//db_connect.php';
	$db = new DB_CONNECT();
	//get data
	$data = $_GET['data'];
	$data = json_decode($data, true);

	$id = $data['id'];					//user id
	$condition = $data['condition'];
	$percentage = $condition[0]['percentage'];	//percentage of the overlapping path
	$distance = $condition[0]['distance'];		//distance of user-accepted
	$waiting = $condition[0]['waiting'];			//waiting time of user-accepted
	$threshold = $condition[0]['rating'];			//threshold of the driver
	$gender = $condition[0]['gender'];			//gender of the driver
	$start = '{"at":"'.$data['start']['at'].'","ng":"'.$data['start']['ng'].'"}';	//乘客上車點
	$end = '{"at":"'.$data['end']['at'].'","ng":"'.$data['end']['ng'].'"}';	//乘客下車點
	$path = $data['path'];
	$passengerPath = array($path);			//path of the passenger
	$totalDistance = $data['total'];			//distance of the path

	$_SAFE = 100;	//safe period
	$_END = 100;	//目前位置與乘客的終點距離(判斷是否要多段共乘)

	function getRating($fid){
		$sql = "SELECT `rating` FROM `account` WHERE `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}

	function getDriverThreshold($fid){
		$sql = "SELECT `threshold` FROM `driver` WHERE `finished` = '0' and `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//calculate the distance of path - use google map api
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
	//calculate the distance of path - use formula
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
	//calculate direct distance - use formula
	function getDirectDistance($origin, $destination){
		$radLat1 = deg2rad($origin["at"]);
		$radLat2 = deg2rad($origin["ng"]);
		$radLng1 = deg2rad($destination["at"]);
		$radLng2 = deg2rad($destination["ng"]);
		$a = $radLat1 - $radLat2;//½n«×®t, ½n«× < 90
		$b = $radLng1 - $radLng2;//¸g«×®t¡A½n«× < 180
		$dis = 2*asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137;
		return round($dis);
	}

	//評價與性別篩選
	$driver_num = 0;
	if(strcmp($gender, "n") == 0){
		$sql = "SELECT `driver`.`aid` FROM `driver`,`account` WHERE `finished` = '0' and `account`.`rating` >= '$threshold' and `account`.`aid`=`driver`.`aid` and `seat` != '0'";
		$result = mysql_query($sql);
		$driver_num = mysql_num_rows($result);
	}else{
		$sql = "SELECT `driver`.`aid` FROM `driver`,`account` WHERE `finished` = '0' and `account`.`rating` >= '$threshold' and `account`.`aid`=`driver`.`aid` and `account`.`gender`= '$gender' and `seat` != '0'";
		$result = mysql_query($sql);
		$driver_num = mysql_num_rows($result);
	}
	$driverId = array();
	$count = 0;
	//紀錄符合評價與性別篩選的司機ID
	for($i=0; $i < $driver_num; $i++){
		$res = mysql_result($result, $i);
		if(getDriverThreshold($res) <= getRating($id)){
			$driverId[$count] = $res;
			$count++;
		}
	}

	$driverPath = array();	//the path of driver
	$driverPos = array();	//the current position of driver
	$driverWait = array();	//the waiting time of driver
	$matchResult = array();	//first match result
	$matchResult2 = array();	//second match result
	$matchResult3 = array();	//third match result
	$list1 = array();		//carpool path1
	$list2 = array();		//carpool path2
	$list3 = array();		//carpool path3
	$matchJson = "";		//match1 json string
	$matchJson2 = "";		//mathc2 json string
	$matchJson3 = "";		//match3 json string
	$result_n = 0;
	$index = 0;

	if($count != 0){
		for($i = 0; $i < $count; $i++){
			$key = $driverId[$i];
			//get the path, current position, waiting time of the driver
			$sql = "SELECT `path`, `curpoint`, `waiting` FROM `driver` WHERE `finished` = '0' and `aid` = '$key'";
			$result = mysql_query($sql);
			$num = mysql_num_rows($result);
			if($num == 1){
				$res = mysql_fetch_array($result);
				array_push($driverPath, $res[0]);
				array_push($driverWait, $res[2]);
				$res = json_decode($res[1], true);
				$res = '{"at":'.(float)($res["at"]).',"ng":'.(float)($res['ng']).'}';
				array_push($driverPos, json_decode($res, true));
			}
		}
		//比對路徑
		$compare_n = count($driverPath);
		$count_failed = 0;
		for($i = 0; $i < $compare_n; $i++){
			$overlap = PathCompare(json_encode($path), $driverPath[$i], true);
			if($overlap != null){
				$carpoolDistance;	//重疊路徑距離
				$match = true;	//共乘1
				$match2 = true;	//共乘2
				$match3 = true;	//共乘3
				$safeDistance;	//紀錄司機與乘客目前距離
				$onDistance;		//紀錄上車點距離(m)
				$offDistance;		//紀錄下車點距離(m)
				$passengerTime;	//乘客到上車點時間(s)
				$driverTime;		//司機到上車點時間(s)
				$carpoolTime;	//共乘路徑1時間
				$carpoolTime2;	//共乘路徑2時間

				//$distRes = getPathDistance($overlap[0][0], $overlap[0][count($overlap[0]) - 1], 'driving');
				$carpoolDistance = getDistance($overlap);
				//計算乘客到上車點距離
				$distRes = getPathDistance($passengerPath[0][0], $overlap[0], 'walking');
				$onDistance = $distRes[0];
				$passengerTime = $distRes[1];
				//計算乘客到下車點距離
				$offn = count($passengerPath[0]) - 1;
				$distArr = array();
				array_push($distArr, $passengerPath[0][$offn], $overlap[count($overlap)-1]);
				$offDistance = getDistance($distArr);
				//計算司機到上車點距離
				$distRes = getPathDistance($driverPos[$i], $overlap[0], 'driving');
				$driverTime = $distRes[1];
				//計算共乘路徑1總時間
				$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
				$carpoolTime = $distRes[1];
				//計算安全區間
				$safeArr = array();
				array_push($safeArr, $driverPos[$i], $passengerPath[0][0]);
				$safeDistance = getDistance($safeArr);
				//計算共乘比例
				$per = round($carpoolDistance / $totalDistance * 100);
				//有誤差，可能會超過100%，需要修正
				if($per > 100)
					$per = 100;

				//判斷是否>安全區間，>=共乘比例，<=上車點距離，<=雙方願意等待時間
				if($safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance
				||($driverTime + $driverWait[$i]*60) < $passengerTime ||($passengerTime + $waiting*60) < $driverTime){
					$match = false;
				}
				$matchResult2[$i][] = array();
				$matchResult3[$i][] = array();
				//§ó·smatchJson¦r¦ê»PmatchResult
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

					//²Ä¤G¦¸´C¦X
					$newOri = $overlap[count($overlap)-1];		//·s°_ÂI
					$check = false;
					$passenger_n = count($passengerPath[0]);
					$newPassengerPath = array();			//·s­¼«È°}¦C
					for($j = 0; $j < $passenger_n; $j++){
						//¥Î²Ä¤@¦¸´C¦Xªº¤U¨®ÂI·í²Ä¤G¦¸´C¦X¤W¨®ÂI
						if(!$check && !strcmp($newOri['at'], $passengerPath[0][$j]['at']) && !strcmp($newOri['ng'], $passengerPath[0][$j]['ng'])){
							$check = true;
						}
						//¬ö¿ý­¼«È¤U¨®ÂI¨ì²×ÂIªº¸ô®|
						if($check){
							array_push($newPassengerPath, $passengerPath[0][$j]);
						}
					}



					//§PÂ_¬O§_»Ý­nÄ~Äò¦@­¼
					if($offDistance > $_END && count($newPassengerPath) != 0){
						//print "match2 start<br>";
						$compare_n = count($driverPath);
						for($j = 0; $j < $compare_n; $j++){
							$overlap = PathCompare(json_encode($newPassengerPath), $driverPath[$j], true);
							if($overlap != null){
								//­pºâ¦@­¼¶ZÂ÷
								$carpoolDistance = getDistance($overlap);
								//­pºâ¤W¨®ÂI»P­¼«È°_ÂI¶ZÂ÷(m)»P­¼«È¨«¸ô®É¶¡(s)
								$distRes = getPathDistance($newPassengerPath[0], $overlap[0], 'walking');
								$onDistance = $distRes[0];
								$passengerTime2 = $distRes[1];
								//­pºâ¤U¨®ÂI»P­¼«È²×ÂI¶ZÂ÷(m)
								$offn = count($newPassengerPath) - 1;
								$distArr = array();
								array_push($distArr, $newPassengerPath[$offn], $overlap[count($overlap)-1]);
								$offDistance = getDistance($distArr);
								//­pºâ¥q¾÷¦æ¾p®É¶¡(s)
								$distRes = getPathDistance($driverPos[$j], $overlap[0], 'driving');
								$driverTime2 = $distRes[1];
								//­pºâ¥q¾÷¦æ¾p¦@­¼¸ô®|2®É¶¡
								$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
								$carpoolTime2 = $distRes[1];
								//­pºâ¦w¥þ¶ZÂ÷
								$safeArr = array();
								array_push($safeArr, $driverPos[$i], $newPassengerPath[0]);
								$safeDistance = getDistance($safeArr);
								//­pºâ¦@­¼¤ñ¨Ò
								$per = round($carpoolDistance / $totalDistance * 100);
								//¦]¬°»~®t¡A¦³¥i¯à¶W¹L100%
								if($per > 100)
									$per = 100;

								//¿z¿ï¥q¾÷Ä@·Nµ¥«Ý®É¶¡»P­¼«ÈÄ@·Nµ¥«Ý®É¶¡
								if(	$safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
									$driverTime2 > $passengerTime + $carpoolTime + $passengerTime2 + $waiting*60 ||
								    $passengerTime + $carpoolTime + $passengerTime2 > $driverTime2 + $driverWait[$j]*60){
									$match2 = false;
								}
								//print ("offd:".$offDistance);
								//§ó·smatchJson¦r¦ê»PmatchResult
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


									//²Ä¤T¦¸´C¦X
									$newOri = $overlap[count($overlap)-1];		//·s°_ÂI
									$check = false;
									$passenger_n = count($newPassengerPath[0]);
									$newPassengerPath2 = array();			//·s­¼«È°}¦C
									for($k = 0; $k < $passenger_n; $k++){
										//¥Î²Ä¤@¦¸´C¦Xªº¤U¨®ÂI·í²Ä¤G¦¸´C¦X¤W¨®ÂI
										if(!$check && !strcmp($newOri['at'], $newPassengerPath[$j]['at']) && !strcmp($newOri['ng'], $newPassengerPath[$j]['ng'])){
											$check = true;
										}
										//¬ö¿ý­¼«È¤U¨®ÂI¨ì²×ÂIªº¸ô®|
										if($check){
											array_push($newPassengerPath2, $newPassengerPath[$k]);
										}
									}

									//§PÂ_¬O§_»Ý­nÄ~Äò¦@­¼
									if($offDistance > $_END && count($newPassengerPath2) != 0){
										//print "match3 start<br>";
										$compare_n = count($driverPath);
										for($k = 0; $k < $compare_n; $k++){
											$overlap = PathCompare(json_encode($newPassengerPath2), $driverPath[$k], true);
											if($overlap != null){
												//­pºâ¦@­¼¶ZÂ÷
												$carpoolDistance = getDistance($overlap[0]);
												//­pºâ¤W¨®ÂI»P­¼«È°_ÂI¶ZÂ÷(m)»P­¼«È¨«¸ô®É¶¡(s)
												$distRes = getPathDistance($newPassengerPath2[0], $overlap[0], 'walking');
												$onDistance = $distRes[0];
												$passengerTime3 = $distRes[1];
												//­pºâ¤U¨®ÂI»P­¼«È²×ÂI¶ZÂ÷(m)
												$offn = count($newPassengerPath2) - 1;
												$distArr = array();
												array_push($distArr, $newPassengerPath2[$offn], $overlap[count($overlap)-1]);
												$offDistance = getDistance($distArr);
												//­pºâ¥q¾÷¦æ¾p®É¶¡(s)
												$distRes = getPathDistance($driverPos[$k], $overlap[0], 'driving');
												$driverTime3 = $distRes[1];
												//­pºâ¦w¥þ¶ZÂ÷
												$safeArr = array();
												array_push($safeArr, $driverPos[$i], $newPassengerPath2[0]);
												$safeDistance = getDistance($safeArr);
												//­pºâ¦@­¼¤ñ¨Ò
												$per = round($carpoolDistance / $totalDistance * 100);
												//¦]¬°»~®t¡A¦³¥i¯à¶W¹L100%
												if($per > 100)
													$per = 100;
												//¿z¿ï¥q¾÷Ä@·Nµ¥«Ý®É¶¡»P­¼«ÈÄ@·Nµ¥«Ý®É¶¡
												if( $safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
													$driverTime3 > $passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 + $waiting*60 ||
													$passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 > $driverTime3 + $driverWait[$j]*60){
													$match3 = false;
												}
												//§ó·smatchJson¦r¦ê»PmatchResult
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

		//Àx¦s»P¦^¶Çµ²ªG
		if($count_failed == $compare_n){
			echo "NoOverlap";
		}else if(count($matchResult) != 0){
			//Âà´«¦@­¼¸ô®|
			$carstr = "";
			$carstr1 = "";
			$carstr2 = "";
			$carstr3 = "";
			$nn = count($matchResult);						//match1¦³´X±ø¸ô®|

			for($i = 0; $i < $nn; $i++){
				//match1 path
				$n = count($matchResult[$i]);				//match1²Äi±øpathªº¸g½n«×¼Æ¶q
				$carstr1 = $carstr1.'[';
				for($j = 0; $j < $n; $j++){
					if($j != $n - 1)
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'},';
					else
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'}]';
				}

				//match2 path
				$i1 = $list1[$i];											//get match1 index

				if($i != $nn - 1)
					$carstr1 = $carstr1.',';

				if(count($list2) != 0 && array_key_exists($i1, $list2)){	//§PÂ_¬O§_¦³match2
					$n2 = count($list2[$i1]);//match2 index ¼Æ¶q
					if($carstr2 === "")
						$carstr2 = $carstr2.'{"index":"'.$i.'","path":[';
					else
						$carstr2 = $carstr2.',{"index":"'.$i.'","path":[';
					for($j = 0; $j < $n2; $j++){
						$carstr2 = $carstr2.'[';
						$j1 = $list2[$i1][$j];								//get match2 index
						$n3 = count($matchResult2[$i1][$j1]);				//match2²Äi1¤¤ªºj1±øpathªº¸g½n«×¼Æ¶q
						for($k = 0; $k < $n3; $k++){
							if($k != $n3 - 1)
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'},';
							else
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'}]';
						}

						if($j != $n2 - 1)
							$carstr2 = $carstr2.',';
						else
							$carstr2 = $carstr2.']}';


						if(count($list3) != 0 && array_key_exists($j1, $list3)){	//§PÂ_¬O§_¦³match3
							$n4 = count($list3[$j1]);
							if($carstr3 === "")							//match3 index ¼Æ¶q
								$carstr3 = $carstr3.'{"index":"'.$i.$j.'","path":[';
							else
								$carstr3 = $carstr3.',{"index":"'.$i.$j.'","path":[';
							for($k = 0; $k < $n4; $k++){
								$carstr3 = $carstr3."[";
								$k1 = $list3[$j1][$k];								//get match3 index
								$n5 = count($matchResult3[$i1][$j1][$k1]);			//match3²Äi1¸Ìªºj1¤¤ªºk1±øpathªº¸g½n«×¼Æ¶q
								for($l = 0; $l < $n5; $l++){
									if($l != $n5 - 1)
										$carstr3 = $carstr3.'{"at":'.$matchResult2[$i1][$j1][$k1][$l]["at"].',"ng":'.$matchResult2[$i1][$j1][$k1][$l]["ng"].'},';
									else
										$carstr3 = $carstr3.'{"at":'.$matchResult2[$i1][$j1][$k1][$l]["at"].',"ng":'.$matchResult2[$i1][$j1][$k1][$l]["ng"].'}]';
								}
								if($k != $n4-1)
									$carstr3.',';
								else
									$carstr3.']}';
							}
						}
					}
				}

				if($i == $nn - 1)
					$carstr = $carstr.'{"order1":['.$carstr1.'],"order2":['.$carstr2.'],"order3":['.$carstr3.']}';

			}

			//print $carstr;

			//§ó·spassenger¸ê®Æ
			$sql = "SELECT `aid` FROM `passenger` WHERE `finished` = '0' and `aid` = '$id'";
			$result = mysql_query($sql);
			$num = mysql_num_rows($result);
			$path = json_encode($path);

			//¨ú±o³Ì«á½s¸¹
			$sql2 = "SELECT MAX(`pnum`) FROM `passenger`";
			$result2 = mysql_query($sql2);
			$max = mysql_fetch_array($result2);
			$max = $max[0] + 1;

			if($num == 1){
				$sql = "UPDATE `passenger` SET `path`='$path', `start`='$start', `end`='$end', `time`=CURRENT_TIMESTAMP, `carpoolpath`='$carstr' WHERE `aid` = '$id' AND `finished` = 0";
				$result = mysql_query($sql);
			}else{
				$sql = "INSERT INTO `passenger`(`pnum`, `aid`, `path`, `start`, `end`, `curpoint`, `time`, `carpoolpath`, `finished`, `getinStatus`, `getoffStatus`) VALUES ('$max', '$id', '$path', '$start', '$end', '$start', CURRENT_TIMESTAMP, '$carstr', '0', '0', '0')";
				$result = mysql_query($sql);
			}
			//¦^¶Çmatchµ²ªG
			$matchJson = '{"id":"'.$id.'","result":'.$matchJson.']}';
			echo $matchJson;
		}else{
			echo "NoMatch";
		}
	}else{
		echo "NoDriver";
	}
?>
