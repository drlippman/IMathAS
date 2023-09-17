<?php

// The regression algorithm used here was adapted from AlgoFitLogistic, a
// function in GeoGeogebra created by Hans-Petter Ulven.
// https://github.com/geogebra/geogebra/blob/master/common/src/main/java/org/geogebra/common/kernel/statistics/AlgoFitLogistic.java
// Used under GNU Public License

global $allowedmacros;
array_push($allowedmacros,"logisticregression", "logisticpredict", "logisticsolve");

function logisticpredict($coeff, $x) {
  list($a,$b,$c) = $coeff;
  return $c/(1+$a*exp(-$b*$x));
}

function logisticsolve($coeff, $y) {
  list($a,$b,$c) = $coeff;
  return log(($c/$y - 1)/$a)/(-$b);
}

function logisticregression($x,$y) {
  $allplus = true;
  $allneg = true;
  $data = array_combine($x,$y);
  ksort($data);
  $x = array_keys($data);
  $y = array_values($data);
  foreach ($y as $yval) {
    if ($yval < 0) { $allplus = false; }
    if ($yval > 0) { $allneg = false; }
  }
  $n = count($x);

  // find initial parameters
  $sign = 1;
  $k = .001;
  $x1 = $x[0];
  $y1 = $y[0];
  $x2 = $x[$n-1];
  $y2 = $y[$n-1];
  $ymult = $y1*$y2;
  $e1 = exp($x1);
  $e2 = exp($x2);
  $emult = $e1 * $e2;
  $ydiff = $y1 - $y2;

  $increasing = ($y1 < $y2);

  if ($allplus) {
    if (!$increasing) {
      $sign = -1;
      $k = -$k;
    }
  } else if ($allneg) {
    if ($increasing) {
      $sign = -1;
      $k = -$k;
    }
  } else {
    if (abs($y2) < abs($y1)) {
      $sign = -1;
      $k = -$k;
    }
  }

  // iterate for k
  $err_old =  lr_beta2k($x, $y, $k, $e1, $e2, $emult, $ydiff, $ymult, $y1, $y2);
  $lambda = 0.01;
  $k = $k + $sign * $lambda;
  $err = $err_old + 1;
  while (abs($err - $err_old) > 1e-6) {
    $err = lr_beta2k($x, $y, $k, $e1, $e2, $emult, $ydiff, $ymult, $y1, $y2);
    if ($err < $err_old) {
      $lambda *= 5;
      $err_old = $err;
      $err = $err + 1;
    } else {
      $k = $k - $sign * $lambda;
      $lambda /= 5;
    }
    $k += $sign * $lambda;
  }

  $b = $k;
  $a = lr_a($x1, $y1, $x2, $y2, $k);
  $c = lr_c($x1, $y1, $x2, $y2, $k);
  if (is_nan($a) || is_nan($c) || is_nan($b)) {
    echo "Error in find parameters";
    return;
  }

  // do logistic reg

  $lambda = 0;
  $multfaktor = 2;
  $residual = $old_residual = lr_beta2($x, $y, $a, $b, $c);

  $eps = 1e-14;

  $da = $db = $dc = $eps;

  $iterations = 0;
  $b1 = $b2 = $b3 = 0;
  $m11 = $m22 = $m33 = 0;
  for ($i = 0; $i < $n; $i++) {
    $xv = $x[$i];
    $yv = $y[$i];
    $beta = lr_beta($xv, $yv, $a, $b, $c);
    $dfa = lr_df_a($xv, $a, $b, $c);
    $dfb = lr_df_b($xv, $a, $b, $c);
    $dfc = lr_df_c($xv, $a, $b);
    $b1 += $beta * $dfa;
    $b2 += $beta * $dfb;
    $b3 += $beta * $dfc;
    $m11 += $dfa * $dfa;
    $m22 += $dfb * $dfb;
    $m33 += $dfc * $dfc;
  }

  $startfaktor = max($m11, $m22, $m33);
  $lambda = $startfaktor * 0.001;
  $error = false;

  while (abs($da) + abs($db) + abs($dc) > $eps) {
    $iterations++;
    if ($iterations > 200 || $error) {
      echo "Too many iterations";
      return;
    }
    $b1 = $b2 = $b3 = 0;
    $m11 = $m12 = $m13 = $m21 = $m22 = $m23 = $m31 = $m32 = $m33 = 0;
    for ($i = 0; $i < $n; $i++) {
      $xv = $x[$i];
      $yv = $y[$i];
      $beta = lr_beta($xv, $yv, $a, $b, $c);
      $dfa = lr_df_a($xv, $a, $b, $c);
      $dfb = lr_df_b($xv, $a, $b, $c);
      $dfc = lr_df_c($xv, $a, $b);
      $b1 += $beta * $dfa;
      $b2 += $beta * $dfb;
      $b3 += $beta * $dfc;
      $m11 += $dfa * $dfa + $lambda;
      $m12 += $dfa * $dfb;
      $m13 += $dfa * $dfc;
      $m22 += $dfb * $dfb + $lambda;
      $m23 += $dfb * $dfc;
      $m33 += $dfc * $dfc + $lambda;
    }

    $m21 = $m12;
    $m31 = $m13;
    $m32 = $m23;

    $det = $m11 * ($m22 * $m33 - $m32 * $m23) -
           $m12 * ($m21 * $m33 - $m23 * $m31) +
           $m13 * ($m21 * $m32 - $m22 * $m31);

    if (abs($det) < 1e-20) {
      echo "Singular matrix";
      $error = true;
      $da = $db = $dc = 0;
    } else {
      $da = ($b1 * ($m22 * $m33 - $m32 * $m23) -
             $m12 * ($b2 * $m33 - $m23 * $b3) +
             $m13 * ($b2 * $m32 - $m22 * $b3))/$det;
      $db = ($m11 * ($b2 * $m33 - $b3 * $m23) -
             $b1 * ($m21 * $m33 - $m23 * $m31) +
             $m13 * ($m21 * $b3 - $b2 * $m31))/$det;
      $dc = ($m11 * ($m22 * $b3 - $m32 * $b2) -
             $m12 * ($m21 * $b3 - $b2 * $m31) +
             $b1 * ($m21 * $m32 - $m22 * $m31))/$det;
      $newa = $a + $da;
      $newb = $b + $db;
      $newc = $c + $dc;
      $residual = lr_beta2($x, $y, $newa, $newb, $newc);
      if ($residual < $old_residual) {
        $lambda /= 3;
        $old_residual = $residual;
        $multfaktor = 2;
        $a = $newa;
        $b = $newb;
        $c = $newc;
      } else {
        $lambda *= $multfaktor;
        $multfaktor *= 2;
      }
    }
  }

  if (is_nan($a) || is_nan($c) || is_nan($b)) {
    echo "Error in regression";
    return;
  }
  return array($a, $b, $c);
}

