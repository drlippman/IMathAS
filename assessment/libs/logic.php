<?php
/* This library is used to handle logical expressions.
Useful for creating truth tables checking logical equivalencies, etc.
Updated: April 11, 2022
*/


global $allowedmacros;
array_push($allowedmacros,"logiclegend","logicrand","logicmakepretty","logicevaluate","logicevaluateall","truthtable","logicsteps","logicallyequivalent","logicallyimplies","truthtableanswers");


///////////////
// CONSTANTS //
///////////////

// BONEYARD_LOGIC[b] is an array of 'skeleton' strings containing the all possible postfix binary expressions with b operations.  Variable positions are denoted by *.  Operations by #.
const BONEYARD_LOGIC = array(
					array("*"),
					array("**#"),
					array("**#*#","***##"),
					array("**#*#*#","***##*#","**#**##","***#*##","****###"),
					array("**#*#*#*#","**#*#**##","**#**##*#","**#**#*##","**#***###","***##*#*#","***##**##","***#*##*#","***#*#*##","***#**###","****###*#","****##*##","****#*###","****#*###","*****####"));			
// WEIGHT_LOGIC[b] is an array of weights for each skeleton in BONEYARD_LOGIC[b].  This is useful for getting random expressions, so more interesting ones occur more frequently. Higher weights = higher likelihood of being selected.
const WEIGHT_LOGIC = array(array(1),array(1),array(1,1),array(1,1,3,1,1),array(1,3,2,3,3,2,3,2,2,2,2,2,2,1));
// UNARIES_LOGIC gives all 'proper' unary operations...which is just neg.
const UNARIES_LOGIC = array("neg");
// BINARIES_LOGIC array gives 'proper' binary operations. If more are added later this must be of size 10 or less for this library to function properly!
const BINARIES_LOGIC = array("wedge","oplus","vee","implies","iff");
// ALTS_LOGIC gives other words that can be used by the user and directs them to the proper one above
// **Careful**: 'xor' must be before 'or' below, otherwise when replacing alt words out the 'or' in 'xor' would get treated as an operation!
const ALTS_LOGIC = array( "^^"=> "wedge", "and" => "wedge", "∧"=>"wedge", "\\wedge" => "wedge",
                    "xor" => "oplus", "⊕" => "oplus",  "\\oplus" => "oplus",
                    "or" => "vee", "vv" => "vee", "∨"=>"vee", "\\vee" => "vee",
                    "ifthen" =>"implies", "->" => "implies", "=>" => "implies", "→" => "implies", "⇒" => "implies", "\\implies"=>"implies",
                    "<->" =>"iff", "<=>" => "iff", "⇔" => "iff", "↔" => "iff", "\\iff"=>"iff",
                    "not" =>"neg", "¬" => "neg", "\\neg"=>"neg");
// PRECEDENCE_LOGIC gives the precidence of the operations. Lower is higher precedence.
const PRECEDENCE_LOGIC = array("neg"=>0,"wedge"=>1,"vee"=>1,"oplus"=>1,"implies"=>2,"iff"=>3);

///////////
// NOTES //
///////////

// In the algorithms, binary operations are replaced with numbers, based on their position in the BINARIES_LOGIC array (ALT words are immediately swapped out for a word in BINARIES_LOGIC)
// Unary operations are replaced with '-'
// Variables are single alphabetical characters
// Unfortunately, this means that TRUE and FALSE can't be represented by a 1 and 0 (as those are the operations 'vee' and 'wedge'), nor can it be represented by T and F (as those are reserved for variables). We use '+' for TRUE and '_' for FALSE (since - indicates negation)
// Functions work primarily with arrays of tokens. User input is immediately converted to such, and any output is only converted back to a string at the very end.

///////////////////////
// PRIVATE FUNCTIONS //
///////////////////////

