<?php

function isNaN( $var ) {
  // is_numeric catches most things, but not php-generated NAN or INF
  // is_finite catches those
  if (is_array($var)) { // for complex
    foreach ($var as $v) {
        if (!is_numeric($v) || !is_finite($v)) {
            return true;
        }
    }
    return false;
  }
  return (!is_numeric($var) || !is_finite($var));
     //return !preg_match('/^[-]?[0-9]+([\.][0-9]+)?([eE][+\-]?[0-9]+)?$/', $var);
     //possible alternative:
     //return ($var!==$var || $var*2==$var);
}

function ltrimzero($v,$k) {
	return ltrim($v, ' 0');
}

function formathint($eword,$ansformats,$reqdecimals,$calledfrom, $islist=false,$doshort=false) {
	$tip = '';
	$shorttip = '';
	if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
		$listtype = "set";
	} else {
		$listtype = "list";
	}
    if ($calledfrom === 'calccomplexmatrix') {
        if (in_array('allowjcomplex', $ansformats)) {
            $tip .= sprintf(_('Enter %s as a complex number, like 3-4j. '), $eword);
        } else {
            $tip .= sprintf(_('Enter %s as a complex number, like 3-4i. '), $eword);
        }
        $eword = _('each value');
    }
    if (in_array('fraction',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4) or as an integer (like 4 or -2)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of fractions or integers'), $listtype):_('Enter a fraction or integer');
	} else if (in_array('reducedfraction',$ansformats)) {
		if (in_array('fracordec',$ansformats)) {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6), as an integer (like 4 or -2), or as a decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions, integers, or decimals'), $listtype):_('Enter a reduced fraction, integer, or decimal');
		} else {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6) or as an integer (like 4 or -2)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions or integers'), $listtype):_('Enter a reduced fraction or integer');
		}
	} else if (in_array('mixednumber',$ansformats)) {
		if (in_array("allowunreduced",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
		} else {
			$tip .= sprintf(_('Enter %s as a reduced mixed number or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
		}
		$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers or integers'), $listtype):_('Enter a mixed number or integer');
	} else if (in_array('mixednumberorimproper',$ansformats)) {
		if (in_array("allowunreduced",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number, proper or improper fraction, or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or integers'), $listtype):_('Enter a mixed number, proper or improper fraction, or integer');
		} else {
			$tip .= sprintf(_('Enter %s as a reduced mixed number, reduced proper or improper fraction, or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or integers'), $listtype):_('Enter a reduced mixed number, proper or improper fraction, or integer');
		}
	} else if (in_array('fracordec',$ansformats)) {
		if (in_array("allowmixed",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number (like 2 1/2), fraction (like 3/5), an integer (like 4 or -2), or decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or decimals'), $listtype):_('Enter a mixed number, fraction, or decimal');
		} else {
			$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4), an integer (like 4 or -2), or decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of fractions or decimals'), $listtype):_('Enter a fraction or decimal');
		}
	} else if (in_array('decimal',$ansformats)) {
		$tip .= sprintf(_('Enter %s as an integer or decimal value (like 5 or 3.72)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of integer or decimal values'), $listtype):_('Enter an integer or decimal value');
	} else if (in_array('integer',$ansformats)) {
		$tip .= sprintf(_('Enter %s as an integer value (like 5 or -2)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of integer values'), $listtype):_('Enter an integer value');
	} else if (in_array('scinotordec',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a decimal or in scientific notation.  Example: 3*10^2 = 3 &middot; 10<sup>2</sup>'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of numbers using decimals or scientific notation'), $listtype):_('Enter a number using decimals or scientific notation');
	} else if (in_array('scinot',$ansformats)) {
		$tip .= sprintf(_('Enter %s as in scientific notation.  Example: 3*10^2 = 3 &middot; 10<sup>2</sup>'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of numbers using scientific notation'), $listtype):_('Enter a number using scientific notation');
	} else if (in_array('generalcomplex',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a complex number (like 2+3i) or as a calculation (like e^(3i))'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mathematical expressions'), $listtype):_('Enter a mathematical expression');
    } else {
		$tip .= sprintf(_('Enter %s as a number (like 5, -3, 2.2172) or as a calculation (like 5/3, 2^3, 5+4)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mathematical expressions'), $listtype):_('Enter a mathematical expression');
	}
	if ((in_array('fraction',$ansformats) || in_array('reducedfraction',$ansformats)) && !in_array('allowmixed',$ansformats)) {
		$tip .= '<br/>'._('Do not enter mixed numbers');
		$shorttip .= _(' (no mixed numbers)');
	}
	if (!in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats)) {
		if ($calledfrom == 'calcinterval') {
			$tip .= "<br/>" . _('Enter DNE for an empty set. Use oo to enter Infinity.');
		} else if ($calledfrom != 'calcmatrix') {
			$tip .= "<br/>" . _('Enter DNE for Does Not Exist, oo for Infinity');
		}
	} else if ($calledfrom == 'calcinterval') {
		$tip .= "<br/>" . _('Use oo to enter Infinity.');
	}
	if (in_array('nodecimal',$ansformats)) {
		$tip .= "<br/>" . _('Decimal values are not allowed');
	} else if (isset($reqdecimals) && $reqdecimals !== null) {
		if ($reqdecimals == 0) {
			$tip .= "<br/>" . sprintf(_('Enter %s accurate to the nearest integer.'), $eword);
		} else {
			$tip .= "<br/>" . sprintf(_('Enter %s accurate to %d decimal places.'), $eword, $reqdecimals);
		}
	}
	if (in_array('notrig',$ansformats)) {
		$tip .= "<br/>" . _('Trig functions (sin,cos,etc.) are not allowed');
    }
    if (in_array('allowdegrees',$ansformats)) {
		$tip .= "<br/>" . _('Degrees are allowed');
	}
    if ($calledfrom === 'calccomplexmatrix') {
        if (in_array('generalcomplex', $ansformats)) {
            $shorttip = _('Enter a complex expression');
        } else {
            $shorttip = _('Enter a complex number');
        }
    }
	if ($doshort) {
		return array($tip,$shorttip);
	} else {
		return $tip;
	}
}

function getcolormark($c,$wrongformat=false) {
	global $imasroot,$staticroot;

	if (isset($GLOBALS['nocolormark'])) { return '';}

	if ($c=='ansred') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/redx.gif" width="8" height="8" alt="'._('Incorrect').'"/>';
	} else if ($c=='ansgrn') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/gchk.gif" width="10" height="8" alt="'._('Correct').'"/>';
	} else if ($c=='ansorg') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/orgx.gif" width="8" height="8" alt="'._('Correct answer, but wrong format').'"/>';
	} else if ($c=='ansyel') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/ychk.gif" width="10" height="8" alt="'._('Partially correct').'"/>';
	} else {
		return '';
	}
}

