<?php
//Matrix functions.  Version 1.5, Dec, 2017
//
//Contributors:  David Lippman, Larry Green

global $allowedmacros;
array_push($allowedmacros,"matrix","matrixformat","matrixformatfrac","matrixsystemdisp","matrixsum",
	"matrixdiff","matrixscalar","matrixprod","matrixaugment","matrixrowscale",
	"matrixrowswap","matrixrowcombine","matrixrowcombine3","matrixidentity",
	"matrixtranspose","matrixrandinvertible","matrixrandunreduce","matrixinverse",
	"matrixinversefrac","matrixsolve","matrixsolvefrac","polyregression","matrixgetentry",
	"matrixRandomSpan","matrixNumberOfRows","matrixNumberOfColumns",
	"matrixgetrow","matrixgetcol","matrixgetsubmatrix","matrixdisplaytable","matrixreduce",
	"matrixnumsolutions","matrixround","matrixCompare",
	"matrixGetRank","arrayIsZeroVector","matrixFormMatrixFromEigValEigVec",
	"matrixIsRowsLinInd","matrixIsColsLinInd","matrixIsEigVec","matrixIsEigVal",
	"matrixGetRowSpace","matrixGetColumnSpace","matrixFromEigenvals","matrixFormatEigenvecs",
	"matrixAxbHasSolution","matrixAspansB","matrixAbasisForB",
	"matrixGetMinor","matrixDet","matrixRandomMatrix","matrixParseStuans");

//matrix(vals,rows,cols)
//Creates a new matrix item.
//Use matrixformat() to create display or $answer from a matrix item
//vals: list or array of numbers to form matrix values: R1C1,R1C2,...,R2C1,R2C2,...
//rows, cols: dimensions of matrix
//To define a matrix using calculations use:
//$m = matrix(array(3/2,2^3,5+1,3),2,2)
function matrix($vals,$rows,$cols) {
	$m = array();
	if (!is_array($vals)) {
		$vals = explode(',',$vals);
	}
	if (count($vals)!=$rows*$cols) {
		echo 'Number of matrix values does not match row/column count';
		return $m;
	}
	for ($i=0;$i<$rows;$i++) {
		$m[$i] = array();
	}
	for ($i=0;$i<count($vals);$i++) {
		$m[floor($i/$cols)][$i%$cols] = $vals[$i];
	}
	return $m;
}

//matrixformat(matrix)
//Formats a matrix item into an ASCIIMath string for display or $answer
function matrixformat($m, $bracket='[', $asfraction=false) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixformat input not a valid matrix'; } return '';}
	if ($bracket == '(') {
		$rb = ')';
	} else if ($bracket == '|') {
		$rb = '|';
	} else {
		$bracket = '[';
		$rb = ']';
	}
	$out = $bracket;
	for ($i=0; $i<count($m); $i++) {
		if ($i!=0) {
			$out .= ',';
		}
		$out .= '(';
		for ($j=0;$j<count($m[0]); $j++) {
			if ($j!=0) {
				$out .= ',';
            }
            if ($asfraction) {
                $out .= decimaltofraction($m[$i][$j]);
            } else {
                $out.= $m[$i][$j];
            }
		}
		$out .= ')';
	}
	$out .= $rb;
	return $out;
}

function matrixformatfrac($m, $bracket='[') {
    return matrixformat($m, $bracket, true);
}

