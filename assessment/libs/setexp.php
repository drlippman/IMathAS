<?php
/* This library is used to handle setexpal expressions.
Useful for creating truth tables checking setexpal equivalencies, etc.
Updated: May 16, 2022
*/


global $allowedmacros;
array_push($allowedmacros,"setexpequals","setexpsubset","setexpevaluate","setexpmakepretty","setexpsteps","setexprand","setexplegend","venn2diagram","venn3diagram");




///////////////
// CONSTANTS //
///////////////

// BONEYARD_SETEXP[b] is an array of 'skeleton' strings containing the all possible postfix binary expressions with b operations.  Variable positions are denoted by *.  Operations by #.
const BONEYARD_SETEXP = array(
					array("*"),
					array("**#"),
					array("**#*#","***##"),
					array("**#*#*#","***##*#","**#**##","***#*##","****###"),
					array("**#*#*#*#","**#*#**##","**#**##*#","**#**#*##","**#***###","***##*#*#","***##**##","***#*##*#","***#*#*##","***#**###","****###*#","****##*##","****#*###","****#*###","*****####"));			
// WEIGHT_SETEXP[b] is an array of weights for each skeleton in BONEYARD_SETEXP[b].  This is useful for getting random expressions, so more interesting ones occur more frequently. Higher weights = higher likelihood of being selected.
const WEIGHT_SETEXP = array(array(1),array(1),array(1,1),array(1,1,3,1,1),array(1,3,2,3,3,2,3,2,2,2,2,2,2,1));
// UNARIES_SETEXP gives all 'proper' unary operations...which will be ! here for complement
const UNARIES_SETEXP = array('!');
// BINARIES_SETEXP array gives 'proper' binary operations. If more are added later this must be of size 10 or less for this library to function properly!
const BINARIES_SETEXP = array("nn","ominus","uu","-");
// ALTS_SETEXP gives other words that can be used by the user and directs them to the proper one above
// **Careful**: 'xor' must be before 'or' below, otherwise when replacing alt words out the 'or' in 'xor' would get treated as an operation!
const ALTS_SETEXP = array( "cap"=> "nn", "and" => "nn", "∩"=>"nn", "\\cap" => "nn",
                    "xor" => "ominus", "\\oplus" => "ominus", "oplus" => "ominus", "⊕" => "ominus", "\\ominus" => "ominus", "triangle" => "ominus", "⊖" => "ominus", "\\triangle" => "ominus", "△" => "ominus",
                    "cup" => "uu", "or" => "uu", "∪"=>"uu", "\\cup" => "uu",
                    "'" => '!', "^c" => '!');
// PRECEDENCE_SETEXP gives the precidence of the operations. Lower is higher precedence.
const PRECEDENCE_SETEXP = array('!'=>0,"nn"=>1,"uu"=>1,"ominus"=>1,"-"=>2);
// VENN2_SETEXP gives the coordinates for the 4 regions of the 2-circle Venn diagram, used in venn2diagram
// In order: A'nB', AnB', AnB, BnA'
const VENN2_SETEXP = array(
    'path([[0,2.46],[1,2.73],[2,2.5],[2.7,1.75],[3,0.75],[2.6,-0.35],[2,-1],[1,-1.25],[0,-1],[0,-2],[4,-2],[4,4],[0,4],[0,2.46]]);path([[0,2.46],[-1,2.73],[-2,2.5],[-2.7,1.75],[-3,0.75],[-2.6,-0.35],[-2,-1],[-1,-1.25],[0,-1],[0,-2],[-4,-2],[-4,4],[0,4],[0,2.46]]);',
    'path([[0,2.46],[-1,2.73],[-2,2.5],[-2.7,1.75],[-3,0.75],[-2.6,-0.35],[-2,-1],[-1,-1.25],[0,-1],[-0.77,-0.2],[-1,0.75],[-0.77,1.75],[0,2.46]]);',
    'path([[0,-1],[0.77,-0.2],[1,0.75],[0.77,1.75],[0,2.46],[-0.77,1.75],[-1,0.75],[-0.77,-0.2],[0,-1]]);',
    'path([[0,2.46],[1,2.73],[2,2.5],[2.7,1.75],[3,0.75],[2.6,-0.35],[2,-1],[1,-1.25],[0,-1],[0.77,-0.2],[1,0.75],[0.77,1.75],[0,2.46]]);');
