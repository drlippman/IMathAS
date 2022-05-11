<?php
// Polynomial Division
// Mike Jenck, Originally developed July 25-27, 2018
// licensed under GPL version 2 or later
//
// File Version : 7.5
//

global $allowedmacros;

if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "formpoly3fromstring", "formpoly3fromresults", "dividepoly3", "longdivisionpoly3", "writepoly3");

include_once("fractions.php");  // fraction routine

// function formpoly3fromstring(variable, polynomialstring, [IsFraction=TRUE])
// Creates an array of coefficients whose position in the array
// corresponds to the power of the variable
// Each element is stored in the form of an array(numerator, denominator).
//
// INPUTS:
//
// variable: a string that contains the variable
// polynomialstring: a string that contains the polynomial
//
// Optional
// IsFraction: a boolan that stores the the coeificients as fractions
//
// Example: $dividend = formpoly3fromstring("x","3x^4+ 2x^2+1")
//
//
function formpoly3fromstring($variable, $polynomialstring, $IsFraction=TRUE)
{
	if(is_null($variable)) {
		$variable = "x";
		echo "formpoly3fromstring - variable is null - using x.<br/>\r\n";
	}
	else {
    	if($variable == "") {
        	$variable = "x";
			echo "formpoly3fromstring - variable is an empty string - using x.<br/>\r\n";
		}
	}

	if(is_null($polynomialstring)) {
		echo "formpoly3fromstring - the polynomial is null - FAIL.<br/>\r\n";
	}

	if($polynomialstring != "") {
		// make sure there are no spaces
		$polynomialstring = str_replace(" ","",$polynomialstring);

		// make sure there is something to do
		$results = array();       // the results upon parsing

		$coefficients = array();  // holds each term in a fractional form
		$powers  = array();       // holds the power for each term

		$monoresults = array();   // the results of any operations from the $coefficients and $powers arrays
		$parts = array();         // string array of each term

		// parse to a array of string terms
		$start = 0;
		$index = 0;
		for ($i = 1; $i < strlen($polynomialstring); $i++) {
			$char = $polynomialstring[$i];
			if(($char =="+")or($char =="-")){
				$end = $i-$start;
				$parts[$index] = substr($polynomialstring,$start,$end);
				$start = $i;
				$index++;
			}
		}
		$end = strlen($polynomialstring)-$start;
		$parts[$index] = substr($polynomialstring,$start,$end);

		// parse each term into powers and fractional coefficient
		//
		$highestpower = -1;
		for ($i = 0; $i < count($parts); $i++) {
			$monoreturn = poly3_breakmonomial($variable,$parts[$i]);
			$rawcoefficientreturn = $monoreturn[0];
			$powers[$i] = $monoreturn[1];
			$coefficients[$i] = array();
			if($highestpower<$powers[$i]) {
				$highestpower=$powers[$i];
			}

			// process the $rawcoefficientreturn into a fraction
			$slash = strpos($rawcoefficientreturn,"/");
			//$temp = substr($rawcoefficientreturn,0,$slash);
			if($slash>-2) {
				$length = strlen($rawcoefficientreturn);
				$coefficients[$i][0] = substr($rawcoefficientreturn,0,$slash); // top
				$coefficients[$i][1] = substr($rawcoefficientreturn,($slash+1),$length); // bot
			}
			else {
				$coefficients[$i][0] = $rawcoefficientreturn; // top
				$coefficients[$i][1] = 1; // bot
			}

            // 2020-01-23
            // make integers - to avoid the error of nonnumeric in fractionadd
            $coefficients[$i][0] = intval($coefficients[$i][0],10); // top
            $coefficients[$i][1] = intval($coefficients[$i][1],10); // bot
		}

		// Now add any like terms coefficient
		for ($i = 0; $i < count($parts); $i++) {
			$currentpower = $powers[$i];

			// does the power exist?
			if (!isset($monoresults[$currentpower])) {
				$monoresults[$currentpower]= $coefficients[$i];
			}
			else {
				$monoresults[$currentpower]=fractionadd($monoresults[$currentpower],$coefficients[$i]);
			}
		}

		// now set the return array to all zeros
		for($i=0;$i<=$highestpower; $i++) {
			if($IsFraction){
				$results[$i]=array(0,1);
				if (isset($monoresults[$i])) {
					$results[$i]=$monoresults[$i];
				}
			}
			else {
				$results[$i]=0;
				if (isset($monoresults[$i])) {
					$results[$i]=$monoresults[$i][0]/$monoresults[$i][1];
				}
			}
		}

		return $results;
	}
	else {
		echo "formpoly3fromstring - the polynomial string is empty - FAIL.<br/>\r\n";
		return null;
 	}
}