//matrixdisplaytable(matrix, [matrixname, displayASCIIticks, linemode, headernames, tablestyle, rownames, rowheader, caption])
// Create a string that is a valid HTML table syntax for display.
//
// matrix: a matrix to be displayed as an HTML table
//
// matrixname: a string that holds the matrix name, like A or B.
//    This does not contain tick marks - if you want them you
//    need to supply them.
//    default empty string
//
// displayASCIIticks: put tick marks around each element of the
//                    table, either 0 or 1.  Use 0 if you are
//                    building an answerbox matrix.
//
//              def  0 do not use math ticks
//                   1        use math ticks
//
// linemode: Show none, augments, or simplex style
//      def  0 show no lines
//           1 show aumented line
//           2 show simplex  lines
//
// headernames: list or array of the variables "x1,x2,x3" that are
//              used for the column titles.
// default     none
//
// tablestyle: for any additional styles for the table that you
//             may want.  like "color:#40B3DF;"
//     default none
// rownames:  list or array of the variables "x1,x2,x3" that are
//              used for the row titles.
// default     none
//
// rowheader:  string that is entered into the row name column in the header row
// default     none
//
// caption:    string - caption for the table
// default     none
//
function matrixdisplaytable() {

  //  arguments list -------------------------------------
  //  0 = matrix
  //  1 = matrix name
  //  2 = display ASCII tick marks (yes/no)
  //  3 = linemode - no line, aumented, or simplex
  //  4 = header column names, default is not to show
  //  5 = CSS tablestyle for the table.

  // process arguments ------------------------------------
  $args = func_get_args();

  if (count($args)==0) {
    if ($GLOBALS['myrights']>10) { echo "Nothing to display - no matrix supplied.<br/>"; }
    return "";
  }
  $m = $args[0];
	
  if (!isMatrix($m)) {
    if ($GLOBALS['myrights']>10) { echo 'error: matrixdisplaytable input not a valid matrix'; } 
    return '';
  }
	
  // matrixname
  if(isset($args[1])) {
    $matrixname = $args[1];
  } else {
    $matrixname = "";
  }

  //displayASCII
  if(isset($args[2])) {
    if($args[2]==0) { $ticks = ""; } else { $ticks = "`";}
  }
  else { $ticks = ""; }

  //mode
  if(isset($args[3])) {
    $mode = $args[3];
    if(($mode!=0)&&($mode!=1)&&($mode!=2)) {
      echo "The supplied mode ($mode) is invalid.  Valid modes are 0,1,2.<br/>";
      $mode=0;
    };
  } else { $mode=0; }

  //headernames
  if(isset($args[4])) {
    $headers = $args[4];
    if (!is_array($headers)) {
      $headers = explode(',',$headers);
    }
  } else { $headers = null; }

  //tablestyle
  if(isset($args[5])) {
    $tablestyle = $args[5];
  } else {$tablestyle = ""; }

  //rownames
  if(isset($args[6])) {
      $rownames = $args[6];
      if (!is_array($rownames)) {
          $rownames = explode(',',$rownames);
      }
  } else { $rownames = null; }

  // rowheader
  if(isset($args[7])) {
      $rowheader = $args[7];
  }
  else { $rowheader = ""; }

    // caption
  if(isset($args[8])) {
      $caption = $args[8];
  }
  else { $caption = ""; }

  // Done processing arguments ----------------------------
  //
  // style sheets
  $nopad = 'class="nopad"';
  $onerowleftborder  = "style='border-top:1px solid black;border-left:1px solid black;border-bottom:1px solid black;'";
  $topleftborder     = "style='border-top:1px solid black;border-left:1px solid black;'";
  $leftborder        = "style='border-left:1px solid black;'";
  $leftborderSimplex = "style='border-left:1px solid black;border-top:1px solid black;'";
  $bottomleftborder  = "style='border-bottom:1px solid black;border-left:1px solid black;'";

  $Simplex = "style='border-top:1px solid black;'";

  $onerowrightborder = "style='border-top:1px solid black;border-right:1px solid black;border-bottom:1px solid black;'";
  $toprightborder    = "style='border-top:1px solid black;border-right:1px solid black;'";
  $rightborder       = "style='border-right:1px solid black;'";
  $bottomrightborder = "style='border-bottom:1px solid black;border-right:1px solid black;'";

  // counts
  $rows = count($m);
  $cols = count($m[0]);

  if($rowheader!="") {
      // add default blank spaces if the rowheader cell is not blank
      if($rownames == null) {
          $rownames = array();
          for ($rloop=0; $rloop<$rows; $rloop++) {
              $rownames[$rloop] = "";
          }
      }
      if($headers==null){
          $headers = array();
          for ($cloop=0;$cloop<$cols; $cloop++) {
              $headers[$cloop] = "";
          }
      }
  }

  $lastrow = $rows-1;
  $lastcol = $cols-1;

  $Tableau = "<table cellspacing='0' style='border:none;border-spacing: 0;border-collapse: collapse;text-align:right;border-spacing: 0px 0px;$tablestyle'>\r\n";

  if($caption!="") {
      $Tableau .= "<caption>$caption</caption>\r\n";
  }
$Tableau .= "<tbody>\r\n";

  for ($rloop=0; $rloop<$rows; $rloop++) {
    $Tableau .= "<tr>\r\n";
    if($rloop==0) {
        if($matrixname!="") {
            if(!empty($headers)) { $matricnamerows = $rows+1; } else { $matricnamerows = $rows; }
            // Accessible option added
            $Tableau.= "<td rowspan='$matricnamerows'> $matrixname </td>\r\n";
        }

        if(!empty($headers))  {
            if($rowheader!="") {
                // Accessible option added
                $Tableau.= "<th scope=\"col\">$rowheader</th>\r\n";
            } else {
                if($rownames!=null) {
                    $Tableau.= "<td>&nbsp;</td>\r\n";
                }
            }

            $Tableau.= "<td $nopad>&nbsp;</td>\r\n"; // for the left table border
            for ($cloop=0;$cloop<$cols; $cloop++)
            {
                if  ($cloop==$lastcol) { // R1C(Last)
                    if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $nopad>&nbsp;</td>\r\n";} // add augemented column filler
                }
                if(isset($headers[$cloop])&&($headers[$cloop]!=""))
                {
                    // Accessible option added
                    $Tableau.= "<th scope=\"col\">".$headers[$cloop]."</th>\r\n";
                }
                else
                {
                    $Tableau.= "<td>&nbsp;</td>\r\n";
                }
            }
            $Tableau.= "<td>&nbsp;</td>\r\n</tr>\r\n<tr>\r\n";  // for the right table border
        }
    }

    if(!empty($rownames))  {
        // Accessible option added
        if(($rownames[$rloop]!=null)&&($rownames[$rloop]!="")) {
            $Tableau.= "<th scope=\"row\">".$rownames[$rloop]."</th>\r\n";
        } else {
            $Tableau.= "<td>&nbsp;</td>\r\n";
        }
    }

    for ($cloop=0;$cloop<$cols; $cloop++) {
        //$index =$rloop*$ctemp + $cloop;

        //$TableElement = "&nbsp;".$ticks.$m[$rloop][$cloop].$ticks."&nbsp;";
        $TableElement = $ticks.$m[$rloop][$cloop].$ticks;

        if ($rloop==0) {
            if($rows==1)  {
                // only 1 row
                if ($cloop==0) { // R1C1
                    $Tableau.= "<td $onerowleftborder>&nbsp;</td>\r\n<td>$TableElement</td>\r\n";
                    if($cloop==$lastcol) {
                        $Tableau.= "<td $onerowrightborder>&nbsp;</td>\r\n";
                    }
                }
                elseif ($cloop==$lastcol) { // R1C(Last)
                    if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $leftborder >&nbsp;</td>\r\n";} // add augemented column
                    $Tableau.= "<td>$TableElement</td><td $onerowrightborder>&nbsp;</td>\r\n";
                }
                else {
                    $Tableau.= "<td>$TableElement</td>\r\n";
                }
            }
            else {
                // top row
                if ($cloop==0) { // R1C1
                    $Tableau.= "<td $topleftborder>&nbsp;</td>\r\n<td>$TableElement</td>\r\n";
                    if($cloop==$lastcol) {
                        $Tableau.= "<td $toprightborder>&nbsp;</td>\r\n";
                    }
                }
                elseif  ($cloop==$lastcol) { // R1C(Last)
                    if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $leftborder >&nbsp;</td>\r\n";} // add augemented column
                    $Tableau.= "<td>$TableElement</td>\r\n<td $toprightborder>&nbsp;</td>\r\n";
                }
                else {
                    $Tableau.= "<td>$TableElement</td>\r\n";
                }
            }
        }
        elseif ($rloop==$lastrow) { // top row
            if ($cloop==0) {  // R(last)C1
                $Tableau.= "<td $bottomleftborder>&nbsp;</td>\r\n";
                if($mode==2){
                    $Tableau.= "<td $Simplex>$TableElement</td>\r\n";
                }
                else {
                    $Tableau.= "<td>$TableElement</td>\r\n";
                }
                if  ($cloop==$lastcol){  // R(last)C(Last)
                    $Tableau.= "<td $bottomrightborder>&nbsp;</td>\r\n";
                }
            }
            elseif  ($cloop==$lastcol){  // R(last)C(Last)
                if($mode==2) { $Tableau.= "<td $Simplex>&nbsp;</td><td $leftborderSimplex >&nbsp;</td>\r\n"; }
                if($mode==1) { $Tableau.= "<td $nopad>&nbsp;</td><td $leftborder >&nbsp;</td>\r\n"; }
                if($mode==2) {
                    $Tableau.= "<td $Simplex>$TableElement</td>\r\n";
                }
                else {
                    $Tableau.= "<td>$TableElement</td>\r\n";
                }
                $Tableau.= "<td $bottomrightborder>&nbsp;</td>\r\n";
            }
            else {
                if($mode==2){
                    $Tableau.= "<td $Simplex>$TableElement</td>\r\n";
                }
                else {
                    $Tableau.= "<td>$TableElement</td>\r\n";
                }
            }
        }
        else {
            if ($cloop==0) {
                $Tableau.= "<td $leftborder>&nbsp;</td><td>$TableElement</td>\r\n";
                if ($cloop==$lastcol) {
                    $Tableau.= "<td $rightborder>&nbsp;</td>\r\n";
                }
            }
            elseif ($cloop==$lastcol) {
                if($mode>0) { $Tableau.= "<td $nopad>&nbsp;</td><td $leftborder >&nbsp;</td>\r\n"; }
                $Tableau.= "<td>$TableElement</td><td $rightborder>&nbsp;</td>\r\n";
            }
            else {
                $Tableau.= "<td>$TableElement</td>\r\n";
            }
        }
    }
    $Tableau.= "</tr>\r\n";
    }
  $Tableau.= "</tbody>\r\n";
  $Tableau.= "</table>\r\n";

  return $Tableau;
}

