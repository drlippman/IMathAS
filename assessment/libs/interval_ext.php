<?php
//Interval functions extension. Version 1.0 November 4, 2014

namespace IntervalLib {

global $allowedmacros;
array_push($allowedmacros,"canonicInterval", "intersection");

//include "interval_helper.php";
define ('IntervalLib\EQUAL_LEFT', 1);
define ('IntervalLib\EQUAL_RIGHT', 2);

define ('IntervalLib\LT', 3);
define ('IntervalLib\GT', 4);

define ('IntervalLib\INCLUDED', 5);

function testPointVsInterval($point, $x1, $x2)
{
  $res = 0;

  if ($point < $x1) { $res = LT; }
  elseif ($point > $x2) { $res = GT; }
  elseif ($point == $x1) { $res = EQUAL_LEFT; }
  elseif ($point == $x2) { $res = EQUAL_RIGHT; }
  else { $res = INCLUDED; }

  return $res;
}

$GLOBALS['interval_ext_emptySet'] = array( "left-border" => 0,
		   "right-border" => 0,
		   "is-open-left" => true,
		   "is-open-right" => true );

/*

x1, x2  -> interval in list
y1, y2  -> interval to insert

isOpenX1, isOpenX2, isOpenY1, isOpenY2 -> interval border open vs closed

returns
 -> insert, if y1, y2 is left of x1, x2
 -> SKIP if y1, y2 is right of x1, x2
 -> merge + new interval
 -> right_expand + new interval

*/
define ('IntervalLib\ERROR', 0);

define ('IntervalLib\INSERT', 1);
define ('IntervalLib\SKIP', 2);
define ('IntervalLib\MERGE', 3);
define ('IntervalLib\EXPAND_RIGHT', 4);

function calculateUnion($x1, $x2, $isOpenX1, $isOpenX2, $y1, $y2, $isOpenY1, $isOpenY2) {

// return values
  $result = ERROR;
  $z1 = 0;
  $z2 = 0;
  $isOpenZ1 = false;
  $isOpenZ2 = false;

  switch ( testPointVsInterval($y1,$x1,$x2)) {
    case EQUAL_LEFT:
      // echo "here: " . EQUAL_LEFT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " + " . EQUAL_LEFT . " -> merge";

	  $result = MERGE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1 && $isOpenY1 && $isOpenY2;
	  $isOpenZ2 = $isOpenX2;

	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1 && $isOpenY1;
	  $isOpenZ2 = $isOpenX2 && $isOpenY2;

	  break;
	case LT:
	  // echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT. " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $x1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenX1 && $isOpenY1;
	  $isOpenZ2 = $isOpenY2;

	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED. " -> merge";

	  $result = MERGE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1 && $isOpenY1;
	  $isOpenZ2 = $isOpenX2;

	  break;

	default:
	  // echo "error";
      }

      break;

    case EQUAL_RIGHT:
      // echo "here " . EQUAL_RIGHT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " error " . EQUAL_LEFT;
	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenX2 && $isOpenY1 && $isOpenY2;

	  break;
	case LT:
	  // echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT;

	  if ($isOpenX2 && $isOpenY1) {
	    // echo " -> SKIP";

	    $result = SKIP;
	  } else {
	    // echo " -> expand_right";

	    $result = EXPAND_RIGHT;
	    $z1 = $x1;
	    $z2 = $y2;
	    $isOpenZ1 = $isOpenX1;
	    $isOpenZ2 = $isOpenY2;
	  }
	  break;
	case INCLUDED:
	  // echo " error " . INCLUDED;
	  break;

	default:
	  // echo "error";
      }

      break;

    case LT:
      // echo "here " . LT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " + " . EQUAL_LEFT;

	  if ($isOpenX1 && $isOpenY2) {
	    // echo " -> insert";

	    $result = INSERT;
	  } else {
	    // echo " -> merge";

	    $result = MERGE;
	    $z1 = $y1;
	    $z2 = $x2;
	    $isOpenZ1 = $isOpenY1;
	    $isOpenZ2 = $isOpenX2;
	  }

	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $y1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenX2 && $isOpenY2;
	  break;
	case LT:
	  // echo " + " . LT . " -> insert";

	  $result = INSERT;
	  break;
	case GT:
	  // echo " + " . GT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $y1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenY2;

	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED . " -> merge";

	  $result = MERGE;
	  $z1 = $y1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenX2;
	  break;

