<?php
// A library for financial derivative Functions.  Version 1.0  December 27, 2020
// Author:  Daniel Brown
//          University of Colorado Boulder

global $allowedmacros;
array_push($allowedmacros,"finderiv_payout","finderiv_fwdprice","finderiv_fwdpricediv",
	"finderiv_fwdcontract",	"finderiv_bsm","finderiv_immdate","finderiv_equityfutdate", 
	"finderiv_convertrate","finderiv_fairforwardrate","finderiv_fra");

// function finderiv_payout
// calculates the payout of a portfolio given a terminal value for the 
// underlying asset. The portfolio can contain
// European calls, European puts, zero coupon bonds and forward contracts
function finderiv_payout($asset, $types, $possizes, $strikes){
	if (!is_numeric($asset) ) {
		echo "finderiv_payout: invalid asset level";
		return false;
	}
	if (!is_array($types)) {
		echo 'finderiv_payout: types must be an array';
		return false;
	}
	$numTypes = count($types);
	if ($numTypes>100) {
		echo 'finderiv_payout: numTypes is too big. Capped at 100.';
		return false;
	}
	if (!is_array($possizes)) {
		echo 'finderiv_payout: position sizes must be an array';
		return false;
	}
	$numpossizes = count($possizes);
	if (!is_array($strikes)) {
		echo 'finderiv_payout: strikes must be an array';
		return false;
	}
	$numStrikes = count($strikes);
	if ($numTypes!=$numpossizes) {
		echo 'finderiv_payout: type and position size arrays must be of the same size.';
		return false;
	}
	if ($numTypes!=$numStrikes) {
		echo 'finderiv_payout: type and strike arrays must be of the same size.';
		return false;
	}
	$total = 0;
    for ($i=0;$i<$numTypes;$i++) {
		if (!is_numeric($possizes[$i])) {
			echo "finderiv_payout: position size is invalid. Cannot be " . $possizes[$i] . ".\r\n";
			return false;
		}
		if (!is_numeric($strikes[$i])) {
			echo "finderiv_payout: strike is invalid. Cannot be " . $strikes[$i] . ".";
			return false;
		}

		if (strcasecmp($types[$i],"call")==0) {
			$total += $possizes[$i]* max($asset-$strikes[$i],0);
		} elseif (strcasecmp($types[$i],"put")==0) {
			$total += $possizes[$i]* max($strikes[$i]-$asset,0);
		} elseif (strcasecmp($types[$i],"bond")==0) {
			$total += $possizes[$i];
		} elseif (strcasecmp($types[$i],"forward")==0) {
			$total += $possizes[$i]*($asset-$strikes[$i]);
		} else {
			echo 'finderiv_payout: type ' . $types[$i] . ' is not recognized. It must be call, put, forward, bond';
			return false;
		}
	}
	return $total;
}

// function finderiv_fwdprice
// This function returns the fair forward price for an 
// asset. It handles the situtation where the asset earns a yield and thus 
// can be used for equity indicies or foreign exchange
// mat is given in years
function finderiv_fwdprice($spot, $rf, $yield, $today,$mat,$fmt="F j, Y") {
	if (!is_numeric($spot) ) {
		echo "finderiv_fwdprice: invalid spot price";
		return false;
	}
	if (!is_numeric($rf) ) {
		echo "finderiv_fwdprice: invalid risk-free interest rate";
		return false;
	}
	if (!is_numeric($yield) ) {
		echo "finderiv_fwdprice: invalid yield";
		return false;
	}

	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_fwdprice::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}

	$matdate = date_create_from_format($fmt,$mat);
	if ($matdate==false) {
		echo "finderiv_fwdprice::could not create maturity date from string.";
		echo "The date is " . $mat . " and the format is " . $fmt . ".";
		return false;
	}

	if ($todaydate>$matdate) {
		echo "finderiv_fwdprice:: Forward price request in the past. Maturity date is less than today.";
		return false;
	}

	$matdays = ($todaydate->diff($matdate))->format("%r%a");
	return $spot*exp(($rf-$yield)*$matdays/365);
}

