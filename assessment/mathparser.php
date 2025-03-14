<?php

/**
 * Utility front-end for Parser class
 * Returns Parser instance with parse tree built.
 * Ready for ->evaluate
 *
 * @param  string $str  Math expression to parse
 * @param  string $vars Comma separated list of variables
 * @param array $allowedfuncs  (optional) An array of function names that can
 *                            be called in math expressions.  Defaults to a
 *                            set of standard math functions.
 * @return Parser instance
 */
function parseMath($str, $vars = '', $allowedfuncs = array(), $fvlist = '') {
  $parser = new MathParser($vars, $allowedfuncs, $fvlist);
  $parser->parse($str);
  return $parser;
}

function parseMathQuiet($str, $vars = '', $allowedfuncs = array(), $fvlist = '', $hideerrors=false, $docomplex=false) {
  if (trim($str)=='') {
    return false;
  }
  try {
    $parser = new MathParser($vars, $allowedfuncs, $fvlist, $docomplex);
    $parser->parse($str);
  } catch (Throwable $t) {
    if ($GLOBALS['myrights'] > 10 && !$hideerrors) {
      echo "Parse error on: ".Sanitize::encodeStringForDisplay($str);
      echo ". Error: ".$t->getMessage();
    }
    return false;
  } 
  return $parser;
}

/**
 * Utility front-end for Parser
 * Returns a function that can be evaluated like ->evaluate would be
 * This function will catch any exceptions, and the initial call will return
 * false if there is a parse error, and calling the function will return NaN
 * if there is an eval error
 *
 * @param  string $str  Math expression to parse
 * @param  string $vars Comma separated list of variables
 * @param array $allowedfuncs  (optional) An array of function names that can
 *                            be called in math expressions.  Defaults to a
 *                            set of standard math functions.
 * @param string $fvlist comma separated list of variables to treat as functions
 * @return function
 */
function makeMathFunction($str, $vars = '', $allowedfuncs = array(), $fvlist = '', $hideerrors=false, $docomplex=false) {
  if (trim($str)=='') {
    return false;
  }
  try {
    $parser = new MathParser($vars, $allowedfuncs, $fvlist, $docomplex);
    $parser->parse($str);
  } catch (Throwable $t) {
    if ($GLOBALS['myrights'] > 10 && (!empty($GLOBALS['inQuestionTesting']) || !$hideerrors)) {
      echo "Parse error on: ".Sanitize::encodeStringForDisplay($str);
      echo ". Error: ".$t->getMessage();
    }
    return false;
  } 
  return function($varvals) use ($parser) {
    try {
      return $parser->evaluate($varvals);
    } catch (Throwable $t) {
      return sqrt(-1);
    } 
  };
}

/**
 * Front-end for math parser.  Evaluates numerical mathematical
 * expression.  Returns NaN on parse or eval error
 * @param  string $str  numerical math string
 * @return float
 */
function evalMathParser($str, $docomplex=false) {
  if (trim($str) === '') { return sqrt(-1); } // avoid errors on blank
  try {
    $parser = new MathParser('',[],'',$docomplex);
    $parser->parse($str);
    return $parser->evaluate();
  } catch (Throwable $t) {
    return sqrt(-1);
  }
}

/**
 * Math expression parser and evaluator.
 * (c) 2019 David Lippman
 * GNU LGPL License 3.0 http://www.opensource.org/licenses/lgpl-license.php
 *
 * Adapted from the algorithms in https://github.com/mossadal/math-parser/
 * Frank Wikström <frank@mossadal.se>
 * GNU LGPL License 3.0
 */
class MathParser
{
  private $functions = [];
  private $variables = [];
  private $funcvariables = [];
  private $operators = [];
  private $tokens = [];
  private $operatorStack = [];
  private $operandStack = [];
  public $AST = [];
  private $regex = '';
  private $funcregex = '';
  private $numvarregex = '';
  private $variableValues = [];
  private $origstr = '';
  private $docomplex = false;
  private $allowEscinot = true;

