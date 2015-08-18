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

	$id = $data['id'];							//����id
	$condition = $data['condition'];			//����
	$percentage = $condition[0]['percentage'];	//���Ȧ@����Ҫ��e
	$distance = $condition[0]['distance'];		//���ȤW���Z�����e
	$waiting = $condition[0]['waiting'];		//���ȵ��ݮɶ����e
	$threshold = $condition[0]['rating'];		//���ȵ������e
	$start = '{"latitude":"'.$data['start']['latitude'].'","longitude":"'.$data['start']['longitude'].'"}';	//���Ȱ_�I
	$end = '{"latitude":"'.$data['end']['latitude'].'","longitude":"'.$data['end']['longitude'].'"}';		//���Ȳ��I
	$path = $data['path'];						//���ȸ��|json
	$passengerPath = array($path);				//���ȸ��|�}�C
	$totalDistance = $data['total'];			//���ȸ��|��

	$_SAFE = 100;								//�w���϶�(m)
	$_END = 100;								//�Z�����I�Z��(m)
	//�����Ȧۨ�����
	function getRating($fid){
		$sql = "SELECT `rating` FROM `account` WHERE `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//���q�����e
	function getDriverThreshold($fid){
		$sql = "SELECT `threshold` FROM `driver` WHERE `finished` = '0' and `aid` = '$fid'";
		$result = mysql_query($sql);
		$i = mysql_fetch_array($result);
		return $i[0];
	}
	//�p����|�Z���P�ɶ�
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
	//�p�⪽���Z��
	function getDirectDistance($origin, $destination){
		$radLat1 = deg2rad($origin["at"]);
		$radLat2 = deg2rad($origin["ng"]);
		$radLng1 = deg2rad($destination["at"]);
		$radLng2 = deg2rad($destination["ng"]);
		$a = $radLat1 - $radLat2;//�n�׮t, �n�� < 90
		$b = $radLng1 - $radLng2;//�g�׮t�A�n�� < 180
		$dis = 2*asin(sqrt(pow(sin($a/2),2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137;
		return round($dis);
	}
	//�p����|�����Z��
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
	//���C�X
	function matchPath($dpath, $ppath, $dwait, $did){
		
	}
	
	
	//�q�������ŦX���Ȫ��e
	$sql = "SELECT `driver`.`aid` FROM `driver`,`account` WHERE `finished` = '0' and `account`.`rating` >= '$threshold' and `account`.`aid`=`driver`.`aid` and `seat` != '0'";//�z�ﭼ�ȵ������e
	$result = mysql_query($sql);
	$driver_num = mysql_num_rows($result);

	$driverId = array();	//���������z��᪺�q��id
	$count = 0;
	//���ȵ����ŦX�q�����e
	for($i=0; $i < $driver_num; $i++){
		$res = mysql_result($result, $i);
		if(getDriverThreshold($res) <= getRating($id)){
			$driverId[$count] = $res;
			$count++;
		}
	}

	$driverPath = array();		//���������z��᪺�q��path
	$driverPos = array();		//���������z��᪺�q���ثe��m
	$driverWait = array();		//���������z��᪺�q�����ݮɶ�
	$matchResult = array();		//�����C�X���\��q�@�����|���G
	$matchResult2 = array();	//�����C�X���\�G�q�@�����|���G
	$matchResult3 = array();	//�����C�X���\�T�q�@�����|���G
	$list1 = array();			//�����C�X���\��q�@�����|����
	$list2 = array();			//�����C�X���\�G�q�@�����|����
	$list3 = array();			//�����C�X���\�T�q�@�����|����
	$matchJson = "";			//������q�@���C�X���\��json string
	$matchJson2 = "";			//�����G�q�@���C�X���\��json string
	$matchJson3 = "";			//�����T�q�@���C�X���\��json string
	$result_n = 0;
	$index = 0;
	//�p�G���q��id���G
	if($count != 0){
		for($i = 0; $i < $count; $i++){
			$key = $driverId[$i];
			//���o�q�����|�B�q���ثe��m�B�q�����ݮɶ����e
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
		//�����|�P�C�X
		$compare_n = count($driverPath);
		$count_failed = 0;
		for($i = 0; $i < $compare_n; $i++){
			$overlap = PathCompare(json_encode($path), $driverPath[$i], true);
			//�����|
			if($overlap != null){
				$carpoolDistance;	//�@���Z��
				$match = true;		//�T�{�Ĥ@�q�O�_�q�L�z��
				$match2 = true;		//�T�{�ĤG�q�O�_�q�L�z��
				$match3 = true;		//�T�{�ĤT�q�O�_�q�L�z��
				$safeDistance;		//�w���Z��
				$onDistance;		//�W���I�P���Ȱ_�I�Z��(m)
				$offDistance;		//�U���I�P���Ȳ��I�Z��(m)
				$passengerTime;		//���Ȩ����ɶ�(s)
				$driverTime;		//�q����p�ɶ�(s)
				$carpoolTime;		//�p��@�����|1����p�ɶ�
				$carpoolTime2;		//�p��@�����|2����p�ɶ�
				//�p��@���Z��
				//$distRes = getPathDistance($overlap[0][0], $overlap[0][count($overlap[0]) - 1], 'driving');
				$carpoolDistance = getDistance($overlap);
				//�p��W���I�P���Ȱ_�I�Z��(m)�P���Ȩ����ɶ�(s)
				$distRes = getPathDistance($passengerPath[0][0], $overlap[0], 'walking');
				$onDistance = $distRes[0];
				$passengerTime = $distRes[1];
				//�p��U���I�P���Ȳ��I�Z��(m)
				$offn = count($passengerPath[0]) - 1;
				$distArr = array();
				array_push($distArr, $passengerPath[0][$offn], $overlap[count($overlap)-1]);
				$offDistance = getDistance($distArr);
				//�p��q����p�ɶ�(s)
				$distRes = getPathDistance($driverPos[$i], $overlap[0], 'driving');
				$driverTime = $distRes[1];
				//�p��q����p�@�����|1�ɶ�
				$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
				$carpoolTime = $distRes[1];
				//�p��w���Z��
				$safeArr = array();
				array_push($safeArr, $driverPos[$i], $passengerPath[0][0]);
				$safeDistance = getDistance($safeArr);
				//�p��@�����
				$per = round($carpoolDistance / $totalDistance * 100);
				//�]���~�t�A���i��W�L100%
				if($per > 100)
					$per = 100;
				
				//�z��w���϶��B�@����ҡB�W���I�P���Ȱ_�I�Z���B�q���@�N���ݮɶ��P�����@�N���ݮɶ�
				if($safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance
				||($driverTime + $driverWait[$i]*60) < $passengerTime ||($passengerTime + $waiting*60) < $driverTime){
					$match = false;
				}
				$matchResult2[$i][] = array();
				$matchResult3[$i][] = array();
				//��smatchJson�r��PmatchResult
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
					
					//�ĤG���C�X
					$newOri = $overlap[count($overlap)-1];		//�s�_�I	
					$check = false;
					$passenger_n = count($passengerPath[0]);
					$newPassengerPath = array();			//�s���Ȱ}�C
					for($j = 0; $j < $passenger_n; $j++){
						//�βĤ@���C�X���U���I��ĤG���C�X�W���I
						if(!$check && !strcmp($newOri['at'], $passengerPath[0][$j]['at']) && !strcmp($newOri['ng'], $passengerPath[0][$j]['ng'])){
							$check = true;
						}
						//�������ȤU���I����I�����|
						if($check){
							array_push($newPassengerPath, $passengerPath[0][$j]);
						}
					}
					
					
					
					//�P�_�O�_�ݭn�~��@��
					if($offDistance > $_END && count($newPassengerPath) != 0){
						//print "match2 start<br>";
						$compare_n = count($driverPath);
						for($j = 0; $j < $compare_n; $j++){
							$overlap = PathCompare(json_encode($newPassengerPath), $driverPath[$j], true);
							if($overlap != null){
								//�p��@���Z��
								$carpoolDistance = getDistance($overlap);
								//�p��W���I�P���Ȱ_�I�Z��(m)�P���Ȩ����ɶ�(s)
								$distRes = getPathDistance($newPassengerPath[0], $overlap[0], 'walking');
								$onDistance = $distRes[0];
								$passengerTime2 = $distRes[1];
								//�p��U���I�P���Ȳ��I�Z��(m)
								$offn = count($newPassengerPath) - 1;
								$distArr = array();
								array_push($distArr, $newPassengerPath[$offn], $overlap[count($overlap)-1]);
								$offDistance = getDistance($distArr);
								//�p��q����p�ɶ�(s)
								$distRes = getPathDistance($driverPos[$j], $overlap[0], 'driving');
								$driverTime2 = $distRes[1];
								//�p��q����p�@�����|2�ɶ�
								$distRes = getPathDistance($overlap[0], $overlap[count($overlap)-1], 'driving');
								$carpoolTime2 = $distRes[1];
								//�p��w���Z��
								$safeArr = array();
								array_push($safeArr, $driverPos[$i], $newPassengerPath[0]);
								$safeDistance = getDistance($safeArr);
								//�p��@�����
								$per = round($carpoolDistance / $totalDistance * 100);
								//�]���~�t�A���i��W�L100%
								if($per > 100)
									$per = 100;
								
								//�z��q���@�N���ݮɶ��P�����@�N���ݮɶ�
								if(	$safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
									$driverTime2 > $passengerTime + $carpoolTime + $passengerTime2 + $waiting*60 ||
								    $passengerTime + $carpoolTime + $passengerTime2 > $driverTime2 + $driverWait[$j]*60){
									$match2 = false;
								}
								//print ("offd:".$offDistance);
								//��smatchJson�r��PmatchResult
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
									
									
									//�ĤT���C�X
									$newOri = $overlap[count($overlap)-1];		//�s�_�I	
									$check = false;
									$passenger_n = count($newPassengerPath[0]);
									$newPassengerPath2 = array();			//�s���Ȱ}�C
									for($k = 0; $k < $passenger_n; $k++){
										//�βĤ@���C�X���U���I��ĤG���C�X�W���I
										if(!$check && !strcmp($newOri['at'], $newPassengerPath[$j]['at']) && !strcmp($newOri['ng'], $newPassengerPath[$j]['ng'])){
											$check = true;
										}
										//�������ȤU���I����I�����|
										if($check){
											array_push($newPassengerPath2, $newPassengerPath[$k]);
										}
									}

									//�P�_�O�_�ݭn�~��@��
									if($offDistance > $_END && count($newPassengerPath2) != 0){
										//print "match3 start<br>";
										$compare_n = count($driverPath);
										for($k = 0; $k < $compare_n; $k++){
											$overlap = PathCompare(json_encode($newPassengerPath2), $driverPath[$k], true);
											if($overlap != null){
												//�p��@���Z��
												$carpoolDistance = getDistance($overlap[0]);
												//�p��W���I�P���Ȱ_�I�Z��(m)�P���Ȩ����ɶ�(s)
												$distRes = getPathDistance($newPassengerPath2[0], $overlap[0], 'walking');
												$onDistance = $distRes[0];
												$passengerTime3 = $distRes[1];
												//�p��U���I�P���Ȳ��I�Z��(m)
												$offn = count($newPassengerPath2) - 1;
												$distArr = array();
												array_push($distArr, $newPassengerPath2[$offn], $overlap[count($overlap)-1]);
												$offDistance = getDistance($distArr);
												//�p��q����p�ɶ�(s)
												$distRes = getPathDistance($driverPos[$k], $overlap[0], 'driving');
												$driverTime3 = $distRes[1];
												//�p��w���Z��
												$safeArr = array();
												array_push($safeArr, $driverPos[$i], $newPassengerPath2[0]);
												$safeDistance = getDistance($safeArr);
												//�p��@�����
												$per = round($carpoolDistance / $totalDistance * 100);
												//�]���~�t�A���i��W�L100%
												if($per > 100)
													$per = 100;
												//�z��q���@�N���ݮɶ��P�����@�N���ݮɶ�
												if( $safeDistance <= $_SAFE || $per < $percentage || $onDistance > $distance ||
													$driverTime3 > $passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 + $waiting*60 ||
													$passengerTime + $carpoolTime + $passengerTime2 + $carpoolTime2 + $passengerTime3 > $driverTime3 + $driverWait[$j]*60){
													$match3 = false;
												}
												//��smatchJson�r��PmatchResult
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
		
		//�x�s�P�^�ǵ��G
		if($count_failed == $compare_n){
			echo "NoOverlap";
		}else if(count($matchResult) != 0){
			//�ഫ�@�����|
			$carstr = "[";
			$carstr1 = "";
			$carstr2 = "";
			$carstr3 = "";
			$nn = count($matchResult);						//match1���X�����|

			for($i = 0; $i < $nn; $i++){
				//match1 path
				$n = count($matchResult[$i]);				//match1��i��path���g�n�׼ƶq
				$carstr1 = $carstr1."[";
				for($j = 0; $j < $n; $j++){
					if($j != $n - 1)
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'},';
					else
						$carstr1 = $carstr1.'{"at":'.$matchResult[$i][$j]["at"].',"ng":'.$matchResult[$i][$j]["ng"].'}]';
				}
				
				//match2 path
				$i1 = $list1[$i];											//get match1 index
				
				if(count($list2) != 0 && array_key_exists($i1, $list2)){	//�P�_�O�_��match2
					$n2 = count($list2[$i1]);								//match2 index �ƶq
					for($j = 0; $j < $n2; $j++){				
						$carstr2 = $carstr2."[";
						$j1 = $list2[$i1][$j];								//get match2 index
						$n3 = count($matchResult2[$i1][$j1]);				//match2��i1����j1��path���g�n�׼ƶq	
						for($k = 0; $k < $n3; $k++){
							if($k != $n3 - 1)
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'},';
							else
								$carstr2 = $carstr2.'{"at":'.$matchResult2[$i1][$j1][$k]["at"].',"ng":'.$matchResult2[$i1][$j1][$k]["ng"].'}]';
						}
						
						if(count($list3) != 0 && array_key_exists($j1, $list3)){	//�P�_�O�_��match3					
							$n4 = count($list3[$j1]);								//match3 index �ƶq	
							for($k = 0; $k < $n4; $k++){
								$carstr3 = $carstr3."[";
								$k1 = $list3[$j1][$k];								//get match3 index
								$n5 = count($matchResult3[$i1][$j1][$k1]);			//match3��i1�̪�j1����k1��path���g�n�׼ƶq
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
			
			//��srequester���
			$sql = "SELECT `aid` FROM `passenger` WHERE `finished` = '0' and `aid` = '$id'";
			$result = mysql_query($sql);
			$num = mysql_num_rows($result);
			$path = json_encode($path);
			
			//���o�̫�s��
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
			//�^��match���G
			$matchJson = '{"id":"'.$id.'","result":'.$matchJson.']}';
			echo $matchJson;
		}else{
			echo "NoMatch";
		}
	}else{
		echo "NoDriver";
	}
?>
