<?php
// Simplex method with mixed constraints solver
// Mike Jenck, Originally developed May 16-26, 2014
// licensed under GPL version 2 or later
//
// File Version : 29
//

global $allowedmacros;

// COMMENT OUT BEFORE UPLOADING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros, "simplex", "simplexchecksolution", "simplexcreateanswerboxentrytable", "simplexcreateinequalities", "simplexconverttodecimals", "simplexconverttofraction", "simplexdebug", "simplexdefaultheaders", "simplexdisplaycolortable", "simplexdisplaylatex", "simplexdisplaylatex2", "simplexdisplaytable2", "simplexdisplaytable2string", "simplexfindpivotpoint", "simplexfindpivotpointmixed", "simplexgetentry", "simplexsetentry", "simplexpivot", "simplexreadtoanswerarray", "simplexreadsolution", "simplexsolutiontolatex", "simplexsolve2", "simplexnumberofsolutions", "simplexdisplaytable", "simplexsolve");

include_once("fractions.php");  // fraction routine

// function simplex(type, objective, constraints)
// Creates and returns a new simplex matrix. elements are fractions
// stored in the form of an array(numerator, denominator).
//
// INPUTS:
//
// type: a string that contains either "max" or "min"
// objective: list or array of the coefficients
// constraints: an array that contains the inequality information. Constraints are composed of three parts:
// &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;first  part is a list or array of the coefficients in the inequality
// &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;second part is the inequality '<=' or '>='
// &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;third  part is the right hand number
//
// Examples
//
// objective function: f = x1+7x2+5x3
//						$objective = array(1,7,5)
//							  or
//						$objective = "1,7,5"
// constraint inequality: 3x1+x3 <= 35
//						$constraints[0] = array(array(3,1,0),"<=",35)
//							  or
//						$constraints[0] = array("3,1,0","<=",35)
//							 first part: array(3,0,1) or "3,0,1"
//							 second part: "<="
//							 third part: 35
//
// use simplexdisplaytable() to create a string that can be used for display
function simplex($type,$objective,$constraints) {

	$error = 0;  // flag for any entry that is not a number

	$type = verifytype("simplex",$type,null);
	if(is_null($type)) return null;

	if (!is_array($objective)) { $objective = explode(',',$objective); }

	$constraints = verifyconstraints($type,$constraints);
	if(is_null($constraints)) return null;

	// create zero and one elements
	$zero = array(0,1);		// createsimplexelement(0);
	$one = array(1,1);		// createsimplexelement(1);
	$negone = array(-1,1);	// createsimplexelement(-1);

	$ismixed = hasmixedconstraints($constraints);

	// test for mixed constraint
	if((!$ismixed)&&($type=="min")) {
		$xcount = count($constraints);		// x variables
		$lastrow = count($objective);		// y variables
	}
	else
	{
		$xcount = count($objective);		// x variables
		$lastrow = count($constraints);		// y variables
	}


	$rows = $lastrow+ 1; 					// optimized value column
	$cols = $xcount+$lastrow+1+1;			// f/g column+optimized value column
	$lastcol = $cols-1;


	// This makes a simplex matrix like the following:
	// 2 x variables
	// 3 y variables/slack variables
	// 1 f/g column
	// 1 optimized column
	//
	//  0 	0 	1 	0 	0 	0 |	0
 	//	0 	0 	0 	1 	0 	0 |	0
 	//	0 	0 	0 	0 	1 	0 |	0
 	//  -------------------------
	//	0 	0 	0 	0 	0 	1 |	0
	//
	//
	//

	$sm = array();

	for($r=0;$r<$rows;$r++)
	{
		$sm[$r] = array_fill(0,$cols,$zero);
		$j=$xcount+$r;
		$sm[$r][$j] = $one;
		if(($ismixed)&&($type=="min")&&($r==$lastrow)) { $sm[$r][$j] = $negone; }
	}

	if((!$ismixed)&&($type=="min")) {
		// $constraints[0] = column 0
		// $constraints[1] = column 1
		// etc
		// $objective = last column
		for($c=0;$c<$xcount;$c++) {
			$temp = $constraints[$c][0];
			for($r = 0; $r<$lastrow; $r++){
				$value = $temp[$r];
	  			if(is_numeric($value))
	  			{
					$sm[$r][$c] = createsimplexelement($value);
	 	 		}
	  			else
	  			{
					echo "Simplex Error: constraints row = $r col = $c (".$value.") is not a number.<br/>\r\n";
					$error = 1;
	  			}
			}

			$value = $constraints[$c][2];
			if(is_numeric($value)) {
				$sm[$lastrow][$c] = createsimplexelement(-$value); // create negative value for last row
	 	 	}
			else {
				echo "Simplex Error: constraints row = $lastrow col = $c (".$value.") is not a number.<br/>\r\n";
				$error = 1;
			}
		}

		// last column
		for($r=0;$r<$lastrow;$r++) {
			$value = $objective[$r];
			if(is_numeric($value))
			{
				$sm[$r][$lastcol] = createsimplexelement($value);
			}
			else
			{
				echo "Simplex Error: constraints row = $r col = $lastcol (".$value.") is not a number.<br/>\r\n";
				$error = 1;
			}
		}
	}
	else {
		for($r=0;$r<$lastrow;$r++) {
			$temp = $constraints[$r][0];
			if($constraints[$r][1]=="<=") {
				$inequality = 1;
			}
			else {
				$inequality = -1;
			}

			for($c = 0; $c<$xcount; $c++){
				$value = $temp[$c];
	  			if(is_numeric($value)) {
					$sm[$r][$c] = createsimplexelement($inequality*$value);
	 	 		}
	  			else {
					echo "Simplex Error: constraints row = $r col = $c (".$value.") is not a number.<br/>\r\n";
					$error = 1;
	  			}
			}

			$value = $constraints[$r][2];
			if(is_numeric($value)) {
				$sm[$r][$lastcol] = createsimplexelement($inequality*$value);
		 	}
			else {
				echo "Simplex Error: constraints row = $r col = $lastcol (".$value.") is not a number.<br/>\r\n";
				$error = 1;
			}
		}

		// last row
		for($c = 0; $c<$xcount; $c++){
			$value = $objective[$c];
			if(is_numeric($value)) {
	  			if(($ismixed)&&($type=="min")){
					$sm[$lastrow][$c] = createsimplexelement($value);
				}
				else {
					$sm[$lastrow][$c] = createsimplexelement(-$value);
				}
	 	 	}
	  		else {
				echo "Simplex Error: constraints row = $r col = $c (".$value.") is not a number.<br/>\r\n";
				$error = 1;
	  		}
	  	}
	}

	return $sm;
}

// simplexchecksolution(type,HasObjective,solutionlist,stuanswer, [ismixed=FALSE])
//
// INPUTS:
//
// type: a string that contains either "max" or "min"
// HasObjective: either 0 or 1
//	   default 0 Objective value is not included in the stuanswer array
//			   1 Objective value is included and is the last column in the stuanswer array
// solutionlist: an array of solutions (in the case of multiple solutions).   In the form of
//
//			solutionlist[0] = array(solution values for matrix[0], IsOptimized)
//			solutionlist[1] = array(solution values for matrix[1], IsOptimized)
//			etc.
//			This is returned from simplexsolve
//
// ismixed: an optional flag for the function that tells the routine to read max values instead of min ones for mixed constraints
//		  default is  FALSE
//
// stuanswer: the answer the student submitted
//
//
// RETURNS:  0 if no match is found, 1 if a match is found
function simplexchecksolution($type,$HasObjective,$solutionlist,$stuanswer,$ismixed=FALSE) {

  if(count($solutionlist)==0) {
  	return 0;
  }

  $IsOptimizedcol = count($solutionlist[0])-1; // set Yes/No column index
  $OptimizedValuecol = $IsOptimizedcol -1;	 // the Optimized Value (f/g))
  $match = 0;  // set to no match

  if($HasObjective==1) {
	$LastStuColumn = count($stuanswer)-1; // set to the last column and check seperately
	$LastAnswer = $LastStuColumn;
  } else {
  	$LastStuColumn = -1;
	$LastAnswer = count($stuanswer);
  }

  //echo "<br/>LastStuColumn = $LastStuColumn<br/>";
  //echo "LastAnswer = $LastAnswer<br/>";

  if(($type=="max")||($ismixed)) {
  	for($r=0, $size = count($solutionlist); $r<$size;$r++) {
	  if($solutionlist[$r][$IsOptimizedcol]=="Yes") {
		$match = 1;  // found a possible solution
		// Check Objective
		if($HasObjective==1) {
			if($solutionlist[$r][$OptimizedValuecol]!=$stuanswer[$LastStuColumn]) {
				$match = 0;  // not a solution
				break;
			}
		}

		// check the rest of the answers
		for($c=0;$c<$LastAnswer;$c++) {
		  // now check to see if this solution matches the student
		  // need to evaluate  $solutionlist[$r][$c] to a decimal
		  if(fractiontodecimal($solutionlist[$r][$c])!=fractiontodecimal($stuanswer[$c])) {
			 $match = 0;  // not a solution
			 break;
		  }
		}
		if($match==1) break;
	  }
	}
  } else {
  	for($r=0,$size = count($solutionlist);$r<$size;$r++) {
	  if($solutionlist[$r][$IsOptimizedcol]=="Yes") {
		$match = 1;  // found a possible solution
		// Check Objective
		if($HasObjective==1) {
			if($solutionlist[$r][$OptimizedValuecol]!=$stuanswer[$LastStuColumn]) {
				$match = 0;  // not a solution
				break;
			}
		}
		$start = $OptimizedValuecol - $LastAnswer;
		//echo "start = $start<br/><br/>";

		// check the rest of the answers
		for($c=0;$c<$LastAnswer;$c++) {
		  // now check to see if this solution matches the student
		  $j = $start+$c;
		  //echo "$c) [$r][$j] =".$solutionlist[$r][$j].",".$stuanswer[$c]."<br/>";

		  if(fractiontodecimal($solutionlist[$r][$j])!=fractiontodecimal($stuanswer[$c])) {
			 $match = 0;  // not a solution
			 //break;
		  }
		}
		if($match==1) break;
	  }
	}
  }

  return $match;
}

//function simplexcreateanswerboxentrytable(rows, cols, [startnumber, matrixname, linemode, header, tablestyle])
// Create a string that is a valid HTML table syntax for displaying answerboxes.
//
// INPUTS:
// rows: number of rows to make
// cols: number of columns to make
//
// optional
// startnumber: the starting number for the answerbox.  Default is 0
// matrixname: a string that holds the matrix name, like A or B.  This does not contain
//			 tick marks or brackets - if you want them you need to supply them.
//	default empty string
//
// linemode: Show none, augmented, or simplex, value is either 0, 1 or 2
//		   0 show no lines
//		   1 show augmented line
//	default 2 show simplex  lines
//
// header: list or array of the variables "x1,x2,x3" that are used for the column titles.
// default none
//
// tablestyle: for any additional styles for the table that you may want.  like "color:#40B3DF;"
//	default none
//
// RETURNS: valid HTML table syntax for displaying answerboxes
function simplexcreateanswerboxentrytable() {

  //  arguments list --------------------------------------------------
  //  0 = rows
  //  1 = cols
  //  2 = startnumber
  //  3 = matrix name
  //  4 = linemode - no line, aumented, or simplex
  //  5 = header column names, default is not to show
  //  6 = CSS tablestyle for the table.

  // process arguments -----------------------------------------------
  $args = func_get_args();

  if (count($args)<2) {
	echo "You must supply the number of rows and columns.<br/>\r\n";
	return "";
  }
  $rows = $args[0];
  if($rows<1) {
	echo "You must have at least one row.<br/>\r\n";
	return "";
  }

  $cols  = $args[1];
  if($cols<1) {
	echo "You must have at least one column.<br/>\r\n";
	return "";
  }

  if((count($args)>2)&&(!is_null($args[2]))) {
  	$startnumber = $args[2];
  }
  else {
  	$startnumber = 0;
  }

  // matrixname
  $matrixname = "";
  if ((count($args)>3)&&(!is_null($args[3]))) {
  	$matrixname = $args[3];
  }

  //linemode
  if((count($args)>4)&&(!is_null($args[4]))) {
	$mode = verifymode("simplexcreateanswerboxentrytable",$args[4],2);
  } else { $mode=2; }

  //header
  if((count($args)>5)&&(!is_null($args[5]))) {
	$headers = $args[5];
	if (!is_array($headers)) { $headers = explode(',',$headers); }
  } else { $headers = null; }

  //tablestyle
  if ((count($args)>6)&&(!is_null($args[6]))) {
	$tablestyle = $args[6];
  } else {$tablestyle = ""; }

  $matrixans = array();
  for ($rloop=0; $rloop<$rows; $rloop++) {
	$matrixans[$rloop] = array();
	for ($cloop=0;$cloop<$cols; $cloop++) {
		$answerboxnumber = $startnumber + $rloop*$cols + $cloop;

		$matrixans[$rloop][$cloop] = "[AB".$answerboxnumber."]";
	  }
	}

	return simplexdisplaytable($matrixans, $matrixname, 0, $mode, -1, null, $headers, $tablestyle);
}


