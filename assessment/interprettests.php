<?php

require_once "../init.php";
require_once "interpret5.php";

if ($myrights < 100) {
    exit;
}

echo '<style>.resG { color: #0c0;} .resB { color: #c00; }</style>';
$tests = json_decode('[
{"in":"$a=0\n$a = 1 if $b==2\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif ($b==2) { $a=1 ; };\n$c=1;\n;"},
{"in":"$a=0\n$a = 1 if ($b==2)\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($b==2)) { $a=1 ; };\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) {$b = 1}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($a==0)) { {$b=1;} ;};\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 {$b = 1}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif ($a==0) { {$b=1;} ;};\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($a==0)) { $b=1 ;};\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 $b = 1\n$c=1","out":"error;"},
{"in":"$a=0\nif ($a==0) $b = 1; $c = 1\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($a==0)) { $b=1 ;};\n$c=1;\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1 else if ($a==1) $b = 2 else $b =3\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 };\n$c=1;\n;"},
{"in":"$a=0\nif ($a==0) $b = 1 elseif ($a==1) $b = 2 else $b =3\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 };\n$c=1;\n;"},
{"in":"$a=0\nif $a==0 {$b = 1 } else if $a==1 {$b = 2 } else {$b = 3}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\nif ($a==0) { {$b=1;} ;} else if ($a==1) { {$b=2;} ;} else { {$b=3;} };\n$c=1;\n;"},
{"in":"if ($a+3)\/4==3 { $c = 1 }","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if (($a+3)\/4==3) { {$c=1;} ;};\n;"},
{"in":"if ($a+3)\/4==3 $c = 1 ","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if (($a+3)) { \/4==3*$c=1 ;};\n;"},
{"in":"{$a=0\n$b = 1 } if $c==1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if ($c==1) { {$a=0;\n$b=1;} ; };\n;"},
{"in":"$a=0\n for ($i=0..2) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;if (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(2),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"$a=0\n for ($i=0..2) $a++\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;if (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(2),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {$a++;};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"$a=0\n for $i=0..2 {$a++}\n$c=1","out":"error;"},
{"in":"$a=0\n foreach ($arr as $k=>$v) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;if (!is_array($arr)) {echo \'input of foreach must be an array\';} else {\r\n                        $forloopcnt[1]=0; \r\n                        foreach ($arr as $k=>$v) { \r\n                            $forloopcnt[1]++;$forloopcnt[0]++;\r\n                            if ($forloopcnt[1]==1000 || $forloopcnt[0]>=12000) { break; }\r\n                            { {$a++;} ;}\r\n                        }; \r\n                        if ($forloopcnt[1]>=1000) {echo \"foreach loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested foreach exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"$a=0\n foreach ($arr as $k=>$v) $a++\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;if (!is_array($arr)) {echo \'input of foreach must be an array\';} else {\r\n                        $forloopcnt[1]=0; \r\n                        foreach ($arr as $k=>$v) { \r\n                            $forloopcnt[1]++;$forloopcnt[0]++;\r\n                            if ($forloopcnt[1]==1000 || $forloopcnt[0]>=12000) { break; }\r\n                            { $a++ ;}\r\n                        }; \r\n                        if ($forloopcnt[1]>=1000) {echo \"foreach loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested foreach exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"$a=0\n foreach $arr as $k=>$v {$a++}\n$c=1","out":"error;"},
{"in":"$a=0\n$a = 1 where $a%2==0\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;$a=1;} while (!($a%2==0) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n$c=1;\n;"},
{"in":"$a=0\n$a = 1 where ($a%2==0)\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;$a=1;} while (!(($a%2==0)) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n$c=1;\n;"},
{"in":"{$a=0\n$b = 1 } where $c==1","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$wherecount[0]=0;$wherecount[1]=0;do{$wherecount[1]++;$wherecount[0]++;{$a=0;\n$b=1;};} while (!($c==1) && $wherecount[1]<200 && $wherecount[0]<1000); if ($wherecount[1]==200) {echo \"where not met in 200 iterations\";}; if ($wherecount[0]>=1000 && $wherecount[0]<2000 ) {echo \"nested where not met in 1000 iterations\";};\n;"},
{"in":"  for ($i=0..20) { for ($j=0..11) { $a[] = $j }}","out":"if (!isset($i)){$i=null;}if (!isset($j)){$j=null;}if (!isset($a)){$a=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$forloopcnt[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{if (is_nan(11) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($j=(int)ceil(round(floatval(0),4)),$forloopcnt[2]=0;$j<=(int)floor(round(floatval(11),4)) && $forloopcnt[2]<1000 && $forloopcnt[0]<12000; $j++, $forloopcnt[2]++, $forloopcnt[0]++) {{$a[]=$j;};};\r\n\t\t\t\t\t\tif ($forloopcnt[2]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n;"},
{"in":"$a=0\nfor ($i=2..0) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;if (is_nan(0) || is_nan(2)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(2),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(0),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"$a=0\nfor ($i=0;$i<5;$i++) {$a++}\n$c=1","out":"if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$a=0;\n$forloopcnt[0]=0;for ($i=0,$forloopcnt[1]=0;($i<5) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; ($i++), $forloopcnt[1]++, $forloopcnt[0]++) {{$a++;};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n$c=1;\n;"},
{"in":"  for ($i=0..20) { if $i==4 { $b = $i }}","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$forloopcnt[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{if ($i==4) { {$b=$i;} ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n;"},
{"in":"  for ($i=0..20) { if ($i==4) { $b = $i }}","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$forloopcnt[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{if (($i==4)) { {$b=$i;} ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n;"},
{"in":"  for ($i=0..20) { if ($i==4)  $b = $i }","out":"if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;$forloopcnt[0]=0;if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {\r\n\t\t\t\t\t\tfor ($i=(int)ceil(round(floatval(0),4)),$forloopcnt[1]=0;$i<=(int)floor(round(floatval(20),4)) && $forloopcnt[1]<1000 && $forloopcnt[0]<12000; $i++, $forloopcnt[1]++, $forloopcnt[0]++) {{if (($i==4)) { $b=$i ;};};};\r\n\t\t\t\t\t\tif ($forloopcnt[1]>=1000) {echo \"for loop exceeded 1000 iterations - giving up\";}} if ($forloopcnt[0]>=12000) {echo \"nested for loops exceeded 10000 iterations - giving up\";};\n;"},
{"in":"if ($a==1) || $a==5 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if (($a==1)||$a==5) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} };\n;"},
{"in":"if $a==1 {$b=1} else if $a==2 {$b=2} else if $a==3 {$b=3} else {$b=4}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if ($a==1) { {$b=1;} ;} else if ($a==2) { {$b=2;} ;} else if ($a==3) { {$b=3;} ;} else { {$b=4;} };\n;"},
{"in":"if ($a==1) $b=1 else if ($a==2) $b=2 else if ($a==3) $b=3 else $b=4","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if (($a==1)) { $b=1 ;} else if (($a==2)) { $b=2 ;} else if (($a==3)) { $b=3 ;} else { $b=4 };\n;"},
{"in":"if $a==1 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}","out":"if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}$wherecount[0]=0;$whilecount[0]=0;$forloopcnt[0]=0;if ($a==1) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} };\n;"}
]', true);

foreach ($tests as $k=>$item) {
    if ($item["out"]=="error;") { // suppress expected errors
        ob_start();
    }
    $out = interpret("control", '', $item["in"]);
    if ($item["out"]=="error;") { // suppress expected errors
        ob_clean();
    }
    $res = ($item['out'] == $out)? 'G' : 'B';
    echo '<code class="res'.$res.'">'.$item['in'].'</code><br>';
}


$newtests = json_decode('[
]', true);


foreach ($newtests as $k=>$item) {
    ob_start();
    $out = interpret("control", '', $item["in"]);
    ob_clean();
    $newtests[$k]['out'] = $out;
}

echo '<pre>';
foreach ($newtests as $k) {
echo str_replace("'", "\\'", json_encode($k)).",\n";
}
echo '</pre><br/>';


/*
 * Security regression tests.
 *
 * 1. Question code that must be rejected by interpret()
 * 2. Question code that must still be accepted (guards against over-blocking)
 * 3. PHP snippets that validateInterpretedCode() must reject, even if the
 *    transpiler were somehow to emit them
 * 4. Run-time checks of loop limits and helper functions
 */
$sec_fail = 0;
function sec_report($label, $pass) {
    global $sec_fail;
    if (!$pass) { $sec_fail++; }
    echo '<code class="res'.($pass ? 'G' : 'B').'">'.($pass ? 'PASS' : 'FAIL').': '
        .Sanitize::encodeStringForDisplay($label).'</code><br>';
}
function sec_interpret($code) {
    ob_start();
    $out = interpret('control', '', $code);
    ob_end_clean();
    return $out;
}
//runs question code in a function scope, returns the value of $i afterward
function sec_runcontrol($code) {
    $i = 0;
    ob_start();
    eval(interpret('control', '', $code));
    ob_end_clean();
    return $i;
}

echo '<h3>Security regression tests</h3>';

echo '<b>Must be rejected by interpret()</b><br>';
$mustreject = [
    '$a = $GLOBALS',
    '$a = $GLOBALS["x"]',
    '$a = $_SERVER["x"]',
    '$a = $_POST["x"]',
    '$a = $DBH',
    '$a = $this',
    '$a = $allowedmacros',
    '$a = $disallowedvar',
    '$whilecount[0] = 0',
    '$wherecount[0] = 0',
    '$forloopcnt[0] = 0',
    '$$a = 1',
    '$a = system("ls")',
    '$a = exec("ls")',
    '$a = eval("1")',
    '$a = file_get_contents("x")',
    '$a = call_user_func("system","ls")',
    '$a = unserialize("x")',
    '$a = Sin(1)',
    '$a = \\system("ls")',
    'for ($i=0; 1) || (1; $i++) { $a = 1 }',
    'while (1) { $whilecount[0] = 0 }',
    'for ($i=0..5) || (1..2) { $a = 1 }',
];
foreach ($mustreject as $code) {
    sec_report('reject: '.$code, sec_interpret($code) === 'error;');
}

echo '<b>Must still be accepted</b><br>';
$mustaccept = [
    '$a = rand(1,5)',
    '$a = 3^2 + 5!',
    '$a = sin(pi/2) + e',
    '$a = array(1,2,3)',
    '$a = 5 if $b==2',
    '$a = rand(1,9) where ($a>3)',
    'for ($i=0..5) { $a[] = $i }',
    'for ($i=0; $i<5; $i++) { $a++ }',
    'foreach ($arr as $k=>$v) { $a += $v }',
    'while ($i<5) { $i++ }',
    'if ($a==1) { $b=1 } else { $b=2 }',
    '$s = "x $a y {$b+1}"',
    '${"b"} = 3',
    '$c = ${"b"} + 1',
];
foreach ($mustaccept as $code) {
    sec_report('accept: '.$code, sec_interpret($code) !== 'error;');
}

echo '<b>validateInterpretedCode() must reject</b><br>';
$badphp = [
    'system("ls");',
    '$a = $GLOBALS["x"];',
    '$f = "system"; $f("ls");',
    '("system")("ls");',
    '$a[0]("ls");',
    '"system"("ls");',
    'eval("1");',
    'include "x.php";',
    '$a = `ls`;',
    '$a = new stdClass;',
    '$a = $b->c();',
    'A::b();',
    '$a = \\system("ls");',
    'function f() {}',
    '$f = function() {};',
    'goto x;',
    '$a = @$b;',
    '# comment',
    '?> <?php',
    "\$a = <<<EOT\nx\nEOT;\n",
    '$$a = 1;',
    '$a = "${a}";',
    '$a = "{$a}";',
    'while (1) { $a = 1; }',
    'for (;;) { }',
    'for ($i=0; $i<5 || $forloopcnt[1]<5; $i++) { }',
    'for ($i=0; $i<5, $forloopcnt[1]<5; $i++) { }',
    'while (($a) || $whilecount[1] < 200) { $a = 1; }',
    'foreach ($a as $k=>$v) { $b = 1; }',
    '$a = ${"x"};',
    '${checkvarvarname("a")}("ls");',
];
foreach ($badphp as $code) {
    sec_report('validator rejects: '.str_replace("\n", ' ', $code), validateInterpretedCode($code) !== '');
}

echo '<b>validateInterpretedCode() must accept transpiled output</b><br>';
foreach (array_merge($mustaccept, array_column($tests, 'in')) as $code) {
    $out = sec_interpret($code);
    if ($out === 'error;') { continue; } // covered by the lists above / expected errors
    sec_report('validator accepts output of: '.str_replace("\n", ' ', $code), validateInterpretedCode($out) === '');
}
ob_start();
$qt = interpret('qtext', '', 'Cost is $5, $a[0] and {$a+1} <b>x</b> "quoted"');
ob_end_clean();
sec_report('validator accepts qtext', validateInterpretedCode('$evaledqtext = "'.$qt.'";') === '');

echo '<b>Variable variables</b><br>';
//runs question code in a function scope with preset variables; returns all variables afterward
function sec_runvars($code, $presets = []) {
    extract($presets);
    ob_start();
    eval(interpret('control', '', $code));
    ob_end_clean();
    unset($code, $presets);
    return get_defined_vars();
}
$v = sec_runvars("\$x = 5\n\$c = \${'x'}");
sec_report('varvar: allowed literal name reads the variable', ($v['c'] ?? null) === 5);
$v = sec_runvars("\$x = 5\n\$d = 'x'\n\$c = \${\$d}");
sec_report('varvar: allowed computed name reads the variable', ($v['c'] ?? null) === 5);
$v = sec_runvars("\$x = 5\n\$c = \${'qidx'}", ['qidx' => 'SECRET']);
sec_report('varvar: disallowed literal name does not read the variable', ($v['c'] ?? null) !== 'SECRET');
$v = sec_runvars("\$d = 'la'\n\$c = \${'part'.\$d}", ['partla' => 'SECRET']);
sec_report('varvar: disallowed computed name does not read the variable', ($v['c'] ?? null) !== 'SECRET');
$v = sec_runvars("\$n = 0\n\$x = 5\n\$c = \${\$n++ ? 'x' : 'x'}");
sec_report('varvar: name expression is evaluated exactly once', ($v['n'] ?? null) === 1 && ($v['c'] ?? null) === 5);
$v = sec_runvars("\$n = 0\n\$c = \${\$n++ ? 'qidx' : 'okname'}", ['qidx' => 'SECRET']);
sec_report('varvar: name that changes between evaluations cannot slip past the check', ($v['c'] ?? null) !== 'SECRET');
$v = sec_runvars("\$c = \${array(1)}");
sec_report('varvar: non-scalar name is handled', array_key_exists('c', $v) && $v['c'] === null);
ob_start();
$qt = interpret('qtext', '', 'a ${b} c ${$d}');
ob_end_clean();
sec_report('varvar: removed from qtext', strpos($qt, '${') === false);

echo '<b>Run-time checks</b><br>';
sec_report('while loop is capped', sec_runcontrol('while (1) { $i++ }') <= 200);
sec_report('long for..  loop is capped', sec_runcontrol('for ($j=0..100000) { $i++ }') <= 1000);
sec_report('C-style for loop is capped', sec_runcontrol('for ($j=0; $j<100000; $j++) { $i++ }') <= 1000);
sec_report('foreach loop is capped', sec_runcontrol('for ($j=0..999) { $arr[] = $j }'."\n".'for ($j=0..999) { $arr[] = $j }'."\n".'foreach ($arr as $k=>$v) { $i++ }') <= 1000);
ob_start();
$r1 = checkvarvarname('GLOBALS');
$r2 = checkvarvarname('abc');
$r3 = checkvarvarname('a b');
$r4 = checkvarvarname(['x']);
ob_end_clean();
sec_report('checkvarvarname blocks disallowed/invalid names',
    $r1 === 'invalidvariable' && $r2 === 'abc' && $r3 === 'invalidvariable' && $r4 === 'invalidvariable');
ob_start();
loadlibrary('../config');
loadlibrary('stats/../../config');
$libout = ob_get_clean();
sec_report('loadlibrary rejects path characters', substr_count($libout, 'Error loading library') === 2);

echo '<br><b>' . ($sec_fail == 0 ? 'All security tests passed' : $sec_fail . ' security test(s) FAILED') . '</b>';