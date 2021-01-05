<?php
// A library of date Functions.  Version 1.  December 17, 2020
// Author:  Daniel Brown
//          University of Colorado Boulder

global $allowedmacros;
array_push($allowedmacros,"dates_adddays","dates_addweeks","dates_addmonths","dates_addyears","dates_eomonth",
"dates_bomonth","dates_diffdays","dates_randdate","dates_dateformat");

// function adddays
// adds a number of days to an existing date
// uses $fmt as format of date inputs and outputs
function dates_adddays($date, $days,$fmt = "F j, Y"){
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_adddays::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('+' . $days .' days');
	return $datetime->format($fmt);
}

// function addweeks
// adds a number of weeks to an existing date
// uses $fmt as format of date inputs and outputs
function dates_addweeks($date, $weeks,$fmt = "F j, Y"){
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_addweeks::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('+' . $weeks .' weeks');
	return $datetime->format($fmt);
}

// function addmonths
// adds a number of months to an existing date
// uses $fmt as format of date inputs and outputs
function dates_addmonths($date,$months,$fmt= "F j, Y"){
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_addmonths::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('+' . $months .' months');
	return $datetime->format($fmt);
}

// function addyears
// adds a number of weeks to an existing date
// uses $fmt as format of date inputs and outputs
function dates_addyears($date, $years,$fmt = "F j, Y"){
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_addyears::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('+' . $years .' Years');
	return $datetime->format($fmt);
}

// function eomonth
// returns the last date of the month
// from a given date. 
// uses $fmt as format of date inputs and outputs
function dates_eomonth($date,$fmt="F j, Y") {
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_eomonth::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('last day of this month');
	return $datetime->format($fmt);
}

// function bomonth
// returns the first date of the month
// from a given date. 
// uses $fmt as format of date inputs and outputs
function dates_bomonth($date,$fmt="F j, Y") {
	$datetime = date_create_from_format($fmt,$date);
	if ($datetime==false) {
		echo "date_bomonth::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	$datetime->modify('first day of this month');
	return $datetime->format($fmt);
}

// function diffdays
// returns the number of days between
// date1 and date2. Positive if date2>date1
// uses $fmt as format of date inputs and outputs
function dates_diffdays($date1,$date2,$fmt="F j, Y") {
	$dt1 = date_create_from_format($fmt,$date1);
	if ($dt1==false) {
		echo "date_diffdays::could not create date from string. The date is " . $date1 . " and the format is " . $fmt . ".";
		return false;
	}
	$dt2 = date_create_from_format($fmt,$date2);
	if ($dt2==false) {
		echo "date_diffdays::could not create date from string. The date is " . $date2 . " and the format is " . $fmt . ".";
		return false;
	}

	$interval = $dt1->diff($dt2);
	return (int) $interval->format("%r%a");
}

// function randdate
// returns a random date between $from and $to
// uses $fmt as format of date inputs and outputs
function dates_randdate($from,$to,$fmt="F j, Y") {
	$dt1 = date_create_from_format($fmt,$from);
	if ($dt1==false) {
		echo "date_randdate::could not create date from string. The date is " . $from . " and the format is " . $fmt . ".";
		return false;
	}
	$dt2 = date_create_from_format($fmt,$to);
	if ($dt2==false) {
		echo "date_randdate::could not create date from string. The date is " . $to . " and the format is " . $fmt . ".";
		return false;
	}
	if ($dt1>$dt2) {
		echo "date_randdate::dates are in wrong order.";
		return false;
	}
	$difference = $dt1->diff($dt2);
	$offset = $GLOBALS['RND']->rand(0,$difference->days);
	$dt1->modify('+' . $offset .' days');
	return $dt1->format($fmt);
}

// function dateformat
// returns the date with a different format
function dates_dateformat($date,$newFormat, $origFormat="F j, Y"	) {
	$dt = date_create_from_format($origFormat,$date);
	if ($dt==false) {
		echo "date_dateformat::could not create date from string. The date is " . $date . " and the format is " . $fmt . ".";
		return false;
	}
	return $dt->format($newFormat);
}