// function formpoly3fromresults(results, [IsFraction=TRUE])
// Creates an array of coefficients whose position in the array
// corresponds to the power of the variable
// Each element is stored in the form of an array(numerator, denominator).
//
// INPUTS:
//
// $results: from dividepoly3 $results[*][1]
//
// Optional
// IsFraction: a boolan that stores the the coeificients as fractions
//
// Example: $quotient = formpoly3fromstring($results[0][1])
//
//
function formpoly3fromresults($results, $IsFraction=TRUE)
{
	if(is_null($results)) {
		echo "formpoly3fromresults - the polynomial is null - FAIL.<br/>\r\n";
        return null;
	}

    $power = $results[0];
    for ($i = 0; $i < $power; $i++) {
        $returnval[$i] = array(0,1);
    }

    $returnval[$power] = $results[1];
	return $returnval;
}


// internal only
function poly3_breakmonomial($variable, $monominal)
{
	$position = strpos($monominal,$variable);
	if($position>-1){
		$numbers = explode($variable,$monominal);
		if($numbers[0]=="-") {
			$numbers[0]= -1;
		}
        // bug fix 2018-10-17
		if(($numbers[0]=="")||($numbers[0]=="+")) {
			$numbers[0]= 1;
		}
		// strip off the + sign
		$numbers[0]= str_replace("+","",$numbers[0]);

		// stip off the exponent ^ symbol
		$numbers[1]= str_replace("^","",$numbers[1]);
		if($numbers[1]=="") {
			$numbers[1]= 1;
		}
	}
	else {
		$numbers[0]= str_replace("+","",$monominal);
		$numbers[1]= 0;
	}
	return $numbers;
}

// writepoly3(poly3, [variable="x", IsFraction=TRUE, HideZero=TRUE])
// Creates a string that represents the polynomial

// Each element is stored in the form of an array(numerator, denominator).
//
// INPUTS:
//
// variable: a string that contains the variable
// poly3: an array of coeficients to build the polynomial
//
// Optional
// IsFraction: a boolan that stores the the coeificients as fractions
// HideZero: a boolean that stores a flag to show zero coefinents
//
//
// Example: $dividend = writepoly3("x",array("1,0,2,0,3"))
//
//		    $dividend = "3x^4+2x^2+1"
//
function writepoly3($poly3, $variable="x", $IsFraction=TRUE, $HideZero=TRUE)
{
	if(is_null($poly3)) {
		echo "writepoly3 - the polynomial is null - FAIL.<br/>\r\n";
	}

	if(is_array($poly3) and count($poly3)>0) {
		// make sure there is something to do
		$results = "";
		$start = count($poly3)-1;
		for ($i = $start; $i>=0; $i--) {
			if($IsFraction){
				if(($poly3[$i][0]==0)and($HideZero)) {
					// leave blank
				}
				else {
					if(($i!=$start)and($poly3[$i][0]>=0)) {
						$results .= "+";
					}

					if($i==$start) {
						$coef = $poly3[$i][0]/$poly3[$i][1];
						if($coef == 1) {
							$results .= poly3_variablepower($i,$variable);
						}
						elseif($coef == -1) {
							$results .= "-".poly3_variablepower($i,$variable);
						}
						else {
							$results .= poly3_fractiontostring($poly3[$i]).poly3_variablepower($i,$variable);
						}
					}
					else {
						$results .= poly3_fractiontostring($poly3[$i]).poly3_variablepower($i,$variable);
					}
				}
			}
			else {
				if(($poly3[$i]==0)and($HideZero)) {
					// leave blank
				}
				else {
					if(($i!=$start)and($poly3[$i]>0)) {
							$results .= "+";
						}

					if($i==$start) {
						if($poly3[$i] == 1) {
							$results .= poly3_variablepower($i,$variable);
						}
						elseif($poly3[$i] == -1) {
							$results .= "-".poly3_variablepower($i,$variable);
						}
						else {
							$results .= $poly3[$i].poly3_variablepower($i,$variable);
						}
					}
					else {
						$results .= $poly3[$i].poly3_variablepower($i,$variable);
					}
				}
			}
		}

		return $results;
	} else {
		echo "writepoly3 - the polynomial is an empty array - FAIL.<br/>\r\n";
		return null;
 	}
}

