<?php
	// $p1Json = '[{"at":22.9762,"ng":120.21657},{"at":22.97565,"ng":120.21675},{"at":22.97457,"ng":120.21708},{"at":22.97428,"ng":120.21718},{"at":22.97401,"ng":120.21728},{"at":22.97371,"ng":120.21737},{"at":22.97333,"ng":120.21747},{"at":22.9731,"ng":120.21754},{"at":22.97287,"ng":120.2176},{"at":22.97296,"ng":120.21799},{"at":22.97319,"ng":120.21903},{"at":22.97341,"ng":120.22004},{"at":22.97343,"ng":120.2201},{"at":22.97345,"ng":120.22016},{"at":22.97347,"ng":120.2202},{"at":22.9735,"ng":120.22023},{"at":22.97352,"ng":120.22033},{"at":22.9736,"ng":120.22031},{"at":22.97359,"ng":120.22026},{"at":22.97358,"ng":120.22021},{"at":22.9736,"ng":120.22017},{"at":22.97361,"ng":120.22015},{"at":22.97361,"ng":120.22012},{"at":22.97361,"ng":120.22009},{"at":22.9736,"ng":120.22004},{"at":22.97355,"ng":120.21979}]';
	// $p2Json = '[{"at":22.97857,"ng":120.21592},{"at":22.97852,"ng":120.2158},{"at":22.97665,"ng":120.21642},{"at":22.97565,"ng":120.21675},{"at":22.97457,"ng":120.21708},{"at":22.97428,"ng":120.21718},{"at":22.97401,"ng":120.21728},{"at":22.97371,"ng":120.21737},{"at":22.97333,"ng":120.21747},{"at":22.9731,"ng":120.21754},{"at":22.97287,"ng":120.2176},{"at":22.97296,"ng":120.21799},{"at":22.97319,"ng":120.21903},{"at":22.97341,"ng":120.22004},{"at":22.97343,"ng":120.2201},{"at":22.97345,"ng":120.22016},{"at":22.97347,"ng":120.2202},{"at":22.9735,"ng":120.22023},{"at":22.97352,"ng":120.22033},{"at":22.9737,"ng":120.22124},{"at":22.97375,"ng":120.2215},{"at":22.97376,"ng":120.22155}]';
	class Box {
		public $top;
		public $low;
		public $left;
		public $right;
	}

	class Point {
		public $lat;
		public $lng;
	}

	// compare two path with google map produced
	// p1 is passenger path
	// p2 is driver path
	// directive is two paths direction true is same, false is negative
	function PathCompare($p1, $p2, $directive) {
		$p1 = json_decode($p1, true);
		$p2 = json_decode($p2, true);

		$OverlapBox = new Box();

		// find bound
		$p1Lats = array();
		$p1Lngs = array();
		$p2Lats = array();
		$p2Lngs = array();

		//		push points to array with lat and lng respectively
		for ($i = 0; $i < count($p1); $i++) {
			array_push($p1Lats, $p1[$i]['at']);
			array_push($p1Lngs, $p1[$i]['ng']);
		}
		for ($i = 0; $i < count($p2); $i++) {
			array_push($p2Lats, $p2[$i]['at']);
			array_push($p2Lngs, $p2[$i]['ng']);
		}

		//		find path box
		$box1 = array(max($p1Lats), min($p1Lats), min($p1Lngs), max($p1Lngs));
		$box2 = array(max($p2Lats), min($p2Lats), min($p2Lngs), max($p2Lngs));

		//		find overlapbox
		$intersection = !($box1[3] < $box2[2] || $box1[2] > $box2[3] || $box1[0] < $box2[1] || $box1[1] > $box2[0]);
		if ($intersection) {
			$v = array($box1[0], $box1[1], $box2[0], $box2[1]);
			$h = array($box1[3], $box1[2], $box2[3], $box2[2]);
			asort($v);
			asort($h);

			$OverlapBox->top = $v[2];
			$OverlapBox->low = $v[1];
			$OverlapBox->right = $h[2];
			$OverlapBox->left = $h[1];

			// reduce the dots in path within the overlap box
			$p = array($p1, $p2);
			$PathsRedots = array();

			for ($i = 0; $i < 2; $i++) {
				array_push($PathsRedots, array());
				for ($j = 0; $j < count($p[$i]); $j++) {
					$sd_B_lat = ($OverlapBox->low <= $p[$i][$j]['at']) && ($p[$i][$j]['at'] <= $OverlapBox->top);
					$sd_B_ng = ($OverlapBox->left <= $p[$i][$j]['ng']) && ($p[$i][$j]['ng'] <= $OverlapBox->right);

					if ($sd_B_lat && $sd_B_ng)
						array_push($PathsRedots[$i], $j);
				}
			}

			// compare result
			$cr_arr = array();
			$cr_arrn = 0;
			$cr_arrtn = 0;
			$directArr = array();

			for ($z = 0; $z < count($PathsRedots[0]); $z++) {
				for ($x = 0; $x < count($PathsRedots[1]); $x++) {
					$followBool = (number_format($p[0][$PathsRedots[0][$z]]['at'], 5) == number_format($p[1][$PathsRedots[1][$x]]['at'], 5) && number_format($p[0][$PathsRedots[0][$z]]['ng'], 5) == number_format($p[1][$PathsRedots[1][$x]]['ng'], 5));
					if ($followBool) {
						if (!count($cr_arr)) {
							array_push($cr_arr, array($z));
							array_push($directArr, array($x));
						} else {
							$absv = abs($cr_arr[$cr_arrn][$cr_arrtn] - $z);
							$absv2 = abs($directArr[$cr_arrn][$cr_arrtn] - $x);
							if ($absv == 1 && $absv2 == 1) {
								$cr_arr[$cr_arrn][++$cr_arrtn] = $z;
								$directArr[$cr_arrn][$cr_arrtn] = $x;
							} else {
								$cr_arrtn = 0;
								$cr_arrn++;
								array_push($cr_arr, array($z));
								array_push($directArr, array($x));
							}
						}
					}
				}
			}

			// output intersection
			$oid = array();
			$findPath = 0;

			for ($i = 0; $i < count($cr_arr); $i++) {
				if (count($cr_arr[$i]) > 1) {
					$path1direct = ($cr_arr[$i][1] - $cr_arr[$i][0]) > 0;
					$path2direct = ($directArr[$i][1] - $directArr[$i][0]) > 0;

					if ($directive) {
						if ($path1direct ? $path2direct : !$path2direct) {
							array_push($oid, array());
							for ($j = 0; $j < count($cr_arr[$i]); $j++) {
								array_push($oid[$findPath], $p[0][$PathsRedots[0][$cr_arr[$i][$j]]]);
							}
							$findPath++;
						}
					} else {
						array_push($oid, array());
						for ($j = 0; $j < count($cr_arr[$i]); $j++) {
							array_push($oid[$findPath], $p[0][$PathsRedots[0][$cr_arr[$i][$j]]]);
						}
						$findPath++;
					}
				}
			}

			$carpoolPathIndex = 0;
			for ($i = 1; $i < count($oid); $i++) {
				if (count($oid[$carpoolPathIndex]) < count($oid[$i]))
					$carpoolPathIndex = $i;
			}

			if (count($oid) == 0)
				return null;
			return $oid[$carpoolPathIndex];
		} else {
			return null;
		}
	}
	// $oidd = PathCompare($p1Json, $p2Json, true);
	// echo "oidd: ";
	// print_r($oidd);
?>