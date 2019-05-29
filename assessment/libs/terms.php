<?php
global $allowedmacros;
array_push($allowedmacros,"isSumOf","isProductOf","getPolynomialDegree","isPolynomic","isInstanceOf");

class Term {
  public function __construct($string,$vars='x') {
    //parent::__construct($vars);
    $parser = new MathParser($vars);
    $this->term = $parser->parse($string);
  }

  public function getTerm() {
    return $this->term;
  }
  // Getting arguments of top operator as Arrays
  public function getArgs($term = null) {
    if ($term === null) {
      $term = $this->term;
    }
    $args=[];
    switch ($term['type']) {
      case 'operator':
        if (array_key_exists('left',$term)) {
          array_push($args,$term['left']);
        }
        if (array_key_exists('right',$term)) {
          array_push($args,$term['right']);
        }
        break;
      case 'function':
        if (array_key_exists('input',$term)) {
          $args=[$term['input']];
        }
        break;
      default:
    }
    return $args;
  }

  // getting list of variables actually occuring in term
  public function getVariables($term = null) {
    if ($term === null) {
      $term=$this->term;
    }
    $vars=[];
    if ($term['type'] == 'variable') {
      return [$term['symbol']];
    } else {
      $args=$this->getArgs($term);
      for ($i=0; $i<count($args); $i++) {
        $vars=array_merge($vars,$this->getVariables($args[$i]));
      }
    }
    return array_unique($vars);
  }

  private function neg($t) {
    return ['type' => 'operator', 'symbol' => '~', 'left' => $t];
  }

  private function diff2Sum($t) {
    if ($t['symbol'] == '-') {
      $negRight=($t['right']['symbol'] == '~' ? $t['right']['left'] : $this->neg($t['right']));
      return ['type' => 'operator', 'symbol' => '+', 'left' => $t['left'], 'right' => $negRight];
    } else return $t;
  }

  private function inv($t) {
    if ($t['symbol'] == '/')
      return ['type' => 'operator', 'symbol' => '/', 'left' => $t['right'], 'right' => $t['left']];
    else
      return ['type' => 'operator', 'symbol' => '/', 'left' => ['type' => 'number', 'symbol' => '1'], 'right' => $t];
  }

  private function quot2Prod($t) {
    if ($t['symbol'] == '/') {
      return ['type' => 'operator', 'symbol' => '*', 'left' => $t['left'], 'right' => $this->inv($t['right'])];
    } else return $t;
  }

  public function isSumOf($term = null, $sloppy='+') {
    if ($term === null) {
      $term = $this->term;
    }
    $args=[];
    $op=$term['symbol'];
    switch ($op) {
      case '+':
        $args=$this->isSumOf($term['left'],$sloppy);
        return array_merge($args,$this->isSumOf($term['right'],$sloppy));
        break;
      case '-':
        if (strpos($sloppy,'+') !== false) {
          $args=$this->isSumOf($term['left'],$sloppy);
          $right=$this->neg($term['right'],$sloppy);
          $args[] = $this->toString($right);
          return $args;
        } else
          return [$this->toString($term)];
        break;
      default:
        return [$this->toString($term)];
    }
  }

  public function isProductOf($term = null, $sloppy='') {
    if ($term === null) {
      $term = $this->term;
    }
    $args=[];
    $op=$term['symbol'];
    switch ($op) {
      case '*':
        $args=$this->isProductOf($term['left'],$sloppy);
        return array_merge($args,$this->isProductOf($term['right'],$sloppy));
        break;
      case '/':
        if (strpos($sloppy,'*') !== false) {
          $args=$this->isProductOf($term['left'],$sloppy);
          $right=$this->inv($term['right']);
          $args[] = $this->toString($right);
          return $args;
        } else
          return [$this->toString($term)];
        break;
      default:
        return [$this->toString($term)];
    }
    return [$this->toString($term)];
  }

  public function toString($term = null) {
  if ($term === null) {
    $term = $this->term;
  }
  switch ($term['type']) {
    case 'number':
    case 'variable':
      return $term['symbol'];
      break;
    case 'operator':
      if ($term['symbol'] == '~') {
        return '(-'.$this->toString($term['left']).')';
      }
      $sl='';
      $sr='';
      if (array_key_exists('left',$term)) {
        $sl='('.$this->toString($term['left']).')';
      }
      if (array_key_exists('right',$term)) {
        $sr='('.$this->toString($term['right']).')';
      }
      return $sl.$term['symbol'].$sr;
      break;
    case 'function':
      if (array_key_exists('input',$term)) {
          return $term['symbol'].'('.$this->toString($term['input']).')';
      }
      break;
    default:
          return "Unhandled ".$term['symbol'];
    }
  }