	default:
	  echo "error";
      }

      break;

    case GT:
      // echo "here " . GT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case GT:
	  // echo " + " . GT . " -> SKIP";
	  $result = SKIP;
	  break;
	default:
	  echo "error";
      }

      break;

    case INCLUDED:
      // echo "here " . INCLUDED;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " error " . EQUAL_LEFT;
	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenX2 && $isOpenY2;
	  break;
	case LT:
	  // echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT . " -> expand_right";

	  $result = EXPAND_RIGHT;
	  $z1 = $x1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenY2;
	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED . " -> merge";

	  $result = MERGE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenX2;
	  break;

	default:
	  echo "error";
      }

      break;

    // fallthrough
    default:
      echo "error";
  }

  // echo " ";

  if ($result == ERROR || $result == INSERT || $result == SKIP) {
    return array("result" => $result);
  } else {
    return array("result" => $result,
      "left-border" => $z1, "right-border" => $z2,
      "is-open-left" => $isOpenZ1, "is-open-right" => $isOpenZ2);
  }
}

/*








*/

define ('IntervalLib\DOINTERSECT_STOP', 1);
define ('IntervalLib\DOINTERSECT_CONTINIUE', 2);
define ('IntervalLib\DONOTINTERSECT_CONTINIUE', 3);
define ('IntervalLib\DONOTINTERSECT_STOP', 4);

function calculateIntersection($x1, $x2, $isOpenX1, $isOpenX2, $y1, $y2, $isOpenY1, $isOpenY2) {

// return values
  $result = ERROR;
  $z1 = 0;
  $z2 = 0;
  $isOpenZ1 = false;
  $isOpenZ2 = false;

  switch ( testPointVsInterval($y1,$x1,$x2)) {
    case EQUAL_LEFT:
      // echo "here: " . EQUAL_LEFT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  if ($isOpenX1 || $isOpenY1 || $isOpenY2) {
	    // echo " + " . EQUAL_LEFT . " -> doNotIntersectStop";

	    $result = DONOTINTERSECT_STOP;
	  } else {
	    // echo " + " . EQUAL_LEFT . " -> doIntersectStop";

	    $result = DOINTERSECT_STOP;
	    $z1 = $x1;
	    $z2 = $x1;
	    $isOpenZ1 = false;
	    $isOpenZ2 = false;
	  }
	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1 || $isOpenY1;
	  $isOpenZ2 = $isOpenX2 || $isOpenY2;

	  break;
	case LT:
	  echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT. " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1 || $isOpenY1;
	  $isOpenZ2 = $isOpenX2;

	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED. " -> doIntersectStop";

	  $result = DOINTERSECT_STOP;
	  $z1 = $x1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenX1 || $isOpenY1;
	  $isOpenZ2 = $isOpenY2;

	  break;

	default:
	  echo "error";
      }

      break;

    case EQUAL_RIGHT:
      // echo "here " . EQUAL_RIGHT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " error " . EQUAL_LEFT;
	  break;
	case EQUAL_RIGHT:
	  if ($isOpenX2 || $isOpenY1 || $isOpenY2) {
	    // echo " + " . EQUAL_LEFT . " -> doNotIntersectStop";

	    $result = DONOTINTERSECT_CONTINIUE;
	  } else {
	    // echo " + " . EQUAL_LEFT . " -> doIntersectContiniue";

	    $result = DOINTERSECT_CONTINIUE;
	    $z1 = $x2;
	    $z2 = $x2;
	    $isOpenZ1 = false;
	    $isOpenZ2 = false;
	  }
	  break;
	case LT:
	  echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT;

	  if ($isOpenX2 || $isOpenY1) {
	    // echo " -> doNotIntersectContiniue";

	    $result = DONOTINTERSECT_CONTINIUE;
	  } else {
	    // echo " -> doIntersectContiniue";

	    $result = DOINTERSECT_CONTINIUE;
	    $z1 = $x2;
	    $z2 = $x2;
	    $isOpenZ1 = false;
	    $isOpenZ2 = false;
	  }
	  break;
	case INCLUDED:
	  echo " error " . INCLUDED;
	  break;

	default:
	  echo "error";
      }

      break;

    case LT:
      // echo "here " . LT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  // echo " + " . EQUAL_LEFT;

	  if ($isOpenX1 || $isOpenY2) {
	    // echo " -> doNotIntersectStop";

	    $result = DONOTINTERSECT_STOP;
	  } else {
	    // echo " -> doIntersectStop";

	    $result = DOINTERSECT_STOP;
	    $z1 = $x1;
	    $z2 = $x1;
	    $isOpenZ1 = false;
	    $isOpenZ2 = false;
	  }

	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenX2 || $isOpenY2;
	  break;
	case LT:
	  // echo " + " . LT . " -> doNotIntersectStop";

	  $result = DONOTINTERSECT_STOP;
	  break;
	case GT:
	  // echo " + " . GT . " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $x1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenX2;

	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED . " -> doIntersectStop";

	  $result = DOINTERSECT_STOP;
	  $z1 = $x1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenX1;
	  $isOpenZ2 = $isOpenY2;
	  break;

	default:
	  echo "error";
      }

      break;

    case GT:
      // echo "here " . GT;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case GT:
	  // echo " + " . GT . " -> doNotIntersectContiniue";
	  $result = DONOTINTERSECT_CONTINIUE;
	  break;
	default:
	  echo "error";
      }

      break;

    case INCLUDED:
      // echo "here " . INCLUDED;

      switch ( testPointVsInterval($y2,$x1,$x2)) {
	case EQUAL_LEFT:
	  echo " error " . EQUAL_LEFT;
	  break;
	case EQUAL_RIGHT:
	  // echo " + " . EQUAL_RIGHT . " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $y1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenX2 || $isOpenY2;
	  break;
	case LT:
	  echo " error " . LT;
	  break;
	case GT:
	  // echo " + " . GT . " -> doIntersectContiniue";

	  $result = DOINTERSECT_CONTINIUE;
	  $z1 = $y1;
	  $z2 = $x2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenX2;
	  break;
	case INCLUDED:
	  // echo " + " . INCLUDED . " -> doIntersectStop";

	  $result = DOINTERSECT_STOP;
	  $z1 = $y1;
	  $z2 = $y2;
	  $isOpenZ1 = $isOpenY1;
	  $isOpenZ2 = $isOpenY2;
	  break;

	default:
	  echo "error";
      }

      break;

    // fallthrough
    default:
      echo "error";
  }

  // echo " ";

  if ($result == ERROR || $result == DONOTINTERSECT_STOP || $result == DONOTINTERSECT_CONTINIUE) {
    return array("result" => $result);
  } else {
    return array("result" => $result,
      "left-border" => $z1, "right-border" => $z2,
      "is-open-left" => $isOpenZ1, "is-open-right" => $isOpenZ2);
  }
}

