<?php
/* This library is used to handle logical expressions.
Useful for creating truth tables checking logical equivalencies, etc.
Updated: April 11, 2022
*/


global $allowedmacros;
array_push($allowedmacros,"randlogicexp","treetoexp","exptotree","clean");

// TREE AND WEIGHT ARRAY
// TREE[b][n] is a string containing the nth binary tree with b interior nodes (i.e. an expression with only binary operations)
// The leaves are *, the interior nodes (i.e. the binary operators) are numbers, # is an operation
// WEIGHT[b][n] is the randomization weight for the tree in TREE[b][n].
// Higher values mean the tree is more likely to be chosen, allows for more interesting expressions to appear

const TREE = array(
					array("*"),
					array("( * # * )"),
					array("(( * # * ) # * )","( * # ( * # * ))"),
					array("((( * # * ) # * ) # * )","(( * # ( * # * )) # * )","(( * # * ) # ( * # * ))","( * # (( * # * ) # * ))","( * # ( * # ( * # * )))"),
					array("(((( * # * ) # * ) # * ) # * )",
						"((( * # * ) # * ) # ( * # * ))",
						"((( * # ( * # * )) # * ) # * )",
						"(( * # (( * # * ) # * )) # * )",
						"((( * # * ) # ( * # * )) # * )",
						"(( * # ( * # * )) # ( * # * ))",
						"(( * # ( * # ( * # * ))) # * )",
						"( * # ((( * # * ) # * ) # * ))",
						"(( * # * ) # (( * # * ) # * ))",
						"( * # (( * # * ) # ( * # * )))",
						"( * # (( * # ( * # * )) # * ))",
						"( * # ( * # (( * # * ) # * )))",
						"(( * # * ) # ( * # ( * # * )))",
						"( * # ( * # ( * # ( * # * ))))"));
const WEIGHT = array(array(1),array(1),array(1,1),array(1,1,3,1,1),array(1,2,1,1,2,3,1,1,3,2,1,1,2,1));

// Defines valid operation words, and associative operations
const UNARIES = array("not","neg");
const BINARIES = array("and","wedge","or","vee","ifthen","implies","iff","xor","oplus");
const ASSOCIATIVES = array("and","or","iff","xor","oplus","wedge","vee");

// Full Binary Tree Node Class with the Option of Attaching a $neg flag to each node, which means the operation or variable will be negated after evaluation.
// Nodes with two children represent binary operations
// Nodes without children represent variables (nodes with 1 child should not occur)
class Node {
	public $value;
	public $neg;
	public $left;
	public $right;

	// A list of reserved operations that cannot be used as variable names
	public function __construct($value, $neg, $left, $right){
		$this->value = $value;
		$this->neg = $neg;
		$this->left = $left;
		$this->right = $right;
	}

