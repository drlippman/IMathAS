<?php
// Simplex method functions
// Mike Jenck, Originally developed May 16-26, 2014
// licensed under GPL version 2
//
// 2015-01-09 Added simplexnumberofsolutions and simplexchecksolution
// 2014-10-22 Fixed: simplexpivot typo ($sma --> $sm)
// 2014-09-18 Added simplexsetentry and correct help file typos.
// 2014-06-06 Updated, sorted, and fixed help file information
// 2014-06-02 Bug fixes and added simplexreadtoanswerarray


global $allowedmacros;
array_push($allowedmacros, "simplex", "simplexcreateanswerboxentrytable", "simplexcreateinequalities",
"simplexconverttodecimals", "simplexconverttofraction", "simplexdebug", "simplexdefaultheaders", "simplexdisplaytable", "simplexfindpivotpoint", "simplexgetentry", "simplexsetentry", "simplexpivot", "simplexreadtoanswerarray", "simplexreadsolution", "simplexsolve", "simplexnumberofsolutions", "simplexchecksolution" );


include_once("fractions.php");  // fraction routine

/*
// utility functions needed for this module
//
// createsimplexelement
// simplextoarray
// simplextodisplay
// verifyconstraints
//
//
//function createsimplexelement($value)  
// returns an array in the form of (numerator, denominator) that is calculated from $value
// $value can be any valid real number, that will be converted into a fraction (proper or improper).
*/
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

/*
//function simplextoarray(sm)
// this function takes the simplex matrix and verifies that each entry is a
// array in the form of (numerator, denominator)
// then returns the verified simplex matrix.
*/

function simplextoarray($sm){
  
  for($r=0;$r<count($sm);$r++) {
    for($c=0;$c<count($sm[0]);$c++) {
      $sm[$r][$c] = fractionparse($sm[$r][$c]);
    }
  }
  
  return $sm;
}

/*
// the followiung are the verify functions to verify user input
// the from is a string of the calling function, then the input to be 
// verified, then the program supplied default value
*/
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

function verifyshowfraction($from,$showfractions,$default,$override=0) {
	if($override==1) {
		if(($showfractions!=-1)&&($showfractions!=0)&&($showfractions!=1)) { 
			echo "The supplied showfractions value ($showfractions) is invalid.  Valid values are -1, 0 or 1.<br/>\r\n";
			return $default;  
		}
		else { 
			return $showfractions;  
		}		
	}
	else {
		if(($showfractions!=0)&&($showfractions!=1)) { 
			echo "The supplied showfractions value ($showfractions) is invalid.  Valid values are 0 or 1.<br/>\r\n";
			return $default;  
		}
		else { 
			return $showfractions;  
		}
    }
}

function verifyASCIIticks($from,$displayASCII,$default) {
    if(($displayASCII!=0)&&($displayASCII!=1)) { 
        echo "The supplied displayASCII value ($displayASCII) is invalid.  Valid values are 0 or 1.<br/>\r\n";
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

/*
//function verifyconstraints(type, constraints)
//
// This function verifies that all of the constraints are vaild inequalities
// Currently no mixed constraint problems are supported.
// type:        max or min
// constraints: the constraints to be verified
//
*/
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
        
        if($type=="max") {
		    if ($constraints[$r][1]=="<=") {
                // do nothing - inequality is as expected
            }
            else {
                echo "ERROR mixed constraints ( >= ) are not implemented!<br/>\r\n";
                return null;
            }
	    }
	    elseif ($type=="min") {
		    if($constraints[$r][1]==">=") {
                // do nothing - inequality is as expected
            }
            else {
                echo "ERROR mixed constraints ( <= ) are not implemented!<br/>\r\n";
                return null;
            }
	    }
    }
    return $constraints;
}