//matrixsystemdisp(matrix,[variables,leftbracket])
//Writes out a matrix as an equivalent system of equations
//variables is optional array of variables to use
//leftbracket determines if left bracket is shown (def true)
function matrixsystemdisp($m,$v=null,$leftbracket=true) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixsystemdisp input not a valid matrix'; } return '';}

	if ($leftbracket) {
		$out = '{';
	} else {
		$out = '{:';
	}
	if (!is_array($v)) {
		$v = array('x','y','z','w','v');
	}
	for ($i=0; $i<count($m); $i++) {
		if ($i!=0) {
			$out .= ',';
		}
		$out .= '(';
		$firstout = false;
		for ($j=0; $j<count($m[0]); $j++) {
			if ($j!=0) {
				$out .= ',';
			}
			if ($j==count($m[0])-1) {
				$out .= '=,'.$m[$i][$j];
				break;
			}
			if ($j==0) {
				if ($m[$i][$j]<0) {
					$out .= "-";
				}
			} else {
				if (!is_numeric($m[$i][$j]) && $firstout) {  //something like a variable coefficient
					$out .= '+,';
				} else if ($m[$i][$j]==0) {
					$out .= ",";
				} else if ($m[$i][$j]<0) {
					$out .= "-,";
				} else if ($firstout) {
					$out .= "+,";
				} else {
					$out .= ',';
				}
			}
			if (!is_numeric($m[$i][$j])) {
				$out .= $m[$i][$j];
			} else if ($m[$i][$j]!=0 && abs($m[$i][$j])!=1) {
				$out .= abs($m[$i][$j]);
			}
			if ((!is_numeric($m[$i][$j]) || $m[$i][$j]!=0) && $j<count($m[0])-1) {
				$firstout = true;
				$out .= $v[$j];
			}

		}
		$out .= ')';
	}
	$out .= ':}';
	return $out;
}

//matrixgetentry(matrix,row,col)
//get entry from a matrix at given row and col
//rows and cols are 0 indexed (first row is row 0)
function matrixgetentry($m,$r,$c) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixgetentry input not a valid matrix'; } return '';}
	if ($r<0 || $c<0 || $r>=count($m) || $c>=count($m[0])) {
		echo 'invalid row or column';
		return 0;
	} else {
		return $m[$r][$c];
	}
}

//matrixgetrow(matrix,row,[asArray])
//get row of a matrix as a new 1xm matrix
//  or array if asArray is set to true
//rows and cols are 0 indexed (first row is row 0)
function matrixgetrow($m,$r, $asArray=false) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixgetrow input not a valid matrix'; } return '';}
	if ($r<0 || $r>=count($m)) {
		echo 'invalid row';
	} else {
		if ($asArray) {
			return $m[$r];
		} else {
			return array($m[$r]);
		}
	}
}

//matrixgetcol(matrix,col,[asArray])
//get col of a matrix as a new nx1 matrix
//  or array if asArray is set to true
//rows and cols are 0 indexed (first row is row 0)
function matrixgetcol($m,$c, $asArray=false) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixgetcol input not a valid matrix'; } return '';}
	if ($c<0 || $c>=count($m[0])) {
		echo 'invalid col';
	} else {
		$o = array();
		foreach ($m as $r=>$row) {
			if ($asArray) {
				$o[$r] = $row[$c];
			} else {
				$o[$r] = array($row[$c]);
			}
		}
		return $o;
	}
}

//matrixgetsubmatrix(matrix,rowselector,colselector)
//gets submatrix.  rowselector and colselector are strings
//with format:  "start:end".  ":" to select all
function matrixgetsubmatrix($m,$rs,$cs) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixgetsubmatrix input not a valid matrix'; } return '';}
	$rsp = explode(':',$rs);
	if (count($rsp)<2) {
		$rstart = 0;  $rend = count($m)-1;
	} else {
		if ($rsp[0]!='') {
			$rstart = intval($rsp[0]);
		} else {
			$rstart = 0;
		}
		if ($rsp[1]!='') {
			$rend = intval($rsp[1]);
		} else {
			$rend = count($m)-1;
		}
	}
	$csp = explode(':',$cs);
	if (count($csp)<2) {
		$cstart = 0;  $cend = count($m[0])-1;
	} else {
		if ($csp[0]!='') {
			$cstart = intval($csp[0]);
		} else {
			$cstart = 0;
		}
		if ($csp[1]!='') {
			$cend = intval($csp[1]);
		} else {
			$cend = count($m[0])-1;
		}
	}
	$o = array();
	for ($i=$rstart; $i<=$rend; $i++) {
		$o[$i-$rstart] = array();
		for ($j=$cstart; $j<=$cend; $j++) {
			$o[$i-$rstart][$j-$cstart] = $m[$i][$j];
		}
	}
	return $o;
}

//matrixsum(matrix,matrix)
//Adds two matrices
function matrixsum($m,$n) {
	if (!isMatrix($m) || !isMatrix($n)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixsum input not a valid matrix'; } return '';}
	if (count($m)!=count($n) || count($m[0])!=count($n[0])) {
		echo 'matrix size does not match: cannot add';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] += $n[$i][$j];
		}
	}
	return $m;
}

//matrixdiff(matrix1,matrix2)
//Subtracts matrix1-matrix2
function matrixdiff($m,$n) {
	if (!isMatrix($m) || !isMatrix($n)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixdiff input not a valid matrix'; } return '';}

	if (count($m)!=count($n) || count($m[0])!=count($n[0])) {
		echo 'matrix size does not match: cannot subtract';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] = $m[$i][$j] - $n[$i][$j];
		}
	}
	return $m;
}

//matrixscalar(matrix,n)
//Multiplies the matrix times the number n
function matrixscalar($m,$n) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixscalar input not a valid matrix'; } return '';}
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] *= $n;
		}
	}
	return $m;
}