  // Getting degree of sums of products of constants and powers of veriables
  public function getPolyDegree($var='x',$term=null) {
     if ($term === null) {
      $term = $this->term;
    }
    $op = $term['symbol'];
    if ($op == '+' || $op == '-' || $op == '~') {
      $dl=$this->getPolyDegree($var,$term['left']);
      if ($dl == -1) return -1;
      $dr=0;
      if (array_key_exists('right', $term)) {
        $dr=$this->getPolyDegree($var,$term['right']);
        if ($dr == -1) return -1;
      }
      return ($dl < $dr ? $dr : $dl);
    } else {
      return $this->getMonoDegree($var, $term);
    }
  }

  /**
   * Getting degree of products of constants and powers of polynomial veriable
   * Note that coefficients are not evaluated
   * par $var - polynomial variable, $term - term to be tested
   * ret - Degree of polynomial or -1 if $term is not a polynomial
   */
  private function getMonoDegree($var='x',$term=null) {
     if ($term === null) {
      $term = $this->term;
    }
    if ($term['symbol'] == $var) {
      return 1;
    }
    if ($term['symbol'] == '*') {
      $dl=$this->getMonoDegree($var,$term['left']);
      if ($dl == -1) return -1;
      $dr=$this->getMonoDegree($var,$term['right']);
      if ($dr == -1) return -1;
      return $dl + $dr;
    }
    if ($term['symbol'] == '^') {
      if ($term['left']['symbol'] == $var) {
        $v=(float)$term['right']['symbol'];
        if (fmod($v,1) === 0.0 && $v >= 0) {
          return $term['right']['symbol'];
        } else {
          return -1;
        }
      }
    }
    if (! in_array($var, $this->getVariables($term)))  return 0;
    return -1;
  }

  // Testing whether term is in polynomial ring in variable
  public function isPolynomic($var='x',$term = null) {
    if ($term === null) {
      $term = $this->term;
    }
    switch ($term['type']) {
      case 'variable':
      case 'number':
        return true;
        break;
      case 'function':
      case 'operator':
        $args=$this->getArgs($term);
        switch ($term['symbol']) {
          case '^':
            if ($term['right']['type'] == 'number' && $this->isPolynomic($var,$args[0])) {
              $v=(float)$term['right']['symbol'];
              if (fmod($v,1) === 0.0 && $v >= 0) {
                return true;
              }
              return false;
            } else {return false;}
            break;
          case '+':
          case '-':
          case '~':
          case '*':
            foreach ($args as $a) {
              if (! $this->isPolynomic($var,$a)) {
                return false;
              }
            }
            return true;
            break;
          default:
            if (! in_array($var, $this->getVariables($term))) {
              return true;
            } else {
              return false;
            }
        }
        break;
      default: return false;
    }
  }

  public function instanceOf($template, $sloppy='+',$term=null,$inst=[]) {
    if ($term === null) $term=$this->term;
    if ($template['type'] == 'variable') {
      $x=$template['symbol'];
      $termString=$this->toString($term);
      if ($x == 'pi' || $x == 'e') {
        if ($x == $termString)
          return ['result'=>true, 'instantiation'=>[]];
        else
          return ['result'=> false];
      }
      if (array_key_exists($x,$inst)) {
        if ($inst[$x] != $termString) {
          return ['result'=>false];
        }
      } else {$inst[$x]=$termString;}
      return ['result'=>true,'instantiation'=>$inst];
    }
    if ($term['symbol'] == $template['symbol']) {
      if (array_key_exists('left',$term) & array_key_exists('left',$template)) {
        $left = $this->instanceOf($template['left'],$sloppy,$term['left'],$inst);
        if ($left['result']) {
          foreach ($left['instantiation'] as $var=>$value) {
            if (array_key_exists($var,$inst) && $inst[$var] != $value)
              return ['result'=>false];
            else
              $inst[$var]=$value;
          }
        } else return ['result' => false];
      }
      if (array_key_exists('right',$term) & array_key_exists('right',$template)) {
        $right = $this->instanceOf($template['right'],$sloppy,$term['right'],$inst);
        if ($right['result']) {
          foreach ($right['instantiation'] as $var=>$value) {
            if (array_key_exists($var,$inst) && $inst[$var] != $value)
              return ['result'=>false];
            else
              $inst[$var]=$value;
          }
        } else return ['result'=>false];
      }
      return ['result'=>true,'instantiation'=>$inst];
    } else {
      if (strpos($sloppy,'+') !== false) {
        if ($term['symbol'] == '-' && $template['symbol'] == '+')
          return $this->instanceOf($template,$sloppy,$this->diff2Sum($term),$inst);
        if ($term['symbol'] == '+' && $template['symbol'] == '-')
          return $this->instanceOf($this->diff2Sum($template),$sloppy,$term,$inst);
        if ($template['symbol'] == '~')
          return $this->instanceOf($template['left'],$sloppy,$this->neg($term));
      }
      if (strpos($sloppy,'*') !== false) {
        if ($term['symbol'] == '/' && $template['symbol'] == '*')
          return $this->instanceOf($template,$sloppy,$this->quot2Prod($term),$inst);
        if ($term['symbol'] == '*' && $template['symbol'] == '/')
          return $this->instanceOf($this->quot2Prod($template),$sloppy,$term,$inst);
      }

    }
    return ['result' => false];
  }
}