  /**
   * Construct the parser
   * @param string $variables   A comma-separated list of variables to look for
   * @param array $allowedfuncs  (optional) An array of function names that can
   *                            be called in math expressions.  Defaults to a
   *                            set of standard math functions.
   * @param string $fvlist   A comma-separated list of variables to treat as functions
   */
  public function __construct($variables, $allowedfuncs = array(), $fvlist = '', $docomplex = false) {
    if ($variables !== '') {
      $this->variables = array_values(array_filter(array_map('trim', explode(',', $variables)), 'strlen'));
    }
    if ($fvlist !== '') {
        $this->funcvariables = array_values(array_filter(array_map('trim', explode(',', $fvlist)), 'strlen'));
    }
    //treat pi and e as variables for parsing
    array_push($this->variables, 'pi', 'e');
    if ($docomplex) {
      $this->docomplex = true;
      array_push($this->variables, 'i');
    }
    usort($this->variables, function ($a,$b) { return strlen($b) - strlen($a);});

    $this->allowEscinot = !in_array('E', $this->variables);

    //define functions
    if (count($allowedfuncs) > 0) {
      $this->functions = $allowedfuncs;
    } else {
      $this->functions = explode(',', 'funcvar,arcsinh,arccosh,arctanh,arcsech,arccsch,arccoth,arcsin,arccos,arctan,arcsec,arccsc,arccot,root,sqrt,sign,sinh,cosh,tanh,sech,csch,coth,abs,sin,cos,tan,sec,csc,cot,exp,log,div,ln');
    }

    //build regex's for matching symbols
    $allwords = array_merge($this->functions, $this->variables, ['degree','degrees']);
    usort($allwords, function ($a,$b) { return strlen($b) - strlen($a);});
    $this->regex = '/^('.implode('|',array_map('preg_quote', $allwords)).')/';
    $this->funcregex = '/('.implode('|',array_map('preg_quote', $this->functions)).')/i';
    $this->numvarregex = '/^(\d+\.?\d*|'.implode('|', array_map('preg_quote', $this->variables)).')/';

    //define operators
    $this->operators = [
      '+' => [
        'precedence'=>11,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {
          if ($this->docomplex) {
            if (!is_array($a)) { $a = [$a,0]; }
            if (!is_array($b)) { $b = [$b,0]; }
            return [$a[0]+$b[0],$a[1]+$b[1]];
          } else {
            return $a + $b;
          }
        }],
      '-' => [
        'precedence'=>11,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {
          if ($this->docomplex) {
            if (!is_array($a)) { $a = [$a,0]; }
            if (!is_array($b)) { $b = [$b,0]; }
            return [$a[0]-$b[0],$a[1]-$b[1]];
          } else {
            return $a - $b;
          }
        }],
      '*' => [
        'precedence'=>12,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {
          if ($this->docomplex) {
            if (!is_array($a)) { $a = [$a,0]; }
            if (!is_array($b)) { $b = [$b,0]; }
            return [$a[0]*$b[0] - $a[1]*$b[1],$a[0]*$b[1]+$a[1]*$b[0]];
          } else {
            return $a * $b;
          }
        }],
      '/' => [
        'precedence'=>12,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {
          if ($this->docomplex) {
            if (!is_array($a)) { $a = [$a,0]; }
            if (!is_array($b)) { $b = [$b,0]; }
            if (abs($b[0]) < 1e-50 && abs($b[1]) < 1e-50) {
              throw new MathParserException("Division by zero");
            }
            $den = $b[0]*$b[0] + $b[1]*$b[1];
            return [
              ($a[0]*$b[0] + $a[1]*$b[1])/$den,
              ($a[1]*$b[0]-$a[0]*$b[1])/$den
            ];
          } else {
            if (abs($b) < 1e-50) {
              throw new MathParserException("Division by zero");
            }
            return $a / $b;
          } 
        }],
      '^' => [
        'precedence'=>18,
        'assoc'=>'right',
        'evalfunc'=>function($a,$b) {
          if ($this->docomplex) {
            if (!is_array($a)) { $a = [$a,0]; }
            if (!is_array($b)) { $b = [$b,0]; }
            if ($b[1] == 0) {
              $m = safepow($a[0]*$a[0]+$a[1]*$a[1], $b[0]/2);
              $t = atan2($a[1],$a[0]);
              return [$m*cos($t*$b[0]), $m*sin($t*$b[0])];
            } else {
              // (a+bi)^(c+di)=(a^2+b^2)^(c/2)e^(-darg(a+ib))×{cos[carg(a+ib)+1/2dln(a^2+b^2)]+isin[carg(a+ib)+1/2dln(a^2+b^2)]}.   
              $arg = atan2($a[1],$a[0]);
              $m = safepow($a[0]*$a[0]+$a[1]*$a[1], $b[0]/2) * exp(-$b[1] * $arg);
              $in = $b[0]*$arg + 1/2*$b[1]*log($a[0]*$a[0]+$a[1]*$a[1]);
              return [$m*cos($in), $m*sin($in)];
            }
          } else {
            if ($a == 0 && $b == 0) {
                throw new MathParserException("0^0 is undefined");
            } else if (!is_numeric($a) || !is_numeric($b)) {
                throw new MathParserException("cannot evaluate powers with nonnumeric values");
            } else if ($a < 0 && floor($b) != $b) {
                // some code replication here, but allows us to throw proper exception for invalid inputs
                for ($j=3; $j<50; $j+=2) {
                    if (abs(round($j*$b)-($j*$b))<.000001) {
                        if (round($j*$b)%2==0) {
                            return exp($b*log(abs($a)));
                        } else {
                            return -1*exp($b*log(abs($a)));
                        }
                    }
                }
                throw new MathParserException("invalid power for negative base");
            }
            return safepow($a,$b);
          }
        }],
      '!' => [
        'precedence'=>20,
        'assoc'=>'right'],
      '~' => [
        'precedence'=>16,
        'assoc'=>'left'],
      'not' => [
        'precedence'=>16,
        'assoc'=>'right'],
      '&&' => [
        'precedence'=>8,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a && $b);}],
      '||' => [
        'precedence'=>7,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a || $b);}],
      '#a' => [
        'precedence'=>8,
        'assoc'=>'right',
        'evalfunc'=>function($a,$b) {return ($a && $b);}],
      '#x' => [
          'precedence'=>7,
          'assoc'=>'right',
          'evalfunc'=>function($a,$b) {return ($a xor $b);}],
      '#o' => [
        'precedence'=>7,
        'assoc'=>'right',
        'evalfunc'=>function($a,$b) {return ($a || $b);}],
      '#m' => [
          'precedence'=>7,
          'assoc'=>'right',
          'evalfunc'=>function($a,$b) {return ($a && (!$b));}],
      '#i' => [
        'precedence'=>6,
        'assoc'=>'right',
        'evalfunc'=>function($a,$b) {return ((!$a) || $b);}],
      '#b' => [
        'precedence'=>6,
        'assoc'=>'right',
        'evalfunc'=>function($a,$b) {return (($a && $b) || (!$a && !$b));}],
      '<' => [
        'precedence'=>6,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a<$b)?1:0;}],
      '>' => [
        'precedence'=>6,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a>$b)?1:0;}],
      '<=' => [
        'precedence'=>6,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a<=$b)?1:0;}],
      '>=' => [
        'precedence'=>6,
        'assoc'=>'left',
        'evalfunc'=>function($a,$b) {return ($a>=$b)?1:0;}],
      '(' => true,
      ')' => true
    ];
  }

  /**
   * The main function that will be called to parse an expression.
   * Runs tokenize, handleImplicit, and buildTree
   * @param  string $str   The INFIX expression to tokenize
   * @return array  Builds syntax tree in class, but also returns it
   */
  public function parse($str) {
    $this->origstr = $str;
    $str = preg_replace('/(ar|arg)(sinh|cosh|tanh|sech|csch|coth)/', 'arc$2', $str);
    $str = str_replace(array('\\','[',']','`'), array('','(',')',''), $str);
    // attempt to handle |x| as best as possible
    $str = preg_replace('/(?<!\|)\|([^\|]+?)\|(?!\|)/', 'abs($1)', $str);
    $this->tokenize($str);
    $this->handleImplicit();
    $this->buildTree();
    return $this->AST;
  }

  /**
   * Evaluate the syntax tree
   * For example, after parsing "x*sin(y)", you could call
   * ->evaluate(['x'=>2, 'y'=>1])
   *
   * Note that input values are not parsed, so make sure you're passing
   * numeric values
   *
   * @param  array  $variableValues  Associative array of variables values
   * @return float  value of the function
   */
  public function evaluate($variableValues = array()) {
    foreach ($this->variables as $v) {
      if ($v === 'pi' || $v === 'e' || ($this->docomplex && $v === 'i')) { continue; }
      if (!isset($variableValues[$v])) {
        throw new MathParserException("Missing value for variable $v");
      } else if (!is_numeric($variableValues[$v])) {
        throw new MathParserException("Invalid input value for variable $v");
      }
    }
    $this->variableValues = $variableValues;
    if (empty($this->AST)) {
        return '';
    }
    $out = $this->evalNode($this->AST);
    if ($this->docomplex && !is_array($out)) {
        $out = [$out,0];
    }
    return $out;
  }

  /**
   * Same as evaluate, but returns NaN if there's an error rather than
   * throwing an exception
   * @param  array  $variableValues  Associative array of variables values
   * @return float  value of the function
   */
  public function evaluateQuiet($variableValues = array()) {
    try {
      ob_start(); // buffer any echos so we can ditch them to keep this quiet 
      $out = $this->evaluate($variableValues);
      ob_end_clean(); // ditch buffer contents 
      return $out;
    } catch (Throwable $t) {
      return sqrt(-1);
    } 
  }

  /**
   * Debugging function to print tokens
   * @return void
   */
  public function printTokens () {
    print_r($this->tokens);
  }

  /**
   * Debugging function to print syntax tree
   * @return void
   */
  public function printTree () {
    print_r($this->AST);
  }

  public function compareTrees($a,$b) {
    if ($a['type'] !== $b['type']) {
      return false;
    }
    if ($a['type'] === 'number') {
      return (abs($a['symbol'] - $b['symbol']) < .000001);
    } else if ($a['type'] === 'variable') {
      return $a['symbol'] === $b['symbol'];
    } else if ($a['type'] === 'function') {
      return ($a['symbol'] === $b['symbol']) &&
        $this->compareTrees($a['input'], $b['input']);
    } else {
      return ($a['symbol'] === $b['symbol']) &&
        $this->compareTrees($a['left'], $b['left']) &&
        (!isset($a['right']) || $this->compareTrees($a['right'], $b['right']));
    }
  }

  /**
   * Tokenize the input expression
   * @param  string $str   The INFIX expression to tokenize
   * @return void   The tokens are stored into the class
   */
  public function tokenize($str) {
    $str = preg_replace_callback($this->funcregex, function($m) {
      return strtolower($m[0]);
    }, $str);
    $tokens = [];
    $len = strlen($str);
    $n = 0;
    for ($n=0; $n<$len; $n++) {
      $thistoken = '';
      $c = $str[$n];
      if (ctype_space($c)) {
        // skip spaces
        continue;
      } else if (ctype_digit($c) || $c==='.') {
        // if it's a number/decimal value
        if ($this->allowEscinot) {
            preg_match('/^(\d*\.?\d*(E[\+\-]?\d+(?!\.))?)/', substr($str,$n), $matches);
        } else {
            preg_match('/^(\d*\.?\d*)/', substr($str,$n), $matches);

        }
        if ($matches[1] === '.') { // special case for lone period
            continue;
        }
        $tokens[] = [
          'type'=>'number',
          'symbol'=> (float) $matches[1]
        ];
        $lastTokenType = 'number';
        $n += strlen($matches[1]) - 1;
        continue;
      } else if (($c==='|' || $c==='&' || $c==='#' || $c==='<' || $c==='>') &&
        isset($this->operators[substr($str,$n,2)])
      ) {
        $tokens[] = [
          'type'=>'operator',
          'symbol'=>substr($str,$n,2)
        ];
        $n++;
        $lastTokenType = 'operator';
        continue;
      } else if (isset($this->operators[$c])) {
        // if the symbol matches an operator
        $tokens[] = [
          'type'=>'operator',
          'symbol'=>$c
        ];
        $lastTokenType = 'operator';
        continue;
      } else {
        // look to see if the symbol is in our list of variables and functions
        if (preg_match($this->regex, substr($str,$n), $matches)) {
          $nextSymbol = $matches[1];
          if (in_array($nextSymbol, $this->funcvariables) &&
            strlen($str) > $n+strlen($nextSymbol) &&
            $str[$n+strlen($nextSymbol)] === '('
          ) {
            // found a variable acting as a function 
            $tokens[] = [
              'type'=>'function',
              'symbol'=>'funcvar',
              'input'=>null,
              'index'=>['type'=>'variable', 'symbol'=>$nextSymbol]
            ];
            $lastTokenType = 'function';
          } else if (in_array($nextSymbol, $this->variables)) {
            // found a variable
            $tokens[] = [
              'type'=>'variable',
              'symbol'=>$nextSymbol
            ];
            $lastTokenType = 'variable';
          } else if ($nextSymbol === 'div') {
            $tokens[] = [
                'type'=>'operator',
                'symbol'=>'/'
            ];
            $lastTokenType = 'operator';
          } else {
            // found a function.  We'll handle a couple special cases here too
            if ($nextSymbol === 'log') {
              $tokens[] = [
                'type'=>'function',
                'symbol'=>'log',
                'input'=>null,
                'index'=>['type'=>'number', 'symbol'=>10]
              ];
            } else if ($matches[1] === 'ln') {
              $tokens[] = [
                'type'=>'function',
                'symbol'=>'log',
                'input'=>null,
                'index'=>['type'=>'number', 'symbol'=>M_E]
              ];
            } else if ($matches[1] === 'degree' || $matches[1] === 'degrees') {
                $tokens[] = [
                    'type'=>'number',
                    'symbol'=> M_PI/180
                ];
            } else {
              $tokens[] = [
                'type'=>'function',
                'symbol'=>$nextSymbol,
                'input'=>null
              ];
            }
            $lastTokenType = 'function';
          }
          $n += strlen($nextSymbol) - 1;
          //  need to handle log_2(x) and sin^2(x) as special cases
          //      since they don't follow standard INFIX
          //      For sin^2 we'll attach ^2 to the function, and
          //        can handle it during tree building
          //      For log_2(x) we'll add the base as an element on the token
          //      For log_a(x) we can rewrite as log(x,a)
          //      For sin^p(x) we can rewrite as sin(x)^p
          if ($lastTokenType === 'function' && $n < $len - 2) {
            $peek = $str[$n+1];  // look at upcoming character
            if ($nextSymbol === 'log' && $peek === '_') {
              // found a log_n
              if (preg_match($this->numvarregex, substr($str,$n+2), $sub)) {
                // set the index on the log
                $tokens[count($tokens)-1]['index'] = [
                  'type' => is_numeric($sub[1]) ? 'number' : 'variable',
                  'symbol'=> $sub[1]
                ];
                $n += strlen($sub[1]) + 1;
              } else if ($str[$n+2] === '(') { // handle later
                $tokens[count($tokens)-1]['symbol'] .= '_';
                $n += 1;
              }
            } else if ($peek === '^') {
              // found something like sin^2; append power to symbol for now
              if (preg_match('/^(\-?\d+|\((\-?\d+)\))/', substr($str,$n+2), $sub)) {
                $tokens[count($tokens)-1]['symbol'] .= '^' . (isset($sub[2]) ? $sub[2] : $sub[1]);
                $n += strlen($sub[1]) + 1;
              }
            } else if ($nextSymbol === 'root') {
              // found a root.  Parse the index
              if ($peek === '(') {
                $tokens[count($tokens)-1]['symbol'] .= '(';
              } else if (preg_match('/^[\(\[]*(-?\d+)[\)\]]*/', substr($str,$n+1), $sub)) {
                // replace the last node with an nthroot node
                $tokens[count($tokens)-1] = [
                  'type' => 'function',
                  'symbol' => 'nthroot',
                  'input' => null,
                  'index' => [
                    'type'=>'number',
                    'symbol'=>$sub[1]
                  ]
                ];
                $n += strlen($sub[0]);
              } else {
                throw new MathParserException("Invalid root index");
              }
            } else if ($nextSymbol === 'funcvar') {
                // handle variables acting as functions
                if (preg_match('/^[\(\[](.+?)[\)\]]/', substr($str,$n+1), $sub)) {
                    if (in_array($sub[1], $this->variables)) {
                        $tokens[count($tokens)-1] = [
                            'type' => 'function',
                            'symbol' => 'funcvar',
                            'input' => null,
                            'index' => [
                                'type'=>'variable',
                                'symbol'=>$sub[1]
                            ]
                        ];
                        $n += strlen($sub[0]);
                    } else {
                        throw new MathParserException("Invalid funcvar variable");
                    }
                  } else {
                    throw new MathParserException("Invalid funcvar format");
                  }
            }
          }
          continue ;
        }
        throw new MathParserException("Don't know how to handle symbol: $c.");
      }
    }
    return $this->tokens = $tokens;
  }

  /**
   * Handles implicit multiplication and implicit function definitions
   * Call after tokenize
   * For example:
   *   "2x" -> "2*x"
   *   "sin3x" -> "sin(3)*x"
   * @return void  Updates tokens in class
   */
  public function handleImplicit () {
    $out = [];
    $lastToken = ['type'=>'','symbol'=>''];
    foreach ($this->tokens as $token) {
      if (
        ($lastToken['type'] === 'number' ||
          $lastToken['type'] === 'variable' ||
          $lastToken['symbol'] === '!' ||
          $lastToken['symbol'] === ')'
        ) &&
        ($token['type'] === 'number' ||
          $token['type'] === 'variable' ||
          $token['type'] === 'function' ||
          $token['symbol'] === '('
        )
      ) {
        // implicit multiplication - add *
        $out[] = ['type'=>'operator', 'symbol'=>'*'];
        $out[] = $token;
      } else if ($lastToken['type'] === 'function' && $token['symbol'] !== '(') {
        //fix implicit functions, like sin3
        $out[] = ['type'=>'operator', 'symbol'=>'('];
        $out[] = $token;
        $out[] = ['type'=>'operator', 'symbol'=>')'];
      } else {
        $out[] = $token;
      }
      $lastToken = $token;
    }
    $this->tokens = $out;
  }


  /**
   * Builds the Abstract Syntax tree
   * Call after tokenize
   * @return void  Stores ast in class
   */
  public function buildTree() {
    $this->operatorStack = [];
    $this->operandStack = [];
    $lastNode = null;

    foreach ($this->tokens as $tokenindex => &$token) {
      if ($token['symbol'] === ')') {
        // end of sub expression - handle it
        $this->handleSubExpression($tokenindex);
      } else if ($token['type'] === 'number' || $token['type'] === 'variable') {
        $this->operandStack[] = $token;
      } else if ($token['type'] === 'function') {
        $this->operatorStack[] = $token;
      } else if ($token['symbol'] === '(') {
        $this->operatorStack[] = $token;
      } else if ($token['symbol'] === '!') {
        $unary = $this->isUnary($token, $lastNode);
        if ($unary) { //treat as logical not
          $token['symbol'] = 'not';
          $this->operatorStack[] = $token;
          if (isset($this->tokens[$tokenindex+1]) && $this->tokens[$tokenindex+1]['symbol']==='*') {
            //remove implicit multiplication
            unset($this->tokens[$tokenindex+1]);
          }
        } else { // treat as factorial
          // check to make sure there's something to take factorial of
          if (count($this->operandStack) === 0) {
            throw new MathParserException("Syntax error: ! without something to apply it to");
          }
          $op = array_pop($this->operandStack);
          // rewrite it as a function node
          $this->operandStack[] = [
            'type' => 'function',
            'symbol' => 'factorial',
            'input' => $op
          ];
        }
      } else if ($token['type'] === 'operator') {
        $unary = $this->isUnary($token, $lastNode);
        if ($unary) {
          if ($token['symbol'] === '+') {
            // ignore it by replacing with a blank symbol
            $token = ['type'=>'', 'symbol'=>''];
          } else if ($token['symbol'] === '-') {
            // unary minus; change to ~ to distinguish it.
            $token['symbol'] = '~';
          }
        } else {
          //grab info on current operator
          $curOperator = $this->operators[$token['symbol']];
          // pop operators with higher priority
          while (count($this->operatorStack) > 0) {
            $peek = end($this->operatorStack);
            // get precedence info for the symbols
            $peekinfo = $this->operators[$peek['symbol']];
            $tokeninfo = $this->operators[$token['symbol']];
            if (is_bool($peekinfo) || is_bool($tokeninfo)) {
                break;
            }
            //if lower precedence, or equal and left assoc
            if (
              $tokeninfo['precedence'] < $peekinfo['precedence'] ||
              ($tokeninfo['precedence'] === $peekinfo['precedence'] &&
                $tokeninfo['assoc'] === 'left'
              )
            ) {
              $popped = array_pop($this->operatorStack);
              $popped = $this->handleExpression($popped);
              $this->operandStack[] = $popped;
            } else {
              break;
            }
          }
        }
        if ($token['symbol'] !== '') {
          $this->operatorStack[] = $token;
        }
      }
      if ($token['symbol'] !== '') {
        $lastNode = $token;
      }
    }
    while (count($this->operatorStack) > 0) {
      $popped = array_pop($this->operatorStack);
      $popped = $this->handleExpression($popped);
      $this->operandStack[] = $popped;
    }
    // should be consolidated down now
    if (count($this->operandStack) > 1) {
      throw new MathParserException("Syntax error - expression didn't terminate");
    }

    $this->AST = array_pop($this->operandStack);
  }

  /**
   * Check to see if a token is Unary
   * @param  array  $token     Current token
   * @param  array   $lastNode Previous node
   * @return boolean  True if unary
   */
  private function isUnary($token, $lastNode) {
    // only possible unary symbols are + and -
    if ($token['symbol'] !== '-' && $token['symbol'] !== '+' && $token['symbol'] !== '!') {
      return false;
    }
    // if at very start, or last node was starting paren or unary minus or div
    if ((count($this->operandStack)==0 && count($this->operatorStack)==0) ||
      ($lastNode['type'] === 'operator' && $lastNode['symbol'] !== ')' &&
      $lastNode['symbol'] !== '!')
    ) {
      return true;
    }
    return false;
  }

  /**
   * Populate node with operands
   * @param  array $node  Node to build on
   * @return array  Built up node
   */
  private function handleExpression($node) {
    if ($node['type'] === 'function' || $node['symbol'] === '(') {
      throw new MathParserException("Syntax error - parentheses mismatch");
    }

    if ($node['symbol'] === '~' || $node['symbol'] === 'not') { //unary negation or not
      $left = array_pop($this->operandStack);
      if ($left === null) {
        throw new MathParserException("Syntax error - unary negative with nothing following");
      }
      // unary negation as ['operator', '~', val]
      $node['left'] = $left;
      return $node;
    }

    $right = array_pop($this->operandStack);
    $left = array_pop($this->operandStack);
    if ($left === null || $right === null) {
      throw new MathParserException("Syntax error");
    }
    $node['left'] = $left;
    $node['right'] = $right;
    return $node;
  }

  /**
   * Handle a closing paren, popping operators off the operator stack
   * until we find a matching open paren
   * @return void
   */
  private function handleSubExpression($tokenindex) {
    $clean = false;
    while ($popped = array_pop($this->operatorStack)) {
      if ($popped['symbol'] === '(') {
        $clean = true;
        break;
      }
      $node = $this->handleExpression($popped);
      $this->operandStack[] = $node;
    }

    if (!$clean) {
      throw new MathParserException("Syntax error - parentheses mismatch");
    }

    //see if was function application
    if (count($this->operatorStack) > 0) {
      $previous = end($this->operatorStack);
      if ($previous['type'] === 'function') {
        $node = array_pop($this->operatorStack); //this is the function node
        $operand = array_pop($this->operandStack);
        if ($node['symbol'] === 'log_') {
          if ($operand === null) {
            throw new MathParserException("Syntax error - missing index");
          }
          $node['symbol'] = 'log';
          $node['index'] = $operand;
          $this->operatorStack[] = $node;
          if (isset($this->tokens[$tokenindex+1]) && $this->tokens[$tokenindex+1]['symbol'] === '*') {
            unset($this->tokens[$tokenindex+1]); // remove implicit mult
          };
          return;
        } else if ($node['symbol'] === 'root(') {
          if ($operand === null) {
            throw new MathParserException("Syntax error - missing index");
          }
          $node['symbol'] = 'nthroot';
          $node['index'] = $operand;
          $this->operatorStack[] = $node;
          if (isset($this->tokens[$tokenindex+1]) && $this->tokens[$tokenindex+1]['symbol'] === '*') {
            unset($this->tokens[$tokenindex+1]); // remove implicit mult
          };
          return;
        } else {
          if ($operand === null) {
            throw new MathParserException("Syntax error - missing function input");
          }
          $node['input'] = $operand;  // assign argument to function
        }
        if (strpos($node['symbol'], '^') !== false) { // if it's sin^2, transform now
          list($subSymbol, $power) = explode('^', $node['symbol']);
          if ($power === '-1' && function_exists('a'.$subSymbol)) {
            //treat sin^-1 as asin
            $node['symbol'] = 'arc'.$subSymbol;
          } else {
            //rewrite as power node
            $node['symbol'] = $subSymbol;
            $node = [
              'type'=>'operator',
              'symbol'=>'^',
              'left'=>$node,
              'right'=> [
                'type'=>'number',
                'symbol'=>$power
              ]
            ];
          }
        }
        $this->operandStack[] = $node;
      }
    }
  }

  /**
   * Evaluates the given node
   * @param  array $node  A syntax tree node
   * @return float Value of the node
   */
  private function evalNode($node) {
    if (empty($node)) {
        throw new MathParserException("Cannot evaluate an empty expression");
    }
    if ($node['type'] === 'number') {
      if ($this->docomplex) {
        if (is_array($node['symbol'])) {
          return [floatval($node['symbol'][0]), floatval($node['symbol'][1])];
        } else {
          return [floatval($node['symbol']), 0];
        }
      } else {
        return floatval($node['symbol']);
      }
    } else if ($node['type'] === 'variable') {
      if (isset($this->variableValues[$node['symbol']])) {
        return $this->variableValues[$node['symbol']];
      } else if ($node['symbol'] === 'pi') {
        return M_PI;
      } else if ($node['symbol'] === 'e') {
        return M_E;
      } else if ($this->docomplex && $node['symbol'] === 'i') {
        return [0,1];
      } else {
        throw new MathParserException("Variable found without a provided value");
      }
    } else if ($node['type'] === 'function') {
      // find the value of the input to the function
      $insideval = $this->evalNode($node['input']);
      if (isset($node['index'])) {
        $indexval = $this->evalNode($node['index']);
      }
      $funcname = $node['symbol'];
      // check for syntax errors or domain issues
      if (!$this->docomplex) {
        switch ($funcname) {
          case 'sqrt':
            if ($insideval < 0) {
              throw new MathParserException("Invalid input to $funcname");
            }
            break;
          case 'log':
            if ($insideval <= 0) {
              throw new MathParserException("Invalid input to $funcname");
            }
            if ($indexval <= 0) {
              throw new MathParserException("Invalid base to $funcname");
            }
            break;
          case 'arcsin':
          case 'arccos':
            $insideval = round($insideval, 12);
            if ($insideval < -1 || $insideval > 1) {
              throw new MathParserException("Invalid input to $funcname");
            }
            break;
          case 'arcsec':
          case 'arccsc':
            if ($insideval > -1 && $insideval < 1) {
              throw new MathParserException("Invalid input to $funcname");
            }
            break;
          case 'tan':
          case 'sec':
            if ($this->isMultiple($insideval + M_PI/2, M_PI)) {
              throw new MathParserException("Invalid input to $funcname");
            }
            break;
          case 'cot':
          case 'csc':
            if ($this->isMultiple($insideval, M_PI)) {
              throw new MathParserException("Invalid input to $funcname");
            }
            break;
          case 'nthroot':
            if (floor($indexval)==$indexval && $indexval%2==0 && $insideval<0) {
              throw new MathParserException("no even root of negative");
            }
            break;
          case 'factorial':
            if (round($insideval) !== floor($insideval) || $insideval<0) {
              throw new MathParserException("invalid factorial input ($insideval)");
            } else if ($insideval > 150) {
              throw new MathParserException("too big of factorial input ($insideval)");
            }
            break;
        }
      }
      //rewrite arctan to atan to match php function name
      $funcname = str_replace('arc', 'a', $funcname);
      if ($this->docomplex) {
        $funcname = 'cplx_'.$funcname;
        if (!is_array($insideval)) {
          $insideval = [$insideval, 0];
        }
      }
      if (!empty($node['index'])) {
        return call_user_func($funcname, $insideval, $indexval);
      }
      return call_user_func($funcname, $insideval);
    } else if ($node['symbol'] === '~') {
      // unary negation
      if ($this->docomplex) {
        $ev = $this->evalNode($node['left']);
        if (is_array($ev)) {
          return [-1*$ev[0],-1*$ev[1]];
        } else {
          return -1*$ev;
        }
      } else {
        return -1*$this->evalNode($node['left']);
      }
    } else if ($node['symbol'] === 'not') {
      // unary not
      return !$this->evalNode($node['left']);
    } else if (isset($this->operators[$node['symbol']])) {
      // operator.  We'll use the evalfunc defined for the operator
      $opfunc = $this->operators[$node['symbol']]['evalfunc'];
      return $opfunc(
        $this->evalNode($node['left']),
        $this->evalNode($node['right'])
      );
    } else {
      throw new MathParserException("Syntax error");
    }
  }

  /**
   * Utility function to see if $a is a multiple of $b
   * @param  float  $a
   * @param  float  $b
   * @return boolean
   */
  private function isMultiple($a,$b) {
    if ($b==0) {
      return false;
    }
    $v = abs($a)/abs($b);
    if (abs(floor($v+1e-10) - $v) < 1e-8) {
      return true;
    }
    return false;
  }

  /**
   * Create a string representation of a Node
   * This is not a mathematically accurate representation, just one that
   * can be used to sort elements
   * Unary negation is intentionally invalid so the negative
   * can be ignored in comparison
   * @param  array $node   Node from AST
   * @return string  A string representation of the function
   */
  private function toString($node) {
    if ($node['type'] === 'number' || $node['type'] === 'variable') {
      return $node['symbol'];
    } else if ($node['type'] === 'function') {
      return $node['symbol'] . '(' . $this->toString($node['input']) . ')';
    } else if ($node['type'] === 'operator') {
      if ($node['symbol'] === '~') {
        return '-'.$this->toString($node['left']);
      }
      return $this->toString($node['left']) .
        $node['symbol'] .
        $this->toString($node['right']);
    }
  }

  /**
   * A mathematically valid (but ugly) string representation of the AST
   * @param  array $node   AST node
   * @return string
   */
  public function toOutputString($node) {
    if ($node['type'] === 'number' || $node['type'] === 'variable') {
      return $node['symbol'];
    } else if ($node['type'] === 'function') {
      return $node['symbol'] . '(' . $this->toOutputString($node['input']) . ')';
    } else if ($node['type'] === 'operator') {
      if ($node['symbol'] === '~') {
        return '-('.$this->toOutputString($node['left']).')';
      }
      return '('.$this->toOutputString($node['left']) .
        $node['symbol'] .
        $this->toOutputString($node['right']).')';
    }
  }

  public function removeOneTimes() {
    $this->walkRemoveOne($this->AST);
  }

  /**
   * Normalize the tree and get the result as a string
   * @return string
   */
  public function normalizeTreeString() {
    $this->removeOneTimes();
    // $this->normalizeNodeToString($this->AST);
    //echo $this->toOutputString($this->normalizeNode($this->AST));
    //print_r($this->AST);
    //print_r($this->normalizeNode($this->AST));
    $normed = $this->normalizeNode($this->AST);
    $this->walkRemoveOne($normed);
    return $this->toOutputString($normed);
  }

  /**
   * Normalize the tree and get the result as a string
   * @return string
   */
  public function normalizeTree() {
    return $this->normalizeNode($this->AST);
  }

  /**
   * Sorting function for ordering nodes
   * @param  array $a   AST node
   * @param  array $b   AST node
   * @return int
   */
  private static function nodeSort($a,$b) {
    // first compare types.  For negated nodes, we'll use the type of the
    // node being negated
    $typecmp = strcmp(
      $a['symbol'] === '~' ? $a['left']['type'] : $a['type'],
      $b['symbol'] === '~' ? $b['left']['type'] : $b['type']
    );
    if ($typecmp === 0) {
      // compare strings.  We'll ignore the negative, whether it comes from
      // a negated node or a negative number node.
      return strcmp(
        $a['string'][0] === '-' ? substr($a['string'],1) : $a['string'],
        $b['string'][0] === '-' ? substr($b['string'],1) : $b['string']
      );
    } else {
      return $typecmp;
    }
  }

  /**
   * "Normalizes" a node.  This means that sums/differences and
   * products/quotients of several terms are put in a standard order, and
   * products of several terms are given consistent signs.
   * @param  array $node  Node from AST
   * @return array  normalized AST node
   */
  private function normalizeNode($node) {
    if ($node['type'] === 'number' || $node['type'] === 'variable') {
      // nothing to normalize for these
      return $node;
    } else if ($node['type'] === 'function') {
      // recurse into input
      $node['input'] = $this->normalizeNode($node['input']);
      return $node;
    } else if ($node['symbol'] === '~') {
      // recurse in
      $node['left'] = $this->normalizeNode($node['left']);
      if ($node['left']['symbol'] === '*') {
        // if we have the opposite of a product, move the negative to the first element of the product
        $node['left']['left'] = $this->negNode($node['left']['left']);
        return $node['left'];
      } else if ($node['left']['type'] === 'number') {
        $node['left']['symbol'] = -1*$node['left']['symbol'];
        return $node['left'];
      } else {
        return $node;
      }
    } else if ($node['symbol'] === '^') {
      // recurse in. We're not doing any reordering for these
      $node['left'] = $this->normalizeNode($node['left']);
      $node['right'] = $this->normalizeNode($node['right']);
      return $node;
    } else if ($node['symbol'] !== '+' && $node['symbol'] !== '-' && $node['symbol'] !== '*' && $node['symbol'] !== '/' ) {
      // catch any weird operators that might have snuck in
      return $node;
    } else {
      //$node['left'] = $this->normalizeNode($node['left']);
      //$node['right'] = $this->normalizeNode($node['right']);
      // for +- and */ we're going to gather all the equal-precendence
      // elements then sort them into a standardized order and rebuild tree
      if ($node['symbol'] === '+' || $node['symbol'] === '-') {
        $basesym = '+';
      } else if ($node['symbol'] === '*' || $node['symbol'] === '/') {
        $basesym = '*';
      }
      $allSums = [];


      // walk into node to gather elements
      $this->treeWalk($node, $allSums);

      $invert = false;
      usort($allSums, self::class . '::nodeSort');
      
      if ($basesym === '+' && ($allSums[0]['symbol'] === '~' ||
        ($allSums[0]['type'] === 'number' && $allSums[0]['symbol'] < 0))
      ) {
        // if first element of sum is negative, we'll invert it
        $invert = true;
        for ($i=0;$i<count($allSums);$i++) {
          $allSums[$i] = $this->negNode($allSums[$i]);
        }
      } else if ($basesym === '*') {
        $flip = 1;
        // for set of products, make all positive except first, then adjust
        // the first to keep in balanced
        for ($i=1;$i<count($allSums);$i++) {
          if (($allSums[$i]['symbol'] === '~' ||
            ($allSums[$i]['type'] === 'number' && $allSums[$i]['symbol'] < 0))
          ) {
            $flip *= -1;
            $allSums[$i] = $this->negNode($allSums[$i]);
          }
        }
        if ($flip === -1) {
          $allSums[0] = $this->negNode($allSums[0]);
        }
      }
      // rebuild tree using sorted notes
      $newNode = [];
      for ($i=1;$i<count($allSums);$i++) {
        $tmpnode = [
          'type' => 'operator',
          'symbol' => $basesym,
          'right' => $allSums[$i]
        ];
        if ($i === 1) {
          $tmpnode['left'] = $allSums[0];
        } else {
          $tmpnode['left'] = $newNode;
        }
        $newNode = $tmpnode;
      }
      if ($invert) {
        return $this->negNode($newNode);
      } else {
        return $newNode;
      }
    }
  }

  /**
   * Additive inverse of a node
   * @param  array $node  Node from AST
   * @return array negated node
   */
  private function negNode($node) {
    if ($node['symbol'] === '~') {
      return $node['left'];
    } else if ($node['type'] === 'number') {
      $node['symbol'] *= -1;
      return $node;
    } else if ($node['symbol'] === 'div') {
      // for pseudo-division, negate the inside instead
      $node['input'] = $this->negNode($node['input']);
      return $node;
    } else {
      return [
        'type' => 'operator',
        'symbol' => '~',
        'left' => $node
      ];
    }
  }

  /**
   * "Normalizes" a node.  This means that sums/differences and
   * products/quotients of several terms are put in a standard order, and
   * products of several terms are given consistent signs.
   *
   * This version returns a string.  It isn't fully optimized, as it still
   * uses normalizeNode in recursion
   *
   * @param  array $node  Node from AST
   * @return string   string representation of normalized AST node
   */
  private function normalizeNodeToString($node) {
    if ($node['type'] === 'number' || $node['type'] === 'variable') {
      // nothing to normalize for these
      return $node['symbol'];
    } else if ($node['type'] === 'function') {
      // recurse into input
      return $node['symbol'].'('.$this->normalizeNodeToString($node['input']).')';
    } else if ($node['symbol'] === '~') {
      // recurse in
      return '-('.$this->normalizeNodeToString($node['left']).')';
    } else if ($node['symbol'] === '^') {
      // recurse in. We're not doing any reordering for these
      return $this->normalizeNodeToString($node['left']) . '^' .
        $this->normalizeNodeToString($node['right']);
    } else {
      // for +- and */ we're going to gather all the equal-precendence
      // elements then sort them into a standardized order and rebuild tree
      if ($node['symbol'] === '+' || $node['symbol'] === '-') {
        $basesym = '+';
      } else if ($node['symbol'] === '*' || $node['symbol'] === '/') {
        $basesym = '*';
      }
      $allSums = [];
      // walk into node to gather elements
      $this->treeWalk($node, $allSums);
      
      $invert = false;
      usort($allSums, 'self::nodeSort');
      $invert = false;
      if ($basesym === '+' && ($allSums[0]['symbol'] === '~' ||
        ($allSums[0]['type'] === 'number' && $allSums[0]['symbol'] < 0))
      ) {
        // if first element of sum is negative, we'll invert it
        $invert = true;
        for ($i=0;$i<count($allSums);$i++) {
          $allSums[$i] = $this->negNode($allSums[$i]);
        }
      } else if ($basesym === '*') {
        $flip = 1;
        // for set of products, make all positive except first, then adjust
        // the first to keep in balanced
        for ($i=1;$i<count($allSums);$i++) {
          if (($allSums[$i]['symbol'] === '~' ||
            ($allSums[$i]['type'] === 'number' && $allSums[$i]['symbol'] < 0))
          ) {
            $flip *= -1;
            $allSums[$i] = $this->negNode($allSums[$i]);
          }
        }
        if ($flip === -1) {
          $allSums[0] = $this->negNode($allSums[0]);
        }
      }
      // rebuild tree using sorted notes
      $newNode = [];
      for ($i=0;$i<count($allSums);$i++) {
        $newNode[] = $this->toOutputString($allSums[$i]);
      }
      if ($invert) {
        return '-(' . implode($basesym, $newNode).')';
      } else {
        return implode($basesym, $newNode);
      }
    }
  }

  /**
   * Walk the AST tree, building a collection of elements at the same
   * precedence level (e.g. all the items being added together)
   *  - Converting subtraction to addition by negative
   *  - Converting division to multiplication by pseudo-function 'div'
   *  - Adding the left and right elements to the collection if they are a
   *    different type of node.  If same type, recurse into those nodes
   * @param  array $node        Node in the AST
   * @param  array $collection  Elements get added to this
   * @return void
   */
  private function treeWalk(&$node, &$collection) {

    if ($node['symbol'] === '-') {
      // convert 3-4 to 3 + -4
      $node['symbol'] = '+';
      $node['right'] = $this->negNode($node['right']);
    } else if ($node['symbol'] === '/') {
      // convert 3/4 to 3*div(4)
      $node['symbol'] = '*';
      $node['right'] = [
        'type' => 'function',
        'symbol' => 'div',
        'input' => $node['right']
      ];
    }
    $sym1 = '';
    if ($node['symbol'] === '+') {
      $sym1 = '+';
      $sym2 = '-';
    } else if ($node['symbol'] === '*') {
      $sym1 = '*';
      $sym2 = '/';
    }

    if ($sym1 !== '') {
      if ($node['left']['symbol'] === '~' && ($node['left']['left']['symbol'] === $sym1 || $node['left']['left']['symbol'] === $sym2)) {
        $node['left'] = $this->normalizeNode($node['left']);
      }
      if ($node['left']['symbol'] === $sym1 || $node['left']['symbol'] === $sym2) {
        // same precedence - recurse into node
        $this->treeWalk($node['left'], $collection);
      } else {
        // add node to collection.
        $node['left'] = $this->normalizeNode($node['left']);
        // build string for comparison
        $node['left']['string'] = (string) $this->toString($node['left']);
        $collection[] = $node['left'];
      }
      if ($node['right']['symbol'] === '~' && ($node['right']['left']['symbol'] === $sym1 || $node['right']['left']['symbol'] === $sym2)) {
        $node['right'] = $this->normalizeNode($node['right']);
      }
      if ($node['right']['symbol'] === $sym1 || $node['right']['symbol'] === $sym2) {
        // same precedence - recurse into node
        $this->treeWalk($node['right'], $collection);
      } else {
        // add node to collection
        
        $node['right']= $this->normalizeNode($node['right']);
        $node['right']['string'] = (string) $this->toString($node['right']);
        $collection[] = $node['right'];
      }
    }
  }

  private function walkRemoveOne(&$node) {
    if ($node['symbol'] === '*') {
      if ($node['right']['symbol'] === 1.0) {
        $node = $node['left'];
        $this->walkRemoveOne($node);
        return;
      } else if ($node['left']['symbol'] === 1.0) {
        $node = $node['right'];
        $this->walkRemoveOne($node);
        return;
      } else if ($node['left']['symbol'] === '~' &&
        $node['left']['left']['symbol'] === 1.0
      ) {
        if ($node['right']['symbol'] === '~') { // both neg; remove both negs
          $node = $node['right']['left'];
        } else { // make right neg and remove a level
          $node['left']['left'] = $node['right'];
          $node = $node['left'];
        }
      } else if ($node['right']['symbol'] === '~' &&
        $node['right']['left']['symbol'] === 1.0
      ) {
        if ($node['left']['symbol'] === '~') { // both neg; remove both negs
          $node = $node['left']['left'];
        } else { // make left neg and remove a level
          $node['right']['left'] = $node['left'];
          $node = $node['right'];
        }
      }
    }
    if (isset($node['left'])) {
      $this->walkRemoveOne($node['left']);
    }
    if (isset($node['right'])) {
      $this->walkRemoveOne($node['right']);
    }
    if (isset($node['input'])) {
      $this->walkRemoveOne($node['input']);
    }
  }
}