function lr_a($ax1, $ay1, $ax2, $ay2, $ab) {
  return exp($ab * ($ax1 + $ax2)) * ($ay1 - $ay2) / ($ay2 * exp($ab * $ax1) - $ay1 * exp($ab * $ax2));
}

function lr_c($cx1, $cy1, $cx2, $cy2, $cb) {
  return $cy1 * $cy2 * (exp($cb * $cx1) - exp($cb * $cx2)) / ($cy2 * exp($cb * $cx1) - $cy1 * exp($cb * $cx2));
}

function lr_df_c($x, $a1, $b1) {
  return 1/(1 + $a1 * exp(-$b1 * $x));
}
function lr_df_a($x, $a1, $b1, $c1) {
  $df_c = lr_df_c($x, $a1, $b1);
  return $df_c * $df_c * exp(-$b1 * $x) * (-$c1);
}
function lr_df_b($x, $a1, $b1, $c1) {
  $df_c = lr_df_c($x, $a1, $b1);
  return $df_c * $df_c * exp(-$b1 * $x) * $x * $a1 * $c1;
}

function lr_f($x, $a1, $b1, $c1) {
  return lr_df_c($x, $a1, $b1) * $c1;
}

function lr_beta($x, $y, $a1, $b1, $c1) {
  return $y - lr_f($x, $a1, $b1, $c1);
}

function lr_beta2($x, $y, $a1, $b1, $c1) {
  $sum = 0;
  for ($i = 0; $i < count($y); $i++) {
    $beta = lr_beta($x[$i], $y[$i], $a1, $b1, $c1);
    $sum += $beta * $beta;
  }
  return $sum;
}

function lr_betak($x, $y, $k1, $e1, $e2, $emult, $ydiff, $ymult, $y1, $y2) {
  $e1k = pow($e1, $k1);
  $e2k = pow($e2, $k1);
  $efrac = pow($emult / exp($x), $k1);
  $fval = $ymult * ($e1k - $e2k) / ($y2 * $e1k - $y1 * $e2k + $ydiff * $efrac);
  return $y - $fval;
}
function lr_beta2k($x, $y, $k1, $e1, $e2, $emult, $ydiff, $ymult, $y1, $y2) {
  $sum = 0;
  for ($i = 0; $i < count($y); $i++) {
    $beta = lr_betak($x[$i], $y[$i], $k1, $e1, $e2, $emult, $ydiff, $ymult, $y1, $y2);
    $sum += $beta * $beta;
  }
  return $sum;
}



?>