// function simplex(type, objective, constraints)
// Creates and returns a new simplex matrix.  entries are fractions 
// stored in the form of an array(numerator, denominator).
//
// use simplexdisplaytable() to create a string that can be used for display
//
// type: a string that contains either "max" or "min"
// objective: list or array of the coefficeients 
//
// example: if f = x1+7x2+5x3 then
//     $objective = array(1,7,5)
//           or 
//     $objective = "1,7,5"
//
// constraints: an array that contains the inequality information. Constraints are in the
//              form of:
//                  array(array(3,1,0),"<=",35) 
//                        or 
//                  array("3,1,0","<=",35)
//              constraint first  part is a list or array of the coefficeients in the inequality
//              constraint second part is the inequality '<=' or '>='
//              constraint third  part is the right hand number
//
// example  3x1+x3 <= 35
//       first part: array(3,0,1) or "3,0,1"
//      second part: "<="
//       third part: 35
function simplex($type,$objective,$constraints) {
  
   $error = 0;  // flag for any entry that is not a number
   
   $type = verifytype("simplex",$type,null);
   if(is_null($type)) return null;   
   
   if (!is_array($objective)) { $objective = explode(',',$objective); }
   
   $constraints = verifyconstraints($type,$constraints);
   if(is_null($constraints)) return null;
    
  // test for a minimization
  if($type=="min") {
	    // convert the min objective and constraints to a matrix, then transpose
	    // and then write the transposed back to the objective and constraints
	    
	    // create a temp matrix
	    $temp = array();
	    for($r=0;$r<count($constraints);$r++)
	    {
	      $temp[$r] = $constraints[$r][0];                    // LHS of the inequality - stored as an array
	      $temp[$r][count($temp[$r])] = $constraints[$r][2];  // RHS of the inequality - stored as an number
	    }
	    $temp[count($temp)] =$objective;                      // Last row is the objective function
	
	   // set up the tranpose matrix
	    $temp2  = array();
	    for($c=0;$c<count($temp[0]);$c++) {
		  $temp2[$c] = array();	
		}
		
		// now switch the elements
	    for($r=0;$r<count($temp);$r++) {
	      for($c=0;$c<count($temp[$r]);$c++){
		$temp2[$c][$r] = $temp[$r][$c];
	      }
	    }
	
	    // now write the transpose back to the 
	    // the number of items in  the first row becomes the first column
	    $lastrow = count($temp2)-1;
	    $lastcol = count($temp2[0])-1;
	    $objective = $temp2[$lastrow];
	    
	    for($r=0;$r<$lastrow;$r++) {
		$constraints[$r][2] = $temp2[$r][$lastrow];
		// $constraints[$r][1] is not neede at this time as it contains the inequality symbol
		$constraints[$r][0] = array(); // clear out the existing data            
		for($c=0;$c<$lastcol;$c++) {
		    $constraints[$r][0][$c] = $temp2[$r][$c];
		}
	    }    
  }  
  
  $lastrow = count($constraints);
  $slacks = $lastrow+1;                       // number of slack variables + objective
  $col = count($constraints[0][0])+$slacks+1; // constraint + slack + augmented
  
  $slackstart = count($constraints[0][0]); // starting position of the slacks
  $slackend = $slackstart+$slacks;         // last column - contains the augmented value
  
  $sm = array();    // rows array
  for ($r=0;$r<$lastrow;$r++) {
    // add to the matrix
    $sm[$r] = array();  // columns array
    
    // read each constraint (part [0]) and create a fractional value  
    for($c=0;$c<count($constraints[$r][0]);$c++) {
      
      $value = $constraints[$r][0][$c];
      if(is_numeric($value))
      {
        $sm[$r][$c] = createsimplexelement($value);
      }
      else
      {
        echo "Simplex Error: constraints row = $r col = $c (".$constraints[$r][0][$c].") is not a number.<br/>\r\n";
        $error = 1;
      }    
    }
    
    for($c=$slackstart;$c<$slackend;$c++) {
      if($r==$c-$slackstart) 
      { 
        $sm[$r][$c] = array(1,1); //createsimplexelement(1);
      }
      else
      {
        $sm[$r][$c] = array(0,1); //createsimplexelement(0);
      }
    }
    $value = $constraints[$r][2];
    if(is_numeric($value))
    {
      $sm[$r][$slackend] = createsimplexelement($value);
      
    }
    else
    {
      echo "Simplex Error: constraints row = $r col = $c (".$constraints[$r][0][$c].") is not a number.<br/>\r\n";
      $error = 1;
    }  
  }
  
  $sm[$lastrow] = array();
  // add the objective function
  for($c=0;$c<count($objective);$c++) {
    $value = -$objective[$c];
    if(is_numeric($value)) {
      $sm[$lastrow][$c] = createsimplexelement($value);
    }
    else {
      echo "Simplex Error: objective[$c] (".$objective[$c].") is not a number.<br/>\r\n";
      $error = 1;
    }
  }
  
  for($c=$slackstart;$c<$slackend;$c++) {    
    if($lastrow==$c-$slackstart) { 
      $sm[$lastrow][$c] = array(1,1); //createsimplexelement(1);
    } 
    else {
      $sm[$lastrow][$c] = array(0,1); //createsimplexelement(0);
    }
  }   
  $sm[$lastrow][$slackend] = array(0,1); //createsimplexelement(0);
  
  return $sm;
}