const VENN3_SETEXP = array(
    'path([[0,2.46],[1,2.73],[1.98,2.47],[2.84,1.5],[2.99,0.50],[4,0.5],[4,4],[0,4],[0,2.46]]);path([[0,2.46],[-1,2.73],[-1.98,2.47],[-2.84,1.5],[-2.99,0.50],[-4,0.5],[-4,4],[0,4],[0,2.46]]);path([[3,0.5],[2.55,-0.54],[2,-1],[1.72,-2.01],[0.99,-2.74],[0,-3],[0,-4],[4,-4],[4,0.5],[3,0.5]]);path([[-3,0.5],[-2.55,-0.54],[-2,-1],[-1.72,-2.01],[-0.99,-2.74],[0,-3],[0,-4],[-4,-4],[-4,0.5],[-3,0.5]]);',
    'path([[0,2.46],[-1.01,2.73],[-2.01,2.46],[-2.85,1.49],[-2.99,0.5],[-2.55,-0.54],[-2,-1],[-1.72,0.02],[-1,0.73],[-0.73,1.74],[0,2.46]]);',
    'path([[0,2.46],[-0.73,1.74],[-1,0.73],[0,1],[1,0.73],[0.73,1.74],[0,2.46]]);',
    'path([[0,2.46],[1.01,2.73],[2.01,2.46],[2.85,1.49],[2.99,0.5],[2.55,-0.54],[2,-1],[1.72,0.02],[1,0.73],[0.73,1.74],[0,2.46]]);',
    'path([[-1,0.73],[-1.72,0.02],[-2,-1],[-1.01,-1.27],[0,-1],[-0.76,-0.22],[-1,0.73]]);',
    'path([[-1,0.73],[0,1],[1,0.73],[0.76,-0.22],[0,-1],[-0.76,-0.22],[-1,0.73]]);',
    'path([[1,0.73],[1.72,0.02],[2,-1],[1.01,-1.27],[0,-1],[0.76,-0.22],[1,0.73]]);',
    'path([[0,-1],[-1.01,-1.27],[-2,-1],[-1.72,-2.01],[-1,-2.73],[0,-3],[1,-2.73],[1.72,-2.01],[2,-1],[1.01,-1.27],[0,-1]]);');


///////////
// NOTES //
///////////

// In the algorithms, binary operations are replaced with numbers, based on their position in the BINARIES_SETEXP array (ALT words are immediately swapped out for a word in BINARIES_SETEXP)
// Unary (i.e. complement) operations are replaced with '!' and placed BEFORE the expression they complement before converting to postfix (so it's treated like a negation symbol)
// Variables are single alphabetical characters
// Unfortunately, this means that TRUE and FALSE can't be represented by a 1 and 0 (as those are the operations 'nn' and 'uu'), nor can it be represented by T and F (as those are reserved for variables). We use '+' for TRUE and '_' for FALSE
// Functions work primarily with arrays of tokens. User input is immediately converted to such, and any output is only converted back to a string at the very end.

///////////////////////
// PRIVATE FUNCTIONS //
///////////////////////

// GET OPERANDS
// Input: A postfix expression array (assumed valid)
// Output: If the expression is just a character, returns that character. If it ends in a unary, returns a single-element array with the unary removed. Otherwise, returns a two-element array (L,R) containing the left and right operands of the last binary
// Reason: For evaluation and parsing out the steps
function setexpGetOperands($exp){
    if(!is_array($exp)){
        echo "Error in setexpGetOperands: expects an array of tokens";
        return false;
    }
    if(count($exp) == 1){
        return $exp[0];
    }
    if($exp[count($exp)-1] === '!'){
        array_pop($exp);

        return array($exp);
    }
    
    $balance = 1;
    $Rindex = 0;
    for($i = count($exp)-2; $i >= 0; $i--){
        if(ctype_alpha($exp[$i]) || $exp[$i] == "*"){
            $balance--;
        }        
        elseif($exp[$i] === '!'){
        }
        else{
            $balance++;
        }
        if($balance == 0){
            $Rindex = $i;
            $i = -1;
        }
    }
    $retval = array(array_slice($exp,0,$Rindex),array_slice($exp,$Rindex,count($exp)-$Rindex-1));
    return $retval;
}


// POSTFIX STEPS FUNCTION
// Input: exp is a POSTFIX array.
// Output: an array of postfix arrays containing the steps needed to be done (in order) to compute exp
function setexpPostfixSteps($exp){
    if(!is_array($exp)){
        echo "Error in setexpPostfixSteps: Input is not an array";
        return false;
    }
    if(count($exp) == 1){           // Input is just a single symbol, and thus has no steps
        return array();
    }
    $operands = setexpGetOperands($exp);
    
    if(count($operands) == 1){      // Last operation as a negation
        return array_merge(setexpPostfixSteps($operands[0]),array($exp));
    }
    $retval = array_merge(setexpPostfixSteps($operands[0]),setexpPostfixSteps($operands[1]),array($exp));
    return $retval;
}