//matrixprod(matrix1,matrix2)
//Calculates the matrix product matrix1*matrix2
function matrixprod($m,$n) {
	if (!isMatrix($m) || !isMatrix($n)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixprod input not a valid matrix'; } return '';}
	if (count($m[0])!=count($n)) {
		echo 'matrix sizes do not allow product';
		return $m;
	}
	if (count($m)*count($m[0])*count($n[0])>1000) {
		global $myrights;
		if ($myrights>10) {
			echo "matrixprod: You really shouldn't do products of very large matrices. ";
		}
	}
	$o = array();
	$o = array();

	for ($i=0;$i<count($m); $i++) {
		for ($j=0;$j<count($n[0]); $j++) {
			$v = 0;
			for ($k=0; $k<count($m[0]); $k++) {
				$v += $m[$i][$k]*$n[$k][$j];
			}
			$o[$i][$j] = $v;
		}
	}
	return $o;
}

//matrixaugment(matrix1,matrix2)
//Augments matrix2 to the right side of matrix1
function matrixaugment($m,$n) {
	if (!isMatrix($m) || !isMatrix($n)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixaugment input not a valid matrix'; } return '';}
	if (count($m)!=count($n)) {
		echo 'row count does not match: cannot augment';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		$m[$i] = array_merge($m[$i],$n[$i]);
	}
	return $m;
}

//matrixrowscale(matrix,row,n)
//Multiplies row of matrix by n
//matrix rows are 0-indexed; first row is row 0
function matrixrowscale($m,$r,$n) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixrowscale input not a valid matrix'; } return '';}
	for ($j=0; $j<count($m[$r]); $j++) {
		$m[$r][$j] *= $n;
	}
	return $m;
}

//matrixrowswap(matrix,row1,row2)
//swaps rows in matrix
//matrix rows are 0-indexed; first row is row 0
function matrixrowswap($m,$r,$t) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: input matrixrowswap not a matrix'; } return '';}
	$temp = $m[$t];
	$m[$t] = $m[$r];
	$m[$r] = $temp;
	return $m;
}

//matrixrowcombine(matrix,row1,a,row2,b,endrow)
//replaces endrow in matrix with a*row1 + b*row2
//matrix rows are 0-indexed; first row is row 0
function matrixrowcombine($m,$r1,$a,$r2,$b,$s) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixrowcombine input not a valid matrix'; } return '';}
	for ($j=0; $j<count($m[$s]); $j++) {
		$m[$s][$j] = $a*$m[$r1][$j] + $b*$m[$r2][$j];
	}
	return $m;
}

//matrixrowcombine3(matrix,row1,a,row2,b,row3,c,endrow)
//replaces endrow in matrix with a*row1 + b*row2 + c*row3
//matrix rows are 0-indexed; first row is row 0
function matrixrowcombine3($m,$r1,$a,$r2,$b,$r3,$c,$s) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: input matrixrowcombine3 not a matrix'; } return '';}
	for ($j=0; $j<count($m[$s]); $j++) {
		$m[$s][$j] = $a*$m[$r1][$j] + $b*$m[$r2][$j] + $c*$m[$r3][$j];
	}
	return $m;
}

//matrixidentity(n)
//Creates an n x n identity matrix
function matrixidentity($n) {
	$m = array();
	for ($i=0; $i< $n; $i++) {
		$m[$i] = array();
		for ($j=0; $j<$n; $j++) {
			$m[$i][$j] = 0;
		}
		$m[$i][$i] = 1;
	}
	return $m;
}

//matrixtranspose(m)
//Calculates the transpose of the matrix m
function matrixtranspose($m) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixtranspose input not a valid matrix'; } return '';}
	$n = array();
	for ($c=0; $c<count($m[0]); $c++) {
		$n[$c] = array();
		for ($r=0; $r<count($m); $r++) {
			$n[$c][$r] = $m[$r][$c];
		}
	}
	return $n;
}

//randinvertible(n)
//Creates a random n x n invertible matrix by applying random row combinations to an identity matrix
//returns an array of two matrices:  M and M^-1
function matrixrandinvertible($n) {
	$m = matrixidentity($n);
	$mi = matrixidentity($n);
	$ops = array();
	$mult = nonzerodiffrands(-3,3,5);
	for ($i=0; $i<5; $i++) {
		list($sr,$er) = diffrands(0,$n-1,2);
		$ops[$i] = array($sr,$er);
		$m = matrixrowcombine($m,$sr,$mult[$i],$er,1,$er);
	}
	for ($i=4; $i>-1; $i--) {
		$mi = matrixrowcombine($mi,$ops[$i][0],-1*$mult[$i],$ops[$i][1],1,$ops[$i][1]);
	}
	return array($m,$mi);
}

//matrixrandunreduce(m,n)
//Randomizes the matrix m by applying n random row combinations
function matrixrandunreduce($m,$c) {
	$n = count($m);
	for ($i=0;$i<$c; $i++) {
        if ($n == 2) {
            $r = diffrands(0,$n-1,2);
            $t = nonzerodiffrands(-2,2,2);
            $m = matrixrowcombine($m,$r[0],$t[0],$r[1],$t[1],$r[0]);
        } else {
            $r = diffrands(0,$n-1,3);
            $m = matrixrowcombine3($m,$r[0],-1,$r[1],1,$r[2],2,$r[0]);
        }
	}
	for ($i=0; $i<$c; $i++) {
		list($sr,$er) = diffrands(0,$n-1,2);
		$m = matrixrowswap($m,$sr,$er);
	}
	$c = 0;
	while (hasallzerorow($m) && $c<20) {
        if ($n == 2) {
            $r = diffrands(0,$n-1,2);
            $m = matrixrowcombine($m,$r[0],-2,$r[1],1,$r[0]);
        } else {
            $r = diffrands(0,$n-1,3);
            $m = matrixrowcombine3($m,$r[0],-2,$r[1],1,$r[2],3,$r[0]);
        }
        $c++;
	}
	return $m;
}

function hasallzerorow($m) {
	$n = count($m);
	$nc = count($m[0]);
	for ($i=0;$i<$n;$i++) {
		for ($j=0;$j<$nc;$j++) {
			if ($m[$i][$j]!=0) {
				continue 2;
			}
		}
		return true;
	}
	return false;
}
//matrixinverse(m)
//Finds the inverse of nxn matrices.
function matrixinverse($m) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixinverse input not a valid matrix'; } return '';}
	if (count($m[0])!=count($m)) {
		echo 'matrix must be square';
		return $m;
	}
	return matrixsolve($m,matrixidentity(count($m)));
}

//matrixinversefrac(m)
//Finds the inverse of nxn matrices, with the resulting entries as fractions
//the fraction entries are strings, so do NOT try to use the result of this
//for calculations.
function matrixinversefrac($m) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixinversefrac input not a valid matrix'; } return '';}
	if (count($m[0])!=count($m)) {
		echo 'matrix must be square';
		return $m;
	}
	return matrixsolvefrac($m,matrixidentity(count($m)));
}