function setupnosolninf($qn, $answerbox, $answer, $ansformats, $la, $ansprompt, $colorbox, $format="number") {
	$answerbox = preg_replace('/<label.*?<\/label>/','',$answerbox);  //remove existing ansprompt

	$answerbox = str_replace('<table ','<table style="display:inline-table;vertical-align:middle" ', $answerbox);
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
    $partnum = $qn%1000;
    $out = '';
    $includeinf = in_array('nosolninf',$ansformats);

	if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
		$specsoln = _('One or more solutions: ');
	} else if ($format=='interval') {
		$specsoln = _('Interval notation solution: ');
	} else if ($format=='inequality') {
		$specsoln = _('Inequality notation solution: ');
	} else {
		$specsoln = _('One solution: ');
	}

	if (isset($ansprompt) && $ansprompt != '') {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	$out .= '<div id="qnwrap'.$qn.'" class="'.$colorbox.'" role="group" ';
  if (preg_match('/aria-label=".*?"/', $answerbox, $arialabel)) {
    $answerbox = preg_replace('/aria-label=".*?"/',
      'aria-label="'.Sanitize::encodeStringForDisplay(str_replace('`','',$specsoln)).'"', $answerbox);
    $out .= $arialabel[0];
  }
  $out .= '>';
  
	$out .= '<ul class="likelines">';
	$out .= '<li><input type="radio" id="qs'.$qn.'-s" name="qs'.$qn.'" value="spec" ' .
        (($la!='DNE' && (!$includeinf || $la!='oo'))?'checked':'') . 
        '><label for="qs'.$qn.'-s">'.$specsoln.'</label>';
	if ($la=='DNE' || ($includeinf && $la=='oo')) {
		$laqs = $la;
		$answerbox = str_replace('value="'.$la.'"','value=""', $answerbox);
	} else {
		$laqs = '';
	}

	$out .= str_replace(array($colorbox,getcolormark($colorbox)),'',$answerbox);

	$out .= '<span id="previewloctemp'.$partnum.'"></span>';
	$out .= '</li>';

	$out .= '<li><input type="radio" id="qs'.$qn.'-d" name="qs'.$qn.'" value="DNE" '.($laqs=='DNE'?'checked':'').'><label for="qs'.$qn.'-d">'.$nosoln.'</label></li>';
	if ($includeinf) {
		$out .= '<li><input type="radio" id="qs'.$qn.'-i" name="qs'.$qn.'" value="inf" '.($laqs=='oo'?'checked':'').'><label for="qs'.$qn.'-i">'.$infsoln.'</label></li>';
	}
	$out .= '</ul>';
	//$out .= '<span class="floatright">'.getcolormark($colorbox).'</span>';
	$out .= '</div>';

    $answertype = 0;
	if (preg_match('/^inf/',$answer) || $answer==='oo' || $answer===$infsoln) {
		$answer = $infsoln;
        $answertype = 2;
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer==='DNE' || $answer===$nosoln) {
		$answer = $nosoln;
        $answertype = 1;
	}

	return array($out,$answer,$answertype);
}