// POSTFIX EVALUATE FUNCTION
// Input: exp is a POSTFIX array. vars is a NAMED array, each variable (set) in exp is a key in the vars array, and the corresponding value is an array of elements contained within the corresponding set. universe is the universal set
// Output: The array of elements contained within exp
function setexpPostfixEvaluate($exp,$vars,$universe){
	if(!is_array($exp) || !is_array($vars) || !is_array($universe)){
        echo "Error in setexpPostfixEvaluate: An input is not an array.";
        return FALSE;
    }
	foreach($exp as $token){
		if(ctype_alpha($token) && !in_array($token,array_keys($vars))){
			echo "Error in setexpPostfixEvaluate: expression contains variables not listed in vars array";
			return FALSE;
		}
	}
    foreach($vars as $value){
        if(count(array_diff($value,$universe))>0){
            echo "Error in setexpPostfixEvaluate: one of the variables contains elements not within the universe.";
            return FALSE;
        }
    }
    // Error check complete. We perform the evaluate function recursively.
    $operands = setexpGetOperands($exp);
    // Base case: exp is a single variable. Return the contents
    if(!is_array($operands)){
        foreach($vars as $key=>$value){
            if($key == $operands){
                return $value;
            }
        }
        echo "Error in setexpPostfixEvaluate: exp is a single variable, but could not find the variable in vars array.";
        return false;
    }
    // Recursive case: the last operation is a complement.
    elseif(count($operands)==1){
        $retval = array_diff($universe,setexpPostfixEvaluate($operands[0],$vars,$universe));
        sort($retval);
        return $retval;
    }
    // Recursive case: the last operation is a binary operation.
    else{
        $lastop = $exp[count($exp)-1];
        $B = BINARIES_SETEXP[$lastop];
        $A = setexpPostfixEvaluate($operands[0],$vars,$universe);
        $B = setexpPostfixEvaluate($operands[1],$vars,$universe);
        if(BINARIES_SETEXP[$lastop] == "nn"){                  // AND
            $retval= array_unique(array_intersect($A,$B));
        }
        elseif(BINARIES_SETEXP[$lastop] == "ominus"){           // XOR
            $retval= array_unique(array_merge(array_diff($A,$B),array_diff($B,$A)));
        }
        elseif(BINARIES_SETEXP[$lastop] == "uu"){              // OR
            $retval= array_unique(array_merge($A,$B));
        }
        elseif(BINARIES_SETEXP[$lastop] == "-"){               // MINUS
            $retval= array_unique(array_diff($A,$B));
        }
        sort($retval);
        return $retval;
    }
}

// TO POSTFIX FUNCTION
// Input: An Infix STRING (generally user-created)
// Output: A valid postfix array of the same expression. Attempts to 'clean' the expression by removing silly things (double negations, double parens, excessive spaces, etc.)
// Reason: The user can input some dumb things, this attempts to make it less dumb and prepare for processing in other algorithms.
function setexpToPostfix($exp){
    //1. Replace operations with numerical codes, remove spaces, change brackets to parens, remove double negs and silly paren placements and non-valid characters
    //2. Ensure the result is a valid infix string
    //3. Convert to postfix

	// Convert any word binary operation to a numerical value. Convert any 
	// 0 = nn, 1 = ominus, 2 = uu, etc. (the keys of the BINARIES_SETEXP array)
    $exp = str_replace(array_keys(ALTS_SETEXP),array_values(ALTS_SETEXP),$exp);
    $exp = str_replace(array_values(BINARIES_SETEXP),array_keys(BINARIES_SETEXP),$exp);
	// Convert brackets to parens
	$exp = str_replace(array("[","]"),array("(",")"),$exp);
	// Remove spaces and silly parens
	$exp = preg_replace('/\s+/','',$exp);
	$exp = str_replace("()","",$exp);
    // Complement symbols are already postfix and substituted, how lovely
    

    // Remove any non-alpnumeric, non-paren, non-minus characters
    $exp = preg_replace("/[^\w\(\)\-\!]/","",$exp);

	// Remove outer parens of type (V) or (unary V), or ((V)), or (! (V)) etc.
	$regex = "/\(([\*A-Za-z\-\!]{1,})\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\1',$exp,1);
	}
    // Remove double parens around expressions without parens
    $regex = "/\((\()([\#\*\w\-\!]{1,})(\))\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\1\2\3',$exp,1);
	}
    // Remove double negations
	$exp = preg_replace('/!{2}/','',$exp);

    // Make sure parens are balanced
    $balance = 0;
    for($i = 0; $i < strlen($exp); $i++){
        if($exp[$i] == "("){
            $balance++;
        }
        elseif($exp[$i] == ")"){
            $balance--;
        }
        if($balance < 0){
            echo "Error in setexpToPostfix: Parentheses aren't balanced.";
            return FALSE;
        }
    }
    if($balance < 0){
        echo "Error in setexpToPostfix: Parentheses aren't balanced.";
        return FALSE;
    }
    // Ensures no two variables appear without an operation between them
    if(preg_match('/[A-Za-z][^0-9]*[A-Za-z]/u',$exp)){
        echo "Error in setexpToPostfix: Two adjacent variables appear without a binary operation between them.";
        return false;
    }
    // Ensures no two operations appear without a variable between them
    if(preg_match('/[0-9][^A-Za-z]*[0-9]/u',$exp)){
        echo "Error in setexpToPostfix: Two adjacent binary operations appear without a variable between them.";
        return false;
    }
    $exp = str_split($exp);
    // Convert to postfix.
    // Create our stacks, the operator stack and the result stack and the infix queue
    $oStk = array();
    $rStk = array();
    $Q = array_merge(array("("),$exp,array(")"));
    foreach($Q as $token){
        switch(TRUE){
            case (preg_match('/[A-Za-z]/',$token)):                        // VARIABLE CASE
                $rStk[] = $token;
                break;
            case ($token == "("):                                             // OPEN PAREN CASE
                $oStk[] = $token;
                break;
            case (ctype_digit($token) || $token == "#"):                         // BINARY
                
                while(count($oStk)>0 && (end($oStk) != "(") && (PRECEDENCE_SETEXP[BINARIES_SETEXP[end($oStk)]] <= PRECEDENCE_SETEXP[BINARIES_SETEXP[$token]])){ // POP IF TOP OF OSTK HAS GREATER PRIORITY
                    $rStk[] = array_pop($oStk);
                }
                $oStk[] = $token;
                break;
            case ($token === '!'):                                              // UNARY
                $rStk[] = '!';
                break;
                
            case ($token == ")"):                                             // RIGHT PAREN
                while(count($oStk)>0 && end($oStk) != "("){
                    $rStk[] = array_pop($oStk);
                }
                // Pop the (
                array_pop($oStk);
                break;
            default:
                echo "Error in setexpToPostfix: Unexpected token '$token' found.";
                return false;
                break;
        }
    }
    return $rStk;
}