//matrixsolve(A,b)
//solves the matrix equation Ax = b
//A and b are both matrices
//A is nxn, b is nxm
//returns nxm matrix x so Ax = b
function matrixsolve($A, $b, $silenterror=false) {
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixsolve input not a valid matrix'; } return '';}
	if (count($A) != count($A[0])) {
		echo "can only solve for square matrices A, sorry";  return $b;
	}
	if (count($b)!=count($A)) {
		echo "A and b must have same number of rows"; return $b;
	}
    // number of rows
    $N  = count($b);
    $M = count($b[0]); //number of cols in $b
    
    if ($N>10) {
	global $myrights;
	if ($myrights>10) {
		echo "You really shouldn't use matrixsolve for matrices bigger than 10 rows.";
	}
    }
    // forward elimination
    for ($p=0; $p<$N; $p++) {

      // find pivot row and swap
      $max = $p;
      for ($i = $p+1; $i < $N; $i++)
        if (abs($A[$i][$p]) > abs($A[$max][$p]))
          $max = $i;
      $temp = $A[$p]; $A[$p] = $A[$max]; $A[$max] = $temp;
      $t    = $b[$p]; $b[$p] = $b[$max]; $b[$max] = $t;

      // check if matrix is singular
      if (abs($A[$p][$p]) <= 1e-10) {
      	      if ($silenterror) {
      	      	return false;
      	      } else {
      	      	if ($GLOBALS['myrights']>10) { echo("Solve failed: Matrix is singular or nearly singular"); } return $b;
      	      }
      }

      // pivot within A and b
      for ($i = $p+1; $i < $N; $i++) {
        $alpha = $A[$i][$p] / $A[$p][$p];
	for ($j=0;$j<$M;$j++) {
		$b[$i][$j] -= $alpha * $b[$p][$j];
	}
        for ($j = $p; $j < $N; $j++)
          $A[$i][$j] -= $alpha * $A[$p][$j];
      }
    }

    // zero the solution vector
    $x = array();
    for ($c=0;$c<$M;$c++) {
	    $x[$c] = array_fill(0, $N-1, 0);

	    // back substitution
	    for ($i = $N - 1; $i >= 0; $i--) {
	      $sum = 0.0;
	      for ($j = $i + 1; $j < $N; $j++)
		$sum += $A[$i][$j] * $x[$c][$j];
	      $x[$c][$i] = ($b[$i][$c] - $sum) / $A[$i][$i];
	    }
    }
    return matrixtranspose($x);

}

//matrixsolvefrac(A,b)
//solves the matrix equation Ax = b
//A and b are both matrices
//A is nxn, b is nxm
//returns nxm matrix x so Ax = b
//entries may be fractions (as strings), so don't
//try to use the result in calculations.
function matrixsolvefrac($A, $b, $asString=true) {
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixsolvefrac input not a valid matrix'; } return '';}
	if (count($A) != count($A[0])) {
		echo "can only solve for square matrices A, sorry";  return $b;
	}
	if (count($b)!=count($A)) {
		echo "A and b must have same number of rows";  return $b;
	}
	include_once("fractions.php");
    // number of rows
    $N  = count($b);
    $M = count($b[0]); //number of cols in $b
    if ($N>10) {
	global $myrights;
	if ($myrights>10) {
		echo "You really shouldn't use matrixsolvefrac for matrices bigger than 10 rows."; 
	}
    }
    for ($r=0;$r<$N;$r++) {
    	    for ($c=0;$c<$N;$c++) {
    	    	    $A[$r][$c] = fractionparse($A[$r][$c]);
    	    }
    	    for ($c=0;$c<$M;$c++) {
    	    	    $b[$r][$c] = fractionparse($b[$r][$c]);
    	    }
    }

    // forward elimination
    for ($p=0; $p<$N; $p++) {

      // find pivot row and swap
      $max = $p;
      for ($i = $p+1; $i < $N; $i++)
        if (abs($A[$i][$p][0]/$A[$i][$p][1]) > abs($A[$max][$p][0]/$A[$max][$p][1]))
          $max = $i;

      $temp = $A[$p]; $A[$p] = $A[$max]; $A[$max] = $temp;
      $t    = $b[$p]; $b[$p] = $b[$max]; $b[$max] = $t;

      // check if matrix is singular
      if (abs($A[$p][$p][0]/$A[$p][$p][1]) <= 1e-10) {
        if ($GLOBALS['myrights']>10) { echo("Solve failed: Matrix is singular or nearly singular"); } 
        return $b;
      }

      // pivot within A and b
      for ($i = $p+1; $i < $N; $i++) {
        $alpha = fractiondivide($A[$i][$p], $A[$p][$p]);
	for ($j=0;$j<$M;$j++) {
		//$b[$i][$j] -= $alpha * $b[$p][$j];
		$b[$i][$j] = fractionsubtract($b[$i][$j], fractionmultiply($alpha, $b[$p][$j]));
	}
        for ($j = $p; $j < $N; $j++)
          //$A[$i][$j] -= $alpha * $A[$p][$j];
  	  $A[$i][$j] = fractionsubtract($A[$i][$j], fractionmultiply($alpha, $A[$p][$j]));
      }
    }

    // zero the solution vector
    $x = array();
    for ($c=0;$c<$M;$c++) {
	    $x[$c] = array_fill(0, $N-1, array(0,1));

	    // back substitution
	    for ($i = $N - 1; $i >= 0; $i--) {
	      $sum = array(0,1);
	      for ($j = $i + 1; $j < $N; $j++) {
		//$sum += $A[$i][$j] * $x[$c][$j];
		$sum = fractionadd($sum, fractionmultiply($A[$i][$j], $x[$c][$j]));
	      }
	      //$x[$c][$i] = ($b[$i][$c] - $sum) / $A[$i][$i];
	      $x[$c][$i] = fractiondivide(fractionsubtract($b[$i][$c], $sum), $A[$i][$i]);
	    }
    }
    for ($c=0;$c<$M;$c++) {
    	    for ($r=0;$r<$N;$r++) {
    	    	    $x[$c][$r] = fractionreduce($x[$c][$r]);
    	    }
    }
    return matrixtranspose($x);
}