// function simplexcreateinequalities(type, objectivevariable, objective, constraints, [headers, displayASCIIticks, showfractions, includeinequalities] )
// Creates an array of string that correspond to each line of the simple inequalities
//
// INPUTS:
//
// type:		a string that contains either "max" or "min"
// objectivevariable: the name of the objective function, like f of g.
// objective:   list or array of the coefficients
// constraints: an array that contains the inequality information. Constraints are in the
//			  form of array(array(3,1,0),"<=",35)
//			  constraint first  part is a list or array of the coefficients in the inequality
//			  constraint second part is the inequality *<= or >=)
//			  constraint third  part is the number on the other side of the inequality
//
// OPTIONAL
//
// headers:	 list or array of the variables names to use
//	  default "x1,x2,x3, ..." for max
//	  default "y1,y2,y3, ..." for min
// displayASCIIticks: put tick marks around each element of the table, either 0 or 1.
//					0 do not use math ticks
//			default 1		use math ticks
//					Use 0 if you are building an answerbox matrix.
//
// showfractions: either 0 or 1
//				0 shows decimals
//		default 1 shows fractions
//
// includeinequalities: either 0 or 1
//					  0 does append the inequality and right hand sinde number ("<=",35)
//			  default 1 include the full inequality
//
// RETURNS: an array of strings
function simplexcreateinequalities() {
	$simplexestring = array();  // return value
	//  arguments lise --------------------------------------------------
	//  0 = type (max,min)
	//  1 = objectivevariable
	//  2 = objective
	//  3 = constraints
	//  4 = header column names, default is not to show
	//  5 = display ASCII tick marks (yes/no)
	//  6 = showfractions
	//  7 = includeinequalities

	// process arguments -----------------------------------------------
	$args = func_get_args();

	if (count($args)<4) {
		echo "simplexcreateinequalities requires at least 4 aurguments (type, objectivevariable, objective, and constraints).<br/>\r\n";
		return $simplexestring;
	}

	// type
	if(is_null($args[0])) {
		echo "In simplexcreateinequalities - Supplied Type was null which is not valid.  Valid values are <b>'min'</b> or <b>'max'</b>.<br/>\r\n";
		return $simplexestring;
	}
	else {
		$type = verifytype("simplexcreateinequalities",$args[0],null);
		if(is_null($type)) return $simplexestring;
	}

	 // objectivevariable
	if(is_null($args[1])) {
		$objectivevariable = "";
	}
	else {
		$objectivevariable = $args[1];
	}

	// objective
	if(is_null($args[2])) {
		echo "In simplexcreateinequalities -You must supply an objective function.<br/>\r\n";
		return $simplexestring;
	}
	else {
		$objective = $args[2];
		if (!is_array($objective)) { $objective = explode(',',$objective); }
	}

	//constraints
	if(is_null($args[3])) {
		echo "In simplexcreateinequalities -You must supply constraint information.<br/>\r\n";
		return $simplexestring;
	}
	else {
		$constraints =verifyconstraints($type,$args[3]);
	}

	// OPTIONAL
	//header

	if((count($args)>4)&&(!is_null($args[4]))) {
		$headers = $args[4];
		if (!is_array($headers)) { $headers = explode(',',$headers); }
	}
	else {
		$headers=array();
		for($j=0,$size = count($objective);$j<$size;$j++) {
			$count = $j+1;
			if($type=="max")
			{
			$headers[$j] = "x_$count";
			}
			else
			{
			$headers[$j] = "y_$count";
			}
		}
	}

	//displayASCII
	if((count($args)>5)&&(!is_null($args[5]))) {
		$tick = verifyASCIIticks("simplexcreateinequalities",$args[5],"`");
	}
	else { $tick = "`"; }

	// showfractions
	if((count($args)>6)&&(!is_null($args[6]))) {
		$showfractions = verifyshowfraction("simplexcreateinequalities",$args[6],1);
	}
	else { $showfractions=1; }

	//includeinequalities in the output string flag
	if((count($args)>7)&&(!is_null($args[7]))) {
		$includeinequalities = $args[7];
		if(($includeinequalities!=0)&&($includeinequalities!=1)) {
			echo "In simplexcreateinequalities the supplied inequalities flag value ($includeinequalities) is invalid.  Valid values are 0 or 1.<br/>\r\n";
			$includeinequalities=1;
		}
	}
	else {$includeinequalities = 1; }

	// Done processing arguments ---------------------------------------
	$simplexestring = array();
	if($objectivevariable=="") {
		$simplexestring[0] = $tick;
	}
	else {
		if($type=="max") {
		$simplexestring[0] = "Maximize ";
		}
		else {
		$simplexestring[0] = "Minimize ";
		}
		$simplexestring[0] .= $tick.$objectivevariable." = ";
	}

	// objective
	$isfirst = true;
	for($j=0,$sizeobjective = count($objective);$j<$sizeobjective;$j++) {
		if($objective[$j]==0) {
			// do nothing
		}
		else {

			// do I need to add a + sign
			if($isfirst) {
				$isfirst = false;
			}
			else {
				if($objective[$j]>0) { $simplexestring[0] .= "+"; }
			}

			// take care of +1,-1 case
			if($objective[$j]==-1) {
				$simplexestring[0] .= $objective[$j]."-";
			}
			elseif ($objective[$j]==1) {
				// do nothing
			}
			else {
				// add the number as a fraction display, or decimal value
				$frac = createsimplexelement($objective[$j]);
				if($showfractions==1) {
					$simplexestring[0] .= fractionreduce($frac);
				}
				else {
				$simplexestring[0] .= fractiontodecimal($frac);
				}
			}
			$simplexestring[0] .= $headers[$j];
		}
	}
	$simplexestring[0] .= $tick;
 	if($objectivevariable!="") {
		$simplexestring[0] .= " subject to";
	}

	// now create the inequalities from the constraints
	for($r=0,$sizeconstraints = count($constraints);$r<$sizeconstraints;$r++) {
		// remember row 0 is the Maximize or minimize line
		$row = $r+1;
		$coeff = $constraints[$r][0];
		$isfirst = true;
		$simplexestring[$row] = $tick;
		for($j=0,$sizeobjective = count($objective);$j<$sizeobjective;$j++) {
			if($coeff[$j]==0) {
				// do nothing
			}
			else {
				// do I need to add a + sign
				if($isfirst) {
					$isfirst = false;
				}
				else {
					if($coeff[$j]>0) { $simplexestring[$row] .= "+"; }
				}
				// take care of +1,-1 case
				if($coeff[$j]==-1) {
					$simplexestring[$row] .= "-";
				}
				elseif($coeff[$j]==1) {
				// do nothing
				}
				else {
					// add the number as a fraction display, or decimal value
					$frac = createsimplexelement($coeff[$j]);
					if($showfractions==1) {
						$simplexestring[$row] .= fractionreduce($frac);
					}
					else {
						$simplexestring[$row] .= fractiontodecimal($frac);
					}
				}
				$simplexestring[$row] .= $headers[$j];
			}
		}
		// does the user want the inequality symbol included in the output?
		if($includeinequalities==1) {$simplexestring[$row] .= $constraints[$r][1]." ".$constraints[$r][2]; }
		$simplexestring[$row] .= $tick;
	}

	return $simplexestring;
}


//function simplexconverttodecimals(simplexmatrix)
//
// simplexmatrix: a valid simplex matrix.
//
// this function takes the simplex matrix and converts all elements to decimals
//										  (usefull when displaying the matrix)
function simplexconverttodecimals($sm){

  for($r=0,$size = count($sm);$r<$size;$r++) {
	for($c=0;$c<count($sm[0]);$c++) {
	  $sm[$r][$c] = fractiontodecimal($sm[$r][$c]);
	}
  }

  return $sm;
}

//function simplexconverttofraction(simplexmatrix)
//
// simplexmatrix: a valid simplex matrix.
//
// this function takes the simplex matrix and converts  all elements to fractions
//				using "/" as the fraction bar (usefull when displaying the matrix)
function simplexconverttofraction($sm){

  for($r=0,$sizerow = count($sm);$r<$sizerow;$r++) {
	for($c=0,$sizecol = count($sm[0]);$c<$sizecol;$c++) {
	  $sm[$r][$c] = fractionreduce($sm[$r][$c]);
	}
  }

  return $sm;
}

//function simplexdebug(simplexmatrix)
//
// simplexmatrix: a valid simplex matrix.
//
// a raw echo dump of the contents of the simplex matrix
//
// Not intended to be used in question.  This is to allow the question writer the ability to see what the
// raw values are for each field.  all values should have a "|" between them, in the form of
// numerator | denominator.
function simplexdebug($sm){
	for($r=0,$sizerow = count($sm);$r<$sizerow;$r++) {
		for($c=0,$sizecol = count($sm[0]);$c<$sizecol;$c++) {
			if(count($sm[$r][$c])==1) {
				echo $sm[$r][$c];
			}
			else {
				echo $sm[$r][$c][0]."|".$sm[$r][$c][1];
			}
		 	if($c!=(count($sm[0])-1)) echo " , ";
		}
		echo "<br/>\r\n";
	}
}

// function simplexdefaultheaders(simplexmatrix,type)
//
// INPUTS
//
// simplexmatrix: a valid simplex matrix.
// type: a string that contains either "max" or "min"
//
// creates the default header (x1,x2, ...,s1,s2,...) for max and
//							(x1,x2, ...,y1,y2,...) for min.
//
// RETURNS: a string
function simplexdefaultheaders($sm, $type){
	$headers = array();

	$type = verifytype("simplexdefaultheaders",$type,null);
	if(is_null($type)) return $headers;

	$ycount = count($sm)-1;			// number of slack variables
	$cols =  count($sm[0]);			// total columns
	$xcount = $cols - $ycount-2;	// number of x variables
	for($c=0;$c<$xcount;$c++) {
		$count = $c+1;
		$headers[$c] ="x_$count";
	}

	$xycount = $xcount+$ycount;
	for($c=$xcount;$c<$xycount;$c++) {
		$count = $c-$xcount+1;
		if($type=="max"){
			$headers[$c] = "s_$count";
		}
		else {
			$headers[$c] = "y_$count";
		}
	}

	if($type=="max"){
		$headers[$xycount] = "f";
	}
	else {
		$headers[$xycount] = "g";
	}

	$headers[$xycount+1] = "Objective reached";

	return $headers;
}