// setexpToInfix FUNCTION
// Input: Any VALID postfix expression array
// Output: An infix expression STRING, for answers array etc. (but not for display)
// Reason: Converts postfix to infix for use by user and also standardizes the format
function setexpToInfix($exp){
	foreach($exp as $token){
        switch(TRUE){
           case (preg_match('/[A-Za-z]/',$token)):         // VARIABLE CASE
                $rStk[] = $token;
                break;
           case (preg_match('/[0-9]/u',$token)):                         // BINARY
                $R = array_pop($rStk);
				$L = array_pop($rStk);
				$rStk[] = "(".$L." ".BINARIES_SETEXP[$token]." ".$R.")";
                break;
           case ($token === '!'):                               // UNARY
                $R = array_pop($rStk);						
				$rStk[] = $R."^c";	
                break;
            default:
                echo "Error in setexpToInfix: unexpected token '$token' encountered.";
                return false;
                break;
        }
    }
    $retval = $rStk[0];
    $retval = ($retval[-1] == ")") ? substr($retval,1,strlen($retval)-2) : $retval;    // Remove outer parens if they exist
	return $retval;
}

// POSTFIX MAKEPRETTY FUNCTION
// Input: A postfix array (assumed valid)
// Output: A string that can be displayed
function setexpPostfixMakePretty($exp){
    $exp = setexpToInfix($exp);
    $exp = str_replace("ominus","⊖",$exp);

    
    return $exp;
}

//////////////////////
// PUBLIC FUNCTIONS //
//////////////////////

// EQUALS FUNCTION
// ** Public **
// Input: Two string expressions, $vars = An array/list of variables in the expressions, $contents = an array of array/lists: the entry in $contents[$i] is the set of elements within variable $vars[$i]. $universe = the universal set.
// Note: If contents and universe are both empty, then we will determine if they are always equal (e.g. A' u B' always equals (A n B)')
// Output: true if they are equal, false otherwise
function setexpequals($exp1,$exp2,$vars,$contents=[],$universe=[]){
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $universe = (is_array($universe) ? $universe : explode(',',$universe));
    if(!is_array($contents)){
        echo 'Error in setexpequals: $contents must be an array of arrays or an array of lists.';
        return FALSE;
    }
    if(count($contents) != 0 && count($contents) != count($vars)){
        echo 'Error in setexpequals: $contents must either be empty, or an array with the same number of elements as $vars.';
        return FALSE;
    }
    foreach($contents as &$value){
        if($value == ""){
            $value = array();
        }
        $value = (is_array($value) ? $value : explode(',',$value));
        if(count(array_diff($value,$universe))>0){
            echo "Error in setexpequals: One of the elements within the contents array is not contained within the universe.";
            return FALSE;
        }
    }
    $exp1 = setexpToPostfix($exp1);
    $exp2 = setexpToPostfix($exp2);
    // If contents is empty (not provided), we work in full generality. This means we populate the universe with 2^|vars| elements (0 to pow(2,|vars|-1)), and populate the contents of the ith variable with values that have a 1 in the ith position of the binary representation the elements in universe.
    if($contents == []){
        $numvars = count($vars);
        $universe = range(0,pow(2,$numvars)-1);
        for($i = 0; $i < $numvars; $i++){
            $contents[$i] = array();
            foreach($universe as $number){
                $binary = str_pad(decbin($number),$numvars,"0",STR_PAD_LEFT);
                if($binary[$i]==1){
                    $contents[$i][] = $number;
                }
            }
        }
    }
    $vardict = array();
    for($i = 0; $i < count($vars); $i++){
        $vardict[$vars[$i]] = $contents[$i];
    }
    $eval1 = setexpPostfixEvaluate($exp1,$vardict,$universe);
    $eval2 = setexpPostfixEvaluate($exp2,$vardict,$universe);
    if(count(array_diff($eval1,$eval2))>0){
        return FALSE;
    }
    if(count(array_diff($eval2,$eval1))>0){
        return FALSE;
    }
    return TRUE;
}