	public function switchNeg(){
		$this->neg = !($this->neg);
	}
	public function hasChildren(){
		return (boolean) $this->left;
	}
	// returns TRUE of the trees are the identically the same
	public function strictCompare($node){
		if(!$this->hasChildren() && !$node->hasChildren()){
			if(($this->value == $node->value) && ($this->neg == $node->neg)){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
		elseif(!$this->hasChildren() || !$node->hasChildren()){
			return FALSE;
		}
		else{
			if($this->left->strictCompare($node->left) && $this->right->strictCompare($node->right)){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
	}
	
	// returns TRUE of the trees are roughly the same, just the left/right children may be mixed up
	public function looseCompare($node){
		if(!$this->hasChildren() && !$node->hasChildren()){
			if(($this->value == $node->value) && ($this->neg == $node->neg)){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
		elseif(!$this->hasChildren() || !$node->hasChildren()){
			return FALSE;
		}
		else{
			if(($this->left->looseCompare($node->left) && $this->right->looseCompare($node->right)) || ($this->left->looseCompare($node->right) && $this->right->looseCompare($node->left))){
				return TRUE;
			}
			else{
				return FALSE;
			}
		}
	}

	// returns string
	public function __toString()
	{
		if($this->left == NULL){
			return "Leaf Node with value: $this->value and neg: $this->neg";
		}
		return "Interior Node with value: $this->value; neg: $this->neg";
	}
}

// In the code, binary operations are replaced with numbers
// Unary operations are replaced with '-'
// Variables are single alphabetical characters
// The method 'makepretty' converts to user-friendly display format

// Converts a Node tree to a string expression
function treetoexp($tree){
	if(!$tree->hasChildren()){
		if($tree->neg){
			return "-".$tree->value;
		}
		else{
			return $tree->value;
		}
	}
	if($tree->neg){
		return "-(".treetoexp($tree->left).$tree->value.treetoexp($tree->right).")";
	}else{
		return "(".treetoexp($tree->left).$tree->value.treetoexp($tree->right).")";
	}
}

function exptotree($exp){
	// Check to make sure exp is valid and clean it up
	$exp = clean($exp);

	$opStack = array();
	$symStack = array();

	// If $exp is length 1, then it must be just an alpha
	// Otherwise put a pair of outer parens back on for parsing
	if(strlen($exp) == 1 && ctype_alpha($exp)){
		return new Node($exp,FALSE,NULL,NULL);
	}
	elseif(strlen($exp) == 1){
		echo "Error in exptotree: Input is a single nonalphabetical character, which cannot be a valid expression";
		return FALSE;
	}
    $exp = "(".$exp.")";
	echo "(((PARSING: $exp )))";
	// expQueue explodes the expression into an array of symbols to be parsed
	$expQueue = str_split($exp);
	// get unary operator keys and binary operator keys
	
	foreach($expQueue as $sym){
		switch (TRUE){
			case ($sym == "("):
				$symStack[] = "("; 
				echo nl2br("Adding ( to stack: Size ".count($symStack)."\n");
				break;
			case ($sym == "-"):
				$symStack[] = "-";
				echo nl2br("Adding - to stack: Size ".count($symStack)."\n");
				break;
			case ((ctype_alpha($sym) || $sym == "*") && strlen($sym) == 1):
				$neg = FALSE;
				while($symStack[-1] == "-"){
					$neg = !$neg;
					array_pop($symStack);
				}
				$symStack[] = new Node($sym,$neg,NULL,NULL);
				echo nl2br("Adding $sym to stack: Size ".count($symStack)."\n");
				break;
			case (in_array($sym,BINARIES)):
				$opStack[] = $sym;
				break;
			case ($sym == ")"):
				$R = array_pop($symStack);
				$gt = gettype($R);
				echo "R HAS TYPE: $gt|||".$R->__toString()."|||";
				$L = array_pop($symStack);
				$gt = gettype($L);
				echo "L HAS TYPE: $gt |||".$L->__toString()."|||";
				$O = array_pop($opStack);
				// Remove leading paren
				array_pop($symStack);
				// Any negations?
				$neg = FALSE;
				while($symStack[-1] == "-"){
					$neg = !$neg;
					array_pop($symStack);
				}
				
				echo nl2br("Adding Tree($O,$neg,".$L->__toString().",".$R->__toString().") to stack: Size ".count($symStack)."\n");
				$symStack[] = new Node($O,$neg,$L,$R);
				break;
			default:
				echo "Unknown symbol $sym in expression for function exptotree. Aborting...";
		}
	}
	return $symStack[0];
}


// Given an expression evaluates to 0 or 1 (False or Tre)
function evaluate($exp,$vars,$values){

}

// Given an expression, returns an array of subexpressions that must be computed to evaluate, in order. Useful for creating truth tables and showing solutions
function evalsteps($exp){

}

// Returns a string that generates an HTML block with answerboxes already built in. $startans controls where the answerbox numbers should begin
// Note: If you have further questions after the truth table is built, it may be best to assign them answerboxes of earlier values, otherwise you'll need to make sure the next answerbox after the table is numbered correctly
// Note: This will change the value of $anstypes by appending T/F $choices to it.
function truthtable($exp, $startans=0){
	
}


// Creates a random simple logical expression
function randlogicexp($vars,$ops,$bnum,$unum,$assoc=false){
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
	// $assoc = true or false (default true). If false then this will try to prohibit expressions where
	//          the associative property can be applied (i.e. no (A AND B) AND (C AND B) type expressions).
	//			This generally allows for more interesting expressions to occur if false.
	//			Associative operations are AND, OR, IFF and XOR
	// RETURNS: an expression object

	//////////////////
	// INPUT CHECK //
	/////////////////

	// $var check
	if(!is_array($vars)){
		$vars = explode(',',$vars);
	}
	if(count($vars) < 2 || count($vars) > 4){
		echo "Error in randlogicexp: vars array must be of size 2, 3 or 4";
	}
	foreach($vars as $var){
		if(!ctype_alpha($var)){
			echo "Error in randlogicexp: Every entry in vars must be an alphabetical character";
		}
	}
	// $op check
	if(!is_array($ops)){
		$ops = explode(',',$ops);
	}
	foreach($ops as $entry){
		if(!in_array($entry,BINARIES)){
			echo "Error in randlogicexp: Every entry in ops must be a valid binary operation (see manual for a list)";
		}
	}
	// $bnum check
	if(!is_integer($bnum) && !is_array($bnum)){
		$bnum = explode(',',$bnum);
	}
	if(!is_array($bnum)){
		$bnum = explode(',',$bnum);
	}
	foreach($bnum as $entry){
		if(!in_array($entry,array(1,2,3,4))){
			echo "Error in randlogicexp: Every entry in bnum must be an integer between 1 and 4";
		}
	}
	// $unum check
	$minbnum = 2*min($bnum) + 1;
	if(!is_integer($unum) && !is_array($unum)){
		$unum = explode(',',$unum);
	}
	if(!is_array($unum)){
		$unum = array($unum);
	}
	foreach($unum as $entry){
		if(!is_integer($entry) || $entry < 0 || $entry > $minbnum){
			echo "Error in randlogicexp: Every entry in unum must be an integer between 1 and 2*(minimum of bnum) + 1";
		}
	}
	// $assoc check
	$assoc = (boolean) $assoc;

	///////////////////////
	// CREATE EXPRESSION //
	///////////////////////

	//1. Select an appropraite tree from $TREE that has the correct binary operations
	//2. Replace the *'s and #'s with variables and operation codes
	//3. Insert negation symbols

	// 1. SELECT TREE //
	// $treeList contains a list of possible trees. Duplicates trees based on weights, to increase their odds of being picked, then picks a random tree
	// Then selects a random tree
	$treeList = array();
	foreach($bnum as $b){
		for($n = 0; $n < count(TREE[$b]); $n++){
			for($k = 0; $k < WEIGHT[$b][$n]; $k++){
				$treeList[] = TREE[$b][$n];
			}
		}
	}

	$biTreeExp = $treeList[array_rand($treeList)];
	$biTree = exptotree($biTreeExp);

	// 2. REPLACE * and # symbols //
	// Replace *'s with a symbol in $vars and #'s with a symbol in $ops, tries to ensure that silly trees are avoided: (A AND B) AND (B AND A) for example
	$biTree = treeReplace($biTree,$vars,$ops,$assoc);

	// 3. INSERT NEGATION SYMBOLS //
	// Convert to expression, find all instances of '(' or element in $var, then place $unum negation symbols randomly in front of them.
	$exp = treetoexp($biTree);
	$possibleNegPositions = array();
	for($i = 0; $i < strlen($exp) - 1; $i++){
		if($exp[$i] == '(' || (ctype_alpha($exp[$i]) && ($exp[$i+1] == ')' || $exp[$i+1] == ' '))){
			$possibleNegPositions[] = $i;
		}
	}
	$negPositions = array_rand(array_flip($possibleNegPositions), $unum[array_rand($unum)]); 
	rsort($negPositions, SORT_NUMERIC);
	foreach($negPositions as $position){
		$exp = substr_replace($exp," neg",$position,0);
	}
	return clean($exp);
}

//////////////////////
// HELPER FUNCTIONS //
//////////////////////

// Cleanup a messy, but valid, expression
// Removes all spaces and replaces operators with numerical codes
// If expression is invalid, returns FALSE and throws an echo. Else returns the cleaned string.
function clean($exp){
	// Convert any word binary operation to a numerical value so it's a single character.
	// 0 = and, 1 = wedge, 2 = or, etc. (the keys of the BINARIES array)
	for($o = 0; $o < count(BINARIES); $o++){
		$exp = str_replace(BINARIES[$o],"$o",$exp);
	}
    // Replace any unary word with a '-'
    for($o = 0; $o < count(UNARIES); $o++){
		$exp = str_replace(UNARIES[$o],"-",$exp);
	}
	// Convert brackets to parens
	$exp = str_replace("[","(",$exp);
	$exp = str_replace("]",")",$exp);
	// Remove spaces and silly parens
	$exp = str_replace(" ","",$exp);
	$exp = str_replace("()","",$exp);
    // Remove any non-alpnumeric, non-paren, non-minus, non-* characters
    $exp = preg_replace("/[^\*\w-]]/","",$exp);
	// Remove outer parens of type (V) or (unary V), or ((V)), or (unary (V)) etc.
	$regex = "/\(([\*A-Za-z-]{1,})\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\2',$exp,1);
	}
	// Also remove parens of type ((EXP)) where EXP is any expression.
	$regex = "/\(\(([\*\w-]{1,})\)\)/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'\3',$exp,1);
	}
	// Remove double unaries
	$regex = "/[-]{2,}/";
	while(preg_match($regex,$exp)){
		$exp = preg_replace($regex,'',$exp,1);
	}
    echo "Debugging clean: Current post-clean expression is: ".$exp;
    // Now determine if the result is valid
    
	// Parens must balance
	$balance = 0;
	foreach($exp as $sym){
		if($sym == '(' || $sym == '['){
			$balance++;
		}
		elseif($sym == ')' || $sym == ']'){
			$balance--;
			if($balance < 0){
				echo "Error in clean: Misaligned parentheses.";
				return FALSE;
			}
		}
	}
    if($balance > 0){
        echo "Error in clean: Misaligned parentheses.";
		return FALSE;
    }
    
	// JAVA SOLUTION FROM https://www.interviewkickstart.com/problems/valid-parentheses#:~:text=A%20string%20is%20considered%20valid,well%20as%20valid%20mathematical%20operations.&text=An%20expression%20containing%20only%20parentheses,)%7D%E2%80%9D%20is%20considered%20valid.
    // However, this expression doesn't handle unary operators. We first remove unary operators then we make sure they are inserted appropriately
	$nexp = str_replace("-","",$exp);
	
	// BINARY CHECKER
    /*stores variables stack*/
    $vStack = array();
    /*stores operators and parentheses*/
    $oStack = array();
    $isTrue = TRUE;
    for($i = 0; $i < strlen($nexp); $i++) {
        $char = $nexp[$i];
        /*if the character is a variable, we push it to vStack*/
        if(ctype_alpha($char) || $char == "*"){
            $vStack[] = $char;
            if($isTrue) {
                $isTrue = FALSE;
            }
            else {
                return FALSE;
            }
        } 
        /*if the character is an operator, we push it to oStack*/
        elseif(ctype_alnum($char)){
            $oStack[] = $char;
                $isTrue = TRUE;
            } 
        else {
            /*if the character is an opening parantheses we push it to oStack*/
        	if($char == "(") {
                $oStack[] = $char;
            } 
            /*If it is a closing bracket*/
            else {
                $flag = TRUE;
                /*we keep on removing characters until we find the corresponding
                open bracket or the stack becomes empty*/
                while(!empty($oStack)){
                    $c = array_pop($oStack);
                    if($c == '(') {
                        $flag = FALSE;
                        break;
                    } 
                    else {
                        if (count($vStack) < 2) {
                            return FALSE;
                        }
                    	else {
                            array_pop($vStack);
                        }
                    }
                }
                if ($flag) {
                    return FALSE;
                }

            }
        }
    }
    while (!empty($oStack)){
        $c = array_pop($oStack);
        if (ctype_alnum($c) && !ctype_alpha($c)) {
            return FALSE;
        }
        if (count($vStack) < 2) {
            return FALSE;
        }
        else {
            array_pop($vStack);
        }
    }
    if (count($vStack) > 1 || !empty($oStack)) {
        return FALSE;
    }


	// UNARY CHECKER
	// Unaries must be before a left paren or a variable
	$regex = "/([-{1,}]^(\(|\[|[A-Za-z])/";
	if(preg_match($regex,$exp)){
		echo "Error in isValid: Negations not in correct positions.";
		return FALSE;
	}
    return $exp;

}


// Is $op associative?
function isAssociative($op){
	return in_array($op,ASSOCIATIVES);
}


// Recursive Algorithm that will replace all leaves with $vars and interiors with $ops, but ensuring that no two children of any node are identical
// This prevents (A AND B) OR (B AND A) from occuring, for example
// Begin by replacing all left children with tree
// Randomly generate a right tree repeatedly until it doesn't match with the left tree
// WARNING: I'm assuming this is always possible...I don't see why it wouldn't be given the conditions on $var and $ops
// If $assoc is true, then we also must eliminate two additional possibilities
// a. If we have an # node with one child * and another child #, the two # cannot be the same associative operation.
// b. If we have an # node with both children #, not all three operations can be the same assocative operation.
function treeReplace($tree,$vars,$ops,$assoc){
	// If it's a leaf...
	if(!$tree->hasChildren()){
		return new Node($vars[array_rand($vars)],$tree->neg,NULL,NULL);
	}
	// If it's not a leaf (an operation)
	$L = $tree->left;
	$R = $tree->right;
	$L = treeReplace($L,$vars,$ops,$assoc);
	$isForbiddenTree = TRUE;
	while($isForbiddenTree){
		$R = treeReplace($R,$vars,$ops,$assoc);
		$isForbiddenTree = FALSE;
		if($L->looseCompare($R)){
			$isForbiddenTree = TRUE;
		}
		$O = $ops[array_rand($ops)];
		if(!$assoc){
			$isForbiddenOp = TRUE;
			while($isForbiddenOp){
				$O = $ops[array_rand($ops)];
				$isForbiddenOp = FALSE;
				// Check Condition (a)
				if(!$L->hasChildren() && $R->hasChildren() && isAssociative($O) && $R->value == $O){
					$isForbiddenOp = TRUE;
				}
				if($L->hasChildren() && !$R->hasChildren() && isAssociative($O) && $L->value == $O){
					$isForbiddenOp = TRUE;
				}
				// Check Condition (b)
				if($L->hasChildren() && $R->hasChildren() && isAssociative($O) && $L->value == $R->value && $L->value == $O){
					$isForbiddenOp = TRUE;
				}
			}
		}
	}
	return new Node($O,$tree->neg,$L,$R);
}





?>