function scorenosolninf($qn, $givenans, $answer, $ansprompt, $format="number") {
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
	if (isset($ansprompt)) {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	if (preg_match('/^inf/',$answer) || $answer===$infsoln) {
		$answer = 'oo';
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer===$nosoln) {
		$answer = 'DNE';
	}
	$qs = $_POST["qs$qn"] ?? '';
	if (strtoupper($qs)=='DNE') {
		$givenans = "DNE";
	} else if ($qs=='inf') {
		$givenans = "oo";
	}

	return array($givenans, $answer);
}

function rawscoretocolor($sc,$aw) {
	if ($aw==0) {
		return '';
	} else if ($sc<0) {
		return '';
	} else if ($sc==0) {
		return 'ansred';
	} else if ($sc>.98) {
		return 'ansgrn';
	} else {
		return 'ansyel';
	}
}

function normalizemathunicode($str) {
    $str = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
    $str = str_replace(array('‒','–','—','―','−'),'-',$str);
    $str = str_replace(array('⁄','∕','⁄ ','÷'),'/',$str);
    $str = str_replace(array('（','）','∞','∪','≤','≥','⋅','·'), array('(',')','oo','U','<=','>=','*','*'), $str);
    //these are the slim vector unicodes: u2329 and u232a
    $str = str_replace(array('⟨','⟩'), array('<','>'), $str);
    $str = str_replace(['⁰','¹','²','³','⁴','⁵','⁶','⁷','⁸','⁹'], ['^0','^1','^2','^3','^4','^5','^6','^7','^8','^9'], $str);
    $str = str_replace(array('₀','₁','₂','₃'), array('_0','_1','_2','_3'), $str);
    $str = str_replace(array('√','∛','°'),array('sqrt','root(3)','degree'), $str);
    $greekLetters = array(
        'Α' => 'Alpha',   'α' => 'alpha',
        'Β' => 'Beta',    'β' => 'beta',
        'Γ' => 'Gamma',   'γ' => 'gamma',
        'Δ' => 'Delta',   'δ' => 'delta',
        'Ε' => 'Epsilon', 'ε' => 'epsilon',
        'Ζ' => 'Zeta',    'ζ' => 'zeta',
        'Η' => 'Eta',     'η' => 'eta',
        'Θ' => 'Theta',   'θ' => 'theta',
        'Ι' => 'Iota',    'ι' => 'iota',
        'Κ' => 'Kappa',   'κ' => 'kappa',
        'Λ' => 'Lambda',  'λ' => 'lambda',
        'Μ' => 'Mu',      'μ' => 'mu',
        'Ν' => 'Nu',      'ν' => 'nu',
        'Ξ' => 'Xi',      'ξ' => 'xi',
        'Ο' => 'Omicron', 'ο' => 'omicron',
        'Π' => 'Pi',      'π' => 'pi',
        'Ρ' => 'Rho',     'ρ' => 'rho',
        'Σ' => 'Sigma',   'σ' => 'sigma',
        'Τ' => 'Tau',     'τ' => 'tau',
        'Υ' => 'Upsilon', 'υ' => 'upsilon',
        'Φ' => 'Phi',     'φ' => 'phi',
        'Χ' => 'Chi',     'χ' => 'chi',
        'Ψ' => 'Psi',     'ψ' => 'psi',
        'Ω' => 'Omega',   'ω' => 'omega'
    );
    $str = str_replace(array_keys($greekLetters), array_values($greekLetters), $str);

    $str = preg_replace('/\b(OO|infty)\b/i','oo', $str);
    $str = str_replace('&ZeroWidthSpace;', '', $str);
    if (strtoupper(trim($str))==='DNE') {
        $str = 'DNE';
    }
    // truncate excessively long answer
    if (strlen($str)>8000) {
        $str = substr($str,0,8000);
    }
	return $str;
}

if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep($value) {
		return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
	}
}