//matrixreduce(A,[rref,frac])
//reduces the matrix A to echelon or reduced echelon form
//A is a matrix
//rref = true for rref, false for echelon (default)
//frac = true for fraction output, false for decimal output (default)
//  if true, entries may be fractions (as strings), so don't
//  try to use the result in calculations.
//NOTE:  In most cases, using matrixrandunreduce is a better option than using this!
function matrixreduce($A, $rref = false, $frac = false) {
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixreduce input not a valid matrix'; } return '';}
	include_once("fractions.php");
    // number of rows
    $N  = count($A);
    $M = count($A[0]);
    $pivots = array();
    if ($N>10) {
	global $myrights;
	if ($myrights>10) {
		echo "You really shouldn't use matrixreduce for matrices bigger than 10 rows."; 
	}
    }
		$usefraccalc = true;
		for ($r=0;$r<$N;$r++) {
	    for ($c=0;$c<$M;$c++) {
				if (floor($A[$r][$c]) != $A[$r][$c]) {
					$usefraccalc = false;
					break 2;
				}
	    }
    }

		if ($usefraccalc) {
	    for ($r=0;$r<$N;$r++) {
	    	    for ($c=0;$c<$M;$c++) {
	    	    	    $A[$r][$c] = fractionparse($A[$r][$c]);
	    	    }
	    }
		}

    $r = 0;  $c = 0;
    while ($r < $N && $c < $M) {
			if (($usefraccalc && $A[$r][$c][0]==0) || (!$usefraccalc && $A[$r][$c]==0)) { //swap only if there's a 0 entry
		    $max = $r;
		    for ($i = $r+1; $i < $N; $i++) {
			    if ($usefraccalc && abs($A[$i][$c][0]/$A[$i][$c][1]) > abs($A[$max][$c][0]/$A[$max][$c][1])) {
						$max = $i;
			    } else if (!$usefraccalc && abs($A[$i][$c]) > abs($A[$max][$c])) {
						$max = $i;
			    }
		    }
		    if ($max != $r) {
					$temp = $A[$r]; $A[$r] = $A[$max]; $A[$max] = $temp;
		    }
      }

	    if (($usefraccalc && abs($A[$r][$c][0]/$A[$r][$c][1]) <= 1e-10) ||
			 	(!$usefraccalc && abs($A[$r][$c]) <= 1e-10)
			) {
	    	    $c++;
	    	    continue;
	    }

	    //scale pivot row
	    if ($rref) {
		    $divisor = $A[$r][$c];
		    for ($j = $c; $j < $M; $j++) {
					if ($usefraccalc) {
			    	$A[$r][$j] = fractiondivide($A[$r][$j],$divisor);
					} else {
						$A[$r][$j] /= $divisor;
					}
		    }
	    }

	    for ($i = ($rref?0:$r+1); $i < $N; $i++) {
	    	    if ($i==$r) {continue;}
						if ($usefraccalc) {
	    	    	$mult = fractiondivide($A[$i][$c],$A[$r][$c]);
						} else {
							$mult = $A[$i][$c]/$A[$r][$c];
						}
	    	    if (($usefraccalc && $mult[0]==0) || (!$usefraccalc && $mult==0)) {continue;}
	    	    for ($j = $c; $j < $M; $j++) {
	    	    	 //if ($GLOBALS['myrights']>10) { echo "Entry $i,$j:  ".fractionreduce($A[$i][$j]).' - '.fractionreduce( $mult).'*'.fractionreduce($A[$r][$j]).'<br/>'; }
							if ($usefraccalc) {
							 	$A[$i][$j] = fractionsubtract($A[$i][$j], fractionmultiply($mult,$A[$r][$j]));
							} else {
								$A[$i][$j] = $A[$i][$j] - $mult * $A[$r][$j];
								if (abs($A[$i][$j]) < 1e-10) {
									$A[$i][$j] = 0; //treat values close to 0 as 0
								}
							}
	    	    }
	    }

	    $r++; $c++;
    }

		if ($usefraccalc) {
	    for ($r=0;$r<$N;$r++) {
	    	    for ($c=0;$c<$M;$c++) {
	    	    	    if ($frac) {
	    	    	    	    $A[$r][$c] = fractionreduce($A[$r][$c]);
	    	    	    } else {
	    	    	    	    $A[$r][$c] = $A[$r][$c][0]/$A[$r][$c][1];
	    	    	    }
	    	    }
	    }
		}

    return $A;
}

//matrixnumsolutions(A,n)
//A is an arbitrary coefficient matrix augmented with n columns, after
// being row reduced to reduced echelon form (see matrixreduce)
//Returns the number of Ax=b equations that have at least one solution
function matrixnumsolutions($A,$n=0) {
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixnumsolutions input not a valid matrix'; } return '';}
	$c = count($A[0]);
	$Ac = $c - $n;
	$r = count($A);
	$nosolution = array();
	for ($i=0; $i<$r; $i++) {
		for ($j=0; $j<$Ac; $j++) {
			if (abs($A[$i][$j])>1e-10) {
				continue 2;
			}
		}
		//is all zeros on left
		for ($j=$Ac;$j<$c;$j++) {
			if (abs($A[$i][$j])>1e-10) {
				$nosolution[$j] = 1;
			}
		}
	}
	return ($n - count($nosolution));
}

//matrixround(matrix, decimal places)
//rounds each entry of the matrix the specified decimal places
function matrixround($m,$d) {
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixround input not a valid matrix'; } return '';}
	$c = count($m[0]);
	$r = count($m);
	for ($i=0; $i<$r; $i++) {
		for ($j=0; $j<$c; $j++) {
			$m[$i][$j] = round($m[$i][$j], $d);
		}
	}
	return $m;
}

//polyregression(x,y,n)
//find a nth degree polynomial that best fits the data
//x,y arrays of data
//returns an array (intercept, linear coeff, quad coeff, ...)
function polyregression($x,$y,$n) {
	$m = array();
	for ($i=0;$i<count($x);$i++) {
		$m[$i][0] = 1;
		for ($j=1;$j<=$n;$j++) {
			$m[$i][$j] = $m[$i][$j-1]*$x[$i];
		}
	}
	$m = matrixsolve(matrixprod(matrixtranspose($m),$m),matrixprod(matrixtranspose($m),matrix($y,count($y),1)));
	$m = matrixtranspose($m);
	return $m[0];
}


//The following functions are added in order to evaluate questions that ask for the rank,
//null space, column space and other matrix qualities that are used in linear algebra.

//arrayIsZeroVector(vector) vector is an array, mot a matrix
//determines if a vector is the 0 vector
function arrayIsZeroVector($v){
	for($i=0;$i<count($v);$i++){
		if($v[$i]!=0){
			return(false);
		}
	}
	return(true);
}



//matrixGetRank(matrix)
//returns the rank of a matrix
//column rank = row rank (https://www.maa.org/sites/default/files/3004418139737.pdf.bannered.pdf)
function matrixGetRank($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixGetRank input not a valid matrix'; } return '';}
	$rowRank = 0;

	$refM = matrixreduce($m,false,false);

	for($i=0;$i<count($refM);$i++){
		if(arrayIsZeroVector($refM[$i])==true){
			return($rowRank);
		}
		else{
			$rowRank++;
		}
	}
	return($rowRank);
}

//matrixFormMatrixFromEigValEigVec(eigenvalues,matrix of eigenvectors)
//eigenvalues:  The eigenvalues of the matrix include multiple times if multiplicity > 0
//matrix of eigenvectors:  imput a matrix that consists of the eigenvectors of the original matrix
//returns the matrix PAP^-1
function matrixFormMatrixFromEigValEigVec($eigVal,$eigVec){
	if(count($eigVec)!=count($eigVec[0])){
		echo("The matrix of eigenvectors must be a square matrix");
        return '';
	}
	$A = array();
	$n = count($eigVec);
	for($i=0;$i<$n;$i++){
		$A[$i] = array_fill(0,$n,0);
		$A[$i][$i] = $eigVal[$i];
	}
	return(matrixprod($eigVec,matrixprod($A, matrixinverse($eigVec))));
}
//matrixIsRowsLinInd(matrix)
//matrix: returns true if the rows of the matrix are linearly independent
function matrixIsRowsLinInd($m){
	if(matrixGetRank($m) == count($m)){
		return (true);
	}
	else{
		return(false);
	}
}

//matrixIsColsLinInd(matrix)
//matrix: returns true if the columns of the matrix are linearly independent
function matrixIsColsLinInd($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixIsColsLinInd input not a valid matrix'; } return '';}
	if(matrixGetRank($m) == count($m[0])){
		return (true);
	}
	else{
		return(false);
	}
}

