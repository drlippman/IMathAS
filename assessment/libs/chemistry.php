<?php
//A collection of chemistry routines
//
//Version 0.31 May 7, 2022

global $allowedmacros;
array_push($allowedmacros,"chem_disp","chem_mathdisp","chem_isotopedisp",
"chem_getsymbol","chem_getnumber","chem_getname","chem_getweight",
"chem_getmeltingpoint","chem_getboilingpoint","chem_getfamily",
"chem_randelementbyfamily","chem_diffrandelementsbyfamily", 
"chem_getrandcompound", "chem_getdiffrandcompounds","chem_decomposecompound",
"chem_getcompoundmolmass","chem_randanion","chem_randcation",
"chem_makeioniccompound","chem_getsolubility","chem_balancereaction", "chem_eqndisp");

//chem_disp(compound)
//formats a compound for display in as HTML
function chem_disp($c) {
	$c = preg_replace('/_(\d+)/','<sub>$1</sub>',$c);
	$c = preg_replace('/\^(\d+\+|\d+\-|\+|\-)/','<sup>$1</sup>',$c);
	return str_replace(' ','',$c);
}

//chem_mathdisp(compound)
//formats a compound for better display in math mode
function chem_mathdisp($c) {
	return preg_replace('/([a-zA-Z]+)/','"$1"',$c);
}

//chem_isotopedisp(element,super,sub,[noitalic])
//formats a math display string for an isotope with the given element,
// superscript, and subscript. Set noitalic to true to un-italic element.
function chem_isotopedisp($el,$sup,$sub,$noitalic=false) {
	$nspacenum = $nspaceden = 0;
	$slt = strlen($sup);
	$slb = strlen($sub);
	if ($slt>$slb) {
		$nspaceden = 2*($slt-$slb);
	} else if ($slt<$slb) {
		$nspacenum = 2*($slb-$slt);
	}
	$out = '{::}_{';
	for ($i=0;$i<$nspaceden;$i++) {
		$out .= '\\ ';
	}
	$out .= $sub .'}^{';
	for ($i=0;$i<$nspacenum;$i++) {
		$out .= '\\ ';
	}
	$out .= $sup .'}';
	if ($noitalic) {
		$out .= '"'.$el.'"';
	} else {
		$out .= $el;
	}
	return $out;
}

//chem_getsymbol(atomic number)
//returns the chemical symbol given the atomic number
function chem_getsymbol($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getsymbol: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][0];
}

//chem_getnumber(symbol)
//returns the atomic number given the chemical symbol
function chem_getnumber($s) {
	global $chem_numberbyatom;
    if (!isset($chem_numberbyatom[$s])) {
        echo "chem_getnumber: unknown symbol $s";
    }
	return ($chem_numberbyatom[$s] ?? 0);
}

//chem_getname(atomic number)
//returns the chemical name given the atomic number
function chem_getname($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getname: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][1];
}

//chem_getweight(atomic number)
//returns the chemical standard atomic weight given the atomic number
function chem_getweight($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getweight: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][3];
}

//chem_getmeltingpoint(atomic number)
//returns the melting point given the atomic number
//beware: some elements return weird non-numeric values
function chem_getmeltingpoint($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getmeltingpoint: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][4];
}

//chem_getboilingpoint(atomic number)
//returns the boiling point given the atomic number
//beware: some elements return weird non-numeric values
function chem_getboilingpoint($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getboilingpoint: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][5];
}

//chem_getfamily(atomic number)
//returns the family given the atomic number
//Values may be:  "", "Noble gas", "Alkaline", "Alkaline Earth", "Halogen",
//    "Transition Metal", "Lanthanide",  or "Actinide"
function chem_getfamily($n) {
	global $chem_periodic_table;
    if (!isset($chem_periodic_table[$n])) {
        echo "chem_getfamily: unknown symbol $n";
        return '';
    }
	return $chem_periodic_table[$n][6];
}

//chem_randelementbyfamily(family)
//returns the atomic number of a random element from a family
//Valid families are: "Other", "Noble gas", "Alkaline", "Alkaline Earth",
//    "Halogen", "Transition Metal", "Lanthanide", or "Actinide"
function chem_randelementbyfamily($f) {
	global $chem_families, $RND;
	$f = strtolower($f);
	if (!isset($chem_families[$f])) {
		echo 'Family not valid';
		return 0;
	}
	$c = count($chem_families[$f]);
	return $chem_families[$f][$RND->rand(0,$c-1)];
}

//chem_diffrandelementsbyfamily(family, n)
//returns an array of n atomic numbers for random elements from a family
//Valid families are: "Other", "Noble gas", "Alkaline", "Alkaline Earth",
//    "Halogen", "Transition Metal", "Lanthanide", or "Actinide"
function chem_diffrandelementsbyfamily($f, $n) {
	global $chem_families;
	$f = strtolower($f);
	if (!isset($chem_families[$f])) {
		echo 'Family not valid';
		return 0;
	}
	$c = count($chem_families[$f]);
	$i = diffrands(0,$c-1,$n);
	$out = array();
	for ($j=0;$j<$n;$j++) {
		$out[] = $chem_families[$f][$i[$j]];
	}
	return $out;
}

//chem_getrandcompound(type)
//returns an array of (compound name, compound formula)
//valid types are: twobasic, twosub, threeplus, parens
//if type is not supplied, compound is chosen randomly from all
function chem_getrandcompound($type="twobasic,twosub,threeplus,parens") {
	global $chem_compounds, $RND;
	$types = explode(',',$type);
	$n = array();
	foreach ($types as $v) {
		$n[] = count($chem_compounds[$v]);
	}
	$r = $RND->rand(0,array_sum($n)-1);
	foreach ($types as $i=>$v) {
		if ($r<$n[$i]) {
			return $chem_compounds[$v][$r];
		} else {
			$r -= $n[$i];
		}
	}
}

//chem_getdiffrandcompounds(n,type)
//returns an array n arrays of (compound name, compound formula)
//valid types are: twobasic, twosub, threeplus, parens
//if type is not supplied, compound is chosen randomly from all
function chem_getdiffrandcompounds($c, $type="twobasic,twosub,threeplus,parens") {
	global $chem_compounds;
	$types = explode(',',$type);
	$n = array();
	foreach ($types as $v) {
		$n[] = count($chem_compounds[$v]);
	}
	$r = diffrands(0, array_sum($n)-1, $c);
	$out = array();
	foreach ($types as $i=>$v) {
		foreach ($r as $k=>$rv) {
			if ($rv<$n[$i]) {
				$out[] = $chem_compounds[$v][$rv];
				unset($r[$k]);
			} else {
				$r[$k] -= $n[$i];
			}
		}
	}
	return $out;
}

//chem_decomposecompound(compound)
//breaks a compound into an array of elements and an array of atom counts
function chem_decomposecompound($c, $assoc = false) {
	$cout = array();
	if (preg_match_all('/\(([^\)]*)\)_(\d+)/',$c,$matcharr, PREG_SET_ORDER)) {
        foreach ($matcharr as $matches) {
            $p = explode(' ',$matches[1]);
            foreach ($p as $cb) {
                $cbp = explode('_',$cb);
                if (!isset($cout[$cbp[0]])) { $cout[$cbp[0]] = 0;}
                if (count($cbp)==1) {
                    $cout[$cbp[0]] += $matches[2];
                } else {
                    $cout[$cbp[0]] += $matches[2]*$cbp[1];
                }
            }
            $c = str_replace($matches[0],'',$c);
        }
	}
	$p = explode(' ',trim($c));
	foreach ($p as $cb) {
		if ($cb=='') {continue;}
		$cbp = explode('_',$cb);
		if (!isset($cout[$cbp[0]])) { $cout[$cbp[0]] = 0;}
		if (count($cbp)==1) {
			$cout[$cbp[0]] += 1;
		} else {
			$cout[$cbp[0]] += $cbp[1];
		}
	}
	if ($assoc) { 
		return $cout;
	} else {
		return array(array_keys($cout),array_values($cout));
	}
}