//simplexdisplaycolortable(simplexmatrix, [simplexmatrixname, displayASCIIticks, linemode, showentriesfractions=1, $pivot = array(-1,-1 ["blue","black"]), $header = array(), tabletextcolor = "black", ShowObjectiveColumn=1])
//
// Create a string that is a valid HTML table syntax for display.
//
// INPUTS
//
// simplexmatrix: a valid simplex matrix.
//
// OPTIONAL
// simplexmatrixname: a string that holds the matrix name, like A or B.  You should leave balnk if you
//					are creating a simplex display
// displayASCII: either 0 or 1
//				0 do not use math ticks
//		default 1		use math ticks
// mode: either 0, 1 or 2
//		0 show no lines
//		1 show aumented line
//		2 show simplex  lines
// showfractions: either -1, 0 or 1
//				-1 show as string
//				 0 convert simplex element to a decimal
//		 default 1 convert simplex element to a fraction using "/" as the fraction bar
// pivot: list or array that contains the row, column, border color, and text color.  This puts a
//		 border around the cell at (row,col). Both row and column are ZERO based.
//	default point none
//	default border color = blue
//	default text  color  = black
// headers: list or array of the variables "x1,x2,x3" that are used for the column titles.
//   default none
// tabletextcolor: text color for the table
//	  default black
// showobjective : either 0 or 1
//                0 hide column
//                1 show column (default)
//
// RETURNS: a string that is a valid HTML table syntax for display.
function simplexdisplaycolortable() {

	//  arguments lise --------------------------------------------------
	//  0 = simplex matrix
	//  1 = simplex matrix name
	//  2 = display ASCII tick marks (yes/no)
	//  3 = mode - no line, aumented, or simplex
	//  4 = show fractions (string,yes/no)
	//  5 = circle pivot point, if supplied
	//  6 = header column names, default is not to show
	//  7 = CSS tablestyle for the table.
	//  8 = Show Objective Column - Default yes/no

	// process arguments -----------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
		return "";
	}

	$sm = $args[0];

	$rows = count($sm);
	if($rows==1) {
		// only 1 row
		echo "Error - a simplex matrix must have at least two rows.<br/>\r\n";
		return "";
	}
	$cols = count($sm[0]);

	// simplex matrixname
	if((count($args)>1)&&(!is_null($args[1]))) { $simplexmatrixname = $args[1]; } else { $simplexmatrixname = ""; }

	//displayASCII
	if((count($args)>2)&&(!is_null($args[2]))) {
		$tick = verifyASCIIticks("simplexdisplaytable",$args[2],"`");
	}
	else { $tick = "`"; }

	//mode
	if((count($args)>3)&&(!is_null($args[3]))) {
		$mode = verifymode("simplexdisplaytable",$args[3],2);
	}
	else { $mode=2; }

	//showfractions=1
	if((count($args)>4)&&(!is_null($args[4]))) {
		$showfractions = verifyshowfraction("simplexdisplaytable",$args[4],1,1);
	}
	else { $showfractions=1; }

	$nopad = 'class="nopad"';

	$pivotstylematrix = array();
	for ($rloop=0; $rloop<$rows; $rloop++) {
		$pivotstylematrix[$rloop] = array_fill(0, $cols, "");
	}

	//pivot
	if((count($args)>5)&&(!is_null($args[5]))) {
		if (!is_array($args[5])) { $args[5]=explode(',',$args[5]); }
		$pivots = $args[5];
		for ($r=0; $r<count($pivots); $r++) {
			$currentpoint = $pivots[$r];
			if((count($currentpoint)>0)&&(!is_null($currentpoint[0]))&&($currentpoint[0]>=0)) {
			$prow = $currentpoint[0];
			}
			else {
			$prow = -1;
			}
			if((count($currentpoint)>1)&&(!is_null($currentpoint[1]))&&($currentpoint[1]>=0)) {
			$pcol = $currentpoint[1];
			}
			else {
			$pcol = -1;
			}

			//pivotcolor
			$pivotbordercolor = "blue";
			if((count($currentpoint)>2)&&(!is_null($currentpoint[2]))&&($currentpoint[2]!="")) {
			$pivotbordercolor = $currentpoint[2];
			}

			//pivottextcolor
			$pivottextcolor = "black";
			if((count($currentpoint)>3)&&(!is_null($currentpoint[3]))&&($currentpoint[3]!="")) {
			$pivottextcolor = $currentpoint[3];
			}

			if(($prow >= 0)&&($prow >= 0)) {
			$pivotstylematrix[$prow][$pcol] = "style='border:1px solid $pivotbordercolor;color:$pivottextcolor'";
			}
		}
	}

	//header
	if((count($args)>6)&&(!is_null($args[6]))) {
		$headers = $args[6];
		if (!is_array($headers)) { $headers = explode(',',$headers); }
	}
	else {
		$headers = array();
	}

	//tabletextcolor ;
	if((count($args)>7)&&(!is_null($args[7]))) {
		$tabletextcolor  = $args[7];
	}
	else {
		$tabletextcolor = "black";
	}

	// show objective ;
	if((count($args)>8)&&(!is_null($args[8]))) {
		$ShowObjective  = verifyshowobjective("simplexdisplaycolortable",$args[8],1,1);
	}
	else {
		$ShowObjective = 1;
	}

	// now create custom border styles to change the color of the table text
	$matrixtopborder = "border-top:1px solid $tabletextcolor;";
	$matrixtopleftborder = "border-left:1px solid $tabletextcolor;border-top:1px solid $tabletextcolor;";
	$matrixtoprightborder = "border-right:1px solid $tabletextcolor;border-top:1px solid $tabletextcolor;";
	$matrixleftborder = "border-top:1px solid $tabletextcolor;";
	$matrixleftborder = "border-left:1px solid $tabletextcolor;";
	$matrixrightborder = "border-right:1px solid $tabletextcolor;";
	$matrixbottomleftborder = "border-left:1px solid $tabletextcolor;border-bottom:1px solid $tabletextcolor;";
	$matrixbottomrightborder = "border-right:1px solid $tabletextcolor;border-bottom:1px solid $tabletextcolor;";
	$matrixleft = "border: 1px solid $tabletextcolor; border-width: 1px 0px 1px 1px; margin: 0px; padding: 0px;";
	$matrixright = "border: 1px solid $tabletextcolor; border-width: 1px 1px 1px 0px; margin: 0px; padding: 0px;";

	// Done processing arguments ---------------------------------------

	$lastrow = $rows-1;
	$lastcol = $cols-1;
	$ObjectiveColumn = $lastcol-1;

	$Tableau = "<table class='paddedtable' style='border:none;border-spacing: 0;border-collapse: collapse;text-align:right;border-spacing: 0px 0px;color:$tabletextcolor'>\r\n";
	$Tableau .= "<tbody>\r\n";
	for ($rloop=0; $rloop<$rows; $rloop++) {
		// add simplex line?
		if (($rloop==$lastrow)&&($mode==2)) {
			// add simplex row
			$Tableau .= "<tr class='onepixel'>\r\n";
			$Tableau .= "<td style='$matrixleftborder'></td>";
			for ($cloop=0;$cloop<$cols; $cloop++) {
				if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
					// skip objective column
				}
				else {
					$Tableau.= "<td style='$matrixtopborder'></td>\r\n";
				}
			}
			$Tableau .= "<td style='$matrixtopleftborder'></td><td style='$matrixtopborder'></td><td style='$matrixrightborder'></td>\r\n</tr>\r\n";
		}

	  $Tableau .= "<tr>\r\n";
	  if($rloop==0) {

		// matrix name
		if($simplexmatrixname!="") {
		  if(count($headers)>0) { $matricnamerows = $rows+1; } else { $matricnamerows = $rows; }
		  $Tableau.= "<td rowspan='$matricnamerows'> $simplexmatrixname </td>\r\n";
		}

		// column headers
		if(count($headers)>0) {
		  $Tableau.= "<td $nopad>&nbsp;</td>\r\n"; // for the left table border
		  for ($cloop=0;$cloop<$cols; $cloop++) {
			if  ($cloop==$lastcol) {
			  // R1C(Last)
			  if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $nopad>&nbsp;</td>\r\n";} // add augemented column
			}
			if((!is_null($headers[$cloop]))&&($headers[$cloop]!="")) {
			  $Tableau.= "<td>".$tick.$headers[$cloop].$tick."</td>";
			}
			else {
				if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
					// skip objective column
				}
				else {
			  		$Tableau.= "<td>&nbsp;</td>";
				}
			}
		  }
		  $Tableau.= "<td $nopad>&nbsp;</td></tr>\r\n<tr>\r\n";  // for the right table border
		}
	  }

	//TODO: use $ShowObjective

	  for ($cloop=0;$cloop<$cols; $cloop++) {

		if($showfractions==-1) {
		  $Element = $sm[$rloop][$cloop];					// ignore the denominator and show the string numerator
		}
		elseif($showfractions==1) {
		  $Element = fractionreduce($sm[$rloop][$cloop]);   // convert to fraction
		}
		else {
		  $Element = fractiontodecimal($sm[$rloop][$cloop]); // convert to decimal
		}

		$TableElement = $tick.$Element.$tick;
		$pivotsyle = $pivotstylematrix[$rloop][$cloop];

		if ($rloop==0) {
			// top row
			if ($cloop==0) {

			  // R1C1
			  $Tableau.= "<td style='$matrixtopleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
			}
			elseif  ($cloop==$lastcol) {

			  // R1C(Last)
			 if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td style='$matrixleftborder' >&nbsp;</td>\r\n";} // add augemented column
			 $Tableau.= "<td $pivotsyle>$TableElement</td><td style='$matrixtoprightborder'>&nbsp;</td>\r\n";
			}
			else {
				if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
					// skip objective column
				}
				else {
			  		$Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
				}
			}
		}
		elseif ($rloop==$lastrow) {

		  // top row
		  if ($cloop==0) {
			  // R(last)C1
			  $Tableau.= "<td style='$matrixbottomleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
		  }
		  elseif  ($cloop==$lastcol) {
			// R(last)C(Last)
			if($mode>1) { $Tableau.= "<td $nopad>&nbsp;</td><td style='$matrixleftborder' >&nbsp;</td>\r\n"; }
			$Tableau.= "<td $pivotsyle>$TableElement</td><td style='$matrixbottomrightborder'>&nbsp;</td>\r\n";
		  }
		  else {
		  	if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
					// skip objective column
				}
			else {
				$Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
			}
		  }
		}
		else {

		  if ($cloop==0) {
			$Tableau.= "<td style='$matrixleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
		  }
		  elseif ($cloop==$lastcol) {
			if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td style='$matrixleftborder' >&nbsp;</td>\r\n"; }
			$Tableau.= "<td $pivotsyle>$TableElement</td><td style='$matrixrightborder'>&nbsp;</td>\r\n";
		  }
		  else {
		  	if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
				// skip objective column
			}
			else {
				$Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
			}
		  }
		}
	  }
	  $Tableau.= "</tr>\r\n";
	}
	$Tableau.= "</tbody>\r\n";
	$Tableau.= "</table>\r\n";

	return $Tableau;
  }

//simplexdisplaylatex(simplexmatrix, [simplexmatrixname, showentriesfractions=1, $pivot = array(-1,-1), , ShowObjectiveColumn=1])
//
// Create a string that is a valid latex syntax for display.
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
//
// OPTIONAL
// simplexmatrixname: a string that holds the matrix name, like A or B.  You should leave balnk if you
//					  are creating a simplex for display
// showfractions: either 0 or 1
//				 0 convert simplex element to a decimal
//		 default 1 convert simplex element to a \displaystyle\frac{}{}
// pivot: list or array that contains the row, column.  This puts a circle around the cell at (row,col).
//		  Both row and column are ZERO based.
//		default point none
// showobjective : either 0 or 1
//                0 hide column
//                1 show column (default)
//
// RETURNS: a valid latex string
function simplexdisplaylatex() {

	//  arguments lise --------------------------------------------------
	//  0 = simplex matrix
	//  1 = simplex matrix name
	//  2 = show fractions (string)
	//  3 = circle pivot point, if supplied
	//  4 = Show Objective Column - Default yes/no

	// process arguments -----------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
		return "";
	}
	//   this function CANNOT use
	// as it will mess up when a string is sent to this procedure and is to be displayed
	$sm = $args[0];
	if(is_null($sm)){
		echo "Simplex is NULL - nothing to display.<br/>\r\n";
		return "";
	}

	$rows = count($sm);
	if($rows==1)  {
		// only 1 row
		echo "Error - a simplex matrix must have at least two rows.<br/>\r\n";
		return "";
	}
	$cols = count($sm[0]);

  	// simplex matrixname
  	if((count($args)>1)&&(!is_null($args[1]))) { $simplexmatrixname = $args[1]; } else { $simplexmatrixname = ""; }

  	//showfractions=1
  	if((count($args)>2)&&(!is_null($args[2]))) {
  		$showfractions = verifyshowfraction("simplexdisplaylatex",$args[2],1,1);
	}
  	else { $showfractions=1; }

  	//pivot
  	if((count($args)>3)&&(!is_null($args[3]))) {
		if (!is_array($args[3])) { $args[3]=explode(',',$args[3]); }
		$pivots = $args[3];
  	} else {
  		$pivots = NULL;
  	}

	// show objective ;
	if((count($args)>4)&&(!is_null($args[4]))) {
		$ShowObjective  = verifyshowobjective("simplexdisplaylatex",$args[4],1,1);
	}
	else {
		$ShowObjective = 1;
	}

	// Done processing arguments ---------------------------------------
	$lastrow = $rows-1;
	$lastcol = $cols-1;
	$ObjectiveColumn = $lastcol-1;

	if($simplexmatrixname=="") {
		$Tableau = "$\begin{bmatrix}[";
	} else {
		$Tableau = "$simplexmatrixname = $\begin{bmatrix}[";
	}
	$ExtraLine = "";
	$UseExtraLine = false;
	for ($cloop=0; $cloop<$cols; $cloop++) {
		if($cloop==$cols-1) {
			$Tableau .= "|c";
		} else {
			if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
				// skip objective column
			}
			else {
				$Tableau .= "c";
				$ExtraLine .= "&";
			}
		}

	}
	$Tableau .= "]<br/>\r\n";
	$ExtraLine .= "\\\\";

	for ($rloop=0; $rloop<$rows; $rloop++) {
		for ($cloop=0;$cloop<$cols; $cloop++) {
			if($cloop!=0) {
				$amp = "&";
			} else {
				$amp = "";
			}
			$top = $sm[$rloop][$cloop][0];
			$bot = $sm[$rloop][$cloop][1];
			if($showfractions==1) {
				if($bot!=1){
					$Element = "\displaystyle\\frac{".$top."}{".$bot."}";
					$UseExtraLine = true;
				} else {
					$Element = "$top";
				}

			} else {
				$Element = fractiontodecimal($sm[$rloop][$cloop]); // convert to decimal
			}

			// allow multiple items to be circled
			$isPivot = false;
			if(!is_null($pivots)) {
				for ($pivotloop=0; $pivotloop<count($pivots); $pivotloop++) {
                    $currentpoint = $pivots[$pivotloop];
                    // patched 2019-10-28
                    // not tracked down was this wouldn't be an array...
                    if(!is_array($currentpoint)) {
                        $prow = -1;
                        $pcol = -1;
                        echo "currentpoint is not an array - ".htmlentities($currentpoint).".<br/>\r\n";
                    } else {
                        if((count($currentpoint)>0)&&(!is_null($currentpoint[0]))&&($currentpoint[0]>=0)) {
                            $prow = $currentpoint[0];
                        }
                        else {
                            $prow = -1;
                        }
                        if((count($currentpoint)>1)&&(!is_null($currentpoint[1]))&&($currentpoint[1]>=0)) {
                            $pcol = $currentpoint[1];
                        }
                        else {
                            $pcol = -1;
                        }

                        if(($prow==$rloop)&&($pcol==$cloop)) {
                            $isPivot = TRUE;
                            break;
                        }
                    }
				}
			}

			if((!$ShowObjective)and($ObjectiveColumn==$cloop)){
				// skip objective column
			}
			else {
				// is this a pivot point
				if($isPivot) {
					$Tableau.= $amp." \\numcircledtikz{\$".$Element."\$} ";
				} else {
					$Tableau.= $amp." $Element ";
				}
			}
		}
		$Tableau.= "\\\\";
		if ($rloop==($lastrow-1)) { $Tableau .= " \hline"; }
		$Tableau.= "<br/>\r\n";
		if(($showfractions==1)&&($UseExtraLine)){
			$Tableau.= $ExtraLine."<br/>\r\n";
		}
	}
	$Tableau.= "\\end{bmatrix}$";

	return $Tableau;
  }