/**
 * An exception class for parsing errors
 */
class MathParserException extends Exception
{
  private $data = '';
  public function __construct($message, $data = '') {
    parent::__construct($message);
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }
}

/**
 * Define math functions not native to PHP
 */
// math functions not native in php
function factorial($x) {
	for ($i=$x-1;$i>0;$i--) {
		$x *= $i;
	}
	return ($x<0?false:($x==0?1:$x));
}

function nthroot($x,$n) {
	if ($x==0) {
      return 0;
  } else if (floor($n) != $n) {
    return safepow($x, 1/$n);
  } else if ($n%2==0 && $x<0) { //if even root and negative base
      throw new MathParserException("Can't take even root of negative value");
	} else if ($n==0) {
      throw new MathParserException("Can't take 0th root");
    } else if ($x<0) { //odd root of negative base - negative result
		return -1*exp(1/$n*log(abs($x)));
	} else { //root of positive base
		return exp(1/$n*log(abs($x)));
	}
}

function funcvar ($input, $v) {
    return $v*sin($v + $input);
}

// a safer power function that can handle (-8)^(1/3)
function safepow($base,$power) {
	if ($base==0) {
    if($power==0) {
      echo "0^0 is undefined";
      return NAN;
    } else {
      return 0;
    }
  }
  if (!is_numeric($base) || !is_numeric($power)) {
    echo "cannot evaluate powers with nonnumeric values";
    return NAN;
  }
	if ($base<0 && floor($power)!=$power) {
		for ($j=3; $j<50; $j+=2) {
			if (abs(round($j*$power)-($j*$power))<.000001) {
				if (round($j*$power)%2==0) {
					return exp($power*log(abs($base)));
				} else {
					return -1*exp($power*log(abs($base)));
				}
			}
		}
		echo "invalid power for negative base";
        return NAN;
	}
	if (floor($base)==$base && floor($power)==$power && $power>0) { //whole # exponents
		$result = pow(abs($base),$power);
	} else { //fractional & negative exponents (pow can't handle?)
		$result = exp($power*log(abs($base)));
	}
	if (($base < 0) && ($power % 2 !== 0)) {
		$result = -($result);
	}
	return $result;
}
//basic trig cofunctions
function sec($x) {
  $val = cos($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for sec";
    return NAN;
  }
	return (1/$val);
}
function csc($x) {
  $val = sin($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for csc";
    return NAN;
  }
	return (1/$val);
}
function cot($x) {
  $val = tan($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for cot";
    return NAN;
  }
	return (1/$val);
}
function sech($x) {
  $val = cosh($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for sech";
    return NAN;
  }
	return (1/$val);
}
function csch($x) {
  $val = sinh($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for csch";
    return NAN;
  }
	return (1/$val);
}
function coth($x) {
  $val = tanh($x);
  if (abs($val)<1e-16) {
    echo "Invalid input for coth";
    return NAN;
  }
	return (1/$val);
}
function asec($x) {
  if (abs($x)<1e-16) {
    echo "Invalid input for arcsec";
    return NAN;
  }
  $inv = round(1/$x, 12);
  if ($inv < -1 || $inv > 1) {
    echo "Invalid input for arcsec";
    return NAN;
  }
  return acos($inv);
}
function acsc($x) {
  if (abs($x)<1e-16) {
    echo "Invalid input for arccsc";
    return NAN;
  }
  $inv = round(1/$x, 12);
  if ($inv < -1 || $inv > 1) {
    echo "Invalid input for arccsc";
    return NAN;
  }
  return asin($inv);
}
function acot($x) {
    if (abs($x)<1e-16) {
        echo "Invalid input for arccot";
        return NAN;
    }
    return atan(1/$x);
}
function asech($x) {
    if (abs($x)<1e-16) {
        echo "Invalid input for arcsech";
        return NAN;
    }
    $inv = round(1/$x, 12);
    if ($inv < 1) {
        echo "Invalid input for arcsech";
        return NAN;
    }
    return acosh($inv);
}
function acsch($x) {
    if (abs($x)<1e-16) {
        echo "Invalid input for arccsch";
        return NAN;
    }
    $inv = round(1/$x, 12);
    return asinh($inv);
}
function acoth($x) {
    if (abs($x)<1e-16) {
        echo "Invalid input for arccoth";
        return NAN;
    }
    $inv = round(1/$x, 12);
    if ($inv < -1 || $inv > 1) {
        echo "Invalid input for arccoth";
        return NAN;
    }
    return atanh($inv);
}
function safeasin($x) {
  $inp = round($x, 12);
  if ($inp < -1 || $inp > 1) {
    echo "Invalid input for arcsin";
    return NAN;
  }
  return asin($inp);  
}
function safeacos($x) {
  $inp = round($x, 12);
  if ($inp < -1 || $inp > 1) {
    echo "Invalid input for arccos";
    return NAN;
  }
  return acos($inp);  
}
function sign($a,$str=false) {
	if ($str==="onlyneg") {
		return ($a<0)?"-":"";
	} else if ($str !== false) {
		return ($a<0)?"-":"+";
	} else {
		return ($a<0)?-1:1;
	}
}
function sgn($a, $loose=false) {
	return ($a == 0 || ($loose && abs($a)<1e-8))?0:(($a<0)?-1:1);
}


