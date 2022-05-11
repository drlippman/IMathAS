<?php

require("../init.php");
require('mathparser.php');

if ($myrights < 100) {
  exit;
}


$tests = [
 ['log(100)', [], 2],
 ['ln(e^3)', [], 3],
 ['log_3(9)', [], 2],
 ['1/2 + 4/8', [], 1],
 ['1+2*3', [], 7],
 ['8/4/2', [], 1],
 ['2^3^2', [], 512],
 ['2*3^2', [], 18],
 ['3!', [], 6],
 ['6/-2', [], -3],
 ['6*-2', [], -12],
 ['-6/-2', [], 3],
 ['-3^2', [], -9],
 ['(-3)^2', [], 9],
 ['-x^2', ['x'=>3], -9],
 ['-x^2', ['x'=>-3], -9],
 ['2^-2', [], .25],
 ['2^-2', [], .25],
 ['(1+2)!',[], 6],
 ['2^sqrt(9)', [], 8],
 ['3!(1+1)', [], 12],
 ['3!a', ['a'=>3], 18],
 ['2*3^2', [], 18],
 ['2^3!', [], 64],
 ['8sin(pi/2)/4', [], 2],
 ['4arcsin(sqrt(2)/2)/pi', [], 1],
 ['root(3)(8) + root3(8)', [], 4],
 ['sqrt4+1', [], 3],
 ['sqrt4x', ['x'=>10], 20],
 ['sin^2(pi/4)', [], 1/2],
 ['log_x(9)', ['x'=>3], 2],
 ['log_2(8)',[], 3],
 ['log_(2)(8)',[], 3],
 ['log_(4/2)(8)',[], 3],
 ['log_(4-(2*1))(8)',[], 3],
 ['llog(l)', ['l'=>100], 200],
 ['1 && 1', [], 1],
 ['1 && 0', [], 0],
 ['0 || 1', [], 1],
 ['0 || 0', [], 0],
 ['!0 || 0', [], 1],
 ['0 || !0', [], 1],
 ['1 || 0 || 0', [], 1],
 ['!!1', [], 1],
 ['1 || 1 && 0', [], 1],
 ['pir^2', ['r'=>3], M_PI*9],
 ['e^2', ['e'=>3], 9],
 ['1.2E3', [], 1200],
 ['-3.5E-3', [], -0.0035],
 ['-3.5E-3x', ['x'=>2], -0.007],
 ['Cos(x)+C', ['x'=>M_PI,'C'=>3], 2],
 ['icos(pi)', ['i'=>2], -2],
 ['Icos(pi)', ['I'=>2], -2],
 ['pcos(pi)', ['p'=>2], -2],
 ['pcos(pi)+i', ['p'=>2,'i'=>3], 1],
 ['2pi', ['pi'=>2], 4],
 ['45degrees', [], M_PI/4],
 ['cos(90 degree + 90 degree)', [], -1],
 ['3*e - 4', [], 3*M_E - 4],
 ['3e-4', [], 3*M_E - 4],
 ['3E-2', [], 0.03],
 ['5>3', [], 1],
 ['2>3', [], 0],
 ['5>=3', [], 1],
 ['2<=2', [], 1],
 ['5+1>3', [], 1],
 ['3<1+5', [], 1],
 ['|-3|', [], 3],
 ['p #o q', ['p'=>1, 'q'=>0], 1],
 ['2!3!', [], 12],
 ['3!x', ['x'=>5], 30]
];


foreach ($tests as $test) {
  $p = new MathParser(implode(',', array_keys($test[1])));
  $out = 0;
  try {
    $p->parse($test[0]);
    $out = $p->evaluate($test[1]);
    if (abs($out - $test[2]) > 1e-6) {
      echo "Test failed on {$test[0]}: $out vs {$test[2]}<br>";
    }
  } catch (Throwable $t) {
    echo "Test crashed on {$test[0]}: $out vs {$test[2]}<br>";
    echo $t->getMessage();
    $p->printTokens();
  }
}


$sameformtests = [
    ['(x+3)(-x+5)','(5-x)(3+x)',['x']],
    ['1x+3','3+x',['x']],
    ['(x+2)/(x+3)','(x+2)/((x+3))',['x']],
    ['2^(x)+1','2^x+1',['x']],
    ['3x^2+5xy+4','5yx+4+3x^2',['x','y']],
    ['2x-3','2*x-3',['x']],
    ['3-4x','-4x+3',['x']],
    ['3-4x^2','-4x^2+3',['x']],
    ['3-4^2','-4^2+3',['x']],
    ['3-x*4','-x*4+3',['x']],
    ['3-4(x)','-4(x)+3',['x']]
];
$st = microtime(true);
foreach ($sameformtests as $test) {
    $p = new MathParser(implode(',', $test[2]));
    $out = 0;
    try {
      $p->parse($test[0]);
      $str1 = $p->normalizeTreeString();
      $p->parse($test[1]);
      $str2 = $p->normalizeTreeString();
      if ($str1 != $str2) {
        echo "Sameform Test failed on {$test[0]} vs {$test[1]}: $str1 vs $str2<br>";
      }
    } catch (Throwable $t) {
      echo "Test crashed on {$test[0]}: $out vs {$test[2]}<br>";
      echo $t->getMessage();
    }
}
echo microtime(true) - $st;
echo "Done";