//chem_balanceraction($reactants,$products)
function chem_balancereaction($reactants,$products) {
	$allcompounds = [];
	foreach ($reactants as $i=>$v) {
		$reactants[$i] = chem_decomposecompound($v, true);
		array_push($allcompounds, ...array_keys($reactants[$i]));
	}
	foreach ($products as $i=>$v) {
		$products[$i] = chem_decomposecompound($v, true);
		array_push($allcompounds, ...array_keys($products[$i]));
	}
	$allcompounds = array_unique($allcompounds);
	$compoundrow = array_flip($allcompounds);
	require_once("matrix.php");
	$countreact = count($reactants);
	$countprods = count($products);
	$colcnt = $countreact + $countprods;
	$compoundcnt = count($allcompounds);
	$m = matrix(
		array_fill(0, $compoundcnt*$colcnt, 0),
		$compoundcnt,
		$colcnt
	);
	// in m, row is compound, column is element in equation
	foreach ($allcompounds as $i=>$c) {
		$r = $compoundrow[$c];
		foreach ($reactants as $i=>$v) {
			if (isset($v[$c])) {
				$m[$r][$i] = $v[$c];
			}
		}
		foreach ($products as $i=>$v) {
			if (isset($v[$c])) {
				$m[$r][$countreact + $i] = -$v[$c];
			}
		}
	}
	// now, reduce matrix
	$m = matrixreduce($m, true, true);
	/*  each row corresponds to an atom, each column to a compound
		determine the number of free variable columns
		    if many more compounds than atoms, might have multiple free vars
			if unbalancable, might have zero free variables (only solution all zeros)
			start at last row, ignore any all-zero rows.  
			in first non-zero row, find pivot and count remaining cols to find free vars
			assumption: free variables in these equations will always be in end columns
		parse each value to fraction
		find LCM of denominators in each column; set free variable to that
		calculate values of basic variables
	*/
	$freevars = 0;
	for ($r=$compoundcnt-1;$r>=0;$r--) {
		if (!arrayIsZeroVector($m[$r])) {
			for ($c=0;$c<$colcnt;$c++) {
				if ($m[$r][$c] != 0) {
					$freevars = $colcnt - $c - 1;
					break 2;
				}
			}
		}
	}
	if ($freevars == 0) { // un-balanceable; return array of zeros
		return array_fill(0, $colcnt, 0);
	}
	$coeffs = [];
	$freevarvals = [];
	for ($c = 0; $c < $freevars; $c++) {
		$coeffs[$c] = [];
		$d = 1;
		for ($r = 0; $r < $compoundcnt; $r++) {
			$coeffs[$c][$r] = fractionparse($m[$r][$colcnt - $freevars + $c]);
			// find lcm of denominators - that'll be our free variable value
			$d = lcm($d, $coeffs[$c][$r][1]);
		}
		$freevarvals[$c] = $d;
	}
	$out = [];
	for ($r=0; $r < $colcnt - $freevars; $r++) {
		$out[$r] = 0;
		for ($c = 0; $c < $freevars; $c++) {
			$out[$r] -= $coeffs[$c][$r][0]*$freevarvals[$c]/$coeffs[$c][$r][1];
		}
	}
	foreach ($freevarvals as $v) {
		$out[] = $v;
	}
	if ($freevars > 1) {
		$g = gcd(array_filter($out));
		if ($g > 1) {
			for ($i=0; $i<count($out); $i++) {
				$out[$i] /= $g;
			}
		}
	}
	return $out;
}

function chem_eqndisp($reactants, $products, $coefficients, $arrow = "->", $phases = null) {
	$out = '';
	$n = -1;
	foreach ($reactants as $i=>$r) {
		$n++;
		if ($coefficients[$n] == 0) {
			continue;
		} 
		if ($i>0) {
			$out .= ' + ';
		}
		if ($coefficients[$n] > 1) { 
			$out .= $coefficients[$n] . ' ';
		}
		$out .= $r;
		if (is_array($phases) && !empty($phases[$n])) {
			$out .= ' ('.$phases[$n].')';
		}
	}
	$out .= " $arrow ";
	foreach ($products as $i=>$r) {
		$n++;
		if ($coefficients[$n] == 0) {
			continue;
		} 
		if ($i>0) {
			$out .= ' + ';
		}
		if ($coefficients[$n] > 1) { 
			$out .= $coefficients[$n] . ' ';
		}
		$out .= $r;
		if (is_array($phases) && !empty($phases[$n])) {
			$out .= ' ('.$phases[$n].')';
		}
	}
	return $out;
}

//chem_getcompoundmolmass(compound, [round])
//gets the molecular mass of the given compound
// round: decimals to round the individual atoms' molecular mass to during calculuations
//        default: no additional rounding (4 decimal place accuracy)
//        special value: .5.  Rounds all values to whole numbers, except Cl and Cu to nearest .5
function chem_getcompoundmolmass($c,$round=4) {
	global $chem_periodic_table, $chem_numberbyatom;
	list($els,$cnt) = chem_decomposecompound($c);
	$molmass = 0;
	foreach ($els as $k=>$el) {
		if ($round==.5) {
			if (abs(round(2*$chem_periodic_table[$chem_numberbyatom[$el]][3])/2 - $chem_periodic_table[$chem_numberbyatom[$el]][3]) < .05) {
				$molmass += round(2*$chem_periodic_table[$chem_numberbyatom[$el]][3])/2*$cnt[$k];
			} else {
				$molmass += round($chem_periodic_table[$chem_numberbyatom[$el]][3])*$cnt[$k];
			}
		} else if ($round<4) {
			$molmass += round($chem_periodic_table[$chem_numberbyatom[$el]][3],$round)*$cnt[$k];
		} else {
			$molmass += $chem_periodic_table[$chem_numberbyatom[$el]][3]*$cnt[$k];
		}
	}
	return $molmass;
}

//chem_randcation([group,name type,include uncommon])
//group name:  simple, basic (simple + NH_4), polyvalent, or all. Default = all
//name type: "common" (iron (II)) or "alternate" (ferrous)  default = common
//include uncommon: set true to include francium, radium, cadmium, etc.
//returns array(symbol, charge, name)
function chem_randcation($group="all", $type="common", $uncommon = false) {
	global $chem_cations;
	if ($group=="polyvalent") {
		$pickfrom = range(17,34);
	} else {
		$pickfrom = range(0,12);
	}
	if ($group=="basic" || $group=="all") {
		$pickfrom[] = 13;
	}
	if ($uncommon && $group != 'polyvalent') {
		$pickfrom = array_merge($pickfrom, range(14,16));
	}
	if ($group=="all") {
		$pickfrom = array_merge($pickfrom, range(17,34));
	}
	if ($uncommon && ($group=="all" || $group=="polyvalent")) {
		$pickfrom = array_merge($pickfrom, range(35,36));
	}
	$r = randfrom($pickfrom);
	$name = ($type=="alternate" && $chem_cations[$r][3]!='')?$chem_cations[$r][3]:$chem_cations[$r][2];
	return array($chem_cations[$r][0],$chem_cations[$r][1],$name);
}

