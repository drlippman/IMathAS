<?php


global $allowedmacros;
array_push($allowedmacros, "polycolorplot");


// input is an array of complex numbers
// for example, $a = [[0,1],[2,0],[3,4]] for 0+i , 2+0i and 3+4i
// Function automatically computes conjugates, so do not enter both
// unless you want a double root.
// Function outputs the equation of the polynomial, the asciisvg code, and a list of the zeros.
function polycolorplot($inputs){
  if (!is_array($inputs)) {
		echo 'function expects an array as the input';
		return false;
	}
    // count the number of inputs
    $n = count($inputs) - 1;

    // compute conjugates of any input with a non zero imaginary part and add them to the array of inputs.
    // Also make a list of strings containing the zeros.
    // Also create the coefficients of each linear factor and each irreducible quadratic factor.
    for ($i = 0;$i <= $n;$i++){
        if ($inputs[$i][1] != 0){
            $inputs[] = [$inputs[$i][0], -$inputs[$i][1]];
            $a[] = [1, -2*$inputs[$i][0], $inputs[$i][0]*$inputs[$i][0] + $inputs[$i][1]*$inputs[$i][1]];
        } else {
          $a[] = [1,-$inputs[$i][0]];
        }
    }

    sort ($inputs);


    // form the product of all the (z - c)
    // and create the expanded equation of the polynomial
    $A = $a[0];

    for ($i = 1; $i <= $n; $i++) {
      $B = $a[$i];
      $p = count($A);
      $q = count($B);
      $product = [];
      for ($j = 0; $j < $p; $j++) {
        for ($k = 0; $k < $q; $k++) {
            $product[$j + $k] += $A[$j] * $B[$k];
        }
      }
      $A = $product;
    }
    // recompute index
    $n = count($inputs) - 1;
    // form the expanded equation of the polynomial
    $degree = $n + 1;
    $poly = "{$A[0]} x^{$degree}";
    for ($i = 1; $i <= $degree; $i++) {
      $exponent = $degree - $i;
      $poly .= "+ {$A[$i]} x^{$exponent}";
    }


    // list all of the complex zeros
    // returned as a list that can be used for $answer
    for ($i = 0; $i <= $n; $i++) {
      if ($inputs[$i][1] != 0){
        $zeros[$i] = "{$inputs[$i][0]}+{$inputs[$i][1]}i";
      } else {
        $zeros[$i] = "{$inputs[$i][0]}";
      }
    }
    $zeros_list = implode(",", $zeros);



    // create array representations for each complex zero
    // a+bi -> [a, -b, a, b]
    // and create their white points on the graph
    for ($i = 0;$i <= $n;$i++){
        $C[] = array(
            $inputs[$i][0],
            -$inputs[$i][1],
            $inputs[$i][1],
            $inputs[$i][0]
        );
        $plot_zeros .= "fill='white';fillopacity='0';stroke='white';circle([{$inputs[$i][0]},{$inputs[$i][1]}],0.15);";
    }


    // Form points in the domain and evaluate function (z - c1)(z - c2)...(z - ci)
    $xmin = - 5.5;
    $xmax = 5.5;
    $xres = .12;
    $ymin = - 5.5;
    $ymax = 5.5;
    $yres = .12;

    $xsteps = abs($xmax - $xmin) / $xres;
    $ysteps = abs($ymax - $ymin) / $yres;

    $index = 0;

    $asciisvg = "initPicture({$xmin},{$xmax},{$ymin},{$ymax});";

    for ($i = 0;$i <= $xsteps;$i++){

        for ($j = 0;$j <= $ysteps;$j++){

            // a complex number z = a+bi in the domain, represented as an array
            $z = array(
                ($i - $xsteps / 2) * $xres,
                ($ysteps / 2 - $j) * $yres,
                ($j - $ysteps / 2) * $yres,
                ($i - $xsteps / 2) * $xres
            );

            // coordinates of the point on the plane representing z = a + bi
            $Xin[$index] = $z[0];
            $Yin[$index] = $z[2];

            // compute the difference (z - c)
            for ($k = 0;$k <= $n;$k++){
                $A[$k] = array(
                    $z[0] - $C[$k][0],
                    $z[1] - $C[$k][1],
                    $z[2] - $C[$k][2],
                    $z[3] - $C[$k][3]
                );
            }

            // compute the product of all the (z - c) factors
            $B = array(1, 0, 0, 1);
            for ($k = 0;$k <= $n;$k++){
                $B = array(
                  $A[$k][0]*$B[0] + $A[$k][1]*$B[2],
                  $A[$k][0]*$B[1] + $A[$k][1]*$B[3],
                  $A[$k][2]*$B[0] + $A[$k][3]*$B[2],
                  $A[$k][2]*$B[1] + $A[$k][3]*$B[3]
                );
            }

            // output values, used to determine colors
            $Xout[$index] = $B[0];
            $Yout[$index] = $B[2];

            // coloring scheme
            // arg controls the hue/color
            // modulus controls the luminosity
            // saturation set at 1 for now
            $modulus = sqrt($Xout[$index] * $Xout[$index] + $Yout[$index] * $Yout[$index]);

            if ($Yout[$index]<0) {
              $arg[$index] = 2*M_PI + atan2($Yout[$index],$Xout[$index]);
            } else {
              $arg[$index] = atan2($Yout[$index],$Xout[$index]);
            }

            $h = $arg[$index] / (2 * M_PI);
            //make multiple copies of $h, one for each calculation of r,g,b
            $hr = $hg = $hb = $h;

            $hue[$index] = $h;
            $s = 1;
            $l = .5 + .5 * pow(1.02, - $modulus*$modulus);


            // Converts an HSL color value to RGB and to HEX. Conversion formula
            // adapted from http://en.wikipedia.org/wiki/HSL_color_space.
            // Assumes h, s, and l are contained in [0, 1].
            // Returns r, g, and b in [0, 255].
            if ($s == 0){
                $rgb[0] = $l * 255;
                $rgb[1] = $l * 255;
                $rgb[2] = $l * 255;
            }
            else{
                // some temporary variables to make the calculations easier.
                if ($l < 0.5){
                    $temp2 = $l * (1 + $s);
                }
                else{
                    $temp2 = $l + $s - $l * $s;
                    $temp1 = 2 * $l - $temp2;
                }

                // calculate $rgb[0] (red)
                if ($hr + 1 / 3 < 0){
                    $hr = $hr + 1;
                }
                if ($hr + 1 / 3 > 1){
                    $hr = $hr - 1;
                }

                $rgb[0] = $temp1;
                if ($hr + 1 / 3 < 2 / 3){
                    $rgb[0] = $temp1 + ($temp2 - $temp1) * (2 / 3 - ($hr + 1 / 3)) * 6;
                }
                if ($hr + 1 / 3 < 1 / 2){
                    $rgb[0] = $temp2;
                }
                if ($hr + 1 / 3 < 1 / 6){
                    $rgb[0] = $temp1 + ($temp2 - $temp1) * 6 * ($hr + 1 / 3);
                }

                $rgb[0] = round($rgb[0] * 255);


                // calculate $rgb[1] (green)
                if ($hg < 0){
                    $hg = $hg + 1;
                }
                if ($hg > 1){
                    $hg = $hg - 1;
                }

                $rgb[1] = $temp1;
                if ($hg < 2 / 3){
                    $rgb[1] = $temp1 + ($temp2 - $temp1) * (2 / 3 - $hg) * 6;
                }
                if ($hg < 1 / 2){
                    $rgb[1] = $temp2;
                }
                if ($hg < 1 / 6){
                    $rgb[1] = $temp1 + ($temp2 - $temp1) * 6 * $hg;
                }

                $rgb[1] = round($rgb[1] * 255);


                // calculate $rgb[2] (blue)
                if ($hb - 1 / 3 < 0){
                    $hb = $hb + 1;
                }
                if ($hb - 1 / 3 > 1){
                    $hb = $hb - 1;
                }

                $rgb[2] = $temp1;
                if ($hb - 1 / 3 < 2 / 3){
                    $rgb[2] = $temp1 + ($temp2 - $temp1) * (2 / 3 - ($hb - 1 / 3)) * 6;
                }
                if ($hb - 1 / 3 < 1 / 2){
                    $rgb[2] = $temp2;
                }
                if ($hb - 1 / 3 < 1 / 6){
                    $rgb[2] = $temp1 + ($temp2 - $temp1) * 6 * ($hb - 1 / 3);
                }

                $rgb[2] = round($rgb[2] * 255);
            }


            // Convert rgb values from 0-255 into hex
            // and concatenate them to make #RRGGBB colors
            $digits = array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F"
            );

            for ($k = 0;$k <= 2;$k++){
                $hex1 = $rgb[$k] % 16;
                $hex2 = floor($rgb[$k] / 16) % 16;
                $hex[$k] = $digits[$hex2] . $digits[$hex1];
            }

            $hexnumber[$index] = "#" . $hex[0] . $hex[1] . $hex[2];

            $asciisvg .= "fill='{$hexnumber[$index]}';stroke='{$hexnumber[$index]}';circle([{$Xin[$index]},{$Yin[$index]}],.12);";

            $index++;
        }
    }
            $asciisvg .= $plot_zeros;
            $asciisvg .= "axes(1, 1, 'labels', 1),'350,200'";


    return array($poly,$asciisvg,$zeros_list,$table);
}


?>