// internal only
//https://github.com/drlippman/IMathAS/blob/7f838e9160cc10d51b128def17862c9b9f43acea/assessment/macros.php
function poly3_reducefraction($top, $bot){
	$gcf = gcd($top,$bot);
  	$top /= $gcf;
  	$bot /= $gcf;

	return array($top,$bot);
}

// internal only
function poly3_fractiondivide($numerator, $denominator){
	$top = $numerator[0]*$denominator[1];
	$bot = $numerator[1]*$denominator[0];

	if($bot < 0) {
		$top*=-1;
		$bot*=-1;  // must be positive
	}
  	return poly3_reducefraction($top, $bot);
}

// internal only
function poly3_fractionmonominalmultiply($monominal,$divisor){
	$power = count($divisor)-1;

	// zero out positions that are not used
	for($i = 0; $i < $monominal[0]; $i++){
	  $result[$i] = array(0,1);
	}

	// multiply
    for($i = 0; $i <= $power; $i++){
		$position = $monominal[0]+$i;
		$a = $monominal[1][0];
		$b = $monominal[1][1];
		$c = $divisor[$i][0];
		$d = $divisor[$i][1];
		$top = $a*$c;
		$bot = $b*$d;

		$result[$position] = poly3_reducefraction($top, $bot);
	}
	return $result;
}

// internal only
function poly3_fractionnegative($poly3){
	$power = count($poly3)-1;
	for($i = 0; $i <= $power; $i++){
		if($poly3[$i][0]!=0) {
			$poly3[$i][0] *=-1;
		}

	}

	return $poly3;
}

// internal only
function poly3_fractionsubtract($minuend,$subtrahend){
	// minuend − subtrahend = difference
	$powerminuend = count($minuend)-1;
	$powersubtrahend = count($subtrahend)-1;
	if($powerminuend!=$powersubtrahend) {
    	echo "poly3_fractionsubtract - polynomials are different - ERROR.<br/>\r\n minuend=".var_dump($minuend)."<br/>\r\n subtrahend=".var_dump($subtrahend)."<br/>\r\n";
	}

	$power = max($powerminuend,$powersubtrahend);
	for($i = 0; $i <= $power; $i++){
		if($i<=$powerminuend) {
			$a = $minuend[$i][0];
			$b = $minuend[$i][1];
		}
		else {
			$a = 0;
			$b = 1;
		}
		if($i<=$powersubtrahend) {
			$c = $subtrahend[$i][0];
			$d = $subtrahend[$i][1];
		}
		else {
			$c = 0;
			$d = 1;
		}
		$top = $a*$d-$b*$c;
		$bot = $b*$d;

		$result[$i] = poly3_reducefraction($top, $bot);
	}

	// now eliminate any leading terms that are 0
	for($i = $power; $i >=0; $i--){
	  if($result[$i][0]==0) {
	  	 unset($result[$i]);
	  }
	  else {
	  	break;
	  }
	}

	return $result;
}

// internal only
function poly3_decimaldivide($numerator, $denominator){
  	return $numerator/$denominator;
}