//simplexdisplaylatex2(simplex solution sets [, show$pivot=1, showentriesfractions=1, ShowObjectiveColumn=1])
//
// Creates an array of strings that is valid latex syntax for display.
//
// INPUTS
// simplex solution sets: a valid solution sets array
//
// OPTIONAL
// showpivot :either 0 or 1
//				   0 don't circle a pivot point
//		   default 1 circle a pivot point
//
// showfractions: either 0 or 1
//				 0 convert simplex element to a decimal
//		 default 1 convert simplex element to a \displaystyle\frac{}{}
//
// showobjective : either 0 or 1
//                0 hide column
//                1 show column (default)
//
// RETURNS: a solution sets of valid latex
function simplexdisplaylatex2() {

	//  arguments lise --------------------------------------------------
	//  0 = simplex solution sets
	//  1 = show pivot
	//  2 = show fractions (string)
	//  3 = Show Objective Column - Default yes/no

	// process arguments -----------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
		return "";
	}
	$simplexsolutionsets = $args[0];

	$rows = count($simplexsolutionsets);
	$cols = 1;
	for($r = 0; $r < $rows; $r++){
		$cols = max($cols, count($simplexsolutionsets[$r]));
	}

	//pivot
	if((count($args)>1)&&(!is_null($args[1]))) {
		if (!is_array($args[1])) { $args[1]=explode(',',$args[3]); }
		$showpivot = $args[1];
  	} else {
  		$showpivot = 1;
  	}

	//showfractions=1
	if((count($args)>2)&&(!is_null($args[2]))) {
  		$showfractions = verifyshowfraction("simplexdisplaylatex2",$args[2],1,1);
	}
	else { $showfractions=1; }

  	// show objective ;
	if((count($args)>3)&&(!is_null($args[3]))) {
		$ShowObjective  = verifyshowobjective("simplexdisplaylatex",$args[3],1,1);
	}
	else {
		$ShowObjective = 1;
	}

	// Done processing arguments ---------------------------------------

	$solutionsetsreturn = array();

	for($r = 0; $r < $rows; $r++){
  	$solutionsetsreturn[$r] = array();
  	for($c = 0; $c < $cols; $c++){
  		$temp = $simplexsolutionsets[$r];

  		if(!isset($temp[$c])) {
			$smtable = "";
		}
		elseif(is_null($temp[$c])){
			$smtable = "";
		}
		else {
			//	parent column number: column of the parent simplex (zero based).
		  	//	Pivot Point Condition:
		  	//	pivot point: point that will be pivoted on
		  	//	pivot points: array of all possible pivots
		  	//	simplexmatrix: simplex matrix to pivot on
		  	//	solution: the solution to the simplex matrix
			$solutionset = $simplexsolutionsets[$r][$c];
			$parentcolumn = $solutionset[0];
			$PivotPointCondition = $solutionset[1];
			$pivotpoint = $solutionset[2];
			$pivots = $solutionset[3];
			$sm = $solutionset[4];
			$solution = $solutionset[5];

			unset($mypivot);

			if($showpivot==1) {
				if(!is_null($pivotpoint)) {
					$mypivot[0] = $pivotpoint;
				} else {
					$mypivot = $pivots;
				}
			} else {
				$mypivot = NULL;
			}

			$smtable = simplexdisplaylatex($sm, "", $showfractions, $mypivot,$ShowObjective);
		}

	  	$solutionsetsreturn[$r][$c] = $smtable;
	  }
  }

  return $solutionsetsreturn;
  }


//simplexdisplaytable2(simplex solution sets[, ASCII tick marks,mode,show fractions,header column names,CSS tabletextcolor=black, multiple solution pivot border color=red, multiple solution pivot text color=blue, pivot border color=blue, pivot text color=black, ShowObjectiveColumn=1])
//
// Create a 1 or two dimensional array (depends on the input) that each element either contains a valid valid HTML table syntax or
//		 a nbsp; for displaying in a browser.
//
// INPUTS
// simplex solution sets: a valid solution sets array
//
// OPTIONAL
// displayASCII: either 0 or 1
//				0 do not use math ticks
//		default 1		use math ticks
// mode: either 0, 1 or 2
//		0 show no lines
//		1 show aumented line
//		2 show simplex  lines
// showfractions: either -1, 0 or 1
//				-1 show as string
//				 0 convert simplex element to a decimal
//		 default 1 convert simplex element to a fraction
// headers: list or array of the variables "x1,x2,x3" that are used for the column titles.
//   default none
// tablestyle: for any additional styles for the table that you may want.  like "color:#40B3DF;"
//	  default none
//
// showobjective : either 0 or 1
//                0 hide column
//                1 show column (default)
//
// RETURNS: an array
function simplexdisplaytable2() {
	//
	// DEFAULT colors
	$defaultMultipleSolutionpivotbordercolor = "red";
	$defaultMultipleSolutionpivottextcolor = "blue";

	$defaultpivotbordercolor = "blue";
	$defaultpivottextcolor = "black";

	$defaulttabletextcolor = "black";
	//
	//  arguments list --------------------------------------------------
	//  0 = simplex solution sets
	//		   parent column
	//		 , pivot
	//		 , all pivot points
	//		 , simplex matrix
	//		 , solution
	//  1 = display ASCII tick marks (1=yes/0=no)
	//  2 = mode - no line, aumented, or simplex
	//  3 = show fractions (integer,1=yes/0=no)
	//  4 = header column names, default is not to show
	//  5 = CSS tabletextcolor.
	//  6 = Multiple Solution pivot border color
	//  7 = Multiple Solution pivot text color
	//  8 = pivot border color
	//  9 = pivot text color
	// 10 = Show Objective Column - Default yes/no

	// process arguments -----------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
		return "";
	}

	$simplexsolutionsets = $args[0];

	$rows = count($simplexsolutionsets);
	$cols = 1;
	for($r = 0; $r < $rows; $r++){
		$cols = max($cols, count($simplexsolutionsets[$r]));
	}

	//displayASCII
	if((count($args)>1)&&(!is_null($args[1]))) {
	$tick = verifyASCIIticks("simplexdisplaytable2",$args[1],"`");
	}
	else { $tick = "`"; }

	//mode
	if((count($args)>2)&&(!is_null($args[2]))) {
		$mode = verifymode("simplexdisplaytable2",$args[2],2);
	}
	else { $mode=2; }

	//showfractions=1
	if((count($args)>3)&&(!is_null($args[3]))) {
		$showfractions = verifyshowfraction("simplexdisplaytable2",$args[3],1,1);
	}
	else { $showfractions=1; }

	//header
	if((count($args)>4)&&(!is_null($args[4]))) {
	$headers = $args[4];
	if (!is_array($headers)) { $headers = explode(',',$headers); }
	}
	else {
		$headers = array();
	}

	//table text color
	if((count($args)>5)&&(!is_null($args[5]))) {
		$tabletextcolor = $args[5];
		if($tabletextcolor=="") { $tabletextcolor = $defaulttabletextcolor; }
	}
	else {
		$tabletextcolor = $defaulttabletextcolor;
	}

	//Multiple Solution pivot border color
	if((count($args)>6)&&(!is_null($args[6]))) {
		$MultipleSolutionpivotbordercolor = $args[6];
		if($MultipleSolutionpivotbordercolor=="") { $MultipleSolutionpivotbordercolor = $defaultMultipleSolutionpivotbordercolor; }
	}
	else {
		$MultipleSolutionpivotbordercolor = $defaultMultipleSolutionpivotbordercolor;
	}

	//Multiple Solution pivot text color
	if((count($args)>7)&&(!is_null($args[7]))) {
		$MultipleSolutionpivottextcolor = $args[7];
		if($MultipleSolutionpivottextcolor=="") { $MultipleSolutionpivottextcolor = $defaultMultipleSolutionpivottextcolor; }
	}
	else {
		$MultipleSolutionpivottextcolor = $defaultMultipleSolutionpivottextcolor;
	}

	// pivot border color
	if((count($args)>8)&&(!is_null($args[8]))) {
		$pivotbordercolor = $args[8];
		if($pivotbordercolor=="") { $pivotbordercolor = $defaultpivotbordercolor; }
	}
	else {
		$pivotbordercolor = $defaultpivotbordercolor;
	}

	// pivot text color
	if((count($args)>9)&&(!is_null($args[9]))) {
		$pivottextcolor = $args[9];
		if($pivottextcolor=="") { $pivottextcolor = $defaultpivottextcolor; }
	}
	else {
		$pivottextcolor = $defaultpivottextcolor;
	}

	// show objective ;
	if((count($args)>10)&&(!is_null($args[10]))) {
		$ShowObjective  = verifyshowobjective("simplexdisplaylatex",$args[10],1,1);
	}
	else {
		$ShowObjective = 1;
	}


	// Done processing arguments ---------------------------------------

	$solutionsetsreturn = array();

	for($r = 0; $r < $rows; $r++){
		$solutionsetsreturn[$r] = array();
		for($c = 0; $c < $cols; $c++){
			$temp = $simplexsolutionsets[$r];

			if(!isset($temp[$c])) {
				$smtable = "&nbsp;";
			}
			elseif(is_null($temp[$c])) {
				$smtable = "&nbsp;";
			}
			else {
				//	parent column number: column of the parent simplex (zero based).
				//	Pivot Point Condition:
				//	pivot point: point that will be pivoted on
				//	pivot points: array of all possible pivots
				//	simplexmatrix: simplex matrix to pivot on
				//	solution: the solution to the simplex matrix
				$solutionset = $simplexsolutionsets[$r][$c];
				$parentcolumn = $solutionset[0];
				$PivotPointCondition = $solutionset[1];
				$pivotpoint = $solutionset[2];
				$pivots = $solutionset[3];
				$sm = $solutionset[4];
				$solution = $solutionset[5];

				if($PivotPointCondition ==PivotPointFoundMultipleSolutionList) {
					$bordercolor = $MultipleSolutionpivotbordercolor;
					$textcolor = $MultipleSolutionpivottextcolor;
				}
				else {
					$bordercolor = $pivotbordercolor;
					$textcolor = $pivottextcolor;
				}

				unset($pivotwithcolor); // clear array

				if(!is_null($pivotpoint)) {
					$pivotpoint[count($pivotpoint)] = $bordercolor;
					$pivotpoint[count($pivotpoint)] = $textcolor;
					$pivotwithcolor[0] = $pivotpoint;
				}
				elseif(!is_null($pivots)) {
					// set the color to the pivot points
					$pivotwithcolor = $pivots;
					for($color = 0,$size = count($pivots); $color < $size; $color++){
						$pivotwithcolor[$color][count($pivotwithcolor[$color])] = $bordercolor;
						$pivotwithcolor[$color][count($pivotwithcolor[$color])] = $textcolor;
					}
				}
				else {
					$pivotwithcolor = array();
				}
				$smtable = simplexdisplaycolortable($sm, "", $tick, $mode, $showfractions, $pivotwithcolor, $headers, $tabletextcolor,$ShowObjective);
			}

			$solutionsetsreturn[$r][$c] = $smtable;
		}
	}

	return $solutionsetsreturn;
}

//simplexdisplaytable2string(simplexsetstable, cellpadding=5)
//
// Creates a string used to display all pivot paths of the simplex matrix for displaying in a browser.
//
// INPUTS
//  simplexsetstable: output from simplexdisplaytable2
//  cellpadding: cellpadding in the table for each cell
//
// RETURNS: a string for displaying (usually in showanswer).
//)
function simplexdisplaytable2string($simplexsetstable, $cellpadding=5){

	$retval = "";

	$rows = count($simplexsetstable);
	$cols = count($simplexsetstable[0]);
	$retval .= "<table cellpadding='$cellpadding'>\r\n";
	for($r = 0; $r < $rows; $r++){
 		$retval .= "<tr>\r\n";
  		for($c = 0; $c < $cols; $c++){
  			$retval .= "<td>\r\n";
	  		$retval .= $simplexsetstable[$r][$c];
	  		$retval .= "\r\n</td>\r\n";
	  	}
		$retval .= "</tr>\r\n";
	}
	$retval .= "</table>\r\n";

	return $retval;
}

// *********************************************************************************************************************************
// *********************************************************************************************************************************
//
// pivot point constants
//
// *********************************************************************************************************************************
// *********************************************************************************************************************************
//
define("PivotPointNotMixedConstraint", -2);
define("PivotPointNoSolution", -1);
define("PivotPointFoundList", 0);
define("PivotPointNone", 1);
define("PivotPointFoundMultipleSolutionList", 2);
//
// *********************************************************************************************************************************
// *********************************************************************************************************************************