// GET OPERANDS
// Input: A postfix expression array (assumed valid)
// Output: If the expression is just a character, returns that character. If it ends in a unary, returns a single-element array with the unary removed. Otherwise, returns a two-element array (L,R) containing the left and right operands of the last binary
// Reason: For evaluation and parsing out the steps
function logicGetOperands($exp){
    if(!is_array($exp)){
        echo "Error in logicGetOperands: expects an array of tokens";
        return false;
    }
    if(count($exp) == 1){
        return $exp[0];
    }
    if($exp[count($exp)-1] == '-'){
        array_pop($exp);
        return array($exp);
    }
    $balance = 1;
    $Rindex = 0;
    for($i = count($exp)-2; $i >= 0; $i--){
        if(ctype_alpha($exp[$i]) || $exp[$i] == "*"){
            $balance--;
        }        
        elseif($exp[$i] == "-"){
        }
        else{
            $balance++;
        }
        if($balance == 0){
            $Rindex = $i;
            $i = -1;
        }
    }
    return array(array_slice($exp,0,$Rindex),array_slice($exp,$Rindex,count($exp)-$Rindex-1));
}


// POSTFIX EVALUATE FUNCTION
// Input: exp is a POSTFIX array.
// Output: an array of postfix arrays containing the steps needed to be done (in order) to compute exp
function logicPostfixSteps($exp){
    if(!is_array($exp)){
        echo "Error in logicPostfixSteps: Input is not an array";
        return false;
    }
    if(count($exp) == 1){           // Input is just a single symbol, and thus has no steps
        return array();
    }
    $operands = logicGetOperands($exp);
    
    if(count($operands) == 1){      // Last operation as a negation
        return array_merge(logicPostfixSteps($operands[0]),array($exp));
    }
    $retval = array_merge(logicPostfixSteps($operands[0]),logicPostfixSteps($operands[1]),array($exp));
    return $retval;
}

// POSTFIX EVALUATE FUNCTION
// Input: exp is a POSTFIX array. vars is an array of possible variables within exp, and truths is an array with truth values (1/0) for each of the vars with the same index
// Output: 1 or 0, the truth value of the expression with the given truth values of its components
// Reason: Easier to implement than infix evaluation algorithms
function logicPostfixEvaluate($exp,$vars,$truths){
	if(!is_array($exp) || !is_array($vars) || !is_array($truths)){
        echo "Error in logicPostfixEvaluate: An input is not an array.";
        return false;
    }
	if(count($vars) != count($truths)){
		echo "Error in logicPostfixEvaluate: vars and truths have mismatched size. Aborting.";
		return FALSE;
	}
	foreach($exp as $token){
		if(ctype_alpha($token) && !in_array($token,$vars)){
			echo "Error in logicPostfixEvaluate: expression contains variables not listed in vars array";
			return FALSE;
		}
	}
	// Error check complete. Now replace vars with + for TRUE and _ for FALSE (since digitis and characters and - can't be used, as they represent binaries, variables and negations)
	for($j = 0 ; $j < count($exp); $j++){
        for($i = 0; $i < count($vars); $i++){
            if($exp[$j] == $vars[$i]){
                if($truths[$i]== 1){
                    $exp[$j] = '+';
                }
                else{
                    $exp[$j] = '_';
                }
            }
        }
    }
	$rStk = array();


	// Since operations are digits and variables are letters we can't use 1 for T and 0 for False (poor planning!)
	// We will use '+' for True and '_' for False
	foreach($exp as $token){
		switch(TRUE){
			case ($token == "+" || $token == "_"):				// TRUTHVALUE
				$rStk[] = $token;
				break;
			case (preg_match('/[0-9]/',$token)):				// BINARY
				$R = array_pop($rStk);
				$L = array_pop($rStk);
				$O = BINARIES_LOGIC[$token];
				if($O == "wedge"){
					$rStk[] = ($L == "+" && $R == "+") ? '+' : '_';
				}
				elseif($O == "oplus"){
					$rStk[] = ($L != $R) ? '+' : '_';
				}
				elseif($O == "vee"){
					$rStk[] = ($L == "+" || $R == "+") ? '+' : '_';
				}
				elseif($O == "implies"){
					$rStk[] = ($L == "_" || $R == "+") ? '+' : '_';
				}
				elseif($O == "iff"){
					$rStk[] = ($L == $R) ? '+' : '_';
				}
				break;
			case ($token == "-"):
				$R = array_pop($rStk);
				$rStk[] = ($R == "+") ? '_' : '+';
				break;
			default:
				echo "Error in postfixEvalaute: Expression contains an invalid token '$token'.";
				break;
		}
	}
	if($rStk[0] == '+'){
		return 1;
	}
	return 0;
}