// internal only
function poly3_decimalmonominalmultiply($monominal,$divisor){
	$power = count($divisor)-1;

	// zero out positions that are not used
	for($i = 0; $i < $monominal[0]; $i++){
	  $result[$i] = 0;
	}

	// multiply
    for($i = 0; $i <= $power; $i++){
		$position = $monominal[0]+$i;
		$a = $monominal[1];
		$c = $divisor[$i];

		$result[$position] = $a*$c;
	}
	return $result;
}

// internal only
function poly3_decimalnegative($poly3){
	$power = count($poly3)-1;
	for($i = 0; $i <= $power; $i++){
		$poly3[$i] *=-1;
	}

	return $poly3;
}

// internal only
function poly3_decimalsubtract($minuend,$subtrahend){
	// minuend − subtrahend = difference
	$powerminuend = count($minuend)-1;
	$powersubtrahend = count($subtrahend)-1;
	if($powerminuend!=$powersubtrahend) {
    	echo "poly3_fractionsubtract - polynomials are different - ERROR.<br/>\r\n minuend=".var_dump($minuend)."<br/>\r\n subtrahend=".var_dump($subtrahend)."<br/>\r\n";
	}

	$power = max($powerminuend,$powersubtrahend);
	for($i = 0; $i <= $power; $i++){
		if($i<=$powerminuend) {
			$a = $minuend[$i];
		}
		else {
			$a = 0;
		}
		if($i<=$powersubtrahend) {
			$c = $subtrahend[$i];
		}
		else {
			$c = 0;
		}

		$result[$i] = $a-$c;
	}

	// now eliminate any leading terms that are 0
	for($i = $power; $i >=0; $i--){
	  if($result[$i]==0) {
	  	 unset($result[$i]);
	  }
	  else {
	  	break;
	  }
	}

	return $result;
}

// dividepoly3(dividend, divisor [, IsFraction=TRUE])
//
// Does the polynomial long division and returns an array of results
//
// INPUTS:
//
// dividend: a poly3 formed dividend
// divisor: a poly3 formed divisor
//
// Optional
// IsFraction: a boolan that stores the the coeificients as fractions
//
// Example: $result  = dividepoly3($dividend, $divisor);
//
//          $result will contain an array in the following form
//  $result[0][0] = $dividend
//  $result[0][1] = the power, array(numerator, denominator) (quotient for this step)
//  $result[0][2] = a poly3 polynomial by multipling $result[0][1] and the divisor
//  $result[0][3] = the poly3 polynomial that needs to be subtracted (-$result[0][2])
//  $result[1][0] = the resultant divided (at least 1 power smaller than the previous
//  etc.
//
//
function dividepoly3($dividend, $divisor, $IsFraction=TRUE) {

	if($IsFraction){
		return poly3_dividefractions($dividend, $divisor);
	}
	else {
		return poly3_dividedecimal($dividend, $divisor);
	}
}

// internal only
function poly3_dividefractions($dividendstart, $divisor) {

	$power = count($dividendstart)-1;
	$divisorposition = count($divisor)-1;
	$results[0][0] = $dividendstart;
	$resultindex = 0;
	// now start the loop
	for($i = $power; $i >=$divisorposition; $i--){
		$dividend = $results[$resultindex][0];
		$dividendposition = count($dividend)-1;

		if($dividendposition <$i) {
            // fixed bug 2018-10-14
            // divisor power is greater then the dividend power
			// position is suppose to be a zero
            if(!isset($results[$resultindex][0][0])){
                $results[$resultindex][0][0] = array(0,1);
                $dividend = array(0,1);
                $dividendposition = 0;
            }
		} else {
            if($dividend[$dividendposition][0]==0) {
                // nothing to do - skip a zero entry
            }
            else {
                $quotientposition = $i-$divisorposition;
                $results[$resultindex][1] = array($quotientposition, poly3_fractiondivide($dividend[$dividendposition],$divisor[$divisorposition]));
                $resultsfor2 = poly3_fractionmonominalmultiply($results[$resultindex][1],$divisor);
                $count0 = count($results[$resultindex][0]);
                $count2 = count($resultsfor2);
                if($count0!=$count2) {
                    // Crashed fix - 2018-10-08
                    //skip this as the exponents don't line up
                } else {
                    $results[$resultindex][2] = $resultsfor2;
                    $results[$resultindex][3] = poly3_fractionnegative($results[$resultindex][2]);
                    $nextstep = $resultindex +1;
                    $results[$nextstep][0] = poly3_fractionsubtract($results[$resultindex][0],$results[$resultindex][2]);
                }

                // move counter forward
                $resultindex++;
            }
        }
	}
	return $results;
}