// function finderiv_fwdpricediv
// This function returns the fair forward price for an 
// asset that pays a single dividend.
// mat is given in years
function finderiv_fwdpricediv($spot, $rf, $div, $today, $divmat, $mat, $fmt="F j, Y" ) {
	if (!is_numeric($spot) ) {
		echo "finderiv_fwdpricediv: invalid spot price";
		return false;
	}
	if (!is_numeric($rf) ) {
		echo "finderiv_fwdpricediv: invalid risk-free interest rate";
		return false;
	}
	if (!is_numeric($div) ) {
		echo "finderiv_fwdpricediv: invalid dividend";
		return false;
	}
	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_fwdpricediv::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}

	$divmatdate = date_create_from_format($fmt,$divmat);
	if ($divmatdate==false) {
		echo "finderiv_fwdpricediv::could not create dividend date from string.";
		echo "The date is " . $divmat . " and the format is " . $fmt . ".";
		return false;
	}

	if ($todaydate>$divmatdate) {
		echo "finderiv_fwdpricediv:: Dividend date in the past.";
		return false;
	}

	$matdate = date_create_from_format($fmt,$mat);
	if ($matdate==false) {
		echo "finderiv_fwdpricediv::could not create maturity date from string.";
		echo "The date is " . $mat . " and the format is " . $fmt . ".";
		return false;
	}

	if ($todaydate>$matdate) {
		echo "finderiv_fwdpricediv:: Forward price request in the past. Maturity date is less than today.";
		return false;
	}

	$matdays = ($todaydate->diff($matdate))->format("%r%a");
	$divmatdays = ($todaydate->diff($divmatdate))->format("%r%a");

	if ($divmatdays<=$matdays) {
		return ($spot-$div*exp(-$rf*$divmatdays/365))*exp(($rf)*$matdays/365);
	} else {
		return $spot*exp(($rf)*$matdays/365);
	}	
}

// function finderiv_fwdcontract
// THis function returns the value of a forward contracts
// it takes as input the forward price.
function finderiv_fwdcontract ($count, $forward, $strike, $rf, $today,$mat, $fmt="F j, Y" ) {
	if (!is_numeric($count) ) {
		echo "finderiv_fwdcontract: invalid number of contracts";
		return false;
	}
	if (!is_numeric($forward) ) {
		echo "finderiv_fwdcontract: invalid fair forward price";
		return false;
	}
	if (!is_numeric($strike) ) {
		echo "finderiv_fwdcontract: invalid strike";
		return false;
	}
	if (!is_numeric($rf) ) {
		echo "finderiv_fwdcontract: invalid risk-free interest rate";
		return false;
	}
	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_fwdcontract::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}

	$matdate = date_create_from_format($fmt,$mat);
	if ($matdate==false) {
		echo "finderiv_fwdcontract::could not create maturity date from string.";
		echo "The date is " . $mat . " and the format is " . $fmt . ".";
		return false;
	}

	if ($todaydate>$matdate) {
		echo "finderiv_fwdcontract:: Forward price request in the past. Maturity date is less than today.";
		return false;
	}
	$matdays = ($todaydate->diff($matdate))->format("%r%a");
	return $count*($forward-$strike)*exp(-$rf*$matdays/365);
}

// function finderiv_bsm
// This function returns the Black-Scholes-Merton
// value, delta, gamma or vega of a European call
// or put option
// maturity is in days
function finderiv_bsm($type, $strike, $mat, $today, $spot, $rf, $vol, $ans , $fmt="F j, Y" ) {
	if (!is_numeric($vol) || $vol<=0) {
		echo 'finderiv_bsm: vol must be greater than 0';
		return false;
	}
	if (!is_numeric($spot) || $spot<=0) {
		echo 'finderiv_bsm: spot must be greater than 0';
		return false;
	}
	if (!is_numeric($strike) || $strike<=0) {
		echo 'finderiv_bsm: strike must be greater than 0.';
		return false;
	}
	if (!is_numeric($rf) || $rf<0) {
		echo 'finderiv_bsm: rf must be greater than or equal to zero';
		return false;
	}
	if (strcasecmp($type,"call")!=0 && strcasecmp($type,"put")!=0) {
		echo 'finderiv_bsm: option type must be call or put.';
		return false;
	}
	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_bsm::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}

	$matdate = date_create_from_format($fmt,$mat);
	if ($matdate==false) {
		echo "finderiv_bsm::could not create maturity date from string.";
		echo "The date is " . $mat . " and the format is " . $fmt . ".";
		return false;
	}

	if ($todaydate>$matdate) {
		echo "finderiv_bsm:: Option maturity is in the past. Maturity date is less than today.";
		return false;
	}
	$matdays = ($todaydate->diff($matdate))->format("%r%a");

	$mat = $matdays/365;
	$d1 = (log($spot/$strike)+($rf+pow($vol,2)/2)*$mat)/($vol*sqrt($mat));
	$d2 = $d1-$vol*sqrt($mat);
	include_once("stats.php");
	switch (strtoupper($ans)) {
		case "VALUE":
			if (strcasecmp($type,"call")==0) {
				$answer = $spot*normalcdf($d1,16)-$strike*exp(-$rf*$mat)*normalcdf($d2,16);
			} elseif(strcasecmp($type,"put")==0) {
				$answer =	$strike*exp(-$rf*$mat)*normalcdf(-$d2,16)-$spot*normalcdf(-$d1,16);
			}
			break;
		case "DELTA":
			if (strcasecmp($type,"call")==0) {
				$answer = normalcdf($d1,16);
			} elseif(strcasecmp($type,"put")==0) {
				$answer = normalcdf($d1,16)-1;
			}
			break;
		case "GAMMA":
			$answer = 1/sqrt(2*M_PI)*exp(-.5*pow($d1,2))/($spot*$vol*sqrt($mat));
			break;
		case "VEGA":
			$answer = 1/sqrt(2*M_PI)*exp(-.5*pow($d1,2))*$spot*sqrt($mat)/100;
			break;
		default:
			echo "finderiv_bsm:: the answer type must be value, delta, gamma or vega.";
			return false;
	}	
	return $answer;
}