//chem_randanion([group,name type,include uncommon])
//group name:  simple, polyatomic, or all. Default = all
//name type: "common" (bicarbonate) or "alternate" (hydrogen carbonate)  default = common
//include uncommon: set true to include selenide, peroxide
//returns array(symbol, charge, name)
function chem_randanion($group="all", $type="common", $uncommon = false) {
	global $chem_anions;
	if ($group=="polyatomic") {
		$pickfrom = range(10,36);
	} else {
		$pickfrom = range(0,8);
	}
	if ($uncommon && $group != 'polyatomic') {
		$pickfrom[] = 9;
	}
	if ($group=="all") {
		$pickfrom = array_merge($pickfrom, range(10,36));
	}
	if ($uncommon && ($group=="all" || $group=="polyatomic")) {
		$pickfrom[] = 35;
	}
	$r = randfrom($pickfrom);
	$name = ($type=="alternate" && $chem_anions[$r][3]!='')?$chem_anions[$r][3]:$chem_anions[$r][2];
	return array($chem_anions[$r][0],$chem_anions[$r][1],$name);
}

//chem_makeioniccompound(cation, anion)
//takes a cation and anion
//  these are the array returned by chem_randcation and chem_randanion
//returns array(formula, name)
function chem_makeioniccompound($cation,$anion) {
	if (!is_array($cation)) {
		global $chem_cations;
		$found = false;
		foreach($chem_cations as $c) {
			if ($c[0]==$cation) {
				$cation = $c;
				$found = true;
				break;
			}
		}
		if (!$found) {
			echo "Cation not found.";
		}
	}
	if (!is_array($anion)) {
		global $chem_anions;
		$found = false;
		foreach($chem_anions as $c) {
			if ($c[0]==$anion) {
				$anion = $c;
				$found = true;
				break;
			}
		}
		if (!$found) {
			echo "Anion not found.";
		}
	}
    if (!isset($cation) || !isset($anion)) {
        return false;
    }
	$lcm = lcm($cation[1],$anion[1]);
	$catsub = $lcm/$cation[1];
	if ($catsub==1) {
		$formula = $cation[0];
	} else {
		if (strpos($cation[0],' ')!==false) {
			$formula = '('.$cation[0].')';
			$formula .= '_'.$catsub;
		} else if (strpos($cation[0],'_')!==false) {
			//this is Hg_2 only
			$pts = explode('_',$cation[0]);
			$formula = $pts[0].'_'.($pts[1]*$catsub);
		} else {
			$formula = $cation[0];
			$formula .= '_'.$catsub;
		}

	}
	$ansub = $lcm/$anion[1];
	if ($ansub==1) {
		$formula .= ' '.$anion[0];
	} else {
		if (strpos($anion[0],' ')!==false) {
			$formula .= ' ('.$anion[0].')';
			$formula .= '_'.$ansub;
		} else if (strpos($anion[0],'_')!==false) {
			$pts = explode('_',$anion[0]);
			$formula .= ' '.$pts[0].'_'.($ansub*$pts[1]);
		} else {
			$formula .= ' '.$anion[0];
			$formula .= '_'.$ansub;
		}

	}
	$name = $cation[2].' '.$anion[2];
	return array($formula,$name);
}