//simplexfindpivotpoint(simplexmatrix)
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
//
// returns array(condition, pivotpoints )
// where
// condition: -1 means No Solution
//			   0 found pivot point(s)
//			   1 means no pivot points found
//			   2 found possible multiple solution pivot point(s)
//
// pivotpoints: an array where the entries are the row, column where the pivot point was
//			  found.  Both row and column are ZERO based.
//			  $pivotpoints[0] = (0,1)
//			  $pivotpoints[1] = (1,2)
function simplexfindpivotpoint($sm) {
	// variables used for loops and conditions
	$rows = count($sm);
	if($rows<2) {
		echo "In simplexfindpivotpoint you must supply a simplex matrix with at least two rows.<br/>";
		return NULL;
	}
	$cols = count($sm[0]);

	$lastrow = $rows-1;								// zero based
	$lastcol = $cols-1;								// zero based
	$pivotcolumncount = count($sm[$lastrow])-1-1;	// zero based - minus last column and f/g column

	// variables used for finding the pivot and return vaues
	$pivotpoints = array();					// list of possible pivot point
	$pivotcondition = PivotPointNoSolution;	// set to no solutions
	$minfraction = array(-1,1);				// the smallest ratio - set to not found
	$ColumnMinValue = array(1,1);			// not found as we need to find negatives

	// In the last row find the largest negative value
	for($c=0;$c<$pivotcolumncount;$c++){
		$value = $sm[$lastrow][$c]; //fractiontodecimal($sm[$lastrow][$c]);
		if($value[0]<0) {
			if($value[0]*$ColumnMinValue[1] < $value[1]*$ColumnMinValue[0]) {
				// Set the smallest ratio
				$ColumnMinValue = $value;
			}
		}
	}

	// create storage for the ratios
	$ratiotest = array();

	if($ColumnMinValue[0]<0) {
		// Find all columns that are equal to the min value
		$ColumnMinIndexList = array();

		// Find all columns that are equal with the maximum negative values
		for($c=0;$c<$cols;$c++) {
			if($ColumnMinValue==$sm[$lastrow][$c]) {
				$k = count($ColumnMinIndexList);
				$ColumnMinIndexList[$k] = $c;  // save the column index that is equal to the minimum column value
			}
		}

		for ($m=0;$m<count($ColumnMinIndexList); $m++) {
			$ratiotest[$m] = array();	 // create an array of ratios
			$c = $ColumnMinIndexList[$m]; // for column c index m
			for ($r=0;$r<$lastrow; $r++) {
				$lastcolumn =  $sm[$r][$lastcol];
				$testcolumn =  $sm[$r][$c];

				// test column must be positive and last column must be non-negative 3-30-2016
				if(($testcolumn[0]<=0)||($lastcolumn[0]<0)) {
					$value = array(-1,1);
				}
				else {
					$top = $lastcolumn[0]*$testcolumn[1];
					$bot = $lastcolumn[1]*$testcolumn[0];
					if($bot < 0) {
						$top*=-1;
						$bot*=-1;  // make the bottom positive
					}
					if($bot==0) { $bot =1; }
					$gcf = gcd($top,$bot);
					$top /= $gcf;
					$bot /= $gcf;
					$value = array($top,$bot);
				}

				if($value[0] >= 0) {
					// test for the smallest non-negative value to find the pivot points - fixed 2-2-2016
					if($minfraction[0] == -1) {
						$minfraction = $value;
					}
					elseif($value[0]*$minfraction[1] < $value[1]*$minfraction[0]) { // $value <$minfraction
						$minfraction = $value;
					}
				}
				$ratiotest[$m][$r] = $value;
			}
		}

		if($minfraction[0] == -1) {
		  // no more pivot points - set to no solutions as there are negative in the objective row
		  $pivotcondition = PivotPointNoSolution;
		}
		else {

		  $pivotcondition = PivotPointFoundList;
		  // find all points that the user could pivot on
		  for ($m=0;$m<count($ColumnMinIndexList); $m++){
			$c = $ColumnMinIndexList[$m];
			for ($r=0;$r<$lastrow;$r++) {
			  if($ratiotest[$m][$r]==$minfraction) {
				// find a pivot point - add to the list
				$pivotpoints[count($pivotpoints)] = array($r,$c);
			  }
			}
		  }
		}
  }
  else {
	// check for multiple solutions
	// look at all zero indicator (non-basic) variables and see if the objective row is zero
	// and there are nonnegative ratios in the column
	// there are $lastrow number of slack variables

	// use lastcol to eliminate the augmented column and the optimization column variable
	//
	// if we have 3 constraints with 2 variables then we will have:
	//
	// x1,  x2,  s1,  s2,  s3,  f,  obj
	//  0	1	2	3	4   5	6
	//
	$startcol = 0;		 // the first possible place to check
	$endcol = $lastcol-2;  // 1 for the objective and 1 for the f/g variable

	$ColumnMultipleIndexList = array();

	for ($c=$startcol;$c<$endcol; $c++) {
		if($sm[$lastrow][$c][0]==0) { // if the objective row has a nonbasic variable, then multiple solutions MAY exist
			$numberofnonzeroenteries = 0;
			$j = count($ColumnMultipleIndexList);
			$ratiotest[$j] = array();	 // create an array of ratios
			// now test to see if this is a valid
			for ($r=0;$r<$lastrow; $r++){
		  $ratiotest[$j][$r] = array();
		  $lastcolumn =  $sm[$r][$lastcol];
		  $testcolumn =  $sm[$r][$c];
		  if($testcolumn[0]<=0) {
			$value = array(-1,1);
		  }
		  else {
			$numberofnonzeroenteries++;  // found a positive possible pivot value
			$top = $lastcolumn[0]*$testcolumn[1];
			$bot = $lastcolumn[1]*$testcolumn[0];
			if($bot < 0) {
				$top*=-1;
				$bot*=-1;  // bottom must be positive
			}
			$gcf = gcd($top,$bot);
			$top /= $gcf;
			$bot /= $gcf;
			$value = array($top,$bot);
		  }
		  $ratiotest[$j][$r] = $value;
		}

		if($numberofnonzeroenteries > 1) {
		  // check for miniman value since this is a valid column
		  for ($r=0;$r<$lastrow; $r++) {
			if (($ratiotest[$j][$r][0] > 0)&&($sm[$r][$c][0] > 0)) {
			  // save the smallest positive value to find the
			  //if($minfraction == -1) {$minfraction = $ratiotest[$j][$r]; }
			  //if($minfraction > $ratiotest[$j][$r]) {$minfraction = $ratiotest[$j][$r]; }
			  if($minfraction[0] == -1) {$minfraction = $ratiotest[$j][$r]; }
			  if($ratiotest[$j][$r][0]*$minfraction[1] < $ratiotest[$j][$r][1]*$minfraction[0]) { // $value <$minfraction
		  		$minfraction = $ratiotest[$j][$r];
			  }
			}
		  }

		  // save this column as a possile pivot column
		  $ColumnMultipleIndexList[$j] = $c;
		}
		}
	}

	if($minfraction[0] == -1) {
	  // no pivot points found.
	  $pivotcondition = PivotPointNone;
	}
	else {
	  // create a new list of possible pivot colums provide that there
	  $pivotcondition = PivotPointFoundMultipleSolutionList;

	  // find all points that the user could pivot on
	  for ($j=0;$j<count($ColumnMultipleIndexList); $j++)  {
		$c = $ColumnMultipleIndexList[$j];
		for ($r=0;$r<$lastrow; $r++) {
		  if($ratiotest[$j][$r]==$minfraction) {
			// find a pivot point - add to the list
			$pivotpoints[count($pivotpoints)] = array($r,$c);
		  }
		}
	  }
	}
  }

  // return the status and the list of points, if any.
  return array($pivotcondition, $pivotpoints);
}

//simplexfindpivotpointmixed(simplexmatrix)
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
//
// returns array(condition, pivotpoints )
// where
// condition: -2 means Nnot a mixed constraint
//			  -1 means No Solution
//			   0 found pivot point(s)
//			   1 means no pivot points found
//			   2 found possible multiple solution pivot point(s)
//
// pivotpoints: an array where the entries are the row, column where the pivot point was
//			  found.  Both row and column are ZERO based.
//			  $pivotpoints[0] = (0,1)
//			  $pivotpoints[1] = (1,2)
function simplexfindpivotpointmixed($sm) {

  	// variables used for loops and conditions
  	$rows = count($sm);
  	if($rows<2) {
  	echo "In simplexfindpivotpointmixed you must supply a simplex matrix with at least two rows.<br/>";
  	return NULL;
  }
  	$cols = count($sm[0]);

  	$lastrow = $rows-1;					  // zero based
  	$lastcol = $cols-1;					  // zero based

  	// variables used for finding the pivot and return values
  	$columnlist = array_fill(0, $cols, "N"); // column flag where negative entry value was found

  	$ratiolist = array();					// 2 dimensional array that holds all possible ratio values
  	$pivotpoints = array();				  // list of possible pivot point

  	$ColumnMinValue = array(1,1);			// not found as we need to find negatives

	$pivotcondition = PivotPointNotMixedConstraint; // set to not a mixed constraint problem

  	if(!simplexhasmixedconstrants($sm)) return array($pivotcondition,NULL);

  	// Flag all columns that need to be checked.
  	for($r=0;$r<$lastrow;$r++){
  		if(fractiontodecimal($sm[$r][$lastcol])<0) {
  			// now search each column to see if a negative entry is found
  			// if so set flag
  			for($c = 0; $c < $lastcol; $c++){
			  	if(fractiontodecimal($sm[$r][$c])<0) {
					$columnlist[$c] = "Y";  // found a pivot column
				}
			}
		}
  	}

  	$pivotcondition = PivotPointNoSolution;  // set to no solutions
  	$hasvalidpivot = FALSE;

  	// if all columns are "N" then no pivots exists and therfore no solutions exists.
  	for($c = 0; $c < $lastcol; $c++){
	  	if($columnlist[$c] == "Y") {
	  		$hasvalidpivot = TRUE;
			break;
		}
	}

  	if(!$hasvalidpivot) return array($pivotcondition,NULL);

  	// now find all possible ratios

  	for($r=0;$r<$lastrow;$r++){
  		$ratiolist[$r] = array();
  		for($c = 0; $c < $lastcol; $c++){
			if($columnlist[$c] == "Y") {
				$lastcolumn =  $sm[$r][$lastcol];
				$testcolumn =  $sm[$r][$c];
				$top = $lastcolumn[0]*$testcolumn[1];
				$bot = $lastcolumn[1]*$testcolumn[0];
				if($bot < 0) {
					$top*=-1;
					$bot*=-1;  // make the denominator must always be positive
				}
				$gcf = gcd($top,$bot);
				$top /= $gcf;
				$bot /= $gcf;
				$ratiolist[$r][$c] = array($top,$bot);
			}
		}
  	}

  	// There are at least 1 pivot point
  	$pivotcondition = PivotPointFoundList;

  	// Find the pivot point PER column
  	//
	//
	// Test if $ratiolist[$r][$c] < $minfraction
	//
	// $ratiolist[$r][$c][0]   $minfraction[0]
	// --------------------- < ---------------
	// $ratiolist[$r][$c][1]   $minfraction[1]
	//
	// As both denominator are POSITIVE - we need to test for the following:
	//
	// $ratiolist[$r][$c][0]*$minfraction[1] < $ratiolist[$r][$c][1]*$minfraction[0]
	//
  	for($c = 0; $c < $lastcol; $c++){
  		if($columnlist[$c] == "Y") {
  			$minfraction = array(-1,1);			  // the smallest ratio - set to not found
  			for($r=0;$r<$lastrow;$r++){
  				// now save the smallest positive value
				if($ratiolist[$r][$c][0] > 0) {
			  		if($minfraction[0] < 0) {
			  			$minfraction = $ratiolist[$r][$c];
			  		}
			  		elseif($ratiolist[$r][$c][0]*$minfraction[1] < $ratiolist[$r][$c][1]*$minfraction[0]) {
			  			$minfraction = $ratiolist[$r][$c];
					}
				}
			}

			// min fraction for column has been found
			for($r=0;$r<$lastrow;$r++) {
				if($ratiolist[$r][$c]==$minfraction) {
					// find a pivot point - add to the list
					$pivotpoints[count($pivotpoints)] = array($r,$c);
		  		}
			}
		}
  	}

  	// return the status and the list of points, if any.
  	return array($pivotcondition, $pivotpoints);
}

//simplexgetentry(simplexmatrix,row,col)
//
// gets an entry from the simplex matrix
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
// row: row number (zero based - first row is row 0)
// col: column number (zero based - first row is row 0)
//
// RETURNS: entry from a simplex matrix at given row and col
//
function simplexgetentry($sm,$r,$c) {
  if ($r<0 || $r>=count($sm)) {
	echo "$r is an invalid row.<br/>\r\n";
	return 0;
  }

  if ($c<0 || $c>=count($sm[0])) {
	echo "$c is an invalid column.<br/>\r\n";
	return 0;
  }
  return fractionreduce($sm[$r][$c]);
}


// simplexpivot(simplexmatrix,pivotpoint)
//
// this function pivots the simplex matrix on the given point
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
// pivotpoint:  list or array that contains the point to be pivoted on.
//				Both row and column are ZERO based.
//
// RETURNS:  the pivoted simplex matrix
function simplexpivot($sm,$pivotpoint) {

  if (!is_array($pivotpoint)) { $pivotpoint=explode(',',$pivotpoint); }
  $Pivotrow = $pivotpoint[0];
  $Pivotcol = $pivotpoint[1];

  $PivotValue = $sm[$Pivotrow][$Pivotcol];

  // change pivot point to a one
  for ($j=0; $j<count($sm[$Pivotrow]); $j++) {
  	$top = $sm[$Pivotrow][$j][0]*$PivotValue[1];
  	$bot = $sm[$Pivotrow][$j][1]*$PivotValue[0];
  	if($bot < 0) {
		$top*=-1;
		$bot*=-1;  // must be positive
	}
  	$gcf = gcd($top,$bot);
  	$top /= $gcf;
  	$bot /= $gcf;
	$sm[$Pivotrow][$j]= array($top,$bot);  // divide by $PivotValue
  }

  // now zero out all other values in that row
  for ($r=0; $r<count($sm); $r++) {
	if($r!=$Pivotrow) {
	  $PivotValue = array(-$sm[$r][$Pivotcol][0],$sm[$r][$Pivotcol][1]);
	  for ($c=0; $c<count($sm[$r]); $c++) {
	   		// multiplication
	  		$top = $PivotValue[0]*$sm[$Pivotrow][$c][0];
  		$bot = $PivotValue[1]*$sm[$Pivotrow][$c][1];

  		// addition
  		$top = $top*$sm[$r][$c][1] + $sm[$r][$c][0]*$bot;
  		$bot = $bot*$sm[$r][$c][1];
	  		if($bot < 0) {
				$top*=-1;
				$bot*=-1;  // must be positive
		}
	  		$gcf = gcd($top,$bot);
	  		$top /= $gcf;
			$bot /= $gcf;
			$sm[$r][$c]= array($top,$bot);
	  }
	}
  }

  return $sm;
}