// ######################################################################################################################

function traverseUnion($border_left, $border_right, $isOpenLeft, $isOpenRight) {
  $y1 = array();

  for($i=0; $i<count($border_left); $i++) {
    $inskip = false;
    $fallthrough = false;

    $z1 = array();
    $z2 = array();
    $isOpenZ1 = array();
    $isOpenZ2 = array();

    if (count($y1) ==0) {

      $y1 = array($border_left[$i]);
      $y2 = array($border_right[$i]);
      $isOpenY1 = array($isOpenLeft[$i]);
      $isOpenY2 = array($isOpenRight[$i]);

    } else {

      $item_left =$border_left[$i];
      $item_right = $border_right[$i];
      $isOpenItemLeft = $isOpenLeft[$i];
      $isOpenItemRight = $isOpenRight[$i];

      for($j=0; $j<count($y1); $j++) {
        $inskip = false;

	if (!$fallthrough) {

	  $result = calculateUnion($y1[$j], $y2[$j], $isOpenY1[$j], $isOpenY2[$j],
			           $item_left, $item_right, $isOpenItemLeft, $isOpenItemRight);

	  switch ($result["result"]) {
	    case ERROR:
	      echo "-> error";
	      break;
	    case INSERT:
	      $fallthrough = true;

	      $z1[] = $item_left;
      	      $z2[] = $item_right;
	      $isOpenZ1[] = $isOpenItemLeft;
      	      $isOpenZ2[] = $isOpenItemRight;

   	      $z1[] = $y1[$j];
      	      $z2[] = $y2[$j];
	      $isOpenZ1[] = $isOpenY1[$j];
      	      $isOpenZ2[] = $isOpenY2[$j];

	      break;
	    case SKIP:
	      $inskip = true;

	      $z1[] = $y1[$j];
      	      $z2[] = $y2[$j];
	      $isOpenZ1[] = $isOpenY1[$j];
      	      $isOpenZ2[] = $isOpenY2[$j];

	      break;
	    case MERGE;
	      $fallthrough = true;

	      $z1[] = $result["left-border"];
      	      $z2[] = $result["right-border"];
	      $isOpenZ1[] = $result["is-open-left"];
      	      $isOpenZ2[] = $result["is-open-right"];

	      break;
	    case EXPAND_RIGHT:

	      $z1[] = $result["left-border"];
      	      $z2[] = $result["right-border"];
	      $isOpenZ1[] = $result["is-open-left"];
      	      $isOpenZ2[] = $result["is-open-right"];

	      $item_left = $result["left-border"];
      	      $item_right = $result["right-border"];
	      $isOpenItemLeft = $result["is-open-left"];
      	      $isOpenItemRight = $result["is-open-right"];

	      break;
	    default:
	      echo "-> error";
	  }
	} else {

	  $z1[] = $y1[$j];
      	  $z2[] = $y2[$j];
	  $isOpenZ1[] = $isOpenY1[$j];
      	  $isOpenZ2[] = $isOpenY2[$j];

	} // if ! fallthrough
      } // for j

      if ($inskip) {
      	$z1[] = $item_left;
	$z2[] = $item_right;
	$isOpenZ1[] = $isOpenItemLeft;
	$isOpenZ2[] = $isOpenItemRight;
      }

      $y1 = $z1;
      $y2 = $z2;
      $isOpenY1 = $isOpenZ1;
      $isOpenY2 = $isOpenZ2;

      // echo var_dump($y1);
      // echo var_dump($y2);

    } // if count == 0
  } // for i

  return array( "left-border" => $y1,
	   "right-border" => $y2,
	   "is-open-left" => $isOpenY1,
	   "is-open-right" => $isOpenY2 );

}

