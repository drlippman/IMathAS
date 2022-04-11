<?php
//A collection of numerical calculus routines.  These are intended to do
//numerical calculations where they are specifically needed, not substitute
//for entering symbolic derivatives or integrals.
//
//Version 1.1 April 8, 2009

global $allowedmacros;
array_push($allowedmacros,"randlogicexp");

/* This library is used to handle logical expressions.
Useful for creating truth tables and the like
This library assumes expressions will not be too complicated (no sadists allowed)...no more than 4 binary operations.
Because of this assumption, it is easier to simply use a brute-force approach for many functions,
 rather than making fancy recursive algorithms and such
*/

// Full Binary Tree Node Class with the Option of Attaching a $neg flag to each node, which means the operation or variable will be negated after evaluation.
// Nodes with two children represent binary operations
// Nodes without children represent variables (nodes with 1 child should not occur)
class Node {
	public $value;
	public $neg;
	public $left;
	public $right;

	public function __construct($value, $neg, $left, $right){
		$this->value = $value;
		$this->neg = $neg;
		$this->left = $left;
		$this->right = $right;
	}

	public function switchNeg(){
		$this->$neg = !($this->$neg);
	}
	public function hasChildren(){
		return (boolean) $this->$left;
	}
	public function isOperation(){
		return in_array($value,$OPERATIONS);
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
}

// Converts a Node tree to a string expression
function treetoexp($tree){
	if(!$tree->hasChildren()){
		if($tree->neg){
			return "neg ".$tree->value;
		}
		else{
			return $tree->value;
		}
	}
	if($tree->neg){
		return "neg (".treetoexp($tree->left)." ".$tree->value." ".treetoexp($tree->right).")";
	}else{
		return "(".treetoexp($tree->left)." ".$tree->value." ".treetoexp($tree->right).")";
	}
}

function exptotree($exp){
	$opStack = array();
	$symStack = array();
	// Add spaces around each symbol so it's easier to explode and parse
	$exp = str_replace("))",") )",str_replace("((","( (",$exp));
	for($i = 1; $i < strlen($exp) - 1; $i++){
		if(ctype_alpha($exp[$i]) && $exp[$i - 1] == "("){
			$exp = substr_replace($exp," ",$i,0);
		}
		if(ctype_alpha($exp[$i]) && $exp[$i + 1] == ")"){
			$exp = substr_replace($exp," ",$i+1,0);
		}
	}
	// Strips away double spaces, and removes leading and trailing spaces and parens.
	// Need to trim the parens twice incase we have both leading spaces and leading parens.
	$exp = str_replace("  "," ",$exp);
	$exp = preg_replace("/^ ?\(|\) ?$/","",$exp);
	// If $exp is length 1, then it must be just an alpha
	// Otherwise put a pair of outer parens back on
	if(strlen($exp) == 1 && ctype_alpha($exp)){

	}
	elseif(strlen($exp) == 1){
		echo "Error in exptotree: Input is a single nonalphabetical character, which cannot be a valid expression";
	}
	else{
		$exp = "( ".$exp." )";
	}

	// expQueue explodes the expression into an array of symbols to be parsed
	$expQueue = explode(" ",$exp);

	foreach($expQueue as $sym){
		switch (TRUE){
			case ($sym == "("):
				$symStack[] = "("; 
				break;
			case ($sym == "neg"):
				$symStack[] = "neg";
				break;
			case ((ctype_alpha($sym) || $sym == "*") && strlen($sym) == 1):
				$neg = FALSE;
				while($symStack[-1] == "neg"){
					$neg = !$neg;
					array_pop($symStack);
				}
				$symStack[] = new Node($sym,$neg,NULL,NULL);
				break;
			case (in_array($sym,$OPERATIONS)):
				$opStack[] = $sym;
				break;
			case ($sym == ")"):
				$R = array_pop($symStack);
				$L = array_pop($symStack);
				$O = array_pop($opStack);
				// Remove leading paren
				array_pop($symStack);
				// Any negations?
				$neg = FALSE;
				while($symStack[-1] == "neg"){
					$neg = !$neg;
					array_pop($symStack);
				}
				$symStack[] = new Node($O,$neg,$L,$R);
				break;
			default:
				echo "Unknown symbol in expression for method exptotree. Aborting...";
		}
	}
	return $symStack[0];
}



// TREE AND WEIGHT ARRAY
// $TREE[b][n] is a string containing the nth binary tree with b interior nodes (i.e. an expression with only binary operations)
// The leaves are *, the interior nodes (i.e. the binary operators) are numbers, 0 is the root, 1 is the root of the left and right subtrees, 2 is the left/right roots of those subtrees etc.
// $TREEWEIGHT[b][n] is the randomization weight for the tree in $TREE[b][n].
// Higher values mean the tree is more likely to be chosen, allows for more interesting expressions to appear

$TREE[0][0] = "*";
$WEIGHT[0] = array(1);
$TREE[1][0] = "(* 0 *)";
$WEIGHT[1] = array(1);
$TREE[2][0] = "((* 1 *) 0 *)";
$TREE[2][1] = "(* 0 (* 1 *))";
$WEIGHT[2] = array(1,1);
$TREE[3][0] = "(((* 2 *) 1 *) 0 *)";
$TREE[3][1] = "((* 1 (* 2 *)) 0 *)";
$TREE[3][2] = "((* 1 *) 0 (* 1 *))";
$TREE[3][3] = "(* 0 ((* 2 *) 1 *))";
$TREE[3][4] = "(* 0 (* 1 (* 2 *)))";
$WEIGHT[3] = array(1,1,3,1,1);
$TREE[4][0] = "((((* 3 *) 2 *) 1 *) 0 *)";
$TREE[4][1] = "(((* 2 *) 1 *) 0 (* 1 *))";
$TREE[4][2] = "(((* 2 (* 3 *)) 1 *) 0 *)";
$TREE[4][3] = "((* 1 ((* 3 *) 2 *)) 0 *)";
$TREE[4][4] = "(((* 2 *) 1 (* 2 *)) 0 *)";
$TREE[4][5] = "((* 1 (* 2 *)) 0 (* 1 *))";
$TREE[4][6] = "((* 1 (* 2 (* 3 *))) 0 *)";
$TREE[4][7] = "(* 0 (((* 3 *) 2 *) 1 *))";
$TREE[4][8] = "((* 1 *) 0 ((* 2 *) 1 *))";
$TREE[4][9] = "(* 0 ((* 2 *) 1 (* 2 *)))";
$TREE[4][10]= "(* 0 ((* 2 (* 3 *)) 1 *))";
$TREE[4][11]= "(* 0 (* 1 ((* 3 *) 2 *)))";
$TREE[4][12]= "((* 1 *) 0 (* 1 (* 2 *)))";
$TREE[4][13]= "(* 0 (* 1 (* 2 (* 3 *))))";
$WEIGHT[4] = array(1,2,1,1,2,3,1,1,3,2,1,1,2,1);

// A list of reserved operations that cannot be used as variable names
$OPERATIONS = array("and","or","not","if","iff","xor","oplus","0","1","2","3","4");

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
		$vars = listtoarray($vars);
	}
	if(count($vars) > 1 && count($vars) < 5){
		echo "Error in randlogicexp: $vars array must be of size 2, 3 or 4";
	}
	foreach($vars as $var){
		if(!ctype_alpha($var)){
			echo "Error in randlogicexp: Every entry in $vars must be an alphabetical character";
		}
	}
	// $op check
	if(!is_array($ops)){
		$ops=listtoarray($ops);
	}
	foreach($ops as $entry){
		if(!in_array($entry,$OPERATIONS)){
			echo "Error in randlogicexp: Every entry in $ops must be a valid operation (see manual for a list)";
		}
	}
	// $bnum check
	if(!is_integer($bnum) && !is_array($bnum)){
		$bnum = listtoarray($bnum);
	}
	if(!is_array($bnum)){
		$bnum = array($bnum);
	}
	foreach($bnum as $entry){
		if(!in_array($bnum,array(1,2,3,4))){
			echo "Error in randlogicexp: Every entry in $bnum must be an integer between 1 and 4";
		}
	}
	// $unum check
	$minbnum = 2*min($bnum) + 1;
	if(!is_integer($unum) && !is_array($unum)){
		$unum = listtoarray($unum);
	}
	if(!is_array($unum)){
		$unum = array($unum);
	}
	foreach($unum as $entry){
		if(!is_integer($entry) || $entry < 0 || $entry > $minbnum){
			echo "Error in randlogicexp: Every entry in $unum must be an integer between 1 and 2*(minimum of bnum) + 1";
		}
	}
	// $assoc check
	$assoc = (boolean) $assoc;

	///////////////////////
	// CREATE EXPRESSION //
	///////////////////////

	//1. Select an appropraite tree from $TREE that has the correct binary operations
	//2. Replace the *'s and #'s with variables and operations
	//3. Insert negation symbols

	// 1. SELECT TREE //
	// $treeList contains a list of possible trees. Duplicates trees based on weights, to increase their odds of being picked, then picks a random tree
	// Then selects a random tree
	$treeList = array();
	foreach($bnum as $b){
		foreach($TREE[$b] as $n){
			for($k = 0; $k < $WEIGHT[$b][$n]; $k++){
				$treeList[] = $TREE[$b][$n];
			}
		}
	}
	$biTreeExp = array_rand($treeList);
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
	$negPositions = rsort(array_rand($possibleNegPositions,$unum), SORT_NUMERIC);
	foreach($negPositions as $position){
		$exp = substr_replace($exp," neg",$position,0);
	}
	return $exp;
}

//////////////////////
// HELPER FUNCTIONS //
//////////////////////

// Is $op associative?
function isAssociative($op){
	return in_array($op,array("and","or","iff","xor","oplus"));
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
		return new Node(array_rand($vars),$tree->neg,NULL,NULL);
	}
	// If it's not a leaf (an operation)
	$L = $tree->left;
	$R = $tree->right;
	$L = assocTreeReplace($L,$vars,$ops);
	$isForbiddenTree = TRUE;
	while($isForbiddenTree){
		$R = assocTreeReplace($R,$vars,$ops);
		$isForbiddenTree = FALSE;
		if($L->looseCompare($R)){
			$isForbiddenTree = TRUE;
		}
		$O = array_rand($ops);
		if(!$assoc){
			$isForbiddenOp = TRUE;
			while($isForbiddenOp){
				$O = array_rand($ops);
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
	return new Node($O,$tree->neg,$L,$R);
}





?>