//function simplexreadtoanswerarray(simplexmatrix, [startnumber, answer])
//
// Create/extend an array of values read by rows for the simplex matrix starting at startnumber
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
//
// OPTIONAL
// startnumber: starting number of the array.  Default is 0
// answer: pass $answer if extending an existing $answer array
//
// RETURNS: an array
function simplexreadtoanswerarray($sm, $startnumber=0, $ans=array()) {

  $rows = count($sm);
  $cols = count($sm[0]);

  for ($r=0; $r<$rows; $r++) {
	for ($c=0;$c<$cols; $c++) {
		$index = $r*$cols+$c + $startnumber;
		$ans[$index] = simplexgetentry($sm,$r,$c);
	}
  }

  return $ans;
}

//simplexreadsolution(simplexmatrix,type,showfractions)
//
// This reads the simplex matrix to find the current solution to the optimization problem.  It returns
// an array that contains the solution.
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
//  type: a string that contains either "max" or "min"
//  showfractions: either 0 or 1
//				0 shows decimals
//		default 1 shows fractions
//
// RETURNS: an array(solution values for sm, IsOptimized)
//
// For Max solutions the solution is an array in the form of:
//
// x1,  x2, etc,  s1,  s2,  etc.,  f,  IsOptimized
// where f contains the maximum value
// IsOptimized contains either a Yes or a No (objective has been reached)
//
//
// For Min solutions the solution is an array in the form of:
//
// x1,  x2, etc,  y1,  y2,  etc.,  g,  IsOptimized
// where g contains the minimium value
// IsOptimized contains either a Yes or a No (objective has been reached)
//
function simplexreadsolution($sm,$type,$showfractions=1,$debug=0) {
	// as the end user will be suppling this it will be in fraction form
	// convert to an array()
	$sma = simplextoarray($sm);
	return simplexreadsolutionarray($sma,$type,$showfractions,$debug);
}

function simplexreadsolutionarray($sma,$type,$showfractions=1,$debug=0) {

	if($debug==1) { echo "starting simplexreadsolutionarray<br/>"; }

	// process arguments -----------------------------------------------
	$type = verifytype("simplexreadsolution",$type,"max");
	if(is_null($type)) return null;

	// showfractions
	$showfractions = verifyshowfraction("simplexreadsolution",$showfractions,1);

	// Done processing arguments ---------------------------------------
	//
	// if we have 3 constraints with 2 variables then we will have:
	//
	// x1,  x2,  s1,  s2,  s3,  f,  obj
	//  0	1	2	3	4   5	6
	// rows = 4 --> slacks = 4 (3 slacks + 1 objectives)
	// lastrow =3
	// cols = 7
	// lastcol = 6
	// startcol = 6-4 = 2  --> lastcol-rows  // start column of slacks
	// endcol   = 2+3 = 5  --> startcol + lastrow
	$solution = array();
	$rows = count($sma);
	$cols = count($sma[0]);
	$lastrow = $rows-1;
	$lastcol = $cols-1;
	$optimizevariable = $lastcol -1;
	$objectiveposition = $lastcol-1;
	$dualplusobjective  = count($sma)+1;
	$var  = $lastcol-$rows;  // number of x variables
	$pivotcolumncount = $cols-1-1;	 //  zero based - minus last column and f/g column

	if(simplexhasmixedconstrants($sma)){
		$type=="max";	// override default reaing as mixed constrainsts min problems are read as max problems
	}

	if($type=="min") {
		for($c=0;$c<$var;$c++) {
			$solution[$c] = 0;
			$columnsolutionfound  = true;
			$zerorow = -1;   // not found
			if($sma[$lastrow][$c][0]==0) {
				for($r=0;$r<$lastrow;$r++) {
					if(($sma[$r][$c][0]!=0)&&($sma[$r][$c][0]!=1)) {
						$columnsolutionfound = false;
						break; // This should break out of the for r loop
					}
					if($sma[$r][$c][0]==$sma[$r][$c][1]){
						if($zerorow != -1) {
							$columnsolutionfound = false;
							break; // This should break out of the for r loop
						} else { $zerorow = $r; }
					}
	  			}

	  			if($columnsolutionfound) {
	  				if($showfractions==1) {
						$solution[$c] = fractionreduce($sma[$zerorow][$lastcol]);
		  		  	} else {
		  		  		$solution[$c] = fractiontodecimal($sma[$zerorow][$lastcol]);
	  			  	}
				}
			}
			if($debug==1) { echo "$c) ".$solution[$c]." <br/>";}
		}

		for($c=$var;$c<($dualplusobjective+1);$c++) {
			if($showfractions==1) {
				$solution[$c] = fractionreduce($sma[$lastrow][$c]);
			}
			else {
				$solution[$c] = fractiontodecimal($sma[$lastrow][$c]);
			}
			if($debug==1) { echo "$c) ".$solution[$c]." <br/>";}
		}
	}
	else { // max
		for($c=0;$c<$lastcol-1;$c++) {
			$solution[$c] = 0;
			$columnsolutionfound = true;
			$zerorow = -1;   // not found
			if($sma[$lastrow][$c][0]==0) {
				for($r=0;$r<$lastrow;$r++) {
					if(($sma[$r][$c][0]!=0)&&($sma[$r][$c][0]!=1)) {
						$columnsolutionfound = false;
					}
					if($sma[$r][$c][0]==$sma[$r][$c][1]) {
						if($zerorow != -1) { $columnsolutionfound = false; } else { $zerorow = $r; }
					}
				}

				if($columnsolutionfound) {
					if($showfractions==1) {
						$solution[$c] = fractionreduce($sma[$zerorow][$lastcol]);
					}
					else {
						$solution[$c] = fractiontodecimal($sma[$zerorow][$lastcol]);
					}
				}

			}
			if($debug==1) { echo "$c) ".$solution[$c]." <br/>";}
		}
	}

	// this is for mixed constarants as the optimizevariable value could be -1 which would make the lastcol value also negative
	// multipling them makes it positive
	$optimizetop = $sma[$lastrow][$lastcol][0]*$sma[$lastrow][$optimizevariable][0];
	$optimizebot = $sma[$lastrow][$lastcol][1];
	$optimizefrac = array($optimizetop,$optimizebot);

	if($showfractions==1) {
		$solution[$objectiveposition] = fractionreduce($optimizefrac);
	}
	else {
		$solution[$objectiveposition] = fractiontodecimal($optimizefrac);
	}

	if($debug==1) {echo "$objectiveposition) ".$solution[$objectiveposition]." <br/>";}

	$solution[$lastcol] = "Yes";  // objective reached
	if(simplexhasmixedconstrants($sma)){
		$solution[$lastcol] = "No";
	}
	else
	{
		for($c=0;$c<$pivotcolumncount;$c++) {
			if($sma[$lastrow][$c][0] < 0) {
				$solution[$lastcol] = "No";
				break;
			}
		}
	}
	if($debug==1) {echo "$lastcol) ".$solution[$lastcol]." <br/>";
	  echo "ending simplexreadsolutionarray<br/>";
	}
	return $solution;
}


//simplexsetentry(simplexmatrix,row,col,numerator,denominator)
//
// set entry for the simplex matrix at the given row and col with the given numerator and denominator.
//
// INPUTS
// simplexmatrix: a valid simplex matrix.
// row: row number (zero based - first row is row 0)
// col: column number (zero based - first row is row 0)
// numerator: any integer
// denominator any natural number
//
// RETURNS: nothing
function simplexsetentry($sm,$r,$c,$n,$d) {
  if ($r<0 || $r>=count($sm)) {
	echo "$r is an invalid row.<br/>\r\n";
	return 0;
  }

  if ($c<0 || $c>=count($sm[0])) {
	echo "$c is an invalid column.<br/>\r\n";
	return 0;
  }
  if ($d==0) {
	echo "$d is an invalid denominator.<br/>\r\n";
	return 0;
  }
  if ($d<0) {
	// make denominator positive
	$d*=-1;
	$n*=-1;
  }
  $sm[$r][$c][0] = $n;
  $sm[$r][$c][1] = $d;
  return 1;
}


// simplexsolutiontolatex(solution)
//
// This function converts all fractions in the solution into latex fractions
// \displaystyle\frac{numerator}{denominator}
//
// INPUTS
// solution - an array of "fractions" in the form of array(numerator, denominator)
//
// RETURNS: an array of valid latex
function simplexsolutiontolatex($solution){

	for($i=0,$size = count($solution);$i<$size;$i++)
	{
		$xvar = fractionparse($solution[$i]);
		$Top = $xvar[0];
		$Bot = $xvar[1];
		if($Bot > 1) {
			$returnvalue[$i] = "\displaystyle\\frac{{$Top}}{{$Bot}}";
		} else {
			$returnvalue[$i] = "{$solution[$i]}";
		}

	}

	return $returnvalue;
}