function traverseIntersection($border_left, $border_right, $isOpenLeft, $isOpenRight) {

  $z1 = array();
  $z2 = array();
  $isOpenZ1 = array();
  $isOpenZ2 = array();

  for ($i=0; $i<count($border_left); $i++) {
    for ($j=$i+1; $j<count($border_left); $j++) {

      $result = calculateIntersection($border_left[$i], $border_right[$i], $isOpenLeft[$i], $isOpenRight[$i],
				      $border_left[$j], $border_right[$j], $isOpenLeft[$j], $isOpenRight[$j]);

      switch ($result["result"]) {
	case DOINTERSECT_STOP:
	case DOINTERSECT_CONTINIUE:

	  $z1[] = $result["left-border"];
      	  $z2[] = $result["right-border"];
	  $isOpenZ1[] = $result["is-open-left"];
      	  $isOpenZ2[] = $result["is-open-right"];

      	  break;
	case DONOTINTERSECT_CONTINIUE:
	case DONOTINTERSECT_STOP:
	default:
	  // do noting
      }
    } // for j
  } // for i

  return array( "left-border" => $z1,
	   "right-border" => $z2,
	   "is-open-left" => $isOpenZ1,
	   "is-open-right" => $isOpenZ2 );
}

function calculateMostCommonIntersection($border_left, $border_right, $isOpenLeft, $isOpenRight) {
  global $interval_ext_emptySet;

  // case empy input
  if (count($border_left) == 0) {
    return $interval_ext_emptySet;
  }

  // start condition
  $z1 = $border_left[0];
  $z2 = $border_right[0];
  $isOpenZ1 = $isOpenLeft[0];
  $isOpenZ2 = $isOpenRight[0];

  for ($i=1; $i<count($border_left); $i++) {

    $result = calculateIntersection($border_left[$i], $border_right[$i], $isOpenLeft[$i], $isOpenRight[$i],
			            $z1, $z2, $isOpenZ1, $isOpenZ2);

      switch ($result["result"]) {
	case DOINTERSECT_STOP:
	case DOINTERSECT_CONTINIUE:

	  $z1 = $result["left-border"];
      	  $z2 = $result["right-border"];
	  $isOpenZ1 = $result["is-open-left"];
      	  $isOpenZ2 = $result["is-open-right"];

      	  break;
	case DONOTINTERSECT_CONTINIUE:
	case DONOTINTERSECT_STOP:
	  return $interval_ext_emptySet;

	  break;
	default:
	  // do noting
      }
  } // for i

  return array( "left-border" => $z1,
	   "right-border" => $z2,
	   "is-open-left" => $isOpenZ1,
	   "is-open-right" => $isOpenZ2 );
}