//matrixIsEigVec(matrix,eigenvector)
//matrix:  the matrix that we are testing
//eigenvector:  the possible eigenvector that we are checking.  It is an array, not a matrix.
// returns true is eigenvector is an eigenvector of matrix.  Otherwise it returns false
function matrixIsEigVec($m,$v){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixIsEigVec input not a valid matrix'; } return '';}
	if(count($m)!=count($m[0])){
		echo("The matrix must be a square matrix");
        return false;
	}

	$product = matrixprod($m,matrix($v,count($v),1));
	$mv = array($v); //make $v the first row of $mv
	$mv[1] = array();
	for ($i=0;$i<count($v);$i++) {
		$mv[1][$i] = $product[$i][0]; //put product as second row
	}
	if(matrixGetRank($mv) == 1){
		return(true);
	}
	else{
		return(false);
	}
}
//matrixIsEigVal(matrix,eigenvalue)
//matrix:  the matrix that we are testing
//eigenvalue a real number that we are testing to see if it is an eigenvalue of matrix.
function matrixIsEigVal($m,$L){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixIsEigVal input not a valid matrix'; } return '';}
	if(count($m)!=count($m[0])){
		echo("The matrix must be a square matrix");
        return false;
	}

	//this gives A - LI
	$AMinusLI = matrixdiff($m,matrixscalar(matrixidentity(count($m)),$L));
	if(matrixGetRank($AMinusLI) == count($m)){
		return(false);
	}
	else{
		return(true);
	}
	//return($AMinusLI);
}

//matrixGetRowSpace(matrix)
//matrix:  the matrix that we are finding the row space
//returns a matrix whose rows are a basis of the row space of matrix
function matrixGetRowSpace($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixGetRowSpace input not a valid matrix'; } return '';}
	$m = matrixreduce($m,true,false);

	$retMatrix = array();
	for ($i=0;$i<count($m);$i++) {
		if(!arrayIsZeroVector($m[$i])){
			$retMatrix[] = $m[$i];
		} else {
			break;
		}
	}
	return $retMatrix;

}
//matrixGetColumnSpace(matrix)
//matrix:  the matrix that we are finding the column space
//returns a matrix whose columns are a basis of the column space of matrix
function matrixGetColumnSpace($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixGetColumnSpace input not a valid matrix'; } return '';}
	return(matrixtranspose(matrixGetRowSpace(matrixtranspose($m))));
}

//matrixAxbHasSolution(matrix A,matrix b)
//A is a marix and b is a mx1 matrix.
//returns true if there is a solution and false if there isn't one
function matrixAxbHasSolution($A,$b){
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixAxbHasSolution input not a valid matrix'; } return '';}
	if(count($A)!=count($b)){
		echo("The number of entries of b must equal the number of rows of A.  A not b:  ".count($A). " not ".count($b));
	}
	$AB = matrixaugment($A,$b);
	$testMatrix = matrixreduce($AB,false,false);
	$lastCol = count($testMatrix[0])-1;
	for ($r=0;$r<count($testMatrix);$r++) {  //for each row
		if ($testMatrix[$r][$lastCol] != 0) { //if right hand side is non-zero
			$hasnonzero = false;
			for ($c=0;$c<$lastCol;$c++) { //for each column other than last
				if ($testMatrix[$r][$c] != 0) {
					$hasnonzero = true;  //found one
					break; //don't need to keep looking
				}
			}
			if (!$hasnonzero) { //no non-zero on left, right was non-zero
				return false;
			}
		}
	}
	return true;
}