// POSTFIX EVALUATEALL FUNCTION
// Input: exp is an POSTFIX expression. vars is an array of possible variables within exp
// Output: An array of size 2^|var| bits. The entry in position i is the result of evaluate when the truth values of vars is the binary representation of i. For example, if $vars = [p,q] then the 2rd position of the output would be evaluate exp when p=1 and q=0 (since 2=10 in binary)
function logicPostfixEvaluateAll($exp,$vars){
	if(!is_array($exp) || !is_array($vars)){
        echo "Error in logicPostfixEvaluateAll: An input is not an array.";
        return false;
    }
    $result = array();
    for($i = 0; $i < pow(2,count($vars)); $i++){
        $truths = str_split(str_pad(decbin($i),count($vars),"0",STR_PAD_LEFT));     // Create truths array
        $result[] = logicPostfixEvaluate($exp,$vars,$truths);
    }
    return $result;
}

// TO POSTFIX FUNCTION
// Input: An Infix STRING (generally user-created)
// Output: A valid postfix array of the same expression. Attempts to 'clean' the expression by removing silly things (double negations, double parens, excessive spaces, etc.)
// Reason: The user can input some dumb things, this attempts to make it less dumb and prepare for processing in other algorithms.
function logicToPostfix($exp){
    //1. Replace operations with numerical codes, remove spaces, change brackets to parens, remove double negs and silly paren placements and non-valid characters
    //2. Ensure the result is a valid infix string
    //3. Convert to postfix

	// Convert any word binary operation to a numerical value. Convert any 
	// 0 = wedge, 1 = oplus, 2 = or, etc. (the keys of the BINARIES_LOGIC array)
    $exp = str_replace(array_keys(ALTS_LOGIC),array_values(ALTS_LOGIC),$exp);
    $exp = str_replace(array_values(BINARIES_LOGIC),array_keys(BINARIES_LOGIC),$exp);
	$exp = str_replace("neg","-",$exp);
	// Convert brackets to parens
	$exp = str_replace(array("[","]"),array("(",")"),$exp);
	// Remove spaces and silly parens
	$exp = str_replace(" ","",$exp);
	$exp = str_replace("()","",$exp);

    // Remove any non-alpnumeric, non-paren, non-minus characters
    $exp = preg_replace("/[^\w\(\)-]/","",$exp);
	// Remove outer parens of type (V) or (unary V), or ((V)), or (unary (V)) etc.
	$regex = "/\(([\*A-Za-z-]{1,})\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\1',$exp,1);
	}
    // Remove double parens around expressions without parens
    $regex = "/\((\()([\#\*\w-]{1,})(\))\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\1\2\3',$exp,1);
	}
    // Remove double negations
	$exp = preg_replace('/-{2}/','',$exp);
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
            echo "Error in exptotree: Parentheses aren't balanced.";
            return FALSE;
        }
    }
    if($balance < 0){
        echo "Error in logicToPostfix: Parentheses aren't balanced.";
        return FALSE;
    }
    // Ensures no two variables appear without an operation between them
    if(preg_match('/[A-Za-z][^0-9]*[A-Za-z]/u',$exp)){
        echo "Error in logicToPostfix: Two adjacent variables appear without a binary operation between them.";
        return false;
    }
    // Ensures no two operations appear without a variable between them
    if(preg_match('/[0-9][^A-Za-z]*[0-9]/u',$exp)){
        echo "Error in logicToPostfix: Two adjacent binary operations appear without a variable between them.";
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
            case (preg_match('/[A-Za-z]/u',$token)):                        // VARIABLE CASE
                $rStk[] = $token;
                // If the top of oStk is a -, pop it and put it on rStk
                if(!empty($oStk) && end($oStk) == "-"){
                    $rStk[] = array_pop($oStk);
                }
                break;
            case ($token == "("):                                             // OPEN PAREN CASE
                $oStk[] = $token;
                break;
            case (ctype_digit($token) || $token == "#"):                         // BINARY
                
                while(count($oStk)>0 && (end($oStk) != "(") && (PRECEDENCE_LOGIC[BINARIES_LOGIC[end($oStk)]] <= PRECEDENCE_LOGIC[BINARIES_LOGIC[$token]])){ // POP IF TOP OF OSTK HAS GREATER PRIORITY
                    $rStk[] = array_pop($oStk);
                }
                $oStk[] = $token;
                break;
            case ($token == "-"):                                              // UNARY
                $oStk[] = "-";
                break;
                
            case ($token == ")"):                                             // RIGHT PAREN
                while(count($oStk)>0 && end($oStk) != "("){
                    $rStk[] = array_pop($oStk);
                }
                // Pop the (
                array_pop($oStk);
                // If the top of oStk is a -, pop that too and put it on rStk
                if(!empty($oStk) && end($oStk) == "-"){
                    $rStk[] = array_pop($oStk);
                }
                break;
            default:
                echo "Error in logicToPostfix: Unexpected token '$token' found.";
                return false;
                break;
        }
    }
    return $rStk;
}