// function finderiv_immdate
// this function returns the nth IMM date after today.
// The IMM date is the 3rd Wednesday of the month.
// if today is an IMM date then it returns the next date
// format is the date format
function finderiv_immdate($start,$n,$fmt="F j, Y") {	
	$startdate = date_create_from_format($fmt,$start);
	if ($startdate==false) {
		echo "finderiv_immdate::could not create date from string.";
		echo "The date is " . $start . " and the format is " . $fmt . ".";
		return false;
	}
	if ($n<=0) {
		echo "finderiv_immdate:: n must be positive.";
		return false;
	}
	$month = $startdate->format('n');
	$offset = $month%3;
	$IMMdate = clone($startdate);
	if ($offset==0) {
		// need to check whether the start date is 
		// before or after the current month's IMM date
		$IMMdate->modify('third Wednesday of this month');

		if ($IMMdate->format('j')>$startdate->format('j')) {
			$IMMdate->modify ('+ ' . 3*($n-1) . ' months');
		} else {
			$IMMdate->modify ('+ ' . 3*$n . ' months');
		}
	} else {
		$IMMdate->modify ('+ ' . 3*($n-1)+ $offset . ' months');
	}
	$IMMdate->modify('third Wednesday of this month');
	
	return $IMMdate->format($fmt);
}

// function finderiv_equityfutdate
// this function returns the nth equity futures date after today.
// Equity Futures expire on the third friday of March, June, 
// September and December 
// if today is an equity futures date then it returns the next date
// format is the date format
function finderiv_equityfutdate($start,$n,$fmt="F j, Y") {	
	$startdate = date_create_from_format($fmt,$start);
	if ($startdate==false) {
		echo "finderiv_immdate::could not create date from string.";
		echo "The date is " . $start . " and the format is " . $fmt . ".";
		return false;
	}
	if ($n<=0) {
		echo "finderiv_immdate:: n must be positive.";
		return false;
	}
	$month = $startdate->format('n');
	$offset = $month%3;
	$futdate = clone($startdate);
	if ($offset==0) {
		// need to check whether the start date is 
		// before or after the current month's IMM date
		$futdate->modify('third Friday of this month');

		if ($futdate->format('j')>$startdate->format('j')) {
			$futdate->modify ('+ ' . 3*($n-1) . ' months');
		} else {
			$futdate->modify ('+ ' . 3*$n . ' months');
		}
	} else {
		$futdate->modify ('+ ' . 3*($n-1)+ $offset . ' months');
	}
	$futdate->modify('third Friday of this month');
	
	return $futdate->format($fmt);
}


// function finderiv_convertrate	
// this function converts a rate from one style into another
// the allowable types are continuously compounded, ACT/360 and ACT/365
// the type strings are cc, 360 or 365.	
function finderiv_convertrate($days,$rate,$starttype,$endtype) {
	if ($days<=0) {
		echo "finderiv_convertrate:: Days must be positive.";
		return false;
	}
	if (!is_numeric($rate)) {
		echo "finderiv_convertrate:: rate must be a number.";
		return false;
	}
	if (strcasecmp($starttype,"cc")!=0 && strcasecmp($starttype,"360")!=0 &&
			strcasecmp($starttype,"365")!=0) {
		echo "finderiv_convertrate:: starttype must be cc, 360 or 365.";
		return false;
	} 
	if (strcasecmp($endtype,"cc")!=0 && strcasecmp($endtype,"360")!=0 &&
			strcasecmp($endtype,"365")!=0) {
		echo "finderiv_convertrate:: starttype must be cc, 360 or 365.";
		return false;
	}
	switch (strtoupper($starttype)) {
		case "CC":
			switch (strtoupper($endtype)) {
				case "CC":
					$endrate = $rate;
					break;
				default:
					$endrate = (exp($rate*$days/365)-1)*intval($endtype)/$days;
			}
			break;
		default:
			switch (strtoupper($endtype)) {
				case "CC":
					$endrate = log(1+$rate*$days/intval($starttype))*365/$days;
					break;
				default:
					$endrate = $rate*intval($endtype)/intval($starttype);
				}
	} 
	return $endrate;
}