// internal only
function poly3_dividedecimal($dividendstart, $divisor) {

	$power = count($dividendstart)-1;
	$divisorposition = count($divisor)-1;
	$results[0][0] = $dividendstart;
	$resultindex = 0;
	// now start the loop
	for($i = $power; $i >=$divisorposition; $i--){
		$dividend = $results[$resultindex][0];
		$dividendposition = count($dividend)-1;

		if($dividend[$dividendposition] < $i) {
			// divisor power is greater then the dividend power
			// position is suppose to be a zero
            if(!isset($results[$resultindex][0][0])){
                $results[$resultindex][0] = 0;
                $dividend = 0;
                $dividendposition = 0;
            }
		} else {
            if($dividend[$dividendposition][0]==0) {
                // nothing to do - skip a zero entry
            }
            else {
                $quotientposition = $i-$divisorposition;
                $results[$resultindex][1] = array($quotientposition, poly3_decimaldivide($dividend[$dividendposition],$divisor[$divisorposition]));
                $resultsfor2 = poly3_decimalmonominalmultiply($results[$resultindex][1],$divisor);
                $count0 = count($results[$resultindex][0]);
                $count2 = count($resultsfor2);
                if($count0!=$count2) {
                    // Crashed fix - 2018-10-08
                    //skip this as the exponents don't line up
                } else {
                    $results[$resultindex][2] = $resultsfor2;
                    $results[$resultindex][3] = poly3_decimalnegative($results[$resultindex][2]);
                    $nextstep = $resultindex +1;
                    $results[$nextstep][0] = poly3_decimalsubtract($results[$resultindex][0],$results[$resultindex][2]);
                }
                // move counter forward
                $resultindex++;
            }
        }
	}
	return $results;
}


//
//   HTML string ouput section
//

// internal only
function poly3_fractiontostring($f){
	if($f[1]!=1){
		return $f[0]."/".$f[1];
	}
	else {
		return $f[0];
	}
}

// internal only
function poly3_htmlcellspaces($number){
	$cells = "";
	for ($i =0; $i<$number; $i++) {
		$cells .= "<td>&nbsp;</td>";
	}
	return $cells;
}

// internal only
function poly3_coefficientstring($coefarray,$coef,$quotientPower){

	if($coef==-1) {
		if($quotientPower>0) {
			$coefstring = "-";
		}
		else {
			$coefstring = "-1";
		}
	}
	elseif ($coef==1){
		if($quotientPower>0) {
			$coefstring = "";
		}
		else {
			$coefstring = "1";
		}
	}
	else {
		if(is_null($coefarray)){
			$coefstring = $coef;
		}
		else {
		  $coefstring = poly3_fractiontostring($coefarray);
		}
	}

	return $coefstring;

}

// internal only
function poly3_variablepower($quotientPower,$variable) {
	if($quotientPower>1){
		return "$variable^$quotientPower";
	}
	elseif ($quotientPower==1){
		return "$variable";
	}
	else {
		return "";
	}

}