// logicToInfix FUNCTION
// Input: Any VALID postfix expression array
// Output: An infix expression STRING, for answers array etc. (but not for display, since for some reason 'and,or,etc.' doesn't autoconvert to \wedge,\vee,etc. in question syntax...)
// Reason: Converts postfix to infix for use by user and also standardizes the format
function logicToInfix($exp){
	foreach($exp as $token){
        switch(TRUE){
           case (preg_match('/[A-Za-z]/u',$token)):         // VARIABLE CASE
                $rStk[] = $token;
                break;
           case (preg_match('/[0-9]/u',$token)):                         // BINARY
                $R = array_pop($rStk);
				$L = array_pop($rStk);
				$rStk[] = "(".$L." ".BINARIES_LOGIC[$token]." ".$R.")";
                break;
           case ($token == "-"):                               // UNARY
                $R = array_pop($rStk);						
				$rStk[] = "neg ".$R;	
                break;
            default:
                echo "Error in logicToInfix: unexpected token '$token' encountered.";
                return false;
                break;
        }
    }
    $retval = $rStk[0];
    $retval = ($retval[0] == "(") ? substr($retval,1,strlen($retval)-2) : $retval;    // Remove outer parens if they exist
	return $retval;
}

// POSTFIX MAKEPRETTY FUNCTION
// Input: A postfix array (assumed valid)
// Output: A string that can be displayed
function logicPostfixMakePretty($exp){
    $exp = logicToInfix($exp);
    foreach(BINARIES_LOGIC as $v){
        $exp = str_replace($v,"\\".$v,$exp);
    }
   
    $exp = str_replace("neg","\\neg",$exp);
    
    return $exp;
}

//////////////////////
// PUBLIC FUNCTIONS //
//////////////////////

// EQUIV FUNCTION
// ** Public **
// Input: Two string expressions
// Output: true if they are equivalent, false otherwise
// Note: same as evaluateAll(exp1,vars) && evaluateAll(exp2,vars) for a given variable array
function logicallyequivalent($exp1,$exp2,$vars){
    $exp1 = logicToPostfix($exp1);
    $exp2 = logicToPostfix($exp2);
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $retval = (logicPostfixEvaluateAll($exp1,$vars) == logicPostfixEvaluateAll($exp2,$vars)) ? 1 : 0;
    return $retval;
}

// LOGICAL IMPLICATION FUNCTION
// ** Public **
// Input: Two expressions
// Output: true if exp1 logically implies exp2, false otherwise
function logicallyimplies($exp1,$exp2,$vars){
    $exp1 = logicToPostfix($exp1);
    $exp2 = logicToPostfix($exp2);
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $eval1 = logicPostfixEvaluateAll($exp1,$vars);
    $eval2 = logicPostfixEvaluateAll($exp2,$vars);
    for($i = 0; $i < count($eval1); $i++){
        if($eval1[$i] == 1 && $eval2[$i] == 0){
            return 0;
        }
    }
    return 1;
}