// SUBSET FUNCTION
// ** Public **
// Input:  Two string expressions, $vars = An array/list of variables in the expressions, $contents = an array of array/lists: the entry in $contents[$i] is the set of elements within variable $vars[$i]. $universe = the universal set.
// Note: If contents and universe are both empty, then we will determine if they are always equal (e.g. A' u B' always equals (A n B)')
// Output: true if exp1 is a subset of exp2
function setexpsubset($exp1,$exp2,$vars,$contents=[],$universe=[]){
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $universe = (is_array($universe) ? $universe : explode(',',$universe));
    if(!is_array($contents)){
        echo 'Error in setexpequals: $contents must be an array of arrays or an array of lists.';
        return FALSE;
    }
    if(count($contents) != 0 && count($contents) != count($vars)){
        echo 'Error in setexpequals: $contents must either be empty, or an array with the same number of elements as $vars.';
        return FALSE;
    }
    foreach($contents as &$value){
        if($value == ""){
            $value = array();
        }
        $value = (is_array($value) ? $value : explode(',',$value));
        if(count(array_diff($value,$universe))>0){
            echo "Error in setexpequals: One of the elements within the contents array is not contained within the universe.";
            return FALSE;
        }
    }
    $exp1 = setexpToPostfix($exp1);
    $exp2 = setexpToPostfix($exp2);
    // If contents is empty (not provided), we work in full generality. This means we populate the universe with 2^|vars| elements (0 to pow(2,|vars|-1)), and populate the contents of the ith variable with values that have a 1 in the ith position of the binary representation the elements in universe.
    if($contents == []){
        $numvars = count($vars);
        $universe = range(0,pow(2,$numvars)-1);
        for($i = 0; $i < $numvars; $i++){
            $contents[$i] = array();
            foreach($universe as $number){
                $binary = str_pad(decbin($number),$numvars,"0",STR_PAD_LEFT);
                if($binary[$i]==1){
                    $contents[$i][] = $number;
                }
            }
        }
    }
    $vardict = array();
    for($i = 0; $i < count($vars); $i++){
        $vardict[$vars[$i]] = $contents[$i];
    }
    $eval1 = setexpPostfixEvaluate($exp1,$vardict,$universe);
    $eval2 = setexpPostfixEvaluate($exp2,$vardict,$universe);
    if(count(array_diff($eval1,$eval2))>0){
        return FALSE;
    }
    return TRUE;
}

// MAKEPRETTY FUNCTION
// ** Public **
// Input: An infix expression (usually user-defined)
// Output: A cleaner version of the same expression, standardized and suitable for display purposes.
function setexpmakepretty($exp){
    return setexpPostfixMakePretty(setexpToPostfix($exp));
}

// EVALUATE FUNCTION
// ** Public **
// Input: $exp is a user-defined expression, $vars is an array/list of variables (alphabetical chars), $contents is an array of arraylists containing the contents of each var. $universe is an array/list of the universal set.
// Output: an array of the elements within $exp
function setexpevaluate($exp,$vars,$contents,$universe){
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    foreach($vars as $var){
        if(strlen($var)!=1){
            echo "Error in setexpevaluate: each item in the vars array must be a single alphabetical character.";
            return false;
        }
    }
    $universe = (is_array($universe) ? $universe : explode(',',$universe));
    foreach($contents as &$content){
        $content = (is_array($content) ? $content : explode(',',$content));
        if(array_diff($content,$universe) != []){
            echo "Error in setexpevaluate: there are entries in the contents array that are not within the universe.";
            return false;
        }
    }
    if(count($vars) != count($contents)){
        echo "Error in setexpevaluate: the vars array and contents array must be the same size";
        return false;
    }
    $exp = setexpToPostfix($exp);
    $vardict = array();
    for($i = 0; $i < count($vars); $i++){
        $vardict[$vars[$i]] = $contents[$i];
    }
    return setexpPostfixEvaluate($exp,$vardict,$universe);
    
}

// STEPS FUNCTION
// ** Public **
// Input: A user-created (infix) expression
// Outpout: An array of subexpressions that must be computed, in order, to evaluate. For example "(A nn B^c)^c" would return array("B^c", "A nn B^c", "(A nn B^c)^c")
// Reason: Useful for building truth tables and showing the steps in the solution
function setexpsteps($exp){
    $exp = setexpToPostfix($exp);
    $retArray = setexpPostfixSteps($exp);
    foreach($retArray as $k => $v){
        $retArray[$k] = setexpPostfixMakePretty($retArray[$k]);
    }
    return $retArray;
}