//chem_getsolubility(cation, anion)
//	takes a cation array and anion array
//	returns the solubility state using Wikipedia's Solubility Chart https://en.wikipedia.org/wiki/Solubility_chart
//		ex. array('soluble', '(aq)') 
//returns array(stateName, abbreviation)
function chem_getsolubility($receive_cation, $receive_anion){
	[$cation, $cat_charge] = $receive_cation;
	[$anion, $an_charge] = $receive_anion;
	// possible state arrays
	$state_aq = array('soluble', '(aq)');
	$state_i = array('insoluble', '(s)');
	$state_sS = array('slightly soluble', '(aq)');
	$state_r = array('reaction', '(l)');
	$state_unknown = array('unavailable', '(?)');
	$state_missing = array('This info is missing from MOM', '(TODO)');

	// Rule 0: Unknown States
	// Currently Missing Cations: Cd, Fr, Ra, Cr 2+, Mn 3+, Co 3+, Ni 3+, Cu 1+, Au 1+, Sn 4+, Pb 4+, Hg_2
	$unknown_cations = array('Cd', 'Fr', 'Ra', 'Hg_2');
	if (in_array($cation, $unknown_cations)){
		return $state_unknown;
	}
	if ($cat_charge > 3) {
		return $state_unknown;
	}
	if ($cat_charge == 3){
		if ($cation == 'Mn' or $cation == 'Co' or $cation == 'Ni'){
			return $state_unknown;
		}
	}
	if ($cat_charge == 2 and $cation == 'Cr'){
		return $state_unknown;
	}
	if ($cat_charge == 1 and $cation == 'Au'){
		return $state_unknown;
	}
	// Currently Missing Anions: N, P, C, Se, ClO, ClO_2, ClO_3, HCO_3, H_2PO_4, HSO_3, HSO_4, IO_3, MnO_4, NO_2, CrO_4, Cr_2O_7, HPO_4, SO_3, SiO_3, AsO_4, PO_3, O_2
	$unknown_anions = array('N', 'P', 'C', 'Se', 'Cl O', 'Cl O_2', 'Cl O_3', 'H C O_3', 'H_2 P O_4', 'H S O_3', 'H S O_4', 'I O_3', 'Mn O_4', 'N O_2', 'Cr O_4', 'Cr_2 O_7', 'H P O_4', 'S O_3', 'Si O_3', 'As O_4', 'P O_4', 'O_2');

	// Rule 1: Nitrate is always soluble
	if ($anion == 'N O_3'){
		return $state_aq;
	}
	// Rule 1.1: Ammonium and Hydrogen are mostly soluble (Hopefully speed up computing)
	if ($cation == 'N H_4' or $cation == 'H'){
		if ($anion == 'S'){
			if ($cation == 'N H_4'){
				return $state_r;
			} else {
				return $state_sS;
			}
		}
		else {
			return $state_aq;
		}
	}

	// Rule 2: Bromide and Chloride are mostly soluble
	if ($anion == 'Cl' or $anion == 'Br'){
		if ($cation == 'Pb') {
			return $state_sS;
		}
		if ($cation == 'Cr' and $anion == 'Br') {
			return $state_sS;
		}
		if ($cation == 'Au' and $anion == 'Br') {
			return $state_sS;
		}
		if ($cation == 'Ag') {
			return $state_i;
		}
		return $state_aq;
	}
	// Rule 3: Iodide is mostly soluble
	if ($anion == 'I'){
		if ($cation == 'Be' or $cation == 'Ga'){
			return $state_r;
		}
		elseif($cation == 'Fe' and $cat_charge == 3){
			return $state_r;
		}
		elseif ($cation == 'Cu'){
			return $state_unknown;
		}
		elseif ($cation == 'Ag' or $cation == 'Hg' or $cation == 'Au'){
			return $state_i;
		}
		else{return $state_aq;}
	}
	// Rule 4: Fluoride is mostly soluble
	if ($anion == 'F'){
		$f_slightlySoluble = array('Li', 'Mg', 'Sr', 'Ba', 'Al', 'Mn', 'Co', 'Cu', 'Zn', 'Pb', 'Cr');
		$f_insoluble = array('Ca', 'Ga', 'V', 'Au');
		if (in_array($cation, $f_slightlySoluble)){
			return $state_sS;
		}
		elseif (in_array($cation, $f_insoluble)){
			return $state_i;
		}
		elseif ($cation == 'Hg'){
			return $state_r;
		}
		elseif($cation == 'Fe' and $cat_charge == 2){
			return $state_sS;
		}
		return $state_aq;
	}
	// Rule 5: Sulfide and Oxide mostly insoluble
	if ($anion == 'O' or $anion == 'S'){
		$s_and_o_reactive = array('Li', 'Na', 'K', 'Rb', 'Cs', 'Ca', 'Sr', 'Ba');
		if (in_array($cation, $s_and_o_reactive)){
			return $state_r;
		}
		elseif ($cation == 'N H_4'){
			if ($anion == 'O'){
				return $state_aq;
			} else {
				return $state_r;
			}
		}
		elseif ($cation == 'H'){
			if ($anion == 'O'){
				return $state_aq;
			} else {
				return $state_sS;
			}
		}
		elseif ($cation == 'Be' or $cation == 'Mg' or $cation == 'Al' or $cation == 'Ga'){
			if ($anion == 'O'){
				return $state_i;
			} else {
				return $state_r;
			}
		}
		return $state_i;
	}
	// Rule 6: Hydroxide is mostly insoluble
	if ($anion == 'O H'){
		$hydroxide_solubility = array('N H_4', 'H', 'Li', 'Na', 'K', 'Rb', 'Cs', 'Ba');
		if (in_array($cation, $hydroxide_solubility)){
			return $state_aq;
		}
		elseif ($cation == 'Ca' or $cation == 'Sr' or $cation == 'Pb'){
			return $state_sS;
		}
		elseif ($cation == 'V'){
			return $state_unknown;
		}
		return $state_i;
	}
	// Rule 7: Cyanide is mostly soluble
	if ($anion == 'C N'){
		// check reactive
		if ($cation == 'Be' or $cation == 'Mg' or $cation == 'Ca' or $cation == 'Al'){
			return $state_r;
		} elseif ($cation == 'Ga' or $cation == 'Sn' or $cation == 'V'){
			return $state_unknown;
		} elseif ($cation == 'Pb'){
			return $state_sS;
		} elseif ($cation == 'Co' or $cation == 'Ni' or $cation == 'Cu' or $cation == 'Zn' or $cation == 'Ag'){
			return $state_i;
		}
		return $state_aq;
	}
	// Rule 8: Thiocyanate (SCN) is mostly soluble
	if ($anion == 'S C N'){
		if ($cation == 'Mn' or $cation == 'Cu' or $cation == 'Sn' or $cation == 'Ag'){
			return $state_i;
		} elseif ($cation == 'Hg' or $cation == 'Pb'){
			return $state_sS;
		} elseif ($cation == 'Au'){
			return $state_unknown;
		}
		return $state_aq;
	}
	// Rule 9: Perchlorate and Acetate are mostly soluble
	if ($anion == 'Cl O_4' or $anion == 'C_2 H_3 O_2'){
		if ($anion == 'Cl O_4'){
			if ($cation == 'K' or $cation == 'Rb' or $cation == 'Cs'){
				return $state_sS;
			} elseif ($cation == 'Au'){
				return $state_unknown;
			}
		} else {
			if ($cation == 'Sn'){
				return $state_r;
			} elseif ($cation == 'V'){
				return $state_unknown;
			} elseif ($cation == 'Fe' and $cat_charge == 3){
				return $state_i;
			} elseif ($cation == 'Ag'){
				return $state_sS;
			}
		}
		return $state_aq;
	}
	// Rule 10: Carbonate is mostly insoluble
	if ($anion == 'C O_3'){
		if ($cation == 'N H_4' or $cation == 'H' or $cation == 'Na' or $cation == 'K' or $cation == 'Rb' or $cation == 'Cs'){
			return $state_aq;
		} elseif ($cation == 'Li' or $cation == 'Be' or $cation == 'Mg' or $cation == 'Sr'){
			return $state_sS;
		} elseif ($cation == 'Al' or $cation == 'Ga' or $cation == 'Cu'){
			return $state_r;
		} elseif ($cation == 'Fe' and $cat_charge == 3){
			return $state_r;
		} elseif ($cation == 'V'){
			return $state_unknown;
		}
		return $state_i;
	}
	// Rule 11: Sulfate is mostly soluble
	if ($anion == 'S O_4'){
		if ($cation == 'Ca' or $cation == 'Sr' or $cation == 'Ga' or $cation == 'V' or $cation == 'Ag'){
			return $state_sS;
		} elseif ($cation == 'Ba' or $cation == 'Pb'){
			return $state_i;
		} elseif ($cation == 'Hg'){
			return $state_r;
		}
		return $state_aq;
	}
	// Rule 12: Oxalate is mostly insoluble
	if ($anion == 'C_2 O_4'){
		if ($cation == 'N H_4' or $cation == 'H' or $cation == 'Li' or $cation == 'Na' or $cation == 'K'){
			return $state_aq;
		} elseif ($cation == 'Mg' or $cation == 'Ca' or $cation == 'Fe' or $cation == 'Sn' or $cation == 'Hg'){// Both Fe work!
			return $state_sS;
		} elseif ($cation == 'Ga' or $cation == 'V' or $cation == 'Cr' or $cation == 'Au'){
			return $state_unknown;
		}
		return $state_i;
	}
	// Rule 13: Phosphate is mostly insoluble
	if ($anion == 'P O_4'){
		if ($cation == 'Na' or $cation == 'K' or $cation == 'Rb' or $cation == 'Cs' or $cation == 'Be'){
			return $state_aq;
		} elseif ($cation == 'Li' or $cation == 'Sr'){
			return $state_sS;
		} elseif ($cation == 'Fe' and $cat_charge == 3){
			return $state_sS;
		} elseif ($cation == 'Au'){
			return $state_unknown;
		}
		return $state_i;
	}
	return $state_missing;
}
$GLOBALS['chem_periodic_table'] = array(
	1=>array("H", "Hydrogen", 1,1.0079, "-255.34", "-252.87", ""),
        2=>array("He", "Helium", 2,4.00260, "< -272.2", "-268.934", "Noble gas"),
        3=>array("Li", "Lithium", 3,6.941, "180.54", "1342", "Alkaline"),
        4=>array("Be", "Beryllium", 4,9.01218, "1278", "2970", "Alkaline Earth"),
        5=>array("B", "Boron", 5,10.81, "2079", "2550", ""),
        6=>array("C", "Carbon", 6,12.011, "3550", "4827", ""),
        7=>array("N", "Nitrogen", 7,14.0067, "-209.86", "-195.8", ""),
        8=>array("O", "Oxygen", 8,15.9994, "-218.4", "-182.962", ""),
        9=>array("F", "Fluorine", 9,18.9984, "-219.62", "-188", "Halogen"),
        10=>array("Ne", "Neon", 10,20.179, "-248.67", "-246.048", "Noble gas"),
        11=>array("Na", "Sodium", 11,22.9898, "97.81", "882.9", "Alkaline"),
        12=>array("Mg", "Magnesium", 12,24.305, "648.8", "1090", "Alkaline Earth"),
        13=>array("Al", "Aluminum", 13,26.9815, "660.37", "2467", ""),
        14=>array("Si", "Silicon", 14,28.0855, "1410", "2355", ""),
        15=>array("P", "Phosphorus", 15,30.9738, "44.1", "280", ""),
        16=>array("S", "Sulfur", 16,32.06, "112.8 (rhombic), 119.0 (monoclinic)", "4.6", ""),
        17=>array("Cl", "Chlorine", 17,35.453, "-100.98", "-34", "Halogen"),
        18=>array("Ar", "Argon", 18,39.948, "-189.2", "-185.7", "Noble gas"),
        19=>array("K", "Potassium", 19,39.0983, "63.25", "759.9", "Alkaline"),
        20=>array("Ca", "Calcium", 20,40.078, "839", "1484", "Alkaline Earth"),
        21=>array("Sc", "Scandium", 21,44.9579, "1541", "2836", "Transition Metal"),
        22=>array("Ti", "Titanium", 22,47.88, "1660", "3287", "Transition Metal"),
        23=>array("V", "Vanadium", 23,50.9415, "1890", "3380", "Transition Metal"),
        24=>array("Cr", "Chromium", 24,51.996, "1857", "2672", "Transition Metal"),
        25=>array("Mn", "Manganese", 25,54.9380, "1244", "1962", "Transition Metal"),
        26=>array("Fe", "Iron", 26,55.847, "1535", "2750", "Transition Metal"),
        27=>array("Co", "Cobalt", 27,58.9332, "1857", "2672", "Transition Metal"),
        28=>array("Ni", "Nickel", 28,58.69, "1453", "2732", "Transition Metal"),
        29=>array("Cu", "Copper", 29,63.546, "1083", "2567", "Transition Metal"),
        30=>array("Zn", "Zinc", 30,65.38, "419.58", "907", "Transition Metal"),
        31=>array("Ga", "Gallium", 31,69.72, "29.78", "2403", ""),
        32=>array("Ge", "Germanium", 32,72.59, "937.4", "2830", ""),
        33=>array("As", "Arsenic", 33,74.9216, "817", "613", ""),
        34=>array("Se", "Selenium", 34,78.96, "50 (amorphous), 217 (gray form)", "685", ""),
        35=>array("Br", "Bromine", 35,79.904, "-7.2", "58.78", "Halogen"),
        36=>array("Kr", "Krypton", 36,83.80, "-156.6", "-152.30", "Noble gas"),
        37=>array("Rb", "Rubidium", 37,85.4678, "38.89", "686", "Alkaline"),
        38=>array("Sr", "Strontium", 38,87.62, "769", "1384", "Alkaline Earth"),
        39=>array("Y", "Yttrium", 39,88.9059, "1522", "5338", "Transition Metal"),
        40=>array("Zr", "Zirconium", 40,91.22, "1852", "4377", "Transition Metal"),
        41=>array("Nb", "Niobium", 41,92.9064, "2468", "4742", "Transition Metal"),
        42=>array("Mo", "Molybdenum", 42,95.94, "2617", "4612", "Transition Metal"),
        43=>array("Tc", "Technetium", 43,97.9072, "2172", "4877", "Transition Metal"),
        44=>array("Ru", "Ruthenium", 44,101.07, "2310", "3900", "Transition Metal"),
        45=>array("Rh", "Rhodium", 45,102.9055, "1966", "3727", "Transition Metal"),
        46=>array("Pd", "Palladium", 46,106.42, "1554", "3140", "Transition Metal"),
        47=>array("Ag", "Silver", 47,107.8682, "961.93", "2212", "Transition Metal"),
        48=>array("Cd", "Cadmium", 48,112.41, "320.9", "765", "Transition Metal"),
        49=>array("In", "Indium", 49,114.82, "156.61", "2080", ""),
        50=>array("Sn", "Tin", 50,118.69, "231.97", "2270", ""),
        51=>array("Sb", "Antimony", 51,121.76, "630.74", "1750", ""),
        52=>array("Te", "Tellurium", 52,127.60, "449.5", "4877", ""),
        53=>array("I", "Iodine", 53,126.9045, "113.5", "184.35", "Halogen"),
        54=>array("Xe", "Xenon", 54,131.29, "-111.9", "-107.1", "Noble gas"),
        55=>array("Cs", "Cesium", 55,132.9054, "28.40", "669.3", "Alkaline"),
        56=>array("Ba", "Barium", 56,137.33, "725", "1640", "Alkaline Earth"),
        57=>array("La", "Lanthanum", 57,138.9055, "918", "3464", "Lanthanide"),
        58=>array("Ce", "Cerium", 58,140.12, "798", "3443", "Lanthanide"),
        59=>array("Pr", "Praseodymium", 59,140.9077, "931", "3520", "Lanthanide"),
        60=>array("Nd", "Neodymium", 60,144.24, "1021", "3074", "Lanthanide"),
        61=>array("Pm", "Promethium", 61,144.9127, "1042", "3000", "Lanthanide"),
        62=>array("Sm", "Samarium", 62,150.36, "1074", "1794", "Lanthanide"),
        63=>array("Eu", "Europium", 63,151.96, "822", "1527", "Lanthanide"),
        64=>array("Gd", "Gadolinium", 64,157.27, "1313", "3273", "Lanthanide"),
        65=>array("Tb", "Terbium", 65,158.9254, "1356", "3230", "Lanthanide"),
        66=>array("Dy", "Dysprosium", 66,162.50, "1412", "2567", "Lanthanide"),
        67=>array("Ho", "Holmium", 67,164.9304, "1474", "2700", "Lanthanide"),
        68=>array("Er", "Erbium", 68,167.26, "1529", "2868", "Lanthanide"),
        69=>array("Tm", "Thulium", 69,168.9342, "1545", "1950", "Lanthanide"),
        70=>array("Yb", "Ytterbium", 70,172.04, "819", "1196", "Lanthanide"),
        71=>array("Lu", "Lutetium", 71,174.967, "1663", "3402", "Lanthanide"),
        72=>array("Hf", "Hafnium", 72,178.49, "2227", "4602", "Transition Metal"),
        73=>array("Ta", "Tantalum", 73,180.9479, "2996", "5425", "Transition Metal"),
        74=>array("W", "Tungsten", 74,183.85, "3410", "5660", "Transition Metal"),
        75=>array("Re", "Rhenium", 75,186.207, "3180", "5627", "Transition Metal"),
        76=>array("Os", "Osmium", 76,190.2, "3054", "5027", "Transition Metal"),
        77=>array("Ir", "Iridium", 77,192.22, "2410", "4130", "Transition Metal"),
        78=>array("Pt", "Platinum", 78,195.08, "1772", "3827", "Transition Metal"),
        79=>array("Au", "Gold", 79,196.9665, "1064.4", "2808", "Transition Metal"),
        80=>array("Hg", "Mercury", 80,200.59, "-38.87", "356.58", "Transition Metal"),
        81=>array("Tl", "Thallium", 81,204.383, "303.5", "1457", ""),
        82=>array("Pb", "Lead", 82,207.2, "327.502", "1740", ""),
        83=>array("Bi", "Bismuth", 83,208.9804, "271.3", "1560", ""),
        84=>array("Po", "Polonium", 84,208.9824, "254", "962", ""),
        85=>array("At", "Astatine", 85,209.9871, "302", "337", "Halogen"),
        86=>array("Rn", "Radon", 86,222.0176, "-71", "-62", "Noble gas"),
        87=>array("Fr", "Francium", 87,223.0197, "27", "677", "Alkaline"),
        88=>array("Ra", "Radium", 88,226.0254, "700", "1140", "Alkaline Earth"),
        89=>array("Ac", "Actinium", 89,227.0278, "1050", "3200", "Actinide"),
        90=>array("Th", "Thorium", 90,232.0381, "1750", "3800", "Actinide"),
        91=>array("Pa", "Protactinium", 91,231.0359, "1600", "unknown", "Actinide"),
        92=>array("U", "Uranium", 92,238.0289, "1132", "3818", "Actinide"),
        93=>array("Np", "Neptunium", 93,237.0482, "640", "3902", "Actinide"),
        94=>array("Pu", "Plutonium", 94,244.0642, "641", "3232", "Actinide"),
        95=>array("Am", "Americium", 95,243.0614, "994", "2607", "Actinide"),
        96=>array("Cm", "Curium", 96,247.0703, "1340", "unknown", "Actinide"),
        97=>array("Bk", "Berkelium", 97,247.0703, "unknown", "unknown", "Actinide"),
        98=>array("Cf", "Californium", 98,251.0796, "unknown", "unknown", "Actinide"),
        99=>array("Es", "Einsteinium", 99,252.083, "unknown", "unknown", "Actinide"),
        100=>array("Fm", "Fermium", 100,257.0951, "unknown", "unknown", "Actinide"),
        101=>array("Md", "Mendelevium", 101,258.10, "unknown", "unknown", "Actinide"),
        102=>array("No", "Nobelium", 102,259.1009, "unknown", "unknown", "Actinide"),
        103=>array("Lr", "Lawrencium", 103,262.11, "unknown", "unknown", "Actinide"),
        104=>array("Rf", "Rutherfordium", 104,261.11, "unknown", "unknown", ""),
        105=>array("Db", "Dubnium", 105,262.114, "unknown", "unknown", ""),
        106=>array("Sg", "Seaborgium", 106,263.118, "unknown", "unknown", ""),
        107=>array("Bh", "Bohrium", 107,264, "unknown", "unknown", ""),
        108=>array("Hs", "Hassium", 108,269, "unknown", "unknown", ""),
        109=>array("Mt", "Meitnerium", 109,268, "unknown", "unknown", ""),
		110=>array("Ds", "Darmstadtium", 110,281, "unknown", "unknown", ""), //110 t0 118 updated in Oct 2021; Source: https://ptable.com/
		111=>array("Rg", "Roentgenium", 111,282, "unknown", "unknown", ""),
		112=>array("Cn", "Copernicium", 112,285, "unknown", "unknown", ""),
		113=>array("Nh", "Nihonium", 113,286, "unknown", "unknown", ""),
		114=>array("Fl", "Flerovium", 114,289, "unknown", "unknown", ""),
		115=>array("Mc", "Moscovium", 115,290, "unknown", "unknown", ""),
		116=>array("Lv", "Livermorium", 116,293, "unknown", "unknown", "Chalcogen"),
		117=>array("Ts", "Tennessine", 117,294, "unknown", "unknown", "Halogen"),
		118=>array("Og", "Oganesson", 118,294, "unknown", "unknown", "Noble gas")

    );