// MAKEPRETTY FUNCTION
// ** Public **
// Input: An infix expression (usually user-defined)
// Output: A cleaner version of the same expression, standardized and suitable for display purposes.
function logicmakepretty($exp){
    return logicPostfixMakePretty(logicToPostfix($exp));
}

// STEPS FUNCTION
// ** Public **
// Input: A user-created (infix) expression
// Outpout: An array of subexpressions that must be computed, in order, to evaluate. For example "neg(A and neg B)" would return array("neg B", "A and neg B", "neg(A and neg B)")
// Reason: Useful for building truth tables and showing the steps in the solution
function logicsteps($exp){
    $exp = logicToPostfix($exp);
    $retArray = logicPostfixSteps($exp);
    foreach($retArray as $k => $v){
        $retArray[$k] = logicPostfixMakePretty($retArray[$k]);
    }
    return $retArray;
}

// TRUTH TABLE FUNCTION
// ** Public **
// Input: exp is an expression to generate a truth table for. showresult=TRUE means the truth table will be filled with the correct values (T or F), 
//      otherwise it will be filled with empty answerboxes. if showsteps=TRUE it will create a column for each preceding step to generate $exp.
// Output: an HTML table. If showresult=FALSE then answerboxes ([AB#]) will be placed in each cell. 
//      offset will offset the number within the answerbox (i.e. the table will start with [AB$offset] as the first answerbox)
//      If showsteps is true then a column will be made for each step to generate the entire expression.
// Note: This will not change the value of anstypes, or questions. Use truthtableanswercount to get the total number of answerboxes used.
function truthtable($exp, $showresult=FALSE,$showsteps=TRUE,$offset=0){
	// Table stylesheet
    $stylesheet = '<style type="text/css">th, td {border:2px solid; text-align: left; padding: 10px; text-align: center; vertical-align: center;} table {border: 0px solid; margin: 3px; border-collapse:collapse; border-style: hidden;} </style>';
    // Convert to postfix
    $exp = logicToPostfix($exp);
    // ID variables
    $vars = array();
    // Find variables
    $vars = array();
    foreach($exp as $token){
        if(preg_match('/[A-Za-z]{1}/',$token) && !in_array($token,$vars)){
            $vars[] = $token;
        }
    }
    sort($vars);
    // Get the column entries
    $steps = $vars;
    if(!$showsteps){
        $steps[] = logicPostfixMakePretty($exp);
    }
    else{
        $steps = array_merge($steps,array_map('logicPostfixMakePretty',logicPostfixSteps($exp)));
    }
    // Table header
    $header = "<table><tr>";
    foreach($steps as $step){
        $header = $header."<th>`".$step."`</th>";
    }
    $header = $header."</tr>";
    // $abCount determines the number value for each answerbox
    $abCount = $offset;
    // Build the rows
    for($r = 0; $r < pow(2,count($vars)); $r++){
        $row[$r] = "<tr>";
        $truths = str_pad(decbin(pow(2,count($vars))-1-$r),count($vars),"0",STR_PAD_LEFT);
        $truths = str_split($truths);
        // Establish atomic variable truth values
        for($v = 0; $v < count($vars); $v++){
            $letter = (($truths[$v] == "1") ? "T" : "F");
            $row[$r] = $row[$r]."<td><b>".$letter."</b></td>";
        }
        
        // Establish all step truth values
        foreach($steps as $step){
            if(!in_array($step,$vars)){
                $bin = logicPostfixEvaluate(logicToPostfix($step),$vars,$truths);
                $letter = (($bin == "1") ? "T" : "F");
                if($showresult){
                        $row[$r] = $row[$r]."<td>".$letter."</td>";
                }   
                else{
                    $row[$r] = $row[$r]."<td>[AB$abCount]</td>";
                    $abCount++;
                }
            }
        }
        $row[$r] = $row[$r]."</tr>";
    }
    
    $rows = "";
    foreach($row as $r){
        $rows = $rows.$r;
    }
    $footer = "</table>";
    return $stylesheet.$header.$rows.$footer;
}