/**
 * isSumOf calculates the array of summands of the term given in $termString
 * @param string $termString - the string containing the term to be tested
 * @param string $vars - comma-separated list of variables in $termString
 * @param string $sloppy - if it contains +, t1-t2 is handled as t1+(-t2)
 * @return array array of summands
 */
function isSumOf($termString,$vars='x',$sloppy='+') {
  $t = new Term($termString,$vars);
  return $t->isSumOf(null,$sloppy);
}

/**
 * isProductOf calculates the array of factors of the term given in $termString
 * @param string $termString - the string containing the term to be tested
 * @param string $vars - comma-separated list of variables in $termString
 * @param string $sloppy - if it contains *, t1/t2 is handled as t1*(1/t2)
 * @return array array of factors
 */
function isProductOf($termString,$vars='x',$sloppy='') {
  $t = new Term($termString,$vars);
  return $t->isProductOf(null,$sloppy);
}

/**
 * isPolynomic tests whether a given term in the variable $pvar can be obtained from this variable and terms not containing this variable  by addition, multiplication and power only.
 * @param string $termString -String containing term to be tested
 * @param string $vars - all variables in $termstring as comma-separated list
 * @return boolean indicates whether the given term is in the respective polynomial ring
 */
function isPolynomic($termString,$vars='x',$pvar='x') {
  $t = new Term($termString,$vars);
  return $t->isPolynomic($pvar);
}

/**
 * getPolynomialDegree tests whether a given term is a polynomial in a particular variable
 * A polynomial as understood here, is a sum of products of powers of the polynomial variable
 * and terms not containing the polynomial variable.
 * No particular order of the subterms is expected.
 * @param string $termstring - String containing the term to be tested
 * @param string $vars - list of all variables occuring in $termstring. Optional, default is 'x'
 * @param string $pvar - variable of the polynomial. Optional, default is 'x'
 * @return number Degree of the polynomial, -1 if $termstring is not a polynomial
 */
function getPolynomialDegree($termString,$vars='x',$pvar='x') {
  $t = new Term($termString,$vars);
  return $t->getPolyDegree($pvar);
}

/**
 * isInstanceOf Tests whether the term in $termString is an instance of one of the terms in $templateString
 * @param string $termString String containing term to be tested
 * @param string $templateString '|'-separated list of templates
 * @param string $vars List of variables occuring $termString and $templateString. Optional, deafults to 'x'
 * @param string $sloppy - list of operators +,*. If + resp. * is in this list, t1-t2, resp. t1/t2 may be handled as t1+~t2 resp. t1*(1/t2) if this allows additional matches.
 * @return array 'result' => true or false, indicating success of instantiation,
 *  if 'result' is true, 'instantiation' => array containing variable => string with instance of variable
 *  if 'result' is true, 'template' => first template which matched successfully
 */
function isInstanceOf($termString,$templateString,$vars='x',$sloppy='+') {
  $term = new Term($termString,$vars);
  $templates=explode('|',$templateString);
  foreach ($templates as $template) {
    $templateTerm = new Term($template,$vars);
    $instTest=$term->instanceOf($templateTerm->getTerm(),$sloppy);
    if ($instTest['result']) {
      $instTest['template']=trim($template);
      return $instTest;
    }
  }
  return ['result'=> false];
}