function cplx_subt($a,$b) {
    return [$a[0]-$b[0], $a[1]-$b[1]];
}
function cplx_mult($a,$b) {
  return [$a[0]*$b[0] - $a[1]*$b[1],$a[0]*$b[1]+$a[1]*$b[0]];
}
function cplx_div($n,$d) {
  $ds = $d[0]*$d[0] + $d[1]*$d[1];
  if ($ds == 0) {
    throw new MathParserException("Cannot divide by zero in complex division");
  }
  return [($n[0]*$d[0] + $n[1]*$d[1])/$ds, ($n[1]*$d[0]-$n[0]*$d[1])/$ds];
}
function cplx_sqrt($z) {
  return cplx_nthroot($z,2);
}
function cplx_nthroot($z,$b) {
  $r = $z[0]*$z[0] + $z[1]*$z[1];
  $m = safepow($r, $b/(2*$b));
  $t = atan2($z[1],$z[0]);
  return [$m*cos($t/$b), $m*sin($t/$b)];
}
function cplx_log($z, $b = M_E) {
  $r = sqrt($z[0]*$z[0] + $z[1]*$z[1]);
  $t = atan2($z[1],$z[0]);
  return [log($r,$b), $t/log($b)];
}
function cplx_sin($z) {
  return [sin($z[0])*cosh($z[1]), cos($z[0])*sinh($z[1])];
}
function cplx_cos($z) {
  return [cos($z[0])*cosh($z[1]), -1*sin($z[0])*sinh($z[1])];
}
function cplx_tan($z) {
  return cplx_div(cplx_sin($z), cplx_cos($z));
}
function cplx_sec($z) {
  return cplx_div([1,0], cplx_cos($z));
}
function cplx_csc($z) {
  return cplx_div([1,0], cplx_sin($z));
}
function cplx_cot($z) {
  return cplx_div(cplx_cos($z), cplx_sin($z));
}
function cplx_sinh($z) {
  return [sinh($z[0])*cos($z[1]), cosh($z[0])*sin($z[1])];
}
function cplx_cosh($z) {
  return [cosh($z[0])*cos($z[1]), sinh($z[0])*sin($z[1])];
}
function cplx_tanh($z) {
  return cplx_div(cplx_sinh($z), cplx_cosh($z));
}
function cplx_sech($z) {
  return cplx_div([1,0], cplx_cosh($z));
}
function cplx_csch($z) {
  return cplx_div([1,0], cplx_sinh($z));
}
function cplx_coth($z) {
  return cplx_div(cplx_cosh($z), cplx_sinh($z));
}
function cplx_asinh($z) {
  $r = cplx_sqrt([$z[0]*$z[0] - $z[1]*$z[1] + 1,2*$z[0]*$z[1]]);
  return cplx_log([$z[0] + $r[0], $z[1] + $r[1]]);
}
function cplx_acosh($z) {
    $r = cplx_sqrt([$z[0]*$z[0] - $z[1]*$z[1] - 1,2*$z[0]*$z[1]]);
    return cplx_log([$z[0] + $r[0], $z[1] + $r[1]]);
}
function cplx_atanh($z) {
    return cplx_mult([.5,1], cplx_log(cplx_div([1+$z[0],$z[1]], [1-$z[0],-1*$z[1]])));
}
function cplx_asech($z) {
    return cplx_acosh(cplx_div([1,0],$z));
}
function cplx_acsch($z) {
    return cplx_asinh(cplx_div([1,0],$z));
}
function cplx_acoth($z) {
    return cplx_mult([.5,1], cplx_log(cplx_div([$z[0]+1,$z[1]], [$z[0]-1,$z[1]])));
}
function cplx_asin($z) {
  // -i*  ln(iz + sqrt(1-z^2))  
  $zz = [$z[0]*$z[0] - $z[1]*$z[1], 2*$z[0]*$z[1]];
  $s = cplx_nthroot([1 - $zz[0], -1*$zz[1]], 2);
  $in = [$s[0] - $z[1], $s[1] + $z[0]];
  $ln = cplx_log($in);
  return [$ln[1], -1*$ln[0]];
}
function cplx_acos($z) {
  // -i*  ln(z + sqrt(z^2-1))  
  $zz = [$z[0]*$z[0] - $z[1]*$z[1], 2*$z[0]*$z[1]];
  $s = cplx_nthroot([$zz[0] - 1, $zz[1]], 2);
  $in = [$s[0] + $z[0], $s[1] + $z[1]];
  $ln = cplx_log($in);
  return [$ln[1], -1*$ln[0]];
}
function cplx_atan($z) {
  // -2i * ln((i-z)/(i+z)) 
  $ln = cplx_log(cplx_div([-1*$z[0], 1 - $z[1]], [$z[0], 1+$z[1]]));
  return [2*$ln[1], -2*$ln[0]];
}
function cplx_asec($z) {
  // -i*ln((1+sqrt(1-z^2))/z)
  $zz = [$z[0]*$z[0] - $z[1]*$z[1], 2*$z[0]*$z[1]];
  $s = cplx_nthroot([1 - $zz[0], -1*$zz[1]], 2);
  $in = cplx_div([1+$s[0],$s[1]], $z);
  $ln = cplx_log($in);
  return [$ln[1], -1*$ln[0]];
}
function cplx_acsc($z) {
  // -i*ln((i+sqrt(z^2-1))/z)
  $zz = [$z[0]*$z[0] - $z[1]*$z[1], 2*$z[0]*$z[1]];
  $s = cplx_nthroot([$zz[0] - 1, $zz[1]], 2);
  $in = cplx_div([$s[0],1+$s[1]], $z);
  $ln = cplx_log($in);
  return [$ln[1], -1*$ln[0]];
}
function cplx_acot($z) {
  // -2i * ln((z+i)/(z-i)) 
  $ln = cplx_log(cplx_div([$z[0], $z[1]+1], [$z[0], $z[1]-1]));
  return [2*$ln[1], -2*$ln[0]];
}
function cplx_funcvar($input, $v) {
  if (!is_array($input)) { $input = [$input,0];}
  if (!is_array($v)) { $v = [$v,0];}
  return cplx_mult($v,cplx_sin([$v[0] + $input[0], $v[1] + $input[1]]));
}

