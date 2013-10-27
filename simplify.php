<?php

function simplify($points, $tolerance = 1, $highestQuality = false) {
	if (count($points) < 2) return $points;
	$sqTolerance = $tolerance * $tolerance;
	if (!$highestQuality) {
		$points = simplifyRadialDistance($points, $sqTolerance);
	}
	$points = simplifyDouglasPeucker($points, $sqTolerance);

	return $points;
}


function getSquareDistance($p1, $p2) {
	$dx = $p1[0] - $p2[0];
	$dy = $p1[1] - $p2[1];
	return $dx * $dx + $dy * $dy;
}


function getSquareSegmentDistance($p, $p1, $p2) {
	$x = $p1[0];
	$y = $p1[1];

	$dx = $p2[0] - $x;
	$dy = $p2[1] - $y;

	if ($dx !== 0 || $dy !== 0) {

		$t = (($p[0] - $x) * $dx + ($p[1] - $y) * $dy) / ($dx * $dx + $dy * $dy);

		if ($t > 1) {
			$x = $p2[0];
			$y = $p2[1];

		} else if ($t > 0) {
			$x += $dx * $t;
			$y += $dy * $t;
		}
	}

	$dx = $p[0] - $x;
	$dy = $p[1] - $y;

	return $dx * $dx + $dy * $dy;
}


function simplifyRadialDistance($points, $sqTolerance) { // distance-based simplification
	
	$len = count($points);
	$prevPoint = $points[0];
	$newPoints = array($prevPoint);
	$point = null;
	

	for ($i = 1; $i < $len; $i++) {
		$point = $points[$i];

		if (getSquareDistance($point, $prevPoint) > $sqTolerance) {
			array_push($newPoints, $point);
			$prevPoint = $point;
		}
	}

	if ($prevPoint !== $point) {
		array_push($newPoints, $point);
	}

	return $newPoints;
}


// simplification using optimized Douglas-Peucker algorithm with recursion elimination
function simplifyDouglasPeucker($points, $sqTolerance) {

	$len = count($points);

	$markers = array_fill ( 0 , $len - 1, null);
	$first = 0;
	$last = $len - 1;

	$firstStack = array();
	$lastStack  = array();

	$newPoints  = array();

	$markers[$first] = $markers[$last] = 1;

	while ($last) {

		$maxSqDist = 0;

		for ($i = $first + 1; $i < $last; $i++) {
			$sqDist = getSquareSegmentDistance($points[$i], $points[$first], $points[$last]);

			if ($sqDist > $maxSqDist) {
				$index = $i;
				$maxSqDist = $sqDist;
			}
		}

		if ($maxSqDist > $sqTolerance) {
			$markers[$index] = 1;

			array_push($firstStack, $first);
			array_push($lastStack, $index);

			array_push($firstStack, $index);
			array_push($lastStack, $last);
		}

		$first = array_pop($firstStack);
		$last = array_pop($lastStack);
	}

	for ($i = 0; $i < $len; $i++) {
		if ($markers[$i]) {
			array_push($newPoints, $points[$i]);
		}
	}

	return $newPoints;
}

?>