// longdivisionpoly3(dividend, divisor, variable="x", [displayASCIIticks=1, IsFraction=1, HideZero=1, ShowAsSubtraction=0])
//
// Does the long division and returns a string of the results
//
// INPUTS:
//
// dividend: a poly3 formed dividend
// divisor: a poly3 formed divisor
// variable: a string that contains the variable
//
// Optional
// displayASCIIticks: a boolean flag to put tick marks around each monomial
//   1 = use math tick `
//   0 = do not use math tick
//
// IsFraction: a boolan that stores the the coeificients as fractions
// 1 = output fractions (if needed)
// 0 = output decimals
//
// HideZero: a boolean that stores a flag to show zero coefinents
// 1 = don't show zero coefficients
// 0 = show all
//
// ShowAsSubtraction : a boolean that when set to TRUE show the subtraction
// 1 = use -() notation
// 0 = multiply the negative and show the results
//
// Example: $result  = longdivisionpoly3($dividend, $divisor);
//
//          $result will contain a string of HTML table code that can be displayed in the answer.
//
function longdivisionpoly3($dividend, $divisor, $variable="x", $IsFraction=1, $displayASCIIticks=1, $HideZero=1, $ShowAsSubtraction=0) {
	$HideZeroMinus = TRUE;
	$HideZeroDifference = TRUE;

	if($displayASCIIticks) {
		$MathSymbol = "`";
	}
	else {
		$MathSymbol = "";
	}
	$TableResults = dividepoly3($dividend, $divisor, $IsFraction);  // this does the polynomial long division

	// ---------------------------------------------------------------------------------------------------------------
	// quotient ------------------------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------------------------------
	$dividendposition = count($dividend)-1;
	$quotientposition = $TableResults[0][1][0];  // quotient
	//$highestpower = count($TableResults[0][0])-1;
	$number = $dividendposition-$quotientposition;
	$quotient = "<tr>\r\n<td>&nbsp;</td>";  // position for the divisor.
	$quotient .= poly3_htmlcellspaces($number);

	for ($i = $quotientposition; $i>=0; $i--) {
		$index = $quotientposition-$i;
		$quotientPower = NULL;
		if(array_key_exists(1,$TableResults[$index])) {
			if(array_key_exists(0,$TableResults[$index][1])) {
				$quotientPower = $TableResults[$index][1][0];
			}
		}

		if(!is_null($quotientPower)) {
			if($IsFraction){
				$coefarray = $TableResults[$index][1][1];
				$coef = $coefarray[0]/$coefarray[1];
			}
			else {
				$coefarray = NULL;
				$coef = $TableResults[$index][1][1];
			}

			if($coef==0){
				if($HideZero) {
					$quotient .= "<td>&nbsp;</td>";
				}
				else {
					$quotient .= "<td class='right'>$MathSymbol+0".poly3_variablepower($quotientPower,$variable)."$MathSymbol</td>";
				}
			}
			else {
				if(($i != $quotientposition)and($coef>0)) {
					$sign = "+";
				}
				else {
				$sign = "";
			}
				$quotient.= "<td class='right'>$MathSymbol$sign".poly3_coefficientstring($coefarray,$coef,$quotientPower).poly3_variablepower($quotientPower,$variable)."$MathSymbol</td>";
			}
		}
	}

	// ---------------------------------------------------------------------------------------------------------------
	// question ------------------------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------------------------------
	$question = "<tr>\r\n<td>$MathSymbol".writepoly3($divisor,$variable,$IsFraction,$HideZero)."$MathSymbol</td>";

	// create the dividend into a html table
	for ($i = $dividendposition; $i>=0; $i--) {
		// border
		if($i == $dividendposition){
			$question .= "<td class='topleftborder'>";
		}
		else {
			$question .= "<td class='topborder'>";
		}

		// elements
		if($IsFraction){
			$coefarray = $dividend[$i];
			$coef = $coefarray[0]/$coefarray[1];
		}
		else {
			$coefarray = NULL;
			$coef = $dividend[$i];
		}

		if($coef==0){
			if($HideZero) {
				$question .= "&nbsp;</td>";
			}
			else {
				$question .= "$MathSymbol+0".poly3_variablepower($i,$variable)."$MathSymbol</td>";
			}
		}
		else {
			if(($i != $dividendposition)and($coef>0)) {
				$sign = "+";
			}
			else {
				$sign = "";
			}
			$question.= "$MathSymbol$sign".poly3_coefficientstring($coefarray,$coef,$i).poly3_variablepower($i,$variable)."$MathSymbol</td>";
		}
		$remainderColumn = $i;
	}
	// now add for the remainder column
	$question .= "<td>&nbsp;</td></tr>";
	$quotient.= "</tr>\r\n";

	// ---------------------------------------------------------------------------------------------------------------
	// Create the Table ----------------------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------------------------------

	$Table  = "<table class='p3longdiv'>\r\n";
	$Table .= $quotient;
	$Table .= $question;

	// ---------------------------------------------------------------------------------------------------------------
	// Start the polynomial long division  ---------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------------------------------------
    $rows = count($TableResults)-1;
	for($i = 0; $i < $rows; $i++){
        $showZeroRemainder = true;
		// line 1 is the line we subtract and underline
		$line1= "<tr>\r\n<td>&nbsp;</td>".poly3_htmlcellspaces($i);  // position for the divisor and the missing

		// loop through all columns
		if($ShowAsSubtraction){
			$subtrahend = $TableResults[$i][2];
			$subtrahendLeft = "-(";
			$subtrahendRight = ")";
		}
		else {
			$subtrahend = NULL;
			if(array_key_exists(3,$TableResults[$i])) {
				$subtrahend = $TableResults[$i][3];
			}

			$subtrahendLeft = "";
			$subtrahendRight = "";
		}

		if($subtrahend != NULL) {
			$subtrahendLeftShow = TRUE;

			$j = $i+1;  // get the results from the next line
			$difference = $TableResults[$j][0];

			// line 2 is the result of the subtraction
			// calculate the starting position by taking the difference of the powers
			//$temp = $highestpower-count($difference)+1;
			$line2= "<tr>\r\n<td>&nbsp;</td>".poly3_htmlcellspaces($i);  // position for the divisor and the missing

			// Find the last column
			$columns = count($subtrahend)-1;
			$LastColumn = $columns;
			for($k = 0; $k<= $columns; $k++){
				if($IsFraction) {
					$coefarray = $subtrahend[$k];
					$test = $coefarray[0]/$coefarray[1];
				}
				else {
					$coefarray = NULL;
					$test = $subtrahend[$k];
				}
				if($test!=0) {
					$LastColumn = $k;
					break;
				}
			}

			$columns = count($subtrahend)-1;
			for($k = $columns; $k >= 0; $k--){
				if($IsFraction) {
					$coefarray = $subtrahend[$k];
					$coef = $coefarray[0]/$coefarray[1];
				}
				else {
					$coefarray = NULL;
					$coef = $subtrahend[$k];
				}

				if(($k != $columns)and($coef>0)) {
					$sign = "+";
				}
				else {
					$sign = "";
				}

				if($coef==0){
					if($HideZeroMinus) {
						$line1 .= "<td class='bottomborder'>&nbsp;</td>";
					}
					else {
						if($subtrahendLeftShow){
							if($LastColumn == $k){
								$line1 .= "<td class='bottomborder'>$MathSymbol$subtrahendLeft 0".poly3_variablepower($k,$variable)."$subtrahendRight$MathSymbol</td>";
							}
							else {
								$line1 .= "<td class='bottomborder'>$MathSymbol$subtrahendLeft 0".poly3_variablepower($k,$variable)."$MathSymbol</td>";
							}
							$subtrahendLeftShow = FALSE;
						}
						else {
							if($LastColumn == $k){
								$line1 .= "<td class='bottomborder'>$MathSymbol+0".poly3_variablepower($k,$variable)."$subtrahendRight$MathSymbol</td>";
							}
							else {
								$line1 .= "<td class='bottomborder'>$MathSymbol+0".poly3_variablepower($k,$variable)."$MathSymbol</td>";
							}
						}
					}
				}
				else {
					if($subtrahendLeftShow){
						if($LastColumn == $k){
							$linetext = "$MathSymbol$subtrahendLeft$sign".poly3_coefficientstring($coefarray,$coef,$k).poly3_variablepower($k,$variable)."$subtrahendRight$MathSymbol";
						}
						else {
							$linetext = "$MathSymbol$subtrahendLeft$sign".poly3_coefficientstring($coefarray,$coef,$k).poly3_variablepower($k,$variable)."$MathSymbol";
						}
						$subtrahendLeftShow = FALSE;
					}
					else {
						if($LastColumn == $k){
							$linetext = "$MathSymbol$sign".poly3_coefficientstring($coefarray,$coef,$k).poly3_variablepower($k,$variable)."$subtrahendRight$MathSymbol";
						}
						else {
							$linetext = "$MathSymbol$sign".poly3_coefficientstring($coefarray,$coef,$k).poly3_variablepower($k,$variable)."$MathSymbol";
						}
					}
					$line1.= "<td class='bottomborder'>$linetext</td>";
				}

				if (!array_key_exists($k,$difference)) {
					// remainder
					if(($k==$remainderColumn)&&($i ==($rows-1))) {
                        if($showZeroRemainder){
                            $line2 .= "<td class='right'>$MathSymbol"."0$MathSymbol</td>";
                        } else {
                            $line2 .= "<td class='right'>&nbsp;</td>";
                        }
					}
					else {
						$line2 .= "<td class='right'>&nbsp;</td>";
					}
                }
				else {
					if($IsFraction) {
						$coefarray = $difference[$k];
						$coef = $coefarray[0]/$coefarray[1];
					}
					else {
						$coefarray = NULL;
						$coef = $difference[$k];
					}

					if(($k != $columns)and($coef>0)) {
						$sign = "+";
					}
					else {
						$sign = "";
					}
					if($coef==0){
                        if($HideZeroDifference) {
                            if(($k==$remainderColumn)&&($i ==($rows-1))){
                                if($showZeroRemainder){
                                    $line2 .= "<td class='right'>$MathSymbol"."0$MathSymbol</td>";
                                } else {
                                    $line2 .= "<td class='right'>&nbsp;</td>";
                                }

                            }
                            else {
                                $line2 .= "<td class='right'>&nbsp;</td>";
                            }

                        }
                        else {
                            $line2 .= "<td class='right'>$MathSymbol+0".poly3_variablepower($k,$variable)."$MathSymbol</td>";
                        }
                    }
					else {
                        $showZeroRemainder = false;
                        $line2.= "<td class='right'>$MathSymbol$sign".poly3_coefficientstring($coefarray,$coef,$k).poly3_variablepower($k,$variable)."$MathSymbol</td>";
					}
                }
            }
            $Table .= $line1."</tr>\r\n";
            $Table .= $line2."</tr>\r\n";
        }
	}

	$Table .= "</table>\r\n";

	return $Table;
}