// RANDOM setexp EXPRESSION FUNCTION
// ** Public **
// Input: A set of variables, a set of operations, the number of binary operations desired, the number of unary operations desired.
// Output: A (hopefully) intelligent random expression in infix using the variables and operations requested
function setexprand($vars,$ops,$bnum,$unum){
	// $vnum = an array/list of permissible varible names
	//		RESTRICTION: must be an array/list of size 2, 3 or 4
	//		RESTRICTION: must consist only of alphabetical characters
	// $ops = an array/list of permissible binary operations
	//		RESTRICTION: must be of the list "nn,uu,ominus,-"
	//		RESTRICTION: must have size at least 2
	// $bnum = the number of binary operations desired, either a number or an array/list of possible values
	//		RESTRICTION: can only contain numbers 1 through 4
	//		NOTE: If it is a number, it is modified immediately to a single-element array
	// $unum = the number of unary (negation) operations desired, either a number or an array/list of possible values
	//		RESTRICTION: all values must be between 0 and 2*min($bnum)+1
	//		NOTE: If it is a number, it is modified immedaitely to a single-element array

    global $RND;

	// INPUT CHECK //
	// $var check
	$vars = (is_array($vars) ? $vars : explode(',',$vars));
	if(count($vars) < 2 || count($vars) > 4){
		echo "Error in setexprandexp: vars array must be of size 2, 3 or 4";
        return false;
	}
    foreach($vars as $var){
        if(strlen($var) != 1 || !preg_match('/[A-Za-z]{1}/u',$var)){
            echo "Error in setexprandexp: vars array/list must contain only single alphabetical characters";
            return FALSE;
        }
    }    
	// $op check and convert to numerical codes
	$ops = (is_array($ops) ? $ops : explode(',',$ops));
	foreach($ops as $entry){
		if(!in_array($entry,BINARIES_SETEXP) && !in_array($entry,array_keys(ALTS_SETEXP))){
			echo "Error in setexprandexp: Every entry in ops must be a valid binary operation (see manual for a list)";
            return false;
		}
	}
    for($i = 0; $i < count($ops); $i++){
        if(in_array($ops[$i],array_keys(ALTS_SETEXP))){
            $ops[$i] = ALTS_SETEXP[$ops[$i]];
        }
        if(in_array($ops[$i],BINARIES_SETEXP)){
            $ops[$i] = array_search($ops[$i],BINARIES_SETEXP);
        }
    }
	// $bnum check
	$bnum = (is_array($bnum) ? $bnum : explode(',',$bnum));
	foreach($bnum as $entry){
		if(!in_array($entry,array(1,2,3,4))){
			echo "Error in setexprandexp: Every entry in bnum must be an integer between 1 and 4";
            return false;
		}
	}
	// $unum check
	$minbnum = 2*min($bnum) + 1;
	$unum = (is_array($unum) ? $unum : explode(',',$unum));
	foreach($unum as $entry){
		if(!ctype_digit($entry) || $entry < 0 || $entry > $minbnum){
			echo "Error in setexprandexp: Every entry in unum must be an integer between 1 and 2*(minimum of bnum) + 1";
            return false;
		}
	}
    // CREATE EXPRESSION //

	//1. Select an appropraite skeleton expression from BONEYARD_SETEXP
	//2. Insert negation symbols
	//3. Replace the *'s and #'s with variables and operation codes 'intelligently'

	// 1. SELECT SKELETON EXPRESSION //
	// $skeletonList contains a list of possible skeletons. Duplicates based on weights to increase their odds of being picked, then picks a random skeleton from the list
	// Then selects a random tree
	$skeletonList = array();
	foreach($bnum as $b){
		for($n = 0; $n < count(BONEYARD_SETEXP[$b]); $n++){
			for($k = 0; $k < WEIGHT_SETEXP[$b][$n]; $k++){
				$skeletonList[] = BONEYARD_SETEXP[$b][$n];
			}
		}
	}
	$skeleton = $skeletonList[$RND->array_rand($skeletonList)];
    // 2. INSERT NEGATIONS //
    // In postfix, negations can go after any * or any #. There are strlen(skeleton) gaps to place $unum negation symbols. Select the positions first (so we don't get double negatives)
    $unum = $unum[$RND->array_rand($unum)];
    $negPos = array();
    /* rewrite below
    while(count($negPos)<$unum){
        $rand = $RND->rand(1,strlen($skeleton));
        $negPos[$rand] = $rand;         // Ensures no duplicates
    }
    */
    $rndpos = diffrands(1, strlen($skeleton), $unum);
    foreach ($rndpos as $rand) {
        $negPos[$rand] = $rand;
    }
    rsort($negPos);
    foreach($negPos as $k => $v){
        $skeleton = substr_replace($skeleton,'!',$v,0);
    }
    $skeleton = str_split($skeleton);
	// 3. REPLACE * with vars and # with ops intelligently //
    return setexpPostfixMakePretty(setexpFillRandSkeleton($skeleton,$vars,$ops));
}