//simplexsolve2(simplexmatrix,type,[showfractions=1])
//
// Mixed constraints
//
// this method solves the minimization problem which has the following conditions
// 1) The objective function is to be minimized.
// 2) All variables are nonnegative.
// 3) The constraints are of the form: a1x1+ a2x2+ ... + anxn >= b  where b is any constant
//
// INPUTS:
//  simplexmatrix: a valid simplex matrix.
//  type: a string that contains either "max" or "min"
//  showfractions: either 0 or 1
//				0 shows decimals
//		default 1 shows fractions
//
// RETURNS (simplexsets[][], $objectivereachedsolutionlist, runtime)
//
//  simplexsets[][] = a 2 dimensional array used to build an output for the various paths that could be used to solve the simple
//
//	parent column number: column of the parent simplex (zero based).
//	Pivot Point Condition:
//	pivot point: point that will be pivoted on
//	pivot points: array of all possible pivots
//	simplexmatrix: simplex matrix to pivot on
//	solution: the solution to the simplex matrix
//
//    |				   0					  |					1					 |					2				     |
//  0 | simplex (1 pivot) intial matrix		  |										 |									     |
//  1 | simplex (2 pivot)					  |										 |									     |
//  2 | simplex (1st pivot option from (1,0)) |										 | simplex (2nd pivot option from (1,0)) |
//  3 | simplex (2 pivot)					  |										 | simplex (1 pivot)				     |
//  4 | simplex (1st pivot option from (3,0)) | simplex (2nd pivot option from (3,0))| simplex (1 pivot)				     |
//  5 | simplex (0 pivot)					  | simplex (0 pivot)					 | simplex (0 pivot)				     |
//
function simplexsolve2() {
	//  arguments list -------------------------------------------------
	//  0 = simplex matrix
	//  1 = type
	//  2 = show fractions (0 or 1)

	// save start time -------------------------------------------------
	$starttime = microtime(true);  //  for function timing

	// process arguments -----------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to solve - no simplex matrix supplied.<br/>\r\n";
		return "";
	}
	$sm = $args[0];

	if(count($sm)<2)  {
		// only 1 row
		echo "Error - a simplex matrix must have at least two rows.<br/>\r\n";
		return "";
	}

	// simplex type
	if((count($args)>1)&&(!is_null($args[1]))) {
	  $type =  verifytype("simplexsolve2",$args[1],"max");
	} else {
	  $type = "max";
	}

	// showfractions
	if((count($args)>2)&&(!is_null($args[2]))) {
	  $showfractions = verifyshowfraction("simplexsolve2",$args[2],1);
	} else {
	  $showfractions = 1;
	}

	// Done processing arguments ---------------------------------------
	$simplexstack = array();		// a stack of simplexset object that need to be pivoted on
	$simplexsets = array();			// simplex list of matricies
	$objectivereached = array();	// list of solution that have been optimized
	$sm = simplextoarray($sm);		// make sure that all elements are fraction arrays
	$rows = 0;						// set to the initial simplex matrix row
	$simplexsets[$rows] = null;     // set initial condition
	$columns = 0;					// set to the initial simplex matrix column
	$parentcolumn = 0;				// set to the current active column
	$exitwhile = FALSE;				// flag to exit loop
	$popstack = FALSE;				// flag to get an item from the stack

	// step 1
	$solution = simplexreadsolutionarray($sm,$type,$showfractions);

	// step 2
	// is this a objective reached?
	if($solution[(count($solution)-1)]=="Yes") {
		$objectivereached[count($objectivereached)] = $solution;
	}

    $hasmixedconstraints = simplexhasmixedconstrants($sm);

	do {
		// step 3
		$pivots = NULL;

		if($parentcolumn==$columns) {
		   // Need to find all pivot(s) for the simplex matrix
		   $pivotpoint = NULL;

			// step 1 - See if this is a mixed constraint simplex matrix
           if($hasmixedconstraints)
			{
				$pivotpointList	  = simplexfindpivotpointmixed($sm);
		   		$PivotPointCondition = $pivotpointList[0];
		   		$pivotpoints		 = $pivotpointList[1];
			}
			else
			{
				$pivotpointList = simplexfindpivotpoint($sm);
		   		$PivotPointCondition = $pivotpointList[0];
		   		$pivotpoints	= $pivotpointList[1];
		   }
		 }
		else {
			// from the stack - has not been processed
			// do not find a pivot this round
		 }

		// step 4
		if ($PivotPointCondition == PivotPointNoSolution) {
			//								   parent column
			//								   , Pivot Point Condition
			//								   , pivot
			//								   , all pivot points
			//								   , simplex matrix
			//								   , soluiton
            if(is_null($simplexsets[$rows])) {
                $simplexsets[$rows] = array();
			}
			$simplexsets[$row][$columns] = array($parentcolumn, $PivotPointCondition, NULL, NULL, $sm, $solution);
			$exitwhile = TRUE;
			break;
		}

		// step 5
		// count the number of pivots points
		if(count($pivotpoints) > 1) {
			// add the multiple pivot point matrix to the output
			if(is_null($simplexsets[$rows])) {
			  $simplexsets[$rows] = array();
			}

			// step 7
			//  parent column, pivot, all pivot points, simplex matrix, soluiton
			$simplexsets[$rows++][$columns] = array($parentcolumn, $PivotPointCondition, NULL, $pivotpoints, $sm, $solution);

			// push all extra pivot points to the stack
			$i = 1;
			while ($i <count($pivotpoints)):
			  // might need to keep all possible pivot points on the stack?
			  array_push($simplexstack, array($rows, $parentcolumn, $PivotPointCondition, $pivotpoints[$i], $sm, $solution) );
			  $i++;
			endwhile;

			// override defaults
			$pivotpoint = $pivotpoints[0];
			$pivots = $pivotpoints;
		} elseif(count($pivotpoints) == 1) {
			if($parentcolumn==$columns) {
				$pivotpoint = $pivotpoints[0];
			}
		}

		// step 6
		//  parent column, pivot, all pivot points, simplex matrix, solution
		$rowflag = count($simplexsets);

        if(is_null($simplexsets[$rows])) {
            $simplexsets[$rows] = array();
        }

		$simplexsets[$rows++][$columns] = array($parentcolumn, $PivotPointCondition, $pivotpoint, $pivots, $sm, $solution);

		// step 7
		//if($rowflag<$rows) {
		//	$simplexsets[$rows] = array();
		//}

		// step 8
		$parentcolumn = $columns;

		// step 9
		// pivot if possible
		if (!is_null($pivotpoint)) {
			$sm = simplexpivot($sm,$pivotpoint);
			$solution = simplexreadsolutionarray($sm,$type,$showfractions);
		}

		// step 10
		$popstack = FALSE;

		// step 11
		if ($PivotPointCondition == PivotPointFoundMultipleSolutionList) {
			// compare the new solution found in step 8 above to all previous solutions
			// and see if it is unique
			if(simplexfindsolutioninlist($objectivereached,$solution)==1) {
				// solution is already in the list return the results
				$popstack = true;
			} else {
				$objectivereached[count($objectivereached)] = $solution;
			}
		} elseif ($PivotPointCondition == PivotPointNone) {
			$popstack = true;
		} elseif ($PivotPointCondition == PivotPointFoundList) {
			// is this a objective reached?
			if($solution[(count($solution)-1)]=="Yes") {
				if(simplexfindsolutioninlist($objectivereached,$solution)==1) {
					// solution is already in the list return the results
					// don't save it'
				} else {
					$objectivereached[count($objectivereached)] = $solution;
				}
			}
		}

		if($popstack) {
			// is there any item in the stack?
			if(count($simplexstack) > 0) {
				$columns++;

				// pop the Stack and set the variables
				$stack = array_pop($simplexstack);
				$rows = $stack[0];
				$parentcolumn = $stack[1];
				$PivotPointCondition = $stack[2];
				$pivotpoint = $stack[3];
				$sm= $stack[4];
				$solution = $stack[5];

			} else {
				$exitwhile = TRUE;
			}
		}

	} while (!$exitwhile);

	// make sure there are no missing array elements from the above
	// creation of the jagged array
	if($columns>0){
		for($r = 0,$size = count($simplexsets); $r < $size; $r++){
			for($c = 0; $c < $columns; $c++){
				$temp = $simplexsets[$r];

				if(!isset($temp[$c])) {
					$simplexsets[$r][$c] = NULL; // this avoids Undefined offset errors by assigning each element NULL
				}
			}
		}
	}
	else {
		$r = count($simplexsets)-1;
		while($r>=0){
			if(count($simplexsets[$r])==0) {
				// row is empty
				unset($simplexsets[$r]);
				$r--;
			}
			else {
				break;
			}
		}
	}

	return array($simplexsets, $objectivereached, (microtime(true)-$starttime));
}

// *********************************************************************************************************************************
// *********************************************************************************************************************************
//
// internal utility functions needed for this module
//
// *********************************************************************************************************************************
// *********************************************************************************************************************************
//
// createsimplexelement
// simplextoarray
// simplextodisplay
// verifyconstraints
//
//
//function createsimplexelement($value)
// internal only
// returns an array in the form of (numerator, denominator) that is calculated from $value
// $value can be any valid real number, that will be converted into a fraction (proper or improper).
function createsimplexelement($value) {
	if (is_array($value)) {return $value;}
	if (is_numeric($value) && floor($value)!=$value) {
		$frac = decimaltofraction($value);  // located in /assessment/macros.php
		return fractionparse($frac);
	} else {
		return fractionparse($value);
	}
		// creat an array of (numerator, denominator)
}


//function simplextoarray(sm)
// this function takes the simplex matrix and verifies that each entry is a
// array in the form of (numerator, denominator)
// then returns the verified simplex matrix.
function simplextoarray($sm){

	for($r=0,$sizerow = count($sm);$r<$sizerow;$r++) {
		for($c=0,$sizecol = count($sm[0]);$c<$sizecol;$c++) {
			$sm[$r][$c] = fractionparse($sm[$r][$c]);
		}
	}

	return $sm;
}

// the following are the verify functions to verify user input
// the from is a string of the calling function, then the input to be
// verified, then the program supplied default value
function verifytype($from,$type,$default) {
   if(($type!="max")&&($type!="min"))   {
	echo "In $from - Type of <b>$type</b> is not valid.  Valid values are <b>'min'</b> or <b>'max'</b>.<br/>\r\n";
	return $default;
  }
  else {
  	return $type;
  }
}

function verifymode($from,$mode,$default) {
	if(($mode!=0)&&($mode!=1)&&($mode!=2)) {
	  echo "In $from the supplied mode ($mode) is invalid.  Valid modes are 0,1,2.<br/>\r\n";
	  return $default;
	}
	else {
	  return $mode;
	}
}

function verifyshowobjective($from,$ShowObjective,$default) {
	if(($ShowObjective!=0)&&($ShowObjective!=1)) {
		echo "In $from - the supplied show objective value ($ShowObjective) is invalid.  Valid values are 0 or 1.<br/>\r\n";
		return $default;
	}
	else {
		return $ShowObjective;
	}
}

function verifyshowfraction($from,$showfractions,$default,$override=0) {
	if($override==1) {
		if(($showfractions!=-1)&&($showfractions!=0)&&($showfractions!=1)) {
			echo "In $from - the supplied showfractions value ($showfractions) is invalid.  Valid values are -1, 0 or 1.<br/>\r\n";
			return $default;
		}
		else {
			return $showfractions;
		}
	}
	else {
		if(($showfractions!=0)&&($showfractions!=1)) {
			echo "In $from - the supplied showfractions value ($showfractions) is invalid.  Valid values are 0 or 1.<br/>\r\n";
			return $default;
		}
		else {
			return $showfractions;
		}
	}
}

function verifyASCIIticks($from,$displayASCII,$default) {
	if(($displayASCII!=0)&&($displayASCII!=1)) {
		echo "In $from - the supplied displayASCII value ($displayASCII) is invalid.  Valid values are 0 or 1.<br/>\r\n";
		return $default;
	}
	else {
		if($displayASCII==0) {
		  return "";
		}
		else {
		  return "`";
		}
	}
}

//function verifyconstraints(type, constraints)
// internal only
//
// This function verifies that all of the constraints are vaild inequalities
// type:		max or min
// constraints: the constraints to be verified
function verifyconstraints($type,$constraints) {

  $type = verifytype("verifyconstraints",$type,null);
  if(is_null($type)) return null;

	for ($r=0;$r<count($constraints);$r++)  {
		// make the first part an array if it was given as a list
		if (!is_array($constraints[$r][0])) {
		  $constraints[$r][0]=explode(',',$constraints[$r][0]);
		}

		$constraintscoeff = $constraints[$r][0];

		if($r==0) { $constraintscount = count($constraintscoeff); }
		else
		{
			// compare the number of constraints
			if($constraintscount!=count($constraintscoeff)) {
				echo "The number of constraints for constraint[".($r-1)."] is $constraintscount and constraint[$r] is ".count($constraintscoeff[$r]).".   The number of constraints MUST be equal for all items.  Pad with 0 if variables are missing.<br/>\r\n";
				$constraintscount = max($constraintscount,count($constraintscoeff[$r]));
				return null;  // throw an error
			}
		}
		// updated to verify mixed constrants
		if (($constraints[$r][1]=="<=")||($constraints[$r][1]==">=")) {
			// do nothing - inequality is as expected
		}
		else {
			echo "ERROR constrant inequality is invalid.  Constrant has a '".$constraints[$r][1]."' instead of '<=' or '>='.<br/>\r\n";
			return null;
		}
	}

	return $constraints;
}

//function hasmixedconstraints(constraints)
// internal only
//
// This function returns true if mixed constraints are found
//					   false if all are the same type
function hasmixedconstraints($constraints) {

	for ($r=0;$r<count($constraints);$r++)  {
		// make the first part an array if it was given as a list
		if (!is_array($constraints[$r][0])) {
			$constraints[$r][0]=explode(',',$constraints[$r][0]);
		}

		// if this is the first time through - save the existing inequality
		if($r==0) { $previous = $constraints[$r][1]; }

		if ($constraints[$r][1]!=$previous) {
			return TRUE;
		}
	}

	return FALSE;
}

// simplexfindsolutioninlist(solutionlist,solution)
//
// Trys to find the solution if it is already in the solution list.
//
// INPUTS
// solutionlist: an array of solutions (in the case of multiple solutions).   In the form of
//
//			solutionlist[0] = array(solution values for matrix[0], IsOptimized)
//			solutionlist[1] = array(solution values for matrix[1], IsOptimized)
//			etc.
//			This is returned from simplexsolve
//
// solution = array(solution values for matrix, IsOptimized)
//
// RETURNS:  0 if no match is found, 1 if a match is found
function simplexfindsolutioninlist($solutionlist,$solution) {

	$match = 0;
  	for($r=0,$sizerow = count($solutionlist);$r<$sizerow;$r++) {
  		$match = 1;

	  for($c=0,$sizecol = count($solutionlist[0]);$c<$sizecol;$c++) {
		  // now check to see if this solution matches the student
		  // need to evaluate  $solutionlist[$r][$c] to a decimal
		  if(fractiontodecimal($solutionlist[$r][$c])!=fractiontodecimal($solution[$c])) {
			 $match = 0;  // not a solution
			 break;
		  }
		}
		if($match == 1) break;
	}

	return $match;
}

// simplexhasmixedconstrants($sm)
//
// INPUTS:
// $sm - a simpex matrix
//
// RETURNS - true is it is a mixed contrant matrix
//		   false - otherwise
function simplexhasmixedconstrants($sm){
  $lastrow = count($sm)-1;	// exclude the objective function row
  $lastcol = count($sm[0])-1; // Last column

  // now loop throught the last column and check for negatives
  for($i=0;$i<$lastrow;$i++)
  {
	 if(fractiontodecimal($sm[$i][$lastcol]) < 0 ) {
	   return true;
	 }
  }

  return false;
}