// hasarrayval: 0 not array, 1 may be array, 2 must be array
function getOptionVal($options, $key, $multi, $partnum, $hasarrayval=0) {
    if (isset($options[$key])) {
        if ($multi) {
            if ($hasarrayval == 2) { // the normal option value must be an array, so we have to do more logic
                if (is_array($options[$key])) {
                    if (isset($options[$key][$partnum]) && is_array($options[$key][$partnum])) {
                        // we have an array at the part index
                        return $options[$key][$partnum];
                    } else {
                        // no array at part index
                        // check if entries that do exist are arrays
                        if (is_array(current($options[$key]))) {
                            // other entries are array, so this one just isn't defined
                            // do nothing
                        } else {
                            // so array must be intended for all parts
                            return $options[$key];
                        }
                    }
                } // else invalid value - should be array
            } else {
                if (is_array($options[$key])) {
                    if (isset($options[$key][$partnum])) {
                        return $options[$key][$partnum];
                    } 
                } else {
                    return $options[$key];
                }
            }
        } else {
            // single part question.
            if (!is_array($options[$key]) || $hasarrayval > 0) {
                // the normal option value may be an array, or option val is not array
                // just return it
                return $options[$key];
            } 
        }
    }
    // value not found
    if ($key === 'answers') {
        // common mistake to use $answer instead - look for that.
        $altval = getOptionVal($options, 'answer', $multi, $partnum, $hasarrayval);
        return $altval;
    }
    // no value - return empty string
    return '';
}

function rewritePlusMinus($str) {
    return preg_replace('/(.*?)\+\-(.*?)(,|$)/','$1+$2,$1-$2$3',$str);
}

function numfuncPrepShowanswer($string, $variables) {
    $greekletters = array('alpha', 'beta', 'chi', 'delta', 'epsilon', 'gamma', 'varphi', 'phi', 'psi', 'sigma', 'rho', 'theta', 'lambda', 'mu', 'nu', 'omega');

    for ($i = 0; $i < count($variables); $i++) {
        if (strlen($variables[$i]) > 1) {
            $isgreek = false;
            $varlower = strtolower($variables[$i]);
            $isgreek = in_array($varlower, $greekletters);
            
            if (!$isgreek && preg_match('/^(\w+)_(\w+|\(.*?\))$/', $variables[$i], $matches)) {
                $chg = false;
                if (strlen($matches[1]) > 1 && !in_array(strtolower($matches[1]), $greekletters)) {
                    $matches[1] = '"' . $matches[1] . '"';
                    $chg = true;
                }
                if (strlen($matches[2]) > 1 && $matches[2][0] != '(' && !in_array(strtolower($matches[2]), $greekletters)) {
                    $matches[2] = '"' . $matches[2] . '"';
                    $chg = true;
                }
                if ($chg) {
                    $string = str_replace($matches[0], $matches[1] . '_' . $matches[2], $string);
                }
            } else if (!$isgreek && preg_match('/^(hat|bar|vec)\(([^\(]*?)\)$/', $variables[$i], $matches)) {
				$chg = false;
				if (strlen($matches[2]) > 1 && ctype_alnum($matches[2]) && !in_array(strtolower($matches[2]), $greekletters)) {
                    $matches[2] = '"' . $matches[2] . '"';
					$chg = true;
                }
				if ($chg) {
                    $string = str_replace($matches[0], $matches[1] . '(' . $matches[2] . ')', $string);
                }
			} else if (!$isgreek) {
                $string = str_replace($variables[$i], '"' . $variables[$i] . '"', $string);
            }
        }
    }
    return $string;
}

function sizeToCSS($size) {
	if (is_numeric($size)) {
		return (1.2*$size + 1) . 'ch';
	} else {
		return $size;
	}
}