function calculateIntersectionSet($values) {
    global $interval_ext_emptySet;

     // case empy input
     if (count($values) == 0) {
        return $interval_ext_emptySet;
     }

     // start condition
     $item = $values[0];
     // $z1 = $value["left-border"];
     // $z2 = $value["right-border"];
     // $isOpenZ1 = $value["is-open-left"];
     // $isOpenZ2 = $value["is-open-right"];

    for ($i = 1; $i < count($values); $i++) {
        $value = $values[$i];

        $z1 = array();
        $z2 = array();
        $isOpenZ1 = array();
        $isOpenZ2 = array();

        for ($j=0; $j<count($value["left-border"]); $j++) {
            for ($k=0; $k<count($item["left-border"]); $k++) {

//            foreach($item as $y) {

                $result = calculateIntersection($value["left-border"][$j], $value["right-border"][$j],
                                                $value["is-open-left"][$j], $value["is-open-right"][$j],
                                                $item["left-border"][$k], $item["right-border"][$k],
                                                $item["is-open-left"][$k], $item["is-open-right"][$k]);

                switch ($result["result"]) {
            	case DOINTERSECT_STOP:
            	case DOINTERSECT_CONTINIUE:

            	    $z1[] = $result["left-border"];
                  	$z2[] = $result["right-border"];
            	    $isOpenZ1[] = $result["is-open-left"];
                  	$isOpenZ2[] = $result["is-open-right"];

                  	break;
            	case DONOTINTERSECT_CONTINIUE:
            	case DONOTINTERSECT_STOP:
            	default:
            	  // do noting
                }
            }
        } // foreach value
        if (!empty($z1)) {
            $item = traverseUnion($z1, $z2, $isOpenZ1, $isOpenZ2 );
        }

    } // for values

    return $item;
}



define("IntervalLib\EMPTY_SET", "DNE");

function parseFloat($input) {

  if (preg_match("/-\s*oo/i",$input)) {
    return array(-INF, "-oo", false);
  } elseif (preg_match("/\+?oo/i", $input)) {
    return array(INF, "oo", false);
  }

  $result = evalMathParser($input);
  $error = ($result === false) || is_string($result) || isNaN($result);

  return array($result, $input, $error);

}

// ######################################################################

function parseString($input) {
  $parts = preg_split("/\s*(uu|U|cup)\s*/i",$input);

  return parseParts($parts);
}

function parseParts($parts) {
  global $interval_ext_emptySet;

  $hasError = false;

  $borderLeft = array();
  $borderRight = array();
  $isOpenLeft = array();
  $isOpenRight = array();

  $index = array();

  // empty input
  $hasError = (count($parts) == 0);

  foreach($parts as $part) {
    // echo "-> " . $part .  "\n";

    if (preg_match('/dne/i', $part)) {
      // empty set
      // do nothing
    } else {

      $iol = preg_match("/^\s*\[/", $part) > 0;
      $ior = preg_match("/\]\s*$/", $part) > 0;

      // list($a,$b) = preg_split("/\s*,\s*/",$part);
      preg_match('/^\s*[\(\[]\s*(?P<left>.+)\s*,\s*(?P<right>.+)\s*[\)\]]\s*$/', $part, $match);

      // missing colon
      if (!isset($match["left"]) || !isset($match["right"]))
        return array("has-error" => true);

      list($bl, $v1, $e1) = parseFloat($match["left"]);
      list($br, $v2, $e2) = parseFloat($match["right"]);

      if ($e1 || $e2)
        return array("has-error" => true);

      if (($bl == $br) && (!$iol || !$ior)) {
        // empty set
        // do nothing
      } else {
        $index["$bl"] = $v1;
        $index["$br"] = $v2;

        // swap borders if necessary
        if ($bl < $br) {
	      $borderLeft[] = $bl;
	      $borderRight[] = $br;
        } else {
	      $borderLeft[] = $br;
	      $borderRight[] = $bl;
        }

        $isOpenLeft[] = ! $iol;
        $isOpenRight[] = ! $ior;
      }
    }
  }

  if (count($borderLeft) == 0) {
    return array("has-error" => false,
  	    "left-border" => array($interval_ext_emptySet["left-border"]),
  	    "right-border" => array($interval_ext_emptySet["right-border"]),
  	    "is-open-left" => array($interval_ext_emptySet["is-open-left"]),
  	    "is-open-right" => array($interval_ext_emptySet["is-open-right"]),
  	    "index" => $index);
  } else {
    return array("has-error" => false,
	    "left-border" => $borderLeft,
	    "right-border" => $borderRight,
	    "is-open-left" => $isOpenLeft,
	    "is-open-right" => $isOpenRight,
	    "index" => $index);
  }
}

