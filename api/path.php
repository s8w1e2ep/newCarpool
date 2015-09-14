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

	$id = $data['id'];							//­¼«Èid
	$condition = $data['condition'];			//±ø¥ó
	$percentage = $condition[0]['percentage'];	//­¼«È¦@­¼¤ñ¨ÒªùÂe
	$distance = $condition[0]['distance'];		//­¼«È¤W¨®¶ZÂ÷ªùÂe
	$waiting = $condition[0]['waiting'];		//­¼«Èµ¥«Ý®É¶¡ªùÂe
	$threshold = $condition[0]['rating'];		//­¼«Èµû»ùªùÂe
	$start = '{"at":"'.$data['start']['at'].'","ng":"'.$data['start']['ng'].'"}';	//­¼«È°_ÂI
	$end = '{"at":"'.$data['end']['at'].'","ng":"'.$data['end']['ng'].'"}';		//­¼«È²×ÂI
	$path = $data['path'];						//­¼«È¸ô®|json
	$passengerPath = array($path);				//­¼«È¸ô®|°}¦C
	$totalDistance = $data['total'];			//­¼«È¸ô®|ªø

	$_SAFE = 100;								//¦w¥þ°Ï¶¡(m)
	$_END = 100;								//¶ZÂ÷²×ÂI¶ZÂ÷(m)
	//¨ú­¼«È¦Û¨­µû»ù
	function getRating($fid){
		$sql = "SELECT `rating` FROM `account` WHERE `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//¨ú¥q¾÷ªùÂe
	function getDriverThreshold($fid){
		$sql = "SELECT `threshold` FROM `driver` WHERE `finished` = '0' and `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//­pºâ¸ô®|¶ZÂ÷»P®É¶¡
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
	//­pºâª½±µ¶ZÂ÷
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
	//­pºâ¸ô®|ª½±µ¶ZÂ÷
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
	//¤ñ¹ï´C¦X
	function matchPath($dpath, $ppath, $dwait, $did){

	}


	//¥q¾÷µû»ù²Å¦X­¼«ÈªùÂe
	$sql = "SELECT `driver`.`aid` FROM `driver`,`account` WHERE `finished` = '0' and `account`.`rating` >= '$threshold' and `account`.`aid`=`driver`.`aid` and `seat` != '0'";//¿z¿ï­¼«Èµû»ùªùÂe
	$result = mysql_query($sql);
	$driver_num = mysql_num_rows($result);

	$driverId = array();	//¬ö¿ýµû»ù¿z¿ï«áªº¥q¾÷id
	$count = 0;
	//­¼«Èµû»ù²Å¦X¥q¾÷ªùÂe
	for($i=0; $i < $driver_num; $i++){
		$res = mysql_result($result, $i);
		if(getDriverThreshold($res) <= getRating($id)){
			$driverId[$count] = $res;
			$count++;
		}
	}

	$driverPath = array();		//¬ö¿ýµû»ù¿z¿ï«áªº¥q¾÷path
	$driverPos = array();		//¬ö¿ýµû»ù¿z¿ï«áªº¥q¾÷¥Ø«e¦ì¸m
	$driverWait = array();		//¬ö¿ýµû»ù¿z¿ï«áªº¥q¾÷µ¥«Ý®É¶¡
	$matchResult = array();		//¬ö¿ý´C¦X¦¨¥\³æ¬q¦@­¼¸ô®|µ²ªG
	$matchResult2 = array();	//¬ö¿ý´C¦X¦¨¥\¤G¬q¦@­¼¸ô®|µ²ªG
	$matchResult3 = array();	//¬ö¿ý´C¦X¦¨¥\¤T¬q¦@­¼¸ô®|µ²ªG
	$list1 = array();			//¬ö¿ý´C¦X¦¨¥\³æ¬q¦@­¼¸ô®|¯Á¤Þ
	$list2 = array();			//¬ö¿ý´C¦X¦¨¥\¤G¬q¦@­¼¸ô®|¯Á¤Þ
	$list3 = array();			//¬ö¿ý´C¦X¦¨¥\¤T¬q¦@­¼¸ô®|¯Á¤Þ
	$matchJson = "";			//¬ö¿ý³æ¬q¦@­¼´C¦X¦¨¥\ªºjson string
	$matchJson2 = "";			//¬ö¿ý¤G¬q¦@­¼´C¦X¦¨¥\ªºjson string
	$matchJson3 = "";			//¬ö¿ý¤T¬q¦@­¼´C¦X¦¨¥\ªºjson string
	$result_n = 0;
	$index = 0;
	//¦pªG¦³¥q¾÷idµ²ªG
	if($count != 0){
		for($i = 0; $i < $count; $i++){
			$key = $driverId[$i];
			//¨ú±o¥q¾÷¸ô®|¡B¥q¾÷¥Ø«e¦ì¸m¡B¥q¾÷µ¥«Ý®É¶¡ªùÂe
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
		//¤ñ¹ï¸ô®|»P´C¦X
		$compare_n = count($driverPath);
		$count_failed = 0;
		for($i = 0; $i < $compare_n; $i++){
			$overlap = PathCompare(json_encode($path), $driverPath[$i], true);
			//¦³­«Å|
			if($overlap != null){
				$carpoolDistance;	//¦@­¼¶ZÂ÷
				$match = true;		//½T»{²Ä¤@¬q¬O§_³q¹L¿z¿ï
				$match2 = true;		//½T»{²Ä¤G¬q¬O§_³q¹L¿z¿ï
				$match3 = true;		//½T»{²Ä¤T¬q¬O§_³q¹L¿z¿ï
				$safeDistance;		//¦w¥þ¶ZÂ÷
				$onDistance;		//¤W¨®ÂI»P­¼«È°_ÂI¶ZÂ÷(m)
				$offDistance;		//¤U¨®ÂI»P­¼«È²×ÂI¶ZÂ÷(m)
				$passengerTime;		//­¼«È¨«¸ô®É¶¡(s)
				$driverTime;		//¥q¾÷¦æ¾p®É¶¡(s)
				$carpoolTime;		//­pºâ¦@­¼¸ô®|1ªº¦æ¾p®É¶¡
				$carpoolTime2;		//­pºâ¦@­¼¸ô®|2ªº¦æ¾p®É¶¡
				//­pºâ¦@­¼¶ZÂ÷
				//$distRes = getPathDistance($overlap[0][0], $overlap[0][count($overlap[0]) - 1], 'driving');
				$carpoolDistance = getDistance($overlap);
				//­pºâ¤W¨®ÂI»P­¼«È°_ÂI¶ZÂ÷(m)»P­¼«È¨«¸ô®É¶¡(s)
				$distRes = getPathDistance($passengerPath[0][0], $overlap[0], 'walking');
				$onDistance = $distRes[0];
				$passengerTime = $distRes[1];
				//­pºâ¤U¨®ÂI»P­¼«È²×ÂI¶ZÂ÷(m)
				$offn = count($passengerPath[0]) - 1;
				$distArr = array();
				array_push($distArr, $passengerPath[0][$offn], $overlap[count($overlap)-1]);
				$offDistance = getDistance($distArr);
				//­pºâ¥q¾÷¦æ¾p®É¶¡(s)
				$distRes = getPathDistance($driverPos[$i], $overlap[0], 'driving');
				$driverTime = $distRes[1];
				//­pºâ¥q¾÷¦æ¾p¦@­¼¸ô®|1®É¶¡
				$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
				$carpoolTime = $distRes[1];
				//­pºâ¦w¥þ¶ZÂ÷
				$safeArr = array();
				array_push($safeArr, $driverPos[$i], $passengerPath[0][0]);
				$safeDistance = getDistance($safeArr);
				//­pºâ¦@­¼¤ñ¨Ò
				$per = round($carpoolDistance / $totalDistance * 100);
				//¦]¬°»~®t¡A¦³¥i¯à¶W¹L100%
				if($per > 100)
					$per = 100;

				//¿z¿ï¦w¥þ°Ï¶¡¡B¦@­¼¤ñ¨Ò¡B¤W¨®ÂI»P­¼«È°_ÂI¶ZÂ÷¡B¥q¾÷Ä@·Nµ¥«Ý®É¶¡»P­¼«ÈÄ@·Nµ¥«Ý®É¶¡
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