// TRUTH TABLE ANSWERS //
// ** Public **
// Input: An expression and whether or not the steps to obtain the expression is to be included
// Output: An array containing the answers to each entry of the answerbox generated by truthtable
// Reason: Allows the user to 'easily' generate solutions to truth table questions
function truthtableanswers($exp,$showsteps=TRUE){
    $exp = logicToPostfix($exp);
    // Find variables
    $vars = array();
    foreach($exp as $token){
        if(preg_match('/[A-Za-z]/u',$token)){
            $vars[] = $token;
        }
    }
    sort($vars);
    $steps = array();
    if(!$showsteps){
        $steps[] = logicPostfixMakePretty($exp);
    }
    else{
        $steps = array_merge($steps,array_map('logicPostfixMakePretty',logicPostfixSteps($exp)));
    }
    // row[i] contains the truth values of row[i] in the table.
    $retanswers = array();

    for($r = 0; $r < pow(2,count($vars)); $r++){
        $truths = str_pad(decbin(pow(2,count($vars))-1-$r),count($vars),"0",STR_PAD_LEFT);
        $truths = str_split($truths);
        
        // Establish all step truth values
        foreach($steps as $step){
            if(!in_array($step,$vars)){
                $retanswers[] = logicPostfixEvaluate(logicToPostfix($step),$vars,$truths);
            }
        }
    }
    return $retanswers;
}

// EVALUATE FUNCTION
// ** Public **
// Input: exp is an INFIX expression. vars is an array of possible variables within exp, and truths is an array with truth values (1/0) for each of the vars with the same index
// Output: 1 or 0, the truth value of the expression with the given truth values of its components
// Reason: Quesiton writing scenarios
function logicevaluate($exp,$vars,$truths){
	$exp = logicToPostfix($exp);
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    $truths = (is_array($truths) ? $truths : explode(',',$truths));
    foreach($vars as $var){
        if(strlen($var) != 1 || !preg_match('/[A-Za-z]{1}/u',$var)){
            echo "Error in evaluate: vars array/list must contain only single alphabetical or greek characters";
            return FALSE;
        }
    }    
    foreach($truths as $truth){
        if($truth != "1" && $truth != "0"){
            echo "Error in evaluate: truths array/list must contain only binary digits.";
            return FALSE;
        }
    }
    return logicPostfixEvaluate($exp,$vars,$truths);
}

// logicevaluateall FUNCTION
// ** Public **
// Input: exp is an INFIX expression. vars is an array of possible variables within exp
// Output: An ARRAY of size 2^|var| bits. The entry in position i is the result of evaluate when the truth values of vars is the binary representation of i. For example, if $vars = [p,q] then the 2rd position of the output would be evaluate exp when p=1 and q=0 (since 2=10 in binary)
function logicevaluateall($exp,$vars){
	$exp = logicToPostfix($exp);
    $vars = (is_array($vars) ? $vars : explode(',',$vars));
    foreach($vars as $var){
        if(strlen($var) != 1 || !preg_match('/[A-Za-z]{1}/u',$var)){
            echo "Error in logicevaluateall: vars array/list must contain only single alphabetical or greek characters";
            return FALSE;
        }
    }    
    return logicPostfixEvaluateAll($exp,$vars);
}