$GLOBALS['chem_families'] = array(
	'other'=>array(1,5,6,7,8,13,14,15,16,31,32,33,34,49,50,51,52,81,82,83,84,105,106,107,108,109,110),
	'noble gas'=>array(2,10,18,36,54,86),
	'alkaline'=>array(3,11,19,37,55,87),
	'alkaline earth'=>array(4,12,20,38,56,88),
	'halogen'=>array(9,17,35,53,85),
	'transition metal'=>array(21,22,23,24,25,26,27,28,29,30,39,40,41,42,43,44,45,46,47,48,72,73,74,75,76,77,78,79,80),
	'lanthanide'=>array(57,58,59,60,61,62,63,64,65,66,67,68,69,70,71),
	'actinide'=>array(89,90,91,92,93,94,95,96,97,98,99,100,101,102,103)
	);

$GLOBALS['chem_numberbyatom'] = array(
	'H'=>1,'He'=>2,'Li'=>3,'Be'=>4,'B'=>5,'C'=>6,'N'=>7,'O'=>8,'F'=>9,'Ne'=>10,'Na'=>11,'Mg'=>12,'Al'=>13,'Si'=>14,'P'=>15,'S'=>16,'Cl'=>17,
	'Ar'=>18,'K'=>19,'Ca'=>20,'Sc'=>21,'Ti'=>22,'V'=>23,'Cr'=>24,'Mn'=>25,'Fe'=>26,'Co'=>27,'Ni'=>28,'Cu'=>29,'Zn'=>30,'Ga'=>31,'Ge'=>32,'As'=>33,
	'Se'=>34,'Br'=>35,'Kr'=>36,'Rb'=>37,'Sr'=>38,'Y'=>39,'Zr'=>40,'Nb'=>41,'Mo'=>42,'Tc'=>43,'Ru'=>44,'Rh'=>45,'Pd'=>46,'Ag'=>47,'Cd'=>48,'In'=>49,'Sn'=>50,
	'Sb'=>51,'Te'=>52,'I'=>53,'Xe'=>54,'Cs'=>55,'Ba'=>56,'La'=>57,'Ce'=>58,'Pr'=>59,'Nd'=>60,'Pm'=>61,'Sm'=>62,'Eu'=>63,'Gd'=>64,
	'Tb'=>65,'Dy'=>66,'Ho'=>67,'Er'=>68,'Tm'=>69,'Yb'=>70,'Lu'=>71,'Hf'=>72,'Ta'=>73,'W'=>74,'Re'=>75,'Os'=>76,'Ir'=>77,'Pt'=>78,'Au'=>79,'Hg'=>80,'Tl'=>81,'Pb'=>82,
	'Bi'=>83,'Po'=>84,'At'=>85,'Rn'=>86,'Fr'=>87,'Ra'=>88,'Ac'=>89,'Th'=>90,'Pa'=>91,'U'=>92,'Np'=>93,'Pu'=>94,'Am'=>95,
	'Cm'=>96,'Bk'=>97,'Cf'=>98,'Es'=>99,'Fm'=>100,'Md'=>101,'No'=>102,'Lr'=>103);