//matrixAspansB(matrix A,matrix B)
//A is the possible spanning set
//This tests if the rows of A span the row space of B
function matrixAspansB($A,$B){
	if (!isMatrix($A) || !isMatrix($B)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixAspansB input not a valid matrix'; } return '';}
	$C = matrixaugment(matrixtranspose($A),matrixtranspose($B));

	if(matrixGetRank($A) != matrixGetRank($B) || matrixGetRank($A) != matrixGetRank($C)){
		return false;
	}
	return true;
}
//matrixAbasisForB(matrix A, matrix B)
//tests if the rows of A are a basis for the row space of B
function matrixAbasisForB($A,$B){
	if (!isMatrix($A) || !isMatrix($B)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixAbasisForB input not a valid matrix'; } return '';}
	if(count($A[0])!=count($B[0])){
		echo("The number of columns of A must equal to the number of columns of B");
	}
	$retVal = true;
	if(matrixAspansB($A,$B)==false){
		$retVal = false;
	}
	if(matrixIsRowsLinInd($A)==false){
		$retVal = false;
	}
	return($retVal);
}
//matrixGetMinor(matrix,rowNo,colNo)
//returns the n-1 by n-1 matrix minor obtained by removing the rowNo row and colNo column.  Only works for a square matrix.
function matrixGetMinor($A,$rowNo,$colNo){
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixGetMinor input not a valid matrix'; } return '';}
	if(count($A[0])<$colNo){
		echo("The number of columns of A must at least as large as the column selected"); 
	}
	if(count($A)<$rowNo){
		echo("The number of rows of A must at least as large as the row selected"); 
	}

	$retVal = array();
	$m = 0;
	$n = 0;
	for($i=0;$i<count($A);$i++){
		$n = 0;
		if($i!=$rowNo){
			$retVal[$m] = array();
		}
		for($j=0;$j<count($A);$j++){
			if($i!=$rowNo&&$j!=$colNo){
				$retVal[$m][$n] = $A[$i][$j];
				$n++;
			}
		}
		if($i!=$rowNo){
			$m++;
		}
	}
	return($retVal);
}
//det(matrix)
//returns the determinant of a matrix
function matrixDet($A){
	if (!isMatrix($A)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixDet input not a valid matrix'; } return '';}
	if(count($A)!=count($A[0])){
		echo("A must be a square matrix");
	}
	//return(matrixDetMinor($A[0][0]));
	if(count($A)==1){
		return($A[0][0]);
	} else if (count($A)==2) {
		return ($A[0][0]*$A[1][1] - $A[0][1]*$A[1][0]);
	}
	else{
        $retVal = 0;
		for($i=0;$i<count($A);$i++){
			if ($A[0][$i]!=0) {
				$retVal += pow(-1,$i)*$A[0][$i]*matrixDet(matrixGetMinor($A,0,$i));
			}
		}
		return($retVal);
	}
}
//matrixRandomMatrix(max values,minimum,number of rows,number of columns)
//returns matrix with random integer entries where the integers are between max and min values.
//For cases where you'll want to solve Ax=b, use matrixrandinvertible instead
function matrixRandomMatrix($min,$max,$rows,$cols){
	$ranList = rands($min,$max,$rows*$cols);
	return(matrix($ranList,$rows,$cols));
}
//matrixRandomSpan(matrix)
//returns a matrix of rows that span the row space of matrix
//the number of rows of the spanning matrix will either be the same or one larger than the original matrix's number of rows.
function matrixRandomSpan($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixRandomSpan input not a valid matrix'; } return '';}
	$ranCols = $GLOBALS['RND']->rand(count($m),count($m)+1);
	if($ranCols == count($m)){
		return matrixrandunreduce($m,5);
	}
	else{
		//add a new row copied from a random row, then unreduce
		$m[] = $m[$GLOBALS['RND']->rand(0,count($m)-1)];
		return matrixrandunreduce($m,5);
	}

}

//matrixNumberOfRows(matrix)
// returns the number of rows of a matrix
function matrixNumberOfRows($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixNumberOfRows input not a valid matrix'; } return '';}
	return(count($m));
}
//matrixNumberOfColumns(matrix)
// returns the number of columns of a matrix
function matrixNumberOfColumns($m){
	if (!isMatrix($m)) { if ($GLOBALS['myrights']>10) { echo 'error: matrixNumberOfColumns input not a valid matrix'; } return '';}
	return(count($m[0]));
}

function matrixParseStuans($stu) {
	if ($stu === null) {
		return array(); 
	} else {
        $start = substr($stu,0,2);
        if ($start=='[(' || $start=='((' || $start=='|(') {
            $ansr = substr($stu,2,-2);
            $ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
            return explode(',',$ansr);
        } else {
            return explode('|', $stu);
        }
    }
}

function isMatrix($m) {
	if (isset($m) && is_array($m) && isset($m[0]) && is_array($m[0])) {
        $rcnt = count($m);
        $ccnt = count($m[0]);
        if ($rcnt == 0 || $ccnt == 0) { return false; }
        for ($r=0;$r<$rcnt;$r++) {
            if (!isset($m[$r]) || !is_array($m[$r]) || count($m[$r]) != $ccnt) { return false;}
            for ($c=0;$c<$ccnt;$c++) {
                if (!isset($m[$r][$c]) || $m[$r][$c] === '' || $m[$r][$c] === null) { return false;}
            }
        }
		return true;
	} else {
		return false;
	}
}

function matrixFromEigenvals($values) {
    $size = 0;
    foreach ($values as $v) {
        $size += is_array($v) ? 2 : 1;
    }
    $m = matrixidentity($size);
    $mi = matrixidentity($size);
    $d = [];
    $ops = array();
    if ($size > 2) {
        $ord = diffrands(0,$size-2,$size-1);
    } else {
        $ord = [0];
    }
    $ord[] = $size-1;
    $mults = nonzerodiffrands(-3,3,$size*2,'def',true);
	for ($i=1; $i<$size; $i++) {
        $er = $ord[$i];
        $sr = $ord[$i-1];
        $ops[] = array($sr, $er, $mults[$i-1]);
		$m = matrixrowcombine($m,$sr,$mults[$i-1],$er,1,$er);
    }
    
    $n = 0;
    // real evals will get one row as evec
    // complex evals will get one row for imag, and next for real part
    $realrows = [];
    foreach ($values as $i=>$v) {
        $d[$n] = array_fill(0,$size,0);
        if (is_array($v)) {
            // add multiple of last row to real rows
            if ($n+1 != $size-1) {
                $mult = abs($mults[$size+$i]);
                $ops[] = array($size-1, $n+1, $mult);
                $m = matrixrowcombine($m,$size-1,$mult,$n+1,1,$n+1);
                $realrows[] = $n+1;
            }
            $d[$n+1] = array_fill(0,$size,0);
            $d[$n][$n] = $values[$i][0];
            $d[$n][$n+1] = -$values[$i][1];
            $d[$n+1][$n] = $values[$i][1];
            $d[$n+1][$n+1] = $values[$i][0];
            $n += 2;
        } else {
            // add multiple of last row to real rows
            if ($n != $size-1) {
                $mult = $mults[$size+$i];
                $ops[] = array($size-1, $n, $mult);
                $m = matrixrowcombine($m,$size-1,$mult,$n,1,$n);
                $realrows[] = $n;
            }
            $d[$n][$n] = $values[$i];
            $n++;
        }
    }

    if (count($realrows)>0) {
        $sr = randfrom($realrows);
        $mult = $GLOBALS['RND']->rand(-1,1);
        if (is_array($values[count($values)-1]) && $m[$sr][$size-1]*$mult < 0) {
            $mult *= -1;
        }
        $ops[] = array($sr,$size-1, $mult);
        $m = matrixrowcombine($m,$sr,$mult,$size-1,1,$size-1);
    }
    for ($i=count($ops)-1; $i>-1; $i--) {
        $mi = matrixrowcombine($mi,$ops[$i][0],-1*$ops[$i][2],$ops[$i][1],1,$ops[$i][1]);
    }
    
    $evecs = [];
    $r = 0;
    foreach ($values as $v) {
        if (!is_array($v)) { // real: use row as e-vec
            $evecs[] = $m[$r];
            $r++;
        } else { // complex: use two rows as real and complex
            $vec = [];
            for ($i=0; $i<$size; $i++) {
                $vec[] = [$m[$r+1][$i], $m[$r][$i]];
            }
            $evecs[] = $vec;
            $r += 2;
        }
    }

    $p = matrixtranspose($m);
    $pinv = matrixtranspose($mi);
    $A = matrixprod(matrixprod($p, $d), $pinv);
    return [$A,$evecs];
}

function matrixFormatEigenvecs($evecs) {
    $out = [];
    foreach ($evecs as $evec) {
        $elpts = []; $elpts2 = [];
        $iscomplex = false;
        foreach ($evec as $v) {
            if (is_array($v)) {
                $elpts[] = formatcomplex($v[0], $v[1]);
                $elpts2[] = formatcomplex($v[0], -1*$v[1]);
                $iscomplex = true;
            } else {
                $elpts[] = $v;
                $elpts2[] = $v;
            }
        }
        $out[] = '[('.implode('),(', $elpts).')]';
        if ($iscomplex) {
            $out[] = '[('.implode('),(', $elpts2).')]';
        }
    }
    return $out;
}

function matrixCompare($m,$n,$tol='.001') {
	// $m stu, $n correct
	if (!is_array($m) || !is_array($n) || !is_array($m[0]) || !is_array($n[0])) {
		return false;
	}
	if (count($m) != count($n) || count($m[0]) != count($n[0])) {
		return false;
	}
	$toltype = 'rel';
	if ($tol[0]=='|') {
		$toltype = 'abs';
		$tol = substr($tol,1);
	}
	$isequiv = true;
	for ($r = 0 ; $r < count($m); $r++) {
		for ($c = 0; $c < count($m[0]); $c++) {
			if ($toltype == 'rel') {
				if (abs($n[$r][$c]) < 1E-12) {
					if (abs($m[$r][$c] - $n[$r][$c]) > $tol/1000 + 1E-12) {
						$isequiv = false;
						break 2;
					}
				} else {
					if (abs($m[$r][$c] - $n[$r][$c])/(abs($n[$r][$c])+1E-12) > $tol + 1E-12) {
						$isequiv = false;
						break 2;
					}
				}
			} else {
				if (abs($m[$r][$c] - $n[$r][$c]) > $tol + 1E-12) {
					$isequiv = false;
					break 2;
				}
			}
		}
	}
	return $isequiv;
}

?>
