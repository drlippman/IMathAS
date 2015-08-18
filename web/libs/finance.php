<?php
// A library of Financial Functions.  Version 1.  May 22, 2014
// Author:  William Meacham
//          Scottsdale Community College

global $allowedmacros;
array_push($allowedmacros,"futureValue","presentValue","payment","numberOfPeriods","interest","vmTVM");

// function futureValue(Interest, bigN, littleN, payment, principal)
// <p>Returns the Future Value of an investment or loan based on periodic 
// constant payments and a constant Interest Value</p>
// <b>Params</b><br>
// <ul>
// <li>interest:  Annual Interest Rate as a percent.  Example:  For 7.5%, $interest should = 7.5</li>
// <li>bigN:  The total number of periods for the investment/loan</li>
// <li>littleN:  The number of compounding periods per year.  Example:  For quarterly, $littleN = 4</li>
// <li>payment:  The periodic amount paid into or withdrawn from the account.  Should be a negative value
//     if paid into the account.  Should be a positive value if withdrawn fro mthe account</li>
// <li>principal:  The Initial Value of the account.  Should be a negative value
//     if paid into the account.  Should be a positive value if withdrawn from the account</li>
// </ul>
// <b>Returns</b></br>
// <p>Returns the Future Value of the account.  Returns a positive value if the investment/loan resulted in
//  a positive return.  Returns a negative value if the investment/loan resulted in an amount owed.</p>
function futureValue($interest, $bigN, $littleN, $payment,$principal) {
	$rate = $interest/($littleN * 100);
        if ($interest == 0 ) // Interest rate is 0
        {
            $accruedVal = -($principal + ($payment * $bigN));
        } else {
            $x = pow(1 + $rate, $bigN);
            $accruedVal = - ( -$payment + $x * $payment + $rate * $x * $principal ) /$rate;
        }
        $accruedVal = round($accruedVal,2);
        return $accruedVal;   
}

// function presentValue(Interest, bigN, littleN, payment, accruedVal)
// <p>Returns the Present Value of an investment or loan based on periodic 
// constant payments and a constant Interest Value</p>
// <b>Params</b><br>
// <ul>
// <li>interest:  Annual Interest Rate as a percent.  Example:  For 7.5%, $interest should = 7.5</li>
// <li>bigN:  The total number of periods for the investment/loan</li>
// <li>littleN:  The number of compounding periods per year.  Example:  For quarterly, $littleN = 4</li>
// <li>payment:  The periodic amount paid into or withdrawn from the account.  Should be a negative value
//     if paid into the account.  Should be a positive value if withdrawn fro mthe account</li>
// <li>accruedVal:  The Future Value of the Investment.  Should be a positive value of if the investment/loan resulted in
//  a positive return.  Should be a negative value if the investment/loan resulted in an amount owed.</li>
// </ul>
// <b>Returns</b></br>
// <p>Returns the Present Value of the account.  Returns a positive value if the initial principal was withdrawn fromthe account (Loan).  
//    Returns a negative value if the initial principal was invested into the account (Savings)</p>
function presentValue($interest, $bigN, $littleN, $payment,$accruedVal) {
        $rate = $interest/($littleN * 100);
        if ( $rate == 0 ) // Interest rate is 0
        {
            $principal = -($accruedVal - ($payment * $bigN));
        } else {
            $x = pow(1 + $rate, $bigN);
            $principal = (-$accruedVal*$rate+$payment-$x*$payment)/($rate*$x);
        }
        $principal = round($principal,2);
        return $principal;
}

// function payment(Interest, bigN, littleN, principal, accruedVal)
// <p>Returns the Present Value of an investment or loan based on periodic 
// constant payments and a constant Interest Value</p>
// <b>Params</b><br>
// <ul>
// <li>interest:  Annual Interest Rate as a percent.  Example:  For 7.5%, $interest should = 7.5</li>
// <li>bigN:  The total number of periods for the investment/loan</li>
// <li>littleN:  The number of compounding periods per year.  Example:  For quarterly, $littleN = 4</li>
// <li>principal:  The Present Value of the Investment.  Should be a positive value if the account is a lona.  Negative if the
//     account is savings.</li>
// <li>accruedVal:  The Future Value of the Investment.  Should be a positive value of if the investment/loan resulted in
//  a positive return.  Should be a negative value if the investment/loan resulted in an amount owed.</li>
// </ul>
// <b>Returns</b></br>
// <p>Returns the Periodic Payment or Withdrawal for the Loan or Investment.  Positive Value if withdrawn from the account.  Negative if
//    depositied into the account.</p>
function payment($interest,$bigN,$littleN,$principal,$accruedVal) {
        $rate = ($interest)/($littleN * 100);
        if ( $rate === 0 ) // Interest rate is 0
        {
            $payment = (-$accruedVal - $principal)/$bigN;
        } else {
            $x = pow(1 + $rate, $bigN);
            $payment = (-$accruedVal * $rate - $rate*$x*$principal)/($x-1);
        }
        $payment = round($payment,2);
        return $payment;
}