//simplexdisplaytable(simplexmatrix, [simplexmatrixname, displayASCIIticks, linemode, showentriesfractions=1, $pivot = array(-1,-1 ["blue","black"]), $header = array(), $tablestyle = ""])
//
// ***** DEPRECIATED *****
//
// USE simplexdisplaycolortable instead
function simplexdisplaytable() {

	//  arguments list ------------------------------------------------
	//  0 = simplex matrix
	//  1 = simplex matrix name
	//  2 = display ASCII tick marks (yes/no)
	//  3 = mode - no line, aumented, or simplex
	//  4 = show fractions (string,yes/no)
	//  5 = circle pivot point, if supplied
	//  6 = header column names, default is not to show
	//  7 = CSS tablestyle for the table.

	// process arguments ---------------------------------------------
	$args = func_get_args();
	if (count($args)==0) {
		echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
		return "";
	}

	$sm = $args[0];

	$rows = count($sm);
	if($rows==1) {
		// only 1 row
		echo "Error - a simplex matrix must have at least two rows.<br/>\r\n";
		return "";
	}
	$cols = count($sm[0]);

	// simplex matrixname
	if((count($args)>1)&&(!is_null($args[1]))) { $simplexmatrixname = $args[1]; } else { $simplexmatrixname = ""; }

	//displayASCII
	if((count($args)>2)&&(!is_null($args[2]))) {
		$tick = verifyASCIIticks("simplexdisplaytable",$args[2],"`");
	}
	else { $tick = "`"; }

	//mode
	if((count($args)>3)&&(!is_null($args[3]))) {
		$mode = verifymode("simplexdisplaytable",$args[3],2);
	}
	else { $mode=2; }

	//showfractions=1
	if((count($args)>4)&&(!is_null($args[4]))) {
		$showfractions = verifyshowfraction("simplexdisplaytable",$args[4],1,1);
	}
	else { $showfractions=1; }

	$nopad = 'class="nopad"';

	$pivotstylematrix = array();
	for ($rloop=0; $rloop<$rows; $rloop++) {
		$pivotstylematrix[$rloop] = array_fill(0, $cols, "");
	}

	//pivot
	if((count($args)>5)&&(!is_null($args[5]))) {
		if (!is_array($args[5])) { $args[5]=explode(',',$args[5]); }
		$pivots = $args[5];
		for ($r=0; $r<count($pivots); $r++) {
			$currentpoint = $pivots[$r];
			if((count($currentpoint)>0)&&(!is_null($currentpoint[0]))&&($currentpoint[0]>=0)) {
			$prow = $currentpoint[0];
			}
			else {
			$prow = -1;
			}
			if((count($currentpoint)>1)&&(!is_null($currentpoint[1]))&&($currentpoint[1]>=0)) {
			$pcol = $currentpoint[1];
			}
			else {
			$pcol = -1;
			}

			//pivotcolor
			$pivotbordercolor = "blue";
			if((count($currentpoint)>2)&&(!is_null($currentpoint[2]))&&($currentpoint[2]!="")) {
			$pivotbordercolor = $currentpoint[2];
			}

			//pivottextcolor
			$pivottextcolor = "black";
			if((count($currentpoint)>3)&&(!is_null($currentpoint[3]))&&($currentpoint[3]!="")) {
			$pivottextcolor = $currentpoint[3];
			}

			if(($prow >= 0)&&($prow >= 0)) {
			$pivotstylematrix[$prow][$pcol] = "style='border:1px solid $pivotbordercolor;color:$pivottextcolor'";
			}
		}
	}

	//header
	if((count($args)>6)&&(!is_null($args[6]))) {
		$headers = $args[6];
		if (!is_array($headers)) { $headers = explode(',',$headers); }
	}
	else {
		$headers = array();
	}

	//tablestyle
	if((count($args)>7)&&(!is_null($args[7]))) {
		$tablestyle = $args[7];
	}
	else {
		$tablestyle = "";
	}
	// Done processing arguments ---------------------------------------

	$lastrow = $rows-1;
	$lastcol = $cols-1;

	$Tableau = "<table class='paddedtable' style='border:none;border-spacing: 0;border-collapse: collapse;text-align:right;border-spacing: 0px 0px;$tablestyle'>\r\n";
	$Tableau .= "<tbody>\r\n";
	for ($rloop=0; $rloop<$rows; $rloop++) {

		// add simplex line?
		if (($rloop==$lastrow)&&($mode==2)) {
		// add simplex row
		$Tableau .= "<tr class='onepixel'>\r\n";
		$Tableau .= "<td class='matrixleftborder'></td>";
		for ($cloop=0;$cloop<$cols; $cloop++) {
			 $Tableau.= "<td class='matrixtopborder'></td>\r\n";
		}
		$Tableau .= "<td class='matrixtopleftborder'></td><td class='matrixtopborder'></td><td class='matrixrightborder'></td>\r\n</tr>\r\n";
	  }

	  $Tableau .= "<tr>\r\n";
	  if($rloop==0) {

		// matrix name
		if($simplexmatrixname!="") {
		  if(count($headers)>0) { $matricnamerows = $rows+1; } else { $matricnamerows = $rows; }
		  $Tableau.= "<td rowspan='$matricnamerows'> $simplexmatrixname </td>\r\n";
		}

		// column headers
		if(count($headers)>0) {
		  $Tableau.= "<td $nopad>&nbsp;</td>\r\n"; // for the left table border
		  for ($cloop=0;$cloop<$cols; $cloop++) {
			if  ($cloop==$lastcol) {
			  // R1C(Last)
			  if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $nopad>&nbsp;</td>\r\n";} // add augemented column
			}
			if((!is_null($headers[$cloop]))&&($headers[$cloop]!="")) {
			  $Tableau.= "<td>".$tick.$headers[$cloop].$tick."</td>";
			}
			else {
			  $Tableau.= "<td>&nbsp;</td>";
			}
		  }
		  $Tableau.= "<td $nopad>&nbsp;</td></tr>\r\n<tr>\r\n";  // for the right table border
		}
	  }

	  for ($cloop=0;$cloop<$cols; $cloop++) {

		if($showfractions==-1) {
		  $Element = $sm[$rloop][$cloop];					// ignore the denominator and show the string numerator
		} elseif($showfractions==1) {
		  $Element = fractionreduce($sm[$rloop][$cloop]);   // convert to fraction
		}
		else {
		  $Element = fractiontodecimal($sm[$rloop][$cloop]); // convert to decimal
		}

		$TableElement = $tick.$Element.$tick;
		$pivotsyle = $pivotstylematrix[$rloop][$cloop];

		if ($rloop==0) {
			// top row
			if ($cloop==0) {

			  // R1C1
			  $Tableau.= "<td class='matrixtopleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
			}
			elseif  ($cloop==$lastcol) {

			  // R1C(Last)
			 if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td class='matrixleftborder' >&nbsp;</td>\r\n";} // add augemented column
			 $Tableau.= "<td $pivotsyle>$TableElement</td><td class='matrixtoprightborder'>&nbsp;</td>\r\n";
			}
			else {
			  $Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
			}
		}
		elseif ($rloop==$lastrow) {

		  // top row
		  if ($cloop==0) {
			  // R(last)C1
			  $Tableau.= "<td class='matrixbottomleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
		  }
		  elseif  ($cloop==$lastcol) {
			// R(last)C(Last)
			if($mode>1) { $Tableau.= "<td $nopad>&nbsp;</td><td class='matrixleftborder' >&nbsp;</td>\r\n"; }
			$Tableau.= "<td $pivotsyle>$TableElement</td><td class='matrixbottomrightborder'>&nbsp;</td>\r\n";
		  }
		  else {
			$Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
		  }
		}
		else {

		  if ($cloop==0) {
			$Tableau.= "<td class='matrixleftborder'>&nbsp;</td><td $pivotsyle>$TableElement</td>\r\n";
		  }
		  elseif ($cloop==$lastcol) {
			if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td class='matrixleftborder' >&nbsp;</td>\r\n"; }
			$Tableau.= "<td $pivotsyle>$TableElement</td><td class='matrixrightborder'>&nbsp;</td>\r\n";
		  }
		  else {
			$Tableau.= "<td $pivotsyle>$TableElement</td>\r\n";
		  }
		}
	  }
	  $Tableau.= "</tr>\r\n";
	}
	$Tableau.= "</tbody>\r\n";
	$Tableau.= "</table>\r\n";

	return $Tableau;
  }

function simplexnumberofsolutions($solutionlist) {
  $solutioncount = 0;
  for($r=0,$size = count($solutionlist);$r<$size;$r++) {
	  $IsOptimizedcol = count($solutionlist[$r])-1;
	  if($solutionlist[$r][$IsOptimizedcol]=="Yes") {
	  	  $solutioncount++;
	  }
  }

  return $solutioncount;
}

//simplexsolve(simplexmatrix,type)
//
// ***** DEPRECIATED *****
// use simplexsolve2
function simplexsolve($sm,$type,$showfractions=1) {
	$starttime = microtime(true);  //  for function timing

	// process arguments -----------------------------------------------
	$type = verifytype("simplexsolve",$type,"max");
	if(is_null($type)) return null;

	// showfractions
	$showfractions = verifyshowfraction("simplexsolve",$showfractions,1);

	// Done processing arguments ---------------------------------------
	$solutionlist = array();			// solution list of array
	$smlist = array();				  // simplex list of matricies
	$pivotlist = array();			   // pivot point list of points
	$sm = simplextoarray($sm);		  // make sure that all elements are fraction arrays
	$smlist[0] = $sm;				   // save the initial matrix

	$solutionlist[0] = simplexreadsolutionarray($sm,$type,$showfractions);

	// now set up a loop
	$loopcount = 0;
	$loopmax = count($sm)*count($sm[0]);	  // rows * cols
	do {
		// now pivot
		$pivotpointList = simplexfindpivotpoint($sm);
		$PivotPointCondition = $pivotpointList[0];
		$pivotpoints	= $pivotpointList[1];

		// save the pivots
		if(count($pivotpoints) > 0) {
			$pivotlist[count($pivotlist)] = $pivotpoints;
		}

		if (($PivotPointCondition!=PivotPointNoSolution)&&($PivotPointCondition!=PivotPointNone)) {
			$sm = simplexpivot($sm,$pivotpoints[0]);
			$countsmlist = count($smlist);
			$smlist[$countsmlist]  = $sm;
			$solutionlist[count($solutionlist)] = simplexreadsolutionarray($sm,$type,$showfractions);
		}

		$loopcount++;   // add one to the counter
	} while (($loopcount < $loopmax)&&($PivotPointCondition == PivotPointFoundList));

	// save possible multiple solutions
	if($PivotPointCondition == PivotPointFoundMultipleSolutionList) {
		$loopcount = 0;
		$loopmax = count($sm)*count($sm);

		do {
			$pivotpointList = simplexfindpivotpoint($sm);
			$PivotPointCondition = $pivotpointList[0];
			$pivotpoints = $pivotpointList[1];

			if($PivotPointCondition != PivotPointFoundMultipleSolutionList) {
				break;
			}

			$sm = simplexpivot($sm,$pivotpoints[0]);
			$newsolution = simplexreadsolutionarray($sm,$type,$showfractions);

			// use simplexchecksolution here

			for($k=0,$size = count($solutionlist);$k<$size;$k++) {
				// compare all solutions in the list with the new one
				if($newsolution==$solutionlist[$k]) {
					// solution is already in the list return the results
					return array($solutionlist, $smlist, $pivotlist);
				}
			}

			// if you get here another unique solution exists
			// save the results
			$smlist[count($smlist)]  = $sm;
			$solutionlist[count($solutionlist)] = $newsolution;

			// save the pivots
			$pivotlist[count($pivotlist)] = $pivotpoints;

			$loopcount++;   // add one to the counter
		} while ($loopcount < $loopmax);
	}
	//echo (microtime(true)-$starttime); //  for function timing
	return array($solutionlist, $smlist, $pivotlist);
}


// Change Log
// 2019-xx-xx
//
// 2019-10-28 ver 29 - Fixed bug in sinplexsolve2 that added an extra row to some tableaus
//                     patched code in simplexdisplaylatex at 1193 $currentpoint was not an ordered pair - it was a single value.
//
//
// 2018-07-28 Add the ability to suppress the objective column in the following:
//			simplexdisplaylatex,
//			simplexdisplaylatex2,
//			simplexdisplaycolortable,
//			simplexdisplaytable2
//
// 2016-03-30 Found bug in simplexfindpivotpoint when a element in the matrix was zero could be used as a pivot point.
//
// 2016-02-27 standardizing the variable names and created a color display table function
// 2016-02-26 working on standardizing the documentation
// 2016-02-26 - debugging fixed read solution.
// 2016-02-25 - testing simplexsolve2 and debugging.  works for initial mixed max and min
// 2016-02-24 reworked simplex to modify mixed constraints
// 2016-02-22 reworked simplexsolve2 to solve mixed constraints
// 2016-02-11  Working on mixed constraints
// 2016-02-08-14 Completed simplexdisplaylatex2, simplexdisplaytable2, simplexdisplaytable2string, simplexsolve2 working
//			   Updated simplexsolutiontolatex to use fractionparse rather then manually.
//
// 2016-02-07 Starting to design the simplexsolve2 return values
// 2016-02-06 Working on simplexdisplaylatex to get it to correctly circle the pivot point
// 2016-02-04 Used fractiontodecimal() to convert fraction to deciaml for both simplex output and student input for comparision
// 2016-02-03 Fixed bug in simplexchecksolution.  Needed to evaluate the simplex solution if it was a fraction
//			to a decimal to compare it to the student decimal answer.
//
// 2016-02-02 Fixed pivot code to include 0 as a pivot value
// 2015-08-31 Delete extra &lt;br/> in simplexdisplaylatex
// 2015-08-27 Deleted simplexreadsolutionlatex created simplexsolutiontolatex
// 2015-08-20 Updated simplexreadsolutionlatex documentation.
// 2015-08-19 Added debuging info to simplexreadsolutionlatex and simplexreadsolution.  Trying to figure out why they
//			hang when called from WAMAP
//
// 2015-08-19 Created simplexreadsolutionlatex(), fixed bugs in simplexdisplaylatex()
// 2015-04-13 Created simplexdisplaylatex() to output latex commands for a simplex matrix
//
// 2015-04-10 Updated simplexcreateinequalities to accept a blank object variable that will result in an output of
//			just equations for for all inequalities strings.
//
// 2015-04-03 Fixed bug in simplex - an error occurred sometimes when transposing the duality minimization
//			to the maximization problem.
//
// 2015-03-18 Fixed bub in simplexfindpivotpoint - did not look at all non-basic rows for multiple solutions.
//			updated the slove to stop when no more multiple solutions are found.
//
// 2015-03-11 Fixed bug in simplex for the "min" option - was not transposing correctly.
// 2015-03-06 Fixed simplexchecksolution to include type and HasObjective options
// 2015-01-09 Added simplexnumberofsolutions and simplexchecksolution
// 2014-10-22 Fixed: simplexpivot typo ($sma --> $sm)
// 2014-09-18 Added simplexsetentry and correct help file typos.
// 2014-06-06 Updated, sorted, and fixed help file information
// 2014-06-02 Bug fixes and added simplexreadtoanswerarray

?>