// function finderiv_fairforwardrate
// This function returns the fair forward rate between two
// dates given continuously compounded rates for each date.
function finderiv_fairforwardrate($today,$date1,$rate1,$date2,$rate2,$basis, $fmt="F j, Y") {
	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_fairforwardrate::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}
	$startdate = date_create_from_format($fmt,$date1);
	if ($startdate==false) {
		echo "finderiv_fairforwardrate::could not create first date from string.";
		echo "The date is " . $date1. " and the format is " . $fmt . ".";
		return false;
	}
	if ($todaydate>$startdate) {
		echo "finderiv_fairforwardrate:: Rate is in the past. Start date is less than today.";
		return false;
	}
	$enddate = date_create_from_format($fmt,$date2);
	if ($enddate==false) {
		echo "finderiv_fairforwardrate::could not create second date from string.";
		echo "The date is " . $date2. " and the format is " . $fmt . ".";
		return false;
	}
	if ($startdate>=$enddate) {
		echo "finderiv_fairforwardrate:: startdate must be before enddate.";
		return false;
	}

	if (!is_numeric($rate1) ) {
		echo "finderiv_fairforwardrate:: invalid first interest rate";
		return false;
	}
	if (!is_numeric($rate2) ) {
		echo "finderiv_fairforwardrate:: invalid second interest rate";
		return false;
	}
	if ($basis!=360 && $basis!=365) {
		echo "finderiv_fairforwardrate:: The basis must be 360 or 365.";
		return false;	
	}
	$days1 = ($todaydate->diff($startdate))->format("%r%a");
	$days2 = ($todaydate->diff($enddate))->format("%r%a");
	$rate = (exp($rate2*$days2/365-$rate1*$days1/365)-1)*$basis/($days2-$days1);
	return $rate;
}

// function finderiv_fra
// This function returns present value of an FRA
function finderiv_fra($borrowlend, $principal, $date1, $date2, 
		$strike, $basis, $today, $rate1, $rate2, $fmt="F j, Y") {
	if (strcasecmp($borrowlend,"borrow")!=0 && strcasecmp($borrowlend,"lend")) {
		echo "finderiv_fra::borrowlend must be either borrow or lend.";
		return false;
	}
	if (!is_numeric($principal)) {
		echo "finderiv_fra::principal must be a number.";
		return false;
	}
	if ($principal<0) {
		echo "finderiv_fra::principal must be positive.";
		return false;
	}
	$startdate = date_create_from_format($fmt,$date1);
	if ($startdate==false) {
		echo "finderiv_fra::could not create first date from string.";
		echo "The date is " . $date1. " and the format is " . $fmt . ".";
		return false;
	}
	$enddate = date_create_from_format($fmt,$date2);
	if ($enddate==false) {
		echo "finderiv_fra::could not create second date from string.";
		echo "The date is " . $date2. " and the format is " . $fmt . ".";
		return false;
	}
	if ($startdate>=$enddate) {
		echo "finderiv_fra:: startdate must be before enddate.";
		return false;
	}
	if (!is_numeric($strike) ) {
		echo "finderiv_fra:: invalid strike";
		return false;
	}
	if ($basis!=360 && $basis!=365) {
		echo "finderiv_fra:: The basis must be 360 or 365.";
		return false;	
	}

	$todaydate = date_create_from_format($fmt,$today);
	if ($todaydate==false) {
		echo "finderiv_fra::could not create today's date from string.";
		echo "The date is " . $today. " and the format is " . $fmt . ".";
		return false;
	}
	if ($todaydate>$startdate) {
		echo "finderiv_fra:: FRA starts in the past. Start date is less than today.";
		return false;
	}
	if (!is_numeric($rate1) ) {
		echo "finderiv_fra:: invalid first interest rate";
		return false;
	}
	if (!is_numeric($rate2) ) {
		echo "finderiv_fra:: invalid second interest rate";
		return false;
	}
	$days1 = ($todaydate->diff($startdate))->format("%r%a");
	$days2 = ($todaydate->diff($enddate))->format("%r%a");

	$fairrate = (exp($rate2*$days2/365-$rate1*$days1/365)-1)*$basis/($days2-$days1);
	
	switch (strtoupper($borrowlend)) {
		case "LEND":
			$PV = $principal*($strike-$fairrate)*($days2-$days1)/$basis*exp(-$rate2*$days2/365);
			break;
		case "BORROW":
			$PV = $principal*($fairrate-$strike)*($days2-$days1)/$basis*exp(-$rate2*$days2/365);
			break;
		default:
			echo "finderiv_fra::Should not be possible to get here.";
			return false;
	}
	
	return $PV;
}