// RANDOM LOGIC EXPRESSION FUNCTION
// ** Public **
// Input: A set of variables, a set of operations, the number of binary operations desired, the number of unary operations desired.
// Output: A (hopefully) intelligent random expression in infix using the variables and operations requested
function logicrand($vars,$ops,$bnum,$unum){
	// $vnum = an array/list of permissible varible names
	//		RESTRICTION: must be an array/list of size 2, 3 or 4
	//		RESTRICTION: must consist only of alphabetical characters
	// $ops = an array/list of permissible binary operations
	//		RESTRICTION: must be of the list "and,or,if,iff,xor"
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
		echo "Error in logicrand: vars array must be of size 2, 3 or 4";
        return false;
	}
    foreach($vars as $var){
        if(strlen($var) != 1 || !preg_match('/[A-Za-z]{1}/u',$var)){
            echo "Error in logicrand: vars array/list must contain only single alphabetical or greek characters";
            return FALSE;
        }
    }    
	// $op check and convert to numerical codes
	$ops = (is_array($ops) ? $ops : explode(',',$ops));
	foreach($ops as $entry){
		if(!in_array($entry,BINARIES_LOGIC) && !in_array($entry,array_keys(ALTS_LOGIC))){
			echo "Error in logicrand: Every entry in ops must be a valid binary operation (see manual for a list)";
            return false;
		}
	}
    for($i = 0; $i < count($ops); $i++){
        if(in_array($ops[$i],array_keys(ALTS_LOGIC))){
            $ops[$i] = ALTS_LOGIC[$ops[$i]];
        }
        if(in_array($ops[$i],BINARIES_LOGIC)){
            $ops[$i] = array_search($ops[$i],BINARIES_LOGIC);
        }
    }
    $ops = array_unique($ops);
    if(count($ops)<2){
        echo "Error in logicrand: ops array/list must have at least two distinct operations.";
        return FALSE;
    }
	// $bnum check
	$bnum = (is_array($bnum) ? $bnum : explode(',',$bnum));
	foreach($bnum as $entry){
		if(!in_array($entry,array(1,2,3,4))){
			echo "Error in logicrand: Every entry in bnum must be an integer between 1 and 4";
            return false;
		}
	}
	// $unum check
	$minbnum = 2*min($bnum) + 1;
	$unum = (is_array($unum) ? $unum : explode(',',$unum));
	foreach($unum as $entry){
		if(!ctype_digit($entry) || $entry < 0 || $entry > $minbnum){
			echo "Error in logicrand: Every entry in unum must be an integer between 1 and 2*(minimum of bnum) + 1";
            return false;
		}
	}
    // CREATE EXPRESSION //

	//1. Select an appropraite skeleton expression from BONEYARD_LOGIC
	//2. Insert negation symbols
	//3. Replace the *'s and #'s with variables and operation codes 'intelligently'

	// 1. SELECT SKELETON EXPRESSION //
	// $skeletonList contains a list of possible skeletons. Duplicates based on weights to increase their odds of being picked, then picks a random skeleton from the list
	// Then selects a random tree
	$skeletonList = array();
	foreach($bnum as $b){
		for($n = 0; $n < count(BONEYARD_LOGIC[$b]); $n++){
			for($k = 0; $k < WEIGHT_LOGIC[$b][$n]; $k++){
				$skeletonList[] = BONEYARD_LOGIC[$b][$n];
			}
		}
	}
	$skeleton = $skeletonList[$RND->array_rand($skeletonList)];
    // 2. INSERT NEGATIONS //
    // In postfix, negations can go after any * or any #. There are strlen(skeleton) gaps to place $unum negation symbols. Select the positions first (so we don't get double negatives)
    $unum = $unum[$RND->array_rand($unum)];
    $negPos = array();
    /*potential for runaway while, and possible low probability of a hit. diffrands will be simpler.
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
        $skeleton = substr_replace($skeleton,'-',$v,0);
    }
    $skeleton = str_split($skeleton);
	// 3. REPLACE * with vars and # with ops intelligently //
    return logicPostfixMakePretty(logicFillRandSkeleton($skeleton,$vars,$ops));
}

// RANDOMLY FILL SKELETON
// Input: A skeleton expression ARRAY (postfix expression with *,#,- to denote blank symbols), variables to replace *s, op CODES to replace #s
// Output: Each * is replaced by var, Each # is replaced by an op. Prevents both sides of any binary operation being equivalent (no A OR A for example)
// Reason: Used in logicrand
function logicFillRandSkeleton($skeleton,$vars,$ops){
    global $RND;
    // Input check
    if(!is_array($skeleton) || !is_array($vars) || !is_array($ops)){
        echo "Error in logicFillRandSkeleton: Each input must be an array";
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

    // Third base case: Three *'s and at least 3 vars. This forces all three vars to be used to allow for better expressions.
    if(array_count_values($skeleton)["*"]==3 && count($vars)>2){
        $var = $RND->array_rand(array_flip($vars),3);             // Choose two different variables
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
    $operands = logicGetOperands($skeleton);
    // If operands is size 1 then last operation was unary
    if(count($operands)==1){
        return array_merge(logicFillRandSkeleton($operands[0],$vars,$ops),array("-"));
    }
    // If operands is size 2, then last operation was binary
    $L = $operands[0];
    $L = logicFillRandSkeleton($L,$vars,$ops);
    $evalL = logicPostfixEvaluateAll($L,$vars);

    // Redo $L if it is a tautology or contradition. Try 10,000 times only to avoid infinite loops (although this shouldn't occur)
    // Pretty lazy, but honestly probably more elegant than trying to create a more sophisticated process
    for($i = 0; $i<10000; $i++){
        if($evalL==0 || $evalL == pow(2,$vars)-1){              // If the left operand is a tautology or contradiction, redo constructing it
            $L = logicFillRandSkeleton($operands[0],$vars,$ops);
			$evalL = logicPostfixEvaluateAll($L,$vars);
        }
        else{
            break;
        }
    }
	$R = $operands[1];
    $R = logicFillRandSkeleton($R,$vars,$ops);
    $evalR = logicPostfixEvaluateAll($R,$vars);
    // Redo $R (up to 10,000 times) if the following conditions occur
    
    // a. $R is a tautology or contradiction
    // b. $L and $R are equivalent. (creates uninteresting expressions)
    // c. $L and $R are negations. (also creates uninteresting expressions)
    for($i = 0; $i<10000; $i++){
        if($evalR==0 || $evalR==pow(2,$vars)-1){                        // right operand is a tautology or contradiction, redo.
            $R = logicFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = logicPostfixEvaluateAll($R,$vars);
        }
        elseif($evalL==$evalR){                                         // the left and right operands ended up being logically equivalent, redo.
            $R = logicFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = logicPostfixEvaluateAll($R,$vars);
        }
        $evalNR = array();                                              // Create the negation evaluation of right operand
        foreach($evalR as $v){
            $NR[] = 1-$v;
        }                                                               
        if($evalL== $evalNR){                                          // the left and right operands ended up being negations, redo.
            $R = logicFillRandSkeleton($operands[1],$vars,$ops);
			$evalR = logicPostfixEvaluateAll($R,$vars);
        }
        else{
            break;
        }
    }
	// Now select an operation to join the operands together. 
    // If L and R both have operations, and the 'last' ones of each are the same then the joining operation can't be the same as those two, unless it's an ifthen. 
    // THis avoids boring things like (p and q) and (q and r)
    if(count($L)>1 && count($R)>1 && end($L)==end($R) && BINARIES_LOGIC[end($L)]!="ifthen"){
        $allowedops = array_diff($ops,array(end($L)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    elseif(count($L)>1 && count($R)<3 && BINARIES_LOGIC[end($L)]!="ifthen"){
        $allowedops = array_diff($ops,array(end($L)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    elseif(count($R)>1 && count($L)<3 && BINARIES_LOGIC[end($R)]!="ifthen"){
        $allowedops = array_diff($ops,array(end($R)));
        $O = $allowedops[$RND->array_rand($allowedops)];
    }
    else{
        $O = $ops[$RND->array_rand($ops)];
    }
    return array_merge($L,$R,array($O));
}

// LOGIC LEGEND
// ** Public **
// Input: None.
// Output: A string containing a small legend giving keyboard commands to enter logic symbols
function logiclegend(){
    return '<p>The following words can be typed to create logical connectives instead of using the pop-up keyboard.</p>
    <table style="border: 2px solid; text-align:left; border-collapse:collapsed;">
    <tr>
    <th style="border-bottom: 1px solid; text-align:left;">Type...</th><th style="border-bottom: 1px solid; text-align:left;">...to Create</th>
    </tr>
    <tr><td>and</td><td>∧</td></tr>
    <tr><td>or</td><td>∨</td></tr>
    <tr><td>xor</td><td>⊕</td></tr>
    <tr><td>neg (or not)</td><td>¬</td></tr>
    <tr><td>implies</td><td>⇒ (equivalent to → in MyOpenMath)</td></tr>
    <tr><td>iff</td><td>⇔ (equivalent to ↔)</td></tr>
    </table>';
}


?>