// RANDOMLY FILL SKELETON
// Input: A skeleton expression ARRAY (postfix expression with *,#,- to denote blank symbols), variables to replace *s, op CODES to replace #s
// Output: Each * is replaced by var, Each # is replaced by an op. Prevents both sides of any binary operation being equivalent (no A OR A for example)
// Reason: Used in setexprandexp
function setexpFillRandSkeleton($skeleton,$vars,$ops){
    global $RND;
    // Input check
    if(!is_array($skeleton) || !is_array($vars) || !is_array($ops)){
        echo "Error in setexpFillRandSkeleton: Each input must be an array";
    }
    // If skeleton has only one * it cannot contain any binaries. Base case.
    if(array_count_values($skeleton)["*"]==1){
        foreach($skeleton as $k=>$v){
            if($v=="*"){                                    // Replace * with variable
                $skeleton[$k] =$RND->array_rand(array_flip($vars));               
            }
        }
        return $skeleton;
    }

    // Second base case: Two *'s only. This avoids the 'random brute force' approach used for longer expressions, so it runs faster.
    if(array_count_values($skeleton)["*"]==2){
        $var = $RND->array_rand(array_flip($vars),2);             // Choose two different variables
        $RND->shuffle($var);
        foreach($skeleton as $k=>$v){
            if($v=="*"){                                    // Replace * with variable
                $skeleton[$k] = array_pop($var);                
            }
            elseif($v == "#"){                              // Replace # with operation code
                $skeleton[$k] = $ops[$RND->array_rand($ops)];
            }
        }
        return $skeleton;
    }
    // Third base case: Three *'s and |vars|>=3. This forces three sets to be used if three exist, creating better expressions.
    if(array_count_values($skeleton)["*"]==3 && count($vars)>2 && count($ops)>1){
        $var = $RND->array_rand(array_flip($vars),3);             // Choose three different variables
        
        $op = $RND->array_rand(array_flip($ops),2);               // Choose two different operations
        $RND->shuffle($var);
        $RND->shuffle($op);
        foreach($skeleton as $k=>$v){
            if($v=="*"){                                    // Replace * with variable
                $skeleton[$k] = array_pop($var);                
            }
            elseif($v == "#"){                              // Replace # with operation code
                $skeleton[$k] = array_pop($op);
            }
        }
        return $skeleton;
    }
    // More than two *s operates recursively
    $operands = setexpGetOperands($skeleton);
    // If operands is size 1 then last operation was unary
    if(count($operands)==1){
        return array_merge(setexpFillRandSkeleton($operands[0],$vars,$ops),array('!'));
    }
    // If operands is size 2, then last operation was binary.
    // Build general sets for each variable, so we can determine if our expression will be too easy later
    $numvars = count($vars);
    $universe = range(0,pow(2,$numvars)-1);
    for($i = 0; $i < $numvars; $i++){
        $contents[$vars[$i]] = array();
        foreach($universe as $number){
            $binary = str_pad(decbin($number),$numvars,"0",STR_PAD_LEFT);
            if($binary[$i]==1){
                $contents[$vars[$i]][] = $number;
            }
        }
    }
    $L = $operands[0];
    $L = setexpFillRandSkeleton($L,$vars,$ops);
    $evalL = setexpPostfixEvaluate($L,$contents,$universe);

    // Redo $L if it is a empty or the full universe. Try 10,000 times only to avoid infinite loops (although this shouldn't occur)
    // Pretty lazy, but honestly probably more elegant than trying to create a more sophisticated process
    for($i = 0; $i<10000; $i++){
        if(count($evalL)==0 || count($evalL)==count($universe)){              // If the left operand is empty or the full universe, redo constructing it
            $L = setexpFillRandSkeleton($operands[0],$vars,$ops);
			$evalL = setexpPostfixEvaluate($L,$contents,$universe);
        }
        else{
            break;
        }
    }
	$R = $operands[1];
    $R = setexpFillRandSkeleton($R,$vars,$ops);
    $evalR = setexpPostfixEvaluate($R,$contents,$universe);
    // Redo $R (up to 10,000 times) if the following conditions occur
    
    // a. $R is a empty or universe
    // b. $L and $R are equivalent. (creates uninteresting expressions)
    // c. $L and $R are complements. (also creates uninteresting expressions)
    for($i = 0; $i<10000; $i++){
        if(count($evalR)==0 || count($evalR)==count($universe)){                        // right operand is a tautology or contradiction, redo.
            $R = setexpFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = setexpPostfixEvaluate($R,$contents,$universe);
        }
        elseif(count($evalL)==count($evalR) && !array_diff($evalL,$evalR)){                                         // the left and right operands ended up being setexpally equivalent, redo.
            $R = setexpFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = setexpPostfixEvaluate($R,$contents,$universe);
        }
        $evalNR = array_diff($universe,$evalR);                                                         
        if(count($evalL)== count($evalNR) && !array_diff($evalL,$evalNR)){                                          // the left and right operands ended up being negations, redo.
            $R = setexpFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = setexpPostfixEvaluate($R,$contents,$universe);
        }
        else{
            break;
        }
    }
	// Now select an operation to join the operands together. 
    // If L and R both have operations, and the 'last' ones of each are the same then the joining operation can't be the same as those two, unless it's a minus. 
    // THis avoids boring things like (p and q) and (q and r)
    if(count($L)>1 && count($R)>1 && end($L)==end($R) && BINARIES_SETEXP[end($L)]!="-"){
        $allowedops = array_diff($ops,array(end($L)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    elseif(count($L)>1 && count($R)<3 && BINARIES_SETEXP[end($L)]!="-"){
        $allowedops = array_diff($ops,array(end($L)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    elseif(count($R)>1 && count($L)<3 && BINARIES_SETEXP[end($R)]!="-"){
        $allowedops = array_diff($ops,array(end($R)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    else{
        $O = $ops[$RND->array_rand($ops)];
    }
    return array_merge($L,$R,array($O));
}

// setexp LEGEND
// ** Public **
// Input: None.
// Output: A string containing a small legend giving keyboard commands to enter setexp symbols
function setexplegend(){
    return '<p>The following words can be typed to create set operaions instead of using the pop-up keyboard.</p>
    <table style="border: 2px solid; text-align:left; border-collapse:collapsed;">
    <tr>
    <th style="border-bottom: 1px solid; text-align:left;">Type...</th><th style="border-bottom: 1px solid; text-align:left;">...to Create</th>
    </tr>
    <tr><td>nn</td><td>∩</td></tr>
    <tr><td>uu</td><td>∪</td></tr>
    <tr><td>ominus</td><td>⊖</td></tr>
    </table>';
}

// VENN2_SETEXPDIAGRAM
// ** Public **
// Input: $vars is a 2-element array/list of alphabetical characters. shade is the region to shade. $labelA and $labelB are the labels that appear in the diagram for A and B. width is width in px, height is height in px.
// Output: An image of the 2-circle Venn diagram
function venn2diagram($vars,$shade="",$labels="",$size=200){
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $labels = (is_array($labels) ? $labels : explode(',',$labels));
    if(count($vars)!=2){
        echo "Error in venn2diagram: Variable array/list must contain only two items.";
        return false;
    }
    $A = $vars[0];
    $B = $vars[1];
    $labelA = $A;
    $labelB = $B;
    if(count($labels) == 2){
        $labelA = $labels[0];
        $labelB = $labels[1];
    }
    // Input Check
    if(!ctype_alpha($A) || strlen($A)!=1 || !ctype_alpha($B) || strlen($B)!=1){
        echo "Error in venn2diagram: Set symbol inputs must be single alphabetical characters.";
        return false;
    }

    $HEADER = 'initPicture(-3.5,3.5,-3.5,3.5);strokewidth="0";fill="red";';
    $LABELS = 'strokewidth="5";fill="none";rect([-3.4,-1.9],[3.4,3.4]);circle([-1,0.73],2);circle([1,0.73],2);text([-1.5,2],"'.$labelA.'");text([1.5,2],"'.$labelB.'");';
    
    // If nothing to shade, done
    if($shade == ""){
        $script = $HEADER.$LABELS;
        return showasciisvg($script,$size,$size);
    }
    // Else let A = {1,2}, B = {2,3} and U={0,1,2,3} and compute the members of $shade, the members correspond to the regions of VENN2_SETEXP constant to shade
    $shade = setexpToPostfix($shade);
    $vars = array($A => array(1,2), $B=>array(2,3));
    $regions = setexpPostfixEvaluate($shade,$vars,array(0,1,2,3));
    $DRAW = "";
    foreach($regions as $region){
        $DRAW = $DRAW.VENN2_SETEXP[$region];
    }
    $script = $HEADER.$DRAW.$LABELS;
    return showasciisvg($script,$size,$size);
}

// VENN3_SETEXPDIAGRAM
// ** Public **
// Input: $A,$B,$C are variable names (alphabetical chars) for the two circles. shade is the region to shade. $labelA and $labelB and $labelC are the labels that appear in the diagram for A and B. width is width in px, height is height in px.
// Output: An image of the 3-circle Venn diagram
function venn3diagram($vars,$shade="",$labels="",$size=250){
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $labels = (is_array($labels) ? $labels : explode(',',$labels));
    if(count($vars)!=3){
        echo "Error in venn3diagram: Variable array/list must contain three items.";
        return false;
    }
    $A = $vars[0];
    $B = $vars[1];
    $C = $vars[2];
    $labelA = $A;
    $labelB = $B;
    $labelC = $C;
    if(count($labels) == 3){
        $labelA = $labels[0];
        $labelB = $labels[1];
        $labelC = $labels[2];
    }
    // Input Check
    if(!ctype_alpha($A) || strlen($A)!=1 || !ctype_alpha($B) || strlen($B)!=1 || !ctype_alpha($C) || strlen($C)!=1){
        echo "Error in venn2diagram: Set symbol inputs must be single alphabetical characters.";
        return false;
    }
    $HEADER = 'initPicture(-3.5,3.5,-3.5,3.5);strokewidth="0";fill="red";';
    $LABELS = 'strokewidth="5";fill="none";rect([-3.4,-3.4],[3.4,3.4]);circle([-1,0.73],2);circle([1,0.73],2);circle([0,-1],2);text([-1.5,2],"'.$labelA.'");text([1.5,2],"'.$labelB.'");text([0,-2.5],"'.$labelC.'");';
    // If nothing to shade, done
    if($shade == ""){
        $script = $HEADER.$LABELS;
        return showasciisvg($script,$size,$size);
    }
    $shade = setexpToPostfix($shade);
    $vars = array($A => array(1,2,4,5), $B=>array(2,3,5,6), $C=>array(4,5,6,7));
    $regions = setexpPostfixEvaluate($shade,$vars,array(0,1,2,3,4,5,6,7));
    $DRAW = "";
    foreach($regions as $region){
        $DRAW = $DRAW.VENN3_SETEXP[$region];
    }
    $script = $HEADER.$DRAW.$LABELS;
    return showasciisvg($script,$size,$size);
    
}
?>