// File version : 7.5   - Fixed warning in formpoly3fromstring for non-numeric value encountered on line 179 in file /var/app/current/assessment/libs/fraction.php
//                        
// File version : 7.4	- Fixed documentation Bug. Added formpoly3fromresults.
// File version : 7.3	- Bug in the poly3_breakmonomial - missing th "+" sign, bug in the divide when the Remiander constant was 0 but the first degree remainder was not
// File version : 7.2	- Bug in the divide fraction routine were I was not stopping when the divisor had a greater powere then the dividend.
// File version : 7.1	- Changed ($quotientPower != NULL) to !is_null($quotientPower) - the former would skip constants.
// File version : 7		- fixed crash when terms were missing or zero - fixed writepoly3 and longdivisionpoly3.
// File version : 6		- added str_replace() to eliminate space in the formpoly3fromstring,  update the longdivisionpoly3 to use 0 and 1 instead of TRUE
//                        and FALSE, as WAMAP treated them as strings.  Added a remainder of 0 when it was.
// File version : 5		- added the ability to subtract in longdivisionpoly3
// File version : 4		- renamed internval functions to start with poly3_, added 3 back to formpoly3fromstring
// File version : 3		- fixed bug in formpoly3fromstring when a / was entered
// File version : 2.1	- renamed formpoly3 to formpolyfromstring and longdivisionpoly3 to longdivisionpoly
// File Version : 2 	- bug fixes
// File Version : 1		- initial release
?>