// simplexchecksolution(solutionlist,stuanswer)
//
// solutionlist: an array of solutions (in the case of multiple solutions).   In the form of
//            
//            solutionlist[0] = array(solution values for matrix[0], IsOptimized)
//            solutionlist[1] = array(solution values for matrix[1], IsOptimized)
//            etc.
//            This is returned from simplexsolve
//
//
// stuanswer: the answer the student submitted
//
//
// returns:  0 if no match is found, 1 if a match is found
function simplexchecksolution($solutionlist,$stuanswer) {

  $IsOptimizedcol = count($solutionlist[0])-1; // set Yes/No column index
  $OptimizedValuecol = $IsOptimizedcol -1;     // the Optimized Value (f/g))
  $match = 0;  // set to no match
  
  for($r=0;$r<count($solutionlist);$r++) {
    if($solutionlist[$r][$IsOptimizedcol]=="Yes") {
      $match = 1;  // found a possible solution
      for($c=0;$c<$OptimizedValuecol;$c++) {
        // now check to see if this solution matches the student
        if($solutionlist[$r][$c]!=$stuanswer[$c]) {
           $match = 0;  // not a solution
           break;
        }
      }
      if($match==1) break;
    }
  }
  
  return $match;
}


//function simplexcreateanswerboxentrytable(rows, cols, [startnumber, matrixname, linemode, header, tablestyle]) 
// Create a string that is a valid HTML table syntax for displaying answerboxes.
// rows: number of rows to make
// cols: number of columns to make
// optional
// startnumber: the starting number for the answerbox.  Default is 0 
// matrixname: a string that holds the matrix name, like A or B.  This does not contain 
//             tick marks - if you want them you need to supply them.
//     default empty string
// linemode: Show none, augments, or simplex, value is either 0, 1 or 2
//           0 show no lines
//           1 show aumented line
//   default 2 show simplex  lines
// header: list or array of the variables "x1,x2,x3" that are used for the column titles.
// default none
// tablestyle: for any additional styles for the table that you may want.  like "color:#40B3DF;"
//     default none
function simplexcreateanswerboxentrytable() {
  
  //  arguments lise --------------------------------------------------
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
// Creates and returns an array of string that correspond to each line of the simple inequalities
//
// type:        a string that contains either "max" or "min"
// objectivevariable: the name of the objective function, like f of g.
// objective:   list or array of the coefficeients 
// constraints: an array that contains the inequality information. Constraints are in the
//              form of array(array(3,1,0),"<=",35)
//              constraint first  part is a list or array of the coefficeients in the inequality
//              constraint second part is the inequality *<= or >=)
//              constraint third  part is the number on the other side of the inequality
// optional
// headers:     list or array of the variables 
//      default "x1,x2,x3, ..." for max
//      default "y1,y2,y3, ..." for min
// displayASCIIticks: put tick marks around each element of the table, either 0 or 1.  Use 0
//                    if you are building an answerbox matrix.
//                    0 do not use math ticks
//            default 1        use math ticks
// showfractions: either 0 or 1
//                0 shows decimals
//        default 1 shows fractions
// includeinequalities: either 0 or 1
//                      0 does append the inequality and right hand sinde number ("<=",35)
//              default 1 include the full inequality
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
    echo "Supplied object variable was null which is not valid.  Valid values are any function name.<br/>\r\n";
    return $simplexestring;
  } 
  else { 
    $objectivevariable = $args[1]; 
    
    if($objectivevariable=="") {
      if($type=="max") { $objectivevariable= "f"; } else { $objectivevariable= "g"; }
      echo "Objective function name must not be blank. Using $objectivevariable.<br/>\r\n";
    }
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
    for($j=0;$j<count($objective);$j++)
    {
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
    $isfirst = true;
    if($type=="max") { 
      $simplexestring[0] = "Maximize "; 
    } 
    else { 
      $simplexestring[0] = "Minimize "; 
    }
    $simplexestring[0] .= $tick.$objectivevariable." = ";
  
  
  // objective
  for($j=0;$j<count($objective);$j++) {
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
        else  {
          $simplexestring[0] .= fractiontodecimal($frac);
        }
      }
      $simplexestring[0] .= $headers[$j];
    }
  }
  $simplexestring[0] .= $tick." subject to";
  
  // now create the inequalities from the constraints
  for($r=0;$r<count($constraints);$r++) {
  	// remember row 0 is the Maximize or minimize line
  	$row = $r+1;
    $coeff = $constraints[$r][0];
    $isfirst = true;
    $simplexestring[$row] = $tick;
    for($j=0;$j<count($objective);$j++) {
      
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
          else  {
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
// this function takes the simplex matrix and converts it to fractions for displaying
//
function simplexconverttodecimals($sm){
  
  for($r=0;$r<count($sm);$r++) {
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
// this function takes the simplex matrix and converts it to fractions for displaying
//
function simplexconverttofraction($sm){
  
  for($r=0;$r<count($sm);$r++) {
    for($c=0;$c<count($sm[0]);$c++) {
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
  
  for($r=0;$r<count($sm);$r++){
    for($c=0;$c<count($sm[0]);$c++) {
		if(count($frac)==1) {
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
// simplexmatrix: a valid simplex matrix.
// type: a string that contains either "max" or "min" 
//
// creates the default header (x1,x2, ...,s1,s2,...) for max and
//   (x1,x2, ...,y1,y2,...) for min.
//
// sm:   simplex matrix
// type: max or min
function simplexdefaultheaders($sm, $type){
  
  $headers = array(); 
  
  $type = verifytype("simplexdefaultheaders",$type,null);
  if(is_null($type)) return $headers;  
    
  $slack = count($sm)-1;           // number of slack variables 
  $cols =  count($sm[0]);          // total columns
  $xvariables = $cols - $slack-2;  // number of x variables
  for($c=0;$c<$xvariables;$c++) {
    $count = $c+1;
    $headers[$c] ="x_$count";
  }
  
  $objective = $xvariables+$slack;
  for($c=$xvariables;$c<$objective;$c++) {
    $count = $c-$xvariables+1;
    if($type=="max"){
      $headers[$c] = "s_$count";
    }
    else {
      $headers[$c] = "y_$count";
    }
  }
  
  if($type=="max"){
    $headers[$objective] = "f";
  }
  else {
    $headers[$objective] = "g";
  }
  
  $headers[$objective+1] = "Objective reached";
  
  return $headers;
}

//simplexdisplaytable(simplexmatrix, [simplexmatrixname, displayASCIIticks, linemode, showentriesfractions=1, $pivot = array(-1,-1 ["blue","black"]), $header = array(), $tablestyle = ""]) 
// Create a string that is a valid HTML table syntax for display.
// simplexmatrix: a valid simplex matrix.
//
// optional
// simplexmatrixname: a string that holds the matrix name, like A or B.  You should leave balnk if you
//             are creating a simoplex display
// displayASCII: either 0 or 1
//                0 do not use math ticks
//        default 1        use math ticks
// mode: either 0, 1 or 2
//        0 show no lines
//        1 show aumented line
//        2 show simplex  lines
// showfractions: either -1, 0 or 1
//                -1 show as string 
//                 0 convert simplex element to a decimal
//         default 1 convert simplex element to a fraction
// pivot: list or array that contains the row, column, border color, and text color.  This puts a  
//         border around the cell at (row,col). Both row and column are ZERO based.
//    default point none
//    default border color = blue
//    default text  color  = black
// headers: list or array of the variables "x1,x2,x3" that are used for the column titles.
//   default none
// tablestyle: for any additional styles for the table that you may want.  like "color:#40B3DF;"
//      default none
function simplexdisplaytable() {
    
    //  arguments lise --------------------------------------------------
    //  0 = simplex matrix
    //  1 = simplex matrix name
    //  2 = display ASCII tick marks (yes/no)
    //  3 = mode - no line, aumented, or simplex
    //  4 = show fractions (string,yes/no)
    //  5 = circle pivot point, if supplied
    //  6 = header column names, default is not to show
    //  7 = CSS tablestyle for the table.
    
    // process arguments -----------------------------------------------
    $args = func_get_args();
    if (count($args)==0) {
        echo "Nothing to display - no simplex matrix supplied.<br/>\r\n";
        return "";
    }    
    //   this function CANNOT use     
    // as it will mess up when a string is sent to this procedure and is to be displayed
    $sm = $args[0];
    
    $rows = count($sm);
    if($rows==1)  {
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
    $pivotstylematrix[$rloop] = array_fill (0, $cols, "");
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
  
  //$Tableau = "<table border='0' cellspacing='0' style='text-align:right;border-spacing: 0px 0px;$tablestyle'>\r\n";
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
          $Element = $sm[$rloop][$cloop];                    // ignore the denominator and show the string numerator
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

define("PivotPointNoSolution", -1);
define("PivotPointFoundList", 0);
define("PivotPointNone", 1);
define("PivotPointFoundMultipleSolutionList", 2);

//simplexfindpivotpoint(simplexmatrix)
//
// simplexmatrix: a valid simplex matrix.
//
// returns array(condition, pivotpoints )
// where 
// condition: -1 means No Solution
//             0 found pivot point(s)
//             1 means no pivot points found
//             2 found possible multiple solution pivot point(s)
//
// pivotpoints: an array where the entries are the row, column where the pivot point was
//              found.  both are ZERO based.
//              $pivotpoints[0] = (0,1)
//              $pivotpoints[1] = (1,2)
function simplexfindpivotpoint($sm) {
  
  // variables used for loops and conditions
  $rows = count($sm);
  if($rows==1) {
  	echo "In simplexfindpivotpoint you must supply a simplex matrix with at least two rows.<br/>";
  	return NULL;
  }
  $cols = count($sm[0]);
  
  $lastrow = $rows-1;
  $lastcol = $cols-1;
  
  // variables used for finding the pivot and return vaues
  $pivotpoints = array();                  // list of possible pivot point
  $pivotcondition = PivotPointNoSolution;  // set to no solutions
  //$minfraction = -1;                       // the smallest ratio - set to not found
  //$ColumnMinValue = 1;                     // not found as we need to find negatives
  $minfraction = array(-1,1);              // the smallest ratio - set to not found
  $ColumnMinValue = array(1,1);            // not found as we need to find negatives
  for($c=0;$c<count($sm[$lastrow]);$c++){
    $value = $sm[$lastrow][$c]; //fractiontodecimal($sm[$lastrow][$c]);
    if($value[0]<0) {
      if($value[0]*$ColumnMinValue[1] < $value[1]*$ColumnMinValue[0]) { 
        // Get the smallest value in the objective row
        $ColumnMinValue = $value; 
      }
    }
  }
  
   // create storage for the ratios
   $ratiotest = array();
    
  
  if($ColumnMinValue[0]<0) {
    // Find all columns that are equal to the min value
    $ColumnMinIndexList = array();
    
    for($c=0;$c<$cols;$c++) {
      if($ColumnMinValue==$sm[$lastrow][$c]) {	
        $k = count($ColumnMinIndexList);
        $ColumnMinIndexList[$k] = $c;  // save the colun index that is equal to the minimum column value
      }
    }
    
    for ($m=0;$m<count($ColumnMinIndexList); $m++) {
      $ratiotest[$m] = array();     // create an array of ratios
      $c = $ColumnMinIndexList[$m]; // for column c index m
      for ($r=0;$r<$lastrow; $r++) {
        $valuetop =  $sm[$r][$lastcol];
        $valuebot =  $sm[$r][$c];
        if($valuebot[0]<=0) {
            //$value = -1;
            $value = array(-1,1);
        }
        else {
        	//$value = $valuetop/$valuebot;
            $top = $valuetop[0]*$valuebot[1];
            $bot = $valuetop[1]*$valuebot[0];
            if($bot < 0) {
		$top*=-1;
		$bot*=-1;  // make the bottom positive
	    }
            $gcf = gcd($top,$bot);
            $top /= $gcf;
            $bot /= $gcf;
            $value = array($top,$bot);
        }
        
        if($value[0] > 0) {
          // save the smallest value to find the 
          // negative are NOT allowed
          if($minfraction[0] == -1) {$minfraction = $value; }
          //if($minfraction > $value) {$minfraction = $value; }
          // Test if $value < $minfraction
          //
          // $value[0]   $minfraction[0]
          // --------- < ---------------
          // $value[1]   $minfraction[1]
          //
          // As both denominator are POSITIVE - we need to test for the following:
          //
          // $value[0]*$minfraction[1] < $value[1]*$minfraction[0]
          //
          if($value[0]*$minfraction[1] < $value[1]*$minfraction[0]) { // $value <$minfraction
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
    // look at all the slack variables and see if the objective row is zero
    // and there are nonnegative ratios in the column
    // there are $lastrow number of slack variables
    
    // use lastcol to eliminate the augmented column and the optimization column variable
    //
    // if we have 3 constraints with 2 variables then we will have:
    // 
    // x1,  x2,  s1,  s2,  s3,  f,  obj
    //  0    1    2    3    4   5    6
    // rows = 4 --> slacks = 4 (3 slacks + 1 objectives)
    // lastrow =3
    // cols = 7
    // lastcol = 6
    // startcol = 6-4 = 2  --> lastcol-rows
    // endcol   = 2+3 = 5  --> startcol + lastrow
    $startcol = $lastcol -$rows; // the 1 is for the augmented column
    $endcol = $startcol + $lastrow;
  
    $ColumnMultipleIndexList = array();
    
    for ($c=$startcol;$c<$endcol; $c++) {
        if($sm[$lastrow][$c][0]==0) { // if the objective row of a slck is zero then multiple solutioons MAY exist
            $numberofnonzeroenteries = 0;
            $j = count($ColumnMultipleIndexList);
            $ratiotest[$j] = array();     // create an array of ratios
            // now test to see if this is a valid 
            for ($r=0;$r<$lastrow; $r++){
          $ratiotest[$j][$r] = array();
          $valuetop =  $sm[$r][$lastcol];
          $valuebot =  $sm[$r][$c];
          if($valuebot[0]<=0) {
            $value = array(-1,1);
          }
          else {
            $numberofnonzeroenteries++;  // found a positive possible pivot value
            $top = $valuetop[0]*$valuebot[1];
            $bot = $valuetop[1]*$valuebot[0];
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
  
  // return the ststus and the list of points, if any.
  return array($pivotcondition, $pivotpoints);
}

//simplexgetentry(simplexmatrix,row,col)
//
// simplexmatrix: a valid simplex matrix.
// row: row number (zero based - first row is row 0)
// col: column number (zero based - first row is row 0)
//
// get entry from a simplex matrix at given row and col
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

//simplexsetentry(simplexmatrix,row,col,numerator,denominator)
//
// simplexmatrix: a valid simplex matrix.
// row: row number (zero based - first row is row 0)
// col: column number (zero based - first row is row 0)
//
// set entry for the simplex matrix at the given row and col with the given numerator and denominator.
//
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


// simplexnumberofsolutions(solutionlist)
//
// solutionlist: an array of solutions (in the case of multiple solutions).   In the form of
//            
//            solutionlist[0] = array(solution values for matrix[0], IsOptimized)
//            solutionlist[1] = array(solution values for matrix[1], IsOptimized)
//            etc.
//            This is returned from simplexsolve
//
// returns:  the number of solutions
function simplexnumberofsolutions($solutionlist) {
  $solutioncount = 0;
  for($r=0;$r<count($solutionlist);$r++) {
      $IsOptimizedcol = count($solutionlist[$r])-1;
      if($solutionlist[$r][$IsOptimizedcol]=="Yes") {
      	  $solutioncount++;
      }
  }
  
  return $solutioncount;
}


// simplexpivot(simplexmatrix,pivotpoint)
//
// simplexmatrix: a valid simplex matrix.
// pivotpoint:  list or array that contains the point to be pivoted on. 
//              Both row and column are ZERO based.
//              this ALWAYS picks $pivotpoint[0] as the pivot point
//
// this function pivots the simplex matrix on the given point
//
// returns:  the pivoted simplex matrix
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
// simplexmatrix: a valid simplex matrix.
//
// optional 
// startnumber: starting number of the array.  Default is 0 
// answer: pass $answer if extending an existing $answer array
//
// Create an array of values read by rows for the simplex matrix starting at startnumber
//
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
// simplexmatrix: a valid simplex matrix.
//  type: a string that contains either "max" or "min" 
//  showfractions: either 0 or 1
//                0 shows decimals
//        default 1 shows fractions 
//
// This reads the simplex matrix to find the current solution to the optimization problem.  It returns
// an array that contains the solution.
//
// array(solution values for sm, IsOptimized)
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
function simplexreadsolution($sm,$type,$showfractions=1) {
    // as the end user will be suppling this it will be in fraction form
    // convert to an array()	
    $sma = simplextoarray($sm);
    return simplexreadsolutionarray($sma,$type,$showfractions);
}

function simplexreadsolutionarray($sma,$type,$showfractions=1) {
    
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
    //  0    1    2    3    4   5    6
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
    $objectiveposition = $lastcol-1;
    $dualplusobjective  = count($sma)+1;
    $var  = $lastcol-$rows;  // number of x variables
  
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
            }
            else { $zerorow = $r; }
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
        }
        
        for($c=$var;$c<($dualplusobjective+1);$c++) {
            if($showfractions==1) {
                $solution[$c] = fractionreduce($sma[$lastrow][$c]);
            }
            else {
                $solution[$c] = fractiontodecimal($sma[$lastrow][$c]);
            }
        }
    }
    else {
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
        }
    }
  
  
    if($showfractions==1) {
        $solution[$objectiveposition] = fractionreduce($sma[$lastrow][$lastcol]);
    }
    else {
        $solution[$objectiveposition] = fractiontodecimal($sma[$lastrow][$lastcol]);
    }
    
    $solution[$lastcol] = "Yes";  // objective reached
    for($c=0;$c<count($sma[0]);$c++) {
        if($sma[$lastrow][$c][0] < 0) { 
            $solution[$lastcol] = "No";
            break;
        }
    }
    
    return $solution;
}

//simplexsolve(simplexmatrix,type)
//
// simplexmatrix: a valid simplex matrix.
//  type: a string that contains either "max" or "min" 
//
// this method solves the standard maximization problem which has the following conditions
// 1) The objective function is to be maximized.
// 2) All variables are nonnegative.
// 3) The constraints are of the form: a1x1+ a2x2+ ... + anxn <= b  where b >0
//
// returns array(solutionlist, smlist, pivotlist)
// 
// solutionlist: an array of solutions (in the case of multiple solutions).   In the form of
//            
//            solutionlist[0] = array(solution values for matrix[0], IsOptimized)
//            solutionlist[1] = array(solution values for matrix[1], IsOptimized)
//            etc.
// smlist:    an array of simplex matrix with 
//            sma[0] = initial simplex matrix
//            sma[1] = the result of the first pivot
//            sma[2] = the result of the second pivot
//            sma[3] = the result of the third pivot
//            etc.
//
// pivotlist: an array of pivot points
//            pivotlist[0][0] = the pivot used for the first pivot
//            pivotlist[0][1] = if exists, another possible pivot point for this step
//            pivotlist[1][0] = the pivot used for the second pivot
//            pivotlist[2][0] = the pivot used for the third pivot
//            etc.
function simplexsolve($sm,$type,$showfractions=1) {
    $starttime = microtime(true);  //  for function timing

    // process arguments -----------------------------------------------
    $type = verifytype("simplexsolve",$type,"max");
    if(is_null($type)) return null;   
    
    // showfractions
    $showfractions = verifyshowfraction("simplexsolve",$showfractions,1);    
    
    // Done processing arguments ---------------------------------------
    $solutionlist = array();            // solution list of array
    $smlist = array();                  // simplex list of matricies 
    $pivotlist = array();               // pivot point list of points
    $smlist[0] = $sm;                   // save the initial matrix
  
    $sm = simplextoarray($sm);          // make sure that all elements are fraction arrays
    $solutionlist[0] = simplexreadsolutionarray($sm,$type,$showfractions);
    
    // now set up a loop
    $loopcount = 0;
    $loopmax = count($sm)*count($sm[0]);      // rows * cols
    do {
        // now pivot
        $pivotpointList = simplexfindpivotpoint($sm);
        $PivotPointCondition = $pivotpointList[0];
        $pivotpoints    = $pivotpointList[1];
        
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
            
            $sm = simplexpivot($sm,$pivotpoints[0]);
            $newsolution = simplexreadsolutionarray($sm,$type,$showfractions);

            for($k=0;$k<count($solutionlist);$k++) {
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

?>