function toString($borderLeft, $borderRight, $isOpenLeft, $isOpenRight, $index) {
  if (count($borderLeft) == 0) {
    return EMPTY_SET;
  }

  $results = array();

  for ($i=0; $i<count($borderLeft); $i++) {
    $v = toStringPart($borderLeft[$i], $borderRight[$i], $isOpenLeft[$i], $isOpenRight[$i], $index);

    if ($v != EMPTY_SET) $results[] = $v;
  }

  return count($results) == 0 ? EMPTY_SET : join(" U ", $results);
}

function toStringPart($borderLeft, $borderRight, $isOpenLeft, $isOpenRight, $index) {
  global $interval_ext_emptySet;

  if ($borderLeft == $interval_ext_emptySet["left-border"] && $borderRight == $interval_ext_emptySet["right-border"] &&
      $isOpenLeft == $interval_ext_emptySet["is-open-left"] && $isOpenRight == $interval_ext_emptySet["is-open-right"]) {
     return EMPTY_SET;
  }

  $a = $isOpenLeft ? "(" : "[";
  $b = $index["$borderLeft"];
  $c = $index["$borderRight"];
  $d = $isOpenRight ? ")" : "]";

  return $a . $b . "," . $c . $d;
}




// #############################################################################################

function intersectionList($input) {
  $values = parseString($input);

  if ($values["has-error"]) {
    return "input error";
  } else {

    $result = traverseIntersection($values["left-border"], $values["right-border"], $values["is-open-left"], $values["is-open-right"]);

    return toString($result["left-border"],
		    $result["right-border"],
		    $result["is-open-left"],
		    $result["is-open-right"],
		    $values["index"]);
  }
}

function cannonicalIntersection($input) {
  $values = parseParts($input);

  if ($values["has-error"]) {
    return "input error";
  } else {

    $v1 = traverseIntersection($values["left-border"], $values["right-border"], $values["is-open-left"], $values["is-open-right"]);
    // var_dump($v1);
    $result = traverseUnion($v1["left-border"], $v1["right-border"], $v1["is-open-left"], $v1["is-open-right"]);

    return toString($result["left-border"],
		    $result["right-border"],
		    $result["is-open-left"],
		    $result["is-open-right"],
		    $values["index"]);
  }
}

function mostCommonIntersection($input) {
  $values = parseParts($input);

  if ($values["has-error"]) {
    return "input error";
  } else {

    $result = calculateMostCommonIntersection($values["left-border"], $values["right-border"], $values["is-open-left"], $values["is-open-right"]);

    return toStringPart($result["left-border"],
			$result["right-border"],
			$result["is-open-left"],
			$result["is-open-right"],
			$values["index"]);
  }
}


}

namespace {

//canonicInterval(IntervalString)
//Forms a canonic interval string, consisting of a union of connected intervalls sorted by order
//IntervalString: Union of intervals as string
//Example: canonicInterval("[2,2^2) U (-1,3]")="(-1,2^2)"
function canonicInterval($input) {
  $values = IntervalLib\parseString($input);

  if ($values["has-error"]) {
    return "input error";
  } else {

    $result = IntervalLib\traverseUnion($values["left-border"], $values["right-border"], $values["is-open-left"], $values["is-open-right"]);

    return IntervalLib\toString($result["left-border"],
		    $result["right-border"],
		    $result["is-open-left"],
		    $result["is-open-right"],
		    $values["index"]);
  }
}

//intersection(arrayOfIntervals)
//Forms the intersection of unions of intervals in an array
//arrayOfIntervals: array of strings in interval notation, ex: [2,5)
//Example: intersection(array("(-2,0) U (0,2)","[-1,1)"))="[-1,0) U (0,1)"
function intersection($input) {
    $values = array();
    $index = array();

    foreach($input as $item) {
        $el = IntervalLib\parseString($item);

        if ($el["has-error"]) {
            return "input error";
        } else {
            $values[] = $el;
            foreach($el["index"] as $k => $v) {
                $index[$k] = $v;
            }
        }
    }

    $result = IntervalLib\calculateIntersectionSet($values);

    return IntervalLib\toString($result["left-border"],
    		    $result["right-border"],
    		    $result["is-open-left"],
    		    $result["is-open-right"],
    		    $index);
}

}

?>