$GLOBALS['chem_cations'] = array(
	array('Li',1,'lithium','','s'), //common  0
	array('Na',1,'sodium','','s'), //common
	array('K',1,'potassium','','s'), //common
	array('Rb',1,'rubidium','','s'), //common
	array('Cs',1,'cesium','','s'), //common
	array('Be',2,'beryllium','','s'), //common
	array('Mg',2,'magnesium','','s'), //common
	array('Ca',2,'calcium','','s'), //common
	array('Sr',2,'strontium','','s'), //common
	array('Ba',2,'barium','','s'), //common
	array('Zn',2,'zinc','','s'), //common
	array('Ag',1,'silver','','s'), //common
	array('Al',3,'aluminum','','s'), //common
	array('N H_4',1,'ammonium','','pa'), //common  13
	array('Cd',2,'cadmium','','s'),
	array('Fr',1,'francium','','s'),
	array('Ra',2,'radium','','s'),  //16
	array('Cr',2,'chromium (II)','chromous','pv'), //common 17
	array('Cr',3,'chromium (III)','chromic','pv'), //common
	array('Mn',2,'manganese (II)','manganous','pv'), //common
	array('Mn',3,'manganese (III)','manganic','pv'), //common
	array('Fe',2,'iron (II)','ferrous','pv'), //common
	array('Fe',3,'iron (III)','ferric','pv'), //common
	array('Co',2,'cobalt (II)','cobaltous','pv'), //common
	array('Co',3,'cobalt (III)','cobaltic','pv'), //common
	array('Ni',2,'nickel (II)','nickelous','pv'), //common
	array('Ni',3,'nickel (III)','nickelic','pv'), //common
	array('Cu',1,'copper (I)','cuprous','pv'), //common
	array('Cu',2,'copper (II)','cupric','pv'), //common
	array('Au',1,'gold (I)','aurous','pv'), //common
	array('Au',3,'gold (III)','auric','pv'), //common
	array('Sn',2,'tin (II)','stannous','pv'), //common
	array('Sn',4,'tin (IV)','stannic','pv'), //common
	array('Pb',2,'lead (II)','plumbous','pv'), //common
	array('Pb',4,'lead (IV)','plumbic','pv'), //common 34
	array('Hg',2,'mercury (II)','mercuric','pv'),
	array('Hg_2',2,'mercury (I)','mercurous','pa')  //36
);
$GLOBALS['chem_anions'] = array(
	array('F',1,'fluoride','','s'), //common 0
	array('Cl',1,'chloride','','s'), //common
	array('Br',1,'bromide','','s'), //common
	array('I',1,'iodide','','s'), //common
	array('O',2,'oxide','','s'), //common
	array('S',2,'sulfide','','s'), //common
	array('N',3,'nitride','','s'), //common
	array('P',3,'phosphide','','s'), //common
	array('C',4,'carbide','','s'), //common 8
	array('Se',2,'selenide','','s'),
	array('C_2 H_3 O_2',1,'acetate','','pa'), //common  10
	array('Cl O',1,'hypochlorite','','pa'), //common
	array('Cl O_2',1,'chlorite','','pa'), //common
	array('Cl O_3',1,'chlorate','','pa'), //common
	array('Cl O_4',1,'perchlorate','','pa'), //common
	array('C N',1,'cyanide','','pa'), //common
	array('H C O_3',1,'bicarbonate','hydrogen carbonate','pa'), //common
	array('H_2 P O_4',1,'dihydrogen  phosphate','','pa'), //common
	array('H S O_3',1,'bisulfite','hydrogen sulfite','pa'), //common
	array('H S O_4',1,'bisulfate','hydrogen sulfate','pa'), //common
	array('I O_3',1,'iodate','','pa'), //common
	array('Mn O_4',1,'permanganate','','pa'), //common
	array('N O_2',1,'nitrite','','pa'), //common
	array('N O_3',1,'nitrate','','pa'), //common
	array('O H',1,'hydroxide','','pa'), //common
	array('S C N',1,'thiocyanate','','pa'), //common
	array('C O_3',2,'carbonate','','pa'), //common
	array('C_2 O_4',2,'oxalate','','pa'), //common
	array('Cr O_4',2,'chromate','','pa'), //common
	array('Cr_2 O_7',2,'dichromate','','pa'), //common
	array('H P O_4',2,'hydrogen phosphate','','pa'), //common
	array('S O_3',2,'sulfite','','pa'), //common
	array('S O_4',2,'sulfate','','pa'), //common
	array('Si O_3',2,'silicate','','pa'), //common
	array('As O_4',3,'arsenate','','pa'), //common
	array('P O_3',3,'phosphite','','pa'), //common
	array('P O_4',3,'phosphate','','pa'), //common 36
	array('O_2',2,'peroxide','','pa')
);
$GLOBALS['chem_compounds'] = array(
	'twobasic' => array(
		array('Silver bromide','Ag Br'),
		array('Silver chloride','Ag Cl'),
		array('Silver iodide','Ag I'),
		array('Aluminum nitride','Al N'),
		array('Aluminum phosphide','Al P'),
		array('Boron nitride','B N'),
		array('Barium oxide','Ba O'),
		array('Beryllium oxide','Be O'),
		array('Carbon monoxide','C O'),
		array('Chlorine monoxide','Cl O'),
		array('Caesium chloride','Cs Cl'),
		array('Caesium fluoride','Cs F'),
		array('Gallium nitride','Ga N'),
		array('Gallium phosphide','Ga P'),
		array('Hydrogen bromide','H Br'),
		array('Hydrochloric acid','H Cl'),
		array('Hydrogen fluoride','H F'),
		array('Iodine monochloride','I Cl'),
		array('Cyanogen iodide','I CN'),
		array('Indium nitride','In N'),
		array('Potassium bromide','K Br'),
		array('Potassium chloride','K Cl'),
		array('Potassium cyanide','K CN'),
		array('Potassium iodide','K I'),
		array('Lithium bromide','Li Br'),
		array('Lithium chloride','Li Cl'),
		array('Lithium hydride','Li H'),
		array('Lithium iodide','Li I'),
		array('Nitric oxide','N O'),
		array('Sodium bromide','Na Br'),
		array('Sodium chloride','Na Cl'),
		array('Sodium hydride','Na H'),
		array('Sodium iodide','Na I'),
		array('Rubidium bromide','Rb Br'),
		array('Rubidium chloride','Rb Cl'),
		array('Rubidium fluoride','Rb F'),
		array('Rubidium iodide','Rb I'),
		array('Silicon carbide','Si C'),
		array('Strontium oxide','Sr O'),
		array('Tantalum carbide','Ta C'),
		array('Titanium carbide','Ti C'),
		array('Titanium nitride','Ti N'),
		array('Vanadium carbide','V C'),
		array('Tungsten carbide','W C'),
		array('Zinc sulfide','Zn S'),
		array('Zirconium carbide','Zr C')
	),
	'twosub'=> array(
		array('Silver azide','Ag N_3'),
		array('Silver oxide','Ag_2 O'),
		array('Silver sulfide','Ag_2 S'),
		array('Silver nitride','Ag_3 N'),
		array('Aluminum chloride','Al Cl_3'),
		array('Aluminum fluoride','Al F_3'),
		array('Arsine','As H_3'),
		array('Boron trichloride','B Cl_3'),
		array('Boron trifluoride','B F_3'),
		array('Boron carbide','B_4 C'),
		array('Boron suboxide','B_6 O'),
		array('Barium chloride','Ba Cl_2'),
		array('Barium fluoride','Ba F_2'),
		array('Barium iodide','Ba I_2'),
		array('Barium peroxide','Ba O_2'),
		array('Beryllium bromide','Be Br_2'),
		array('Beryllium chloride','Be Cl_2'),
		array('Beryllium fluoride','Be F_2'),
		array('Beryllium hydride','Be H_2'),
		array('Beryllium iodide','Be I_2'),
		array('Bromine trifluoride','Br F_3'),
		array('Carbon tetraiodide','C I_4'),
		array('Carbon dioxide','C O_2'),
		array('Calcium carbide','Ca C_2'),
		array('Calcium chloride','Ca Cl_2'),
		array('Calcium fluoride','Ca F_2'),
		array('Calcium hydride','Ca H_2'),
		array('Cadmium bromide','Cd Br_2'),
		array('Cadmium chloride','Cd Cl_2'),
		array('Cadmium fluoride','Cd F_2'),
		array('Cadmium iodide','Cd I_2'),
		array('Chlorine dioxide','Cl O_2'),
		array('Chlorine trioxide','Cl O_3'),
		array('Gallium trichloride','Ga Cl_3'),
		array('Germane','Ge H_4'),
		array('Hydrazoic acid','H N_3'),
		array('Hydrogen sulfide','H_2 S'),
		array('Sulfane','H_2 S'),
		array('Iodine trichloride','I Cl_3'),
		array('Magnesium chloride','Mg Cl_2'),
		array('Ammonia','N H_3'),
		array('Nitrous oxide','N_2 O'),
		array('Nitrogen dioxide','N O_2'),
		array('Sodium azide','Na N_3'),
		array('Sodium dioxide','Na O_2'),
		array('Sodium oxide','Na_2 O'),
		array('Sodium sulfide','Na_2 S'),
		array('Phosphorus tribromide','P Br_3'),
		array('Phosphorus trifluoride','P F_3'),
		array('Phosphine','P H_3'),
		array('Phosphorus triiodide','P I_3'),
		array('Radium chloride','Ra Cl_2'),
		array('Sulfur tetrafluoride','S F_4'),
		array('Sulfur hexafluoride','S F_6'),
		array('Sulfur dioxide','S O_2'),
		array('Antimony hydride','Sb H_3'),
		array('Stibine','Sb H_3'),
		array('Selenium tetrafluoride','Se F_4'),
		array('Selenium hexafluoride','Se F_6'),
		array('Selenium dioxide','Se O_2'),
		array('Silane','Si H_4'),
		array('Silicon dioxide','Si O_2'),
		array('Strontium chloride','Sr Cl_2'),
		array('Uranium pentafluoride','U F_5'),
		array('Zinc bromide','Zn Br_2'),
		array('Zinc chloride','Zn Cl_2'),
		array('Aluminum oxide','Al_2 O_3'),
		array('Diborane','B_2 H_6'),
		array('Boron oxide','B_2 O_3'),
		array('Pentaborane','B_5 H_9'),
		array('Beryllium nitride','Be_3 N_2'),
		array('Acetylene','C_2 H_2'),
		array('Ethylene','C_2 H_4'),
		array('Ethane','C_2 H_6'),
		array('Propane','C_3 H_8'),
		array('Butane','C_4 H_10'),
		array('Cyclobutane','C_4 H_8'),
		array('Dichlorine trioxide','Cl_2 O_3'),
		array('Dichlorine hexoxide','Cl_2 O_6'),
		array('Dichlorine heptoxide','Cl_2 O_7'),
		array('Digermane','Ge_2 H_6'),
		array('Hydrogen peroxide','H_2 O_2'),
		array('Lithium peroxide','Li_2 O_2'),
		array('Hydrazine','N_2 H_4'),
		array('Sodium carbide','Na_2 C_2'),
		array('Sodium peroxide','Na_2 O_2'),
		array('Disulfur dichloride','S_2 Cl_2'),
		array('Antimony trioxide','Sb_2 O_3'),
		array('Disilane','Si_2 H_6')
	),
	'threeplus' => array(
		array('Silver fluoroborate','Ag B F_4'),
		array('Silver bromate','Ag Br O_3'),
		array('Silver chlorate','Ag Cl O_3'),
		array('Silver perchlorate','Ag Cl O_4'),
		array('Silver nitrate','Ag N O_3'),
		array('Silver hydroxide','Ag O H'),
		array('Silver chromate','Ag_2 Cr O_4'),
		array('Silver sulfate','Ag_2 S O_4'),
		array('Barium carbonate','Ba C O_3'),
		array('Barium chromate','Ba Cr O_4'),
		array('Barium sulfate','Ba S O_4'),
		array('Beryllium carbonate','Be C O_3'),
		array('Beryllium sulfite','Be S O_3'),
		array('Beryllium sulfate','Be S O_4'),
		array('Freon-11','C F Cl_3'),
		array('Aspartame','C_14 H_18 N_2 O_5'),
		array('Stearic acid','C_18 H_36 O_2'),
		array('Acetic acid','C_2 H_4 O_2'),
		array('Glycine','C_2 H_5 N H_2'),
		array('Glutamine','C_5 H_10 N_2 O_3'),
		array('Citric acid','C_6 H_8 O_7'),
		array('Calcium cyanamide','Ca C N_2'),
		array('Calcium chromate','Ca Cr O_4'),
		array('Cadmium sulfate','Cd S O_4'),
		array('Chromyl chloride','Cr O_2 Cl_2'),
		array('Chromyl fluoride','Cr O_2 F_2'),
		array('Caesium carbonate','Cs_2 C O_3'),
		array('Caesium chromate','Cs_2 Cr O_4'),
		array('Hypochlorous acid','H Cl O'),
		array('Chloric acid','H Cl O_3'),
		array('Perchloric acid','H Cl O_4'),
		array('Carbonic acid','H_2 C O_3'),
		array('Sulfurous acid','H_2 S O_3'),
		array('Sulfuric acid','H_2 S O_4'),
		array('Pyrosulfuric acid','H_2 S_2 O_7'),
		array('Selenious acid','H_2 Se O_3'),
		array('Selenic acid','H_2 Se O_4'),
		array('Hypophosphorous acid','H_3 P O_2'),
		array('Phosphoric acid','H_3 P O_4'),
		array('Potassium cyanide','K C N'),
		array('Potassium chlorate','K Cl O_3'),
		array('Potassium perchlorate','K Cl O_4'),
		array('Potassium carbonate','K_2 C O_3'),
		array('Potassium sulfate','K_2 S O_4'),
		array('Lithium Aluminum hydride','Li Al H_4'),
		array('Lithium borohydride','Li B H_4'),
		array('Lithium hypochlorite','Li Cl O'),
		array('Lithium chlorate','Li Cl O_3'),
		array('Lithium perchlorate','Li Cl O_4'),
		array('Lithium cobalt oxide','Li Co O_2'),
		array('Lithium nitrate','Li N O_3'),
		array('Lithium hydroxide','Li O H'),
		array('Lithium sulfate','Li_2 S O_4'),
		array('Magnesium carbonate','Mg C O_3'),
		array('Magnesium sulfate','Mg S O_4'),
		array('Ammonium cyanide','N H_4 C N'),
		array('Ammonium chloride','N H_4 Cl'),
		array('Ammonium chlorate','N H_4 Cl O_3'),
		array('Ammonium perchlorate','N H_4 Cl O_4'),
		array('Ammonium bicarbonate','N H_4 H C O_3'),
		array('Ammonium nitrate','N H_4 N O_3'),
		array('Ammonium hydroxide','N H_4 O H'),
		array('Sodium borohydride','Na B H_4'),
		array('Sodium hypobromite','Na Br O'),
		array('Sodium bromite','Na Br O_2'),
		array('Sodium bromate','Na Br O_3'),
		array('Sodium perbromate','Na Br O_4'),
		array('Sodium cyanide','Na C N'),
		array('Sodium chlorite','Na Cl O_2'),
		array('Sodium chlorate','Na Cl O_3'),
		array('Sodium perchlorate','Na Cl O_4'),
		array('Sodium iodate','Na I O_3'),
		array('Sodium periodate','Na I O_4'),
		array('Sodamide','Na N H_2'),
		array('Sodium nitrite','Na N O_2'),
		array('Sodium nitrate','Na N O_3'),
		array('Sodium hypochlorite','Na O Cl'),
		array('Sodium hydroxide','Na O H'),
		array('Sodium thiocyanate','Na S C N'),
		array('Sodium hydrosulfide','Na S H'),
		array('Sodium carbonate','Na_2 C O_3'),
		array('Sodium sulfite','Na_2 S O_3'),
		array('Sodium sulfate','Na_2 S O_4'),
		array('Sodium thiocyanate','Na_2 S_2 O_3'),
		array('Sodium thiosulfate','Na_2 S_2 O_3'),
		array('Sodium persulfate','Na_2 S_2 O_8'),
		array('Sodium selenite','Na_2 Se O_3'),
		array('Niobium oxychloride','Nb O Cl_3'),
		array('Rubidium nitrate','Rb N O_3'),
		array('Rubidium hydroxide','Rb O H'),
		array('Sulfuryl chloride','S O_2 Cl_2'),
		array('Selenium oxybromide','Se O Br_2'),
		array('Selenium oxydichloride','Se O Cl_2'),
		array('Selenoyl fluoride','Se O_2 F_2'),
		array('Strontium carbonate','Sr C O_3'),
		array('Uranyl chloride','U O_2 Cl_2'),
		array('Uranyl fluoride','U O_2 F_2'),
		array('Zinc sulfate','Zn S O_4')
	),
	'parens'=> array(
		array('Aluminum hydroxide','Al (O H)_3'),
		array('Aluminum nitrate','Al (N O_3)_3'),
		array('Ammonium chromate','(N H_4)_2 Cr O_4'),
		array('Ammonium dichromate','(N H_4)_2 Cr_2 O_7'),
		array('Ammonium persulfate','(N H_4)_2 S_2 O_8'),
		array('Ammonium sulfate','(N H_4)_2 S O_4'),
		array('Ammonium sulfide','(N H_4)_2 S'),
		array('Ammonium sulfite','(N H_4)_2 S O_3'),
		array('Barium chlorate','Ba (Cl O_3)_2'),
		array('Barium hydroxide','Ba (O H)_2'),
		array('Barium nitrate','Ba (N O_3)_2'),
		array('Beryllium hydroxide','Be (O H)_2'),
		array('Beryllium nitrate','Be (N O_3)_2'),
		array('Boric acid','B (O H)_3'),
		array('Cadmium nitrate','Cd (N O_3)_2'),
		array('Calcium chlorate','Ca (Cl O_3)_2'),
		array('Calcium hydroxide','Ca (O H)_2'),
		array('Strontium nitrate','Sr (N O_3)_2'),
		array('Uranyl hydroxide','U O_2(O H)_2'),
		array('Uranyl hydroxide','(U O_2)_2(O H)_4'),
		array('Uranyl nitrate','U O_2(N O_3)_2'),
		array('Zinc cyanide','Zn (C N)_2')
	)
);