// function numberOfPeriods(Interest, littleN, payment, principal, accruedVal)
// <p>Returns the Present Value of an investment or loan based on periodic 
// constant payments and a constant Interest Value</p>
// <b>Params</b><br>
// <ul>
// <li>interest:  Annual Interest Rate as a percent.  Example:  For 7.5%, $interest should = 7.5</li>
// <li>littleN:  The number of compounding periods per year.  Example:  For quarterly, $littleN = 4</li>
// <li>payment:  The periodic amount paid into or withdrawn from the account.  Should be a negative value
//     if paid into the account.  Should be a positive value if withdrawn fro mthe account</li>
// <li>principal:  The Present Value of the Investment.  Should be a positive value if the account is a lona.  Negative if the
//     account is savings.</li>
// <li>accruedVal:  The Future Value of the Investment.  Should be a positive value of if the investment/loan resulted in
//  a positive return.  Should be a negative value if the investment/loan resulted in an amount owed.</li>
// </ul>
// <b>Returns</b></br>
// <p>Returns the number of Payments or Withdrawals for the Loan or Investment rounded to two decimal places.</p>
function numberOfPeriods($interest,$littleN,$payment,$principal,$accruedVal) {
        $rate = ($interest)/($littleN * 100);
        if ( $rate === 0 ) // Interest rate is 0
        {
            $bigN = - ($accruedVal + $principal)/$payment;
        } else {
            $bigN = log((-$accruedVal*$rate+$payment)/($payment+$rate*$principal))/log(1+$rate);
        }
        $bigN = round($bigN,2);
        return $bigN;
}

// function interest(bigN, littleN, payment, principal, accruedVal)
// <p>Returns the Interest Rate of the Investment/Loan as a percentage based on periodic 
// constant payments and a constant Interest Value.</p>
// <b>Params</b><br>
// <ul>
// <li>bigN:  The total number of periods for the investment/loan</li>
// <li>littleN:  The number of compounding periods per year.  Example:  For quarterly, $littleN = 4</li>
// <li>payment:  The periodic amount paid into or withdrawn from the account.  Should be a negative value
//     if paid into the account.  Should be a positive value if withdrawn fro mthe account</li>
// <li>principal:  The Present Value of the Investment.  Should be a positive value if the account is a lona.  Negative if the
//     account is savings.</li>
// <li>accruedVal:  The Future Value of the Investment.  Should be a positive value of if the investment/loan resulted in
//  a positive return.  Should be a negative value if the investment/loan resulted in an amount owed.</li>
// </ul>
// <b>Returns</b></br>
// <p>Returns the Interest Rate of the Investment/Loan as a percentage rounded to two decimal places.</p>
function interest($bigN,$littleN,$payment,$principal,$accruedVal) {

        $rate = calcNR($payment, $principal, $accruedVal, $bigN, $littleN);
        $rate = round($rate,2);
        return $rate;
}

 
function calcPV($inPMT, $inFV, $inNR, $inNP, $inC) {
	$outPV = $inFV * pow((1 + $inNR/(100 * $inC)),(-$inNP));
 	if ($inNR === 0) {
 		$outPV = $outPV + $inPMT * $inNP;
 	} else {
 		$outPV = $outPV + $inPMT*((1-(pow((1 + $inNR/(100*$inC)),(-$inNP))))/($inNR/(100*$inC)));
 	}
 	return $outPV;
}
function calcNR($inPMT, $inPV, $inFV, $inNP, $inC) {
	$outNR = 0.1; 
	$theH = 0.00001;
	$i = 1;
	$theZeros = 0;
	$lastNR = $outNR;
	if ($inNP <= 0) { 
            return "bigN must be positive";
	}
	if ($inFV === 0) {
		$theZeros++;
	}	
	if ($inPMT === 0) {
		$theZeros++;
	}	
	if ($inPV === 0) {
		$theZeros++;
	}
	if ($theZeros >= 2) { // Error, return -1
		return "Unable to calculate Interest";
	}
	if (($inPV > 0) && ($inPMT >= 0) && ($inFV >= 0)) {
 		return "Unable to calculate Interest";
	}
	if (($inPV === 0) && ($inPMT >= 0) && ($inFV >= 0)) {
		return "Unable to calculate Interest";
	}
	$inPV *= -1;
	do {
		$thePV1 = calcPV($inPMT,$inFV,($outNR*100),$inNP,$inC) - $inPV;
		$theDeriv = ((calcPV($inPMT,$inFV,(($outNR+$theH)*100),$inNP,$inC) - $inPV) - $thePV1)/$theH;
		$thePV2 = $thePV1;
		$lastNR = $outNR;
		$outNR = $outNR - $thePV1/$theDeriv;
		if ($i > 200) { 
			return "Unable to calculate Interest";
		}
		$i++;
		if ($thePV2 < 0) $thePV2 *= -1;
	} while ($thePV2 > 0.0001);
	return ($lastNR*100); 	
}


// function TVM()
// <p>Returns an iframe for displaying a Time Value of Money Financial Calculator.  Provides Students with a Virtual Manipulative
//    they can use to calculate various values of an Investment or Loan.  </p>
// <b>Params:</b>None<br>
// <b>Returns</b></br>
// <p>Returns the embeded iframe code for inserting the applet into a MathAS Question.</p>
function vmTVM() {
	return '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/tvm/index.html" name="TVMSolver" width="400" height="500" frameborder="0" scrolling="no" ><p>Your browser does not support iframes.</p></iframe>';
}
?>
