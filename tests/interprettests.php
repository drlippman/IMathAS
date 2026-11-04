<?php

require_once "../init.php";
require_once "../assessment/interpret5.php";

if ($myrights < 100) {
    exit;
}

echo '<style>.resG { color: #0c0;} .resB { color: #c00; }</style>';
$tests = [
    ['in' => '$a=0' . "\n" . '$a = 1 if $b==2' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if ($b==2) { $a=1 ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . '$a = 1 if ($b==2)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($b==2)) { $a=1 ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if ($a==0) {$b = 1}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($a==0)) { {$b=1;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if $a==0 {$b = 1}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if ($a==0) { {$b=1;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if ($a==0) $b = 1' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($a==0)) { $b=1 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if $a==0 $b = 1' . "\n" . '$c=1', 'out' => 'error;'],
    ['in' => '$a=0' . "\n" . 'if ($a==0) $b = 1; $c = 1' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($a==0)) { $b=1 ;};' . "\n" . '$c=1;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if ($a==0) $b = 1 else if ($a==1) $b = 2 else $b =3' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if ($a==0) $b = 1 elseif ($a==1) $b = 2 else $b =3' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (($a==0)) { $b=1 ;} else if (($a==1)) { $b=2 ;} else { $b=3 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'if $a==0 {$b = 1 } else if $a==1 {$b = 2 } else {$b = 3}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if ($a==0) { {$b=1;} ;} else if ($a==1) { {$b=2;} ;} else { {$b=3;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a+3)/4==3 { $c = 1 }', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (($a+3)/4==3) { {$c=1;} ;};' . "\n" . ';'],
    ['in' => 'if ($a+3)/4==3 $c = 1 ', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (($a+3)) { /4==3*$c=1 ;};' . "\n" . ';'],
    ['in' => '{$a=0' . "\n" . '$b = 1 } if $c==1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if ($c==1) { {$a=0;' . "\n" . '$b=1;} ; };' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . ' for ($i=0..2) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(2),4)) && loopstep(\'for\',1); $i++) {{$a++;};};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . ' for ($i=0..2) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (is_nan(2) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(2),4)) && loopstep(\'for\',1); $i++) {$a++;};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . ' for $i=0..2 {$a++}' . "\n" . '$c=1', 'out' => 'error;'],
    ['in' => '$a=0' . "\n" . ' foreach ($arr as $k=>$v) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (!is_array($arr)) {echo \'input of foreach must be an array\';} else {loopstart(\'for\',1);foreach ($arr as $k=>$v) {if (!loopstep(\'for\',1)) { break; }{ {$a++;} ;}};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . ' foreach ($arr as $k=>$v) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($arr)){$arr=null;}if (!isset($k)){$k=null;}if (!isset($v)){$v=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (!is_array($arr)) {echo \'input of foreach must be an array\';} else {loopstart(\'for\',1);foreach ($arr as $k=>$v) {if (!loopstep(\'for\',1)) { break; }{ $a++ ;}};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . ' foreach $arr as $k=>$v {$a++}' . "\n" . '$c=1', 'out' => 'error;'],
    ['in' => '$a=0' . "\n" . '$a = 1 where $a%2==0' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'loopstart(\'where\',1);do{$a=1;} while (!($a%2==0) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . '$a = 1 where ($a%2==0)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'loopstart(\'where\',1);do{$a=1;} while (!(($a%2==0)) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '{$a=0' . "\n" . '$b = 1 } where $c==1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1);do{{$a=0;' . "\n" . '$b=1;};} while (!($c==1) && loopstep(\'where\',1));;' . "\n" . ';'],
    ['in' => '  for ($i=0..20) { for ($j=0..11) { $a[] = $j }}', 'out' => 'if (!isset($i)){$i=null;}if (!isset($j)){$j=null;}if (!isset($a)){$a=null;}loopreset();if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(20),4)) && loopstep(\'for\',1); $i++) {{if (is_nan(11) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($j=(int)ceil(round(floatval(0),4)),loopstart(\'for\',2);$j<=(int)floor(round(floatval(11),4)) && loopstep(\'for\',2); $j++) {{$a[]=$j;};};};};};};' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'for ($i=2..0) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'if (is_nan(0) || is_nan(2)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(2),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(0),4)) && loopstep(\'for\',1); $i++) {{$a++;};};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=0' . "\n" . 'for ($i=0;$i<5;$i++) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($i)){$i=null;}if (!isset($c)){$c=null;}loopreset();$a=0;' . "\n" . 'for ($i=0,loopstart(\'for\',1);($i<5) && loopstep(\'for\',1); ($i++)) {{$a++;};};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '  for ($i=0..20) { if $i==4 { $b = $i }}', 'out' => 'if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}loopreset();if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(20),4)) && loopstep(\'for\',1); $i++) {{if ($i==4) { {$b=$i;} ;};};};};' . "\n" . ';'],
    ['in' => '  for ($i=0..20) { if ($i==4) { $b = $i }}', 'out' => 'if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}loopreset();if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(20),4)) && loopstep(\'for\',1); $i++) {{if (($i==4)) { {$b=$i;} ;};};};};' . "\n" . ';'],
    ['in' => '  for ($i=0..20) { if ($i==4)  $b = $i }', 'out' => 'if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}loopreset();if (is_nan(20) || is_nan(0)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(0),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(20),4)) && loopstep(\'for\',1); $i++) {{if (($i==4)) { $b=$i ;};};};};' . "\n" . ';'],
    ['in' => 'if ($a==1) || $a==5 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}loopreset();if (($a==1)||$a==5) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} ;};' . "\n" . ';'],
    ['in' => 'if $a==1 {$b=1} else if $a==2 {$b=2} else if $a==3 {$b=3} else {$b=4}', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}loopreset();if ($a==1) { {$b=1;} ;} else if ($a==2) { {$b=2;} ;} else if ($a==3) { {$b=3;} ;} else { {$b=4;} ;};' . "\n" . ';'],
    ['in' => 'if ($a==1) $b=1 else if ($a==2) $b=2 else if ($a==3) $b=3 else $b=4', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}loopreset();if (($a==1)) { $b=1 ;} else if (($a==2)) { $b=2 ;} else if (($a==3)) { $b=3 ;} else { $b=4 ;};' . "\n" . ';'],
    ['in' => 'if $a==1 { $b = 1 } else if ($a==2) || ($a==3) { $b = 2 } else { $b = 3}', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}loopreset();if ($a==1) { {$b=1;} ;} else if (($a==2)||($a==3)) { {$b=2;} ;} else { {$b=3;} ;};' . "\n" . ';'],
    ['in' => 'if ($a==2) $b=4' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { $b=4 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if $a==2 {$b=4}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if ($a==2) { {$b=4;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) {$b=4}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { {$b=4;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if (($a-1)/2==1) $c=($b-3)/2' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}if (!isset($b)){$b=null;}loopreset();if ((($a-1)/2==1)) { $c=($b-3)/2 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=1 if $b==3' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if ($b==3) { $a=1 ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a=1 if ($b==3)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($b==3)) { $a=1 ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '{$a=1} if ($b==3)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($b==3)) { {$a=1;} ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '{$a=1} if $b==3' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if ($b==3) { {$a=1;} ; };' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) $b=4 else if ($a==3) $b=5' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { $b=4 ;} else if (($a==3)) { $b=5 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) {$b=4} else if ($a==3) {$b=5}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { {$b=4;} ;} else if (($a==3)) { {$b=5;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if $a==2 {$b=4} else if $a==3 {$b=5}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if ($a==2) { {$b=4;} ;} else if ($a==3) { {$b=5;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) {$b=4} elseif ($a==3) {$b=5}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { {$b=4;} ;} else if (($a==3)) { {$b=5;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) {$b=4} else {$b=5}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { {$b=4;} ;} else { {$b=5;} ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'if ($a==2) $b=4 else $b=5' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();if (($a==2)) { $b=4 ;} else { $b=5 ;};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a = rand(1,5) where $a>2' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1);do{$a=$GLOBALS[\'RND\']->rand(1,5);} while (!($a>2) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a = rand(1,5) where ($a>2)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1);do{$a=$GLOBALS[\'RND\']->rand(1,5);} while (!(($a>2)) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '{$a = rand(1,5) } where $a>2' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1);do{{$a=$GLOBALS[\'RND\']->rand(1,5);};} while (!($a>2) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '{$a = rand(1,5) } where ($a>2)' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1);do{{$a=$GLOBALS[\'RND\']->rand(1,5);};} while (!(($a>2)) && loopstep(\'where\',1));;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a = rand(1,5) where $a>6 else $a=6' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1,true);do{$a=$GLOBALS[\'RND\']->rand(1,5);} while (!($a>6) && loopstep(\'where\',1)); if (loopcut(\'where\',1)) {$a=6;};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => '$a = rand(1,5) where $a>6 else {$a=6}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'where\',1,true);do{$a=$GLOBALS[\'RND\']->rand(1,5);} while (!($a>6) && loopstep(\'where\',1)); if (loopcut(\'where\',1)) {{$a=6;};};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'while $a<5 {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'while\',1);while (($a<5) && loopstep(\'while\',1)) {{$a++;};};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'while ($a<5) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'while\',1);while ((($a<5)) && loopstep(\'while\',1)) {{$a++;};};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'while ($a<5) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();loopstart(\'while\',1);while ((($a<5)) && loopstep(\'while\',1)) {$a++;};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i=1..3) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (is_nan(3) || is_nan(1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(1),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(3),4)) && loopstep(\'for\',1); $i++) {$a++;};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i = 1 .. 3) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (is_nan(3) || is_nan(1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(1),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(3),4)) && loopstep(\'for\',1); $i++) {$a++;};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i=1..3) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (is_nan(3) || is_nan(1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(1),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(3),4)) && loopstep(\'for\',1); $i++) {{$a++;};};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i=$b..$d) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}if (!isset($d)){$d=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (is_nan($d) || is_nan($b)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval($b),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval($d),4)) && loopstep(\'for\',1); $i++) {{$a++;};};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i=$b+1..($b+1)*2) $a++' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($b)){$b=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();if (is_nan(($b+1)*2) || is_nan($b+1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval($b+1),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(($b+1)*2),4)) && loopstep(\'for\',1); $i++) {$a++;};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'for ($i=0; $i<5; $i++) {$a++}' . "\n" . '$c=1', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();for ($i=0,loopstart(\'for\',1);($i<5) && loopstep(\'for\',1); ($i++)) {{$a++;};};;' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'foreach($a as $b=>$c) {$d++}' . "\n" . '$c=1', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}if (!isset($d)){$d=null;}loopreset();if (!is_array($a)) {echo \'input of foreach must be an array\';} else {loopstart(\'for\',1);foreach ($a as $b=>$c) {if (!loopstep(\'for\',1)) { break; }{ {$d++;} ;}};};' . "\n" . '$c=1;' . "\n" . ';'],
    ['in' => 'foreach($a as $b) {$d++}' . "\n" . '$c=1', 'out' => 'error;'],
    ['in' => '$f = \'die\'' . "\n" . '$g = $f(3)', 'out' => 'if (!isset($f)){$f=null;}if (!isset($g)){$g=null;}loopreset();$f=\'die\';' . "\n" . '$g=$f*(3);' . "\n" . ';'],
    ['in' => '$b=5$a', 'out' => 'if (!isset($b)){$b=null;}if (!isset($a)){$a=null;}loopreset();$b=5*$a;' . "\n" . ';'],
    ['in' => '$b=5sin(3)', 'out' => 'if (!isset($b)){$b=null;}loopreset();$b=5*sin(3);' . "\n" . ';'],
    ['in' => '$b=sin(3)sqrt(4)', 'out' => 'if (!isset($b)){$b=null;}loopreset();$b=sin(3)*sqrt(4);' . "\n" . ';'],
    ['in' => '$b=$a$c', 'out' => 'if (!isset($b)){$b=null;}if (!isset($a)){$a=null;}if (!isset($c)){$c=null;}loopreset();$b=$a*$c;' . "\n" . ';'],
    ['in' => '$b=$a($b+1)', 'out' => 'if (!isset($b)){$b=null;}if (!isset($a)){$a=null;}loopreset();$b=$a*($b+1);' . "\n" . ';'],
    ['in' => '$b=($a-1)($b+1)', 'out' => 'if (!isset($b)){$b=null;}if (!isset($a)){$a=null;}loopreset();$b=($a-1)*($b+1);' . "\n" . ';'],
    ['in' => '$a=3;$b="Test {$a} or $a"."more";$b,$c=[2,3];', 'out' => 'if (!isset($a)){$a=null;}if (!isset($b)){$b=null;}if (!isset($c)){$c=null;}loopreset();$a=3;' . "\n" . '$b="Test ".($a)." or $a"."more";' . "\n" . 'list($b,$c)=[2,3];' . "\n" . ';' . "\n" . ';'],
    ['in' => '{for ($i=1..5) {$a=1}} if ($i>2)', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}loopreset();if (($i>2)) { {if (is_nan(5) || is_nan(1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(1),4)),loopstart(\'for\',2);$i<=(int)floor(round(floatval(5),4)) && loopstep(\'for\',2); $i++) {{$a=1;};};};} ; };' . "\n" . ';'],
    ['in' => 'for ($i=1..5) {$a = $a+$i} if ($a>2)', 'out' => 'error;'],
    ['in' => 'for ($i=1..5) { if ($i>2) {$a = $a+$i} }', 'out' => 'if (!isset($i)){$i=null;}if (!isset($a)){$a=null;}loopreset();if (is_nan(5) || is_nan(1)) {echo \'part of for loop is not a number\';} else {for ($i=(int)ceil(round(floatval(1),4)),loopstart(\'for\',1);$i<=(int)floor(round(floatval(5),4)) && loopstep(\'for\',1); $i++) {{if (($i>2)) { {$a=$a+$i;} ;};};};};' . "\n" . ';'],
];

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


$newtests = [
    ['in' => ''],
];


foreach ($newtests as $k=>$item) {
    ob_start();
    $out = interpret("control", '', $item["in"]);
    ob_clean();
    $newtests[$k]['out'] = $out;
}

//returns $s as PHP code: single-quoted strings, with newlines and tabs spliced in as "\n" pieces
function phpStringLiteral($s) {
    $parts = preg_split('/([\r\n\t])/', $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    if (!$parts) {
        return "''";
    }
    foreach ($parts as $k=>$part) {
        $parts[$k] = strlen($part) == 1 && strpos("\r\n\t", $part) !== false
            ? '"' . str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $part) . '"'
            : "'" . addcslashes($part, "\\'") . "'";
    }
    return implode(' . ', $parts);
}

//output the new tests as PHP code, ready to paste into $tests
echo '<pre>';
foreach ($newtests as $k) {
    echo Sanitize::encodeStringForDisplay(
        "    ['in' => " . phpStringLiteral($k['in']) . ", 'out' => " . phpStringLiteral($k['out']) . "],\n"
    );
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

echo '<b>Generated code must be valid PHP</b><br>';
//inputs that are themselves malformed, so the output is expected not to parse
$knownbad = ['if ($a+3)/4==3 $c = 1 '];
foreach ($tests as $item) {
    $out = sec_interpret($item['in']);
    if ($out === 'error;' || in_array($item['in'], $knownbad, true)) { continue; }
    try {
        token_get_all('<?php ' . $out, TOKEN_PARSE);
        $parses = true;
    } catch (ParseError $pe) {
        $parses = false;
    }
    sec_report('parses: ' . str_replace("\n", ' ', $item['in']), $parses);
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
    'for ($i=0; $i<5 || loopstep("for",1); $i++) { }',
    'for ($i=0; $i<5, loopstep("for",1); $i++) { }',
    'while (($a) || loopstep("while",1)) { $a = 1; }',
    'while ($a) { $b = 1; }',
    'while (loopstep("while",1) || $a) { $b = 1; }',
    'foreach ($a as $k=>$v) { $b = 1; if (!loopstep("for",1)) { break; } }',
    '$forloopcnt[1] = 0;',
    '$whilecount[1] = 0;',
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

echo '<b>Loop budgets</b><br>';
sec_report('sibling loops each get their own budget', sec_runcontrol('for ($j=0..999) { $i++ }'."
".'for ($j=0..999) { $i++ }') === 2000);
sec_report('sibling while loops each get their own budget', sec_runcontrol('while (1) { $i++ }'."
".'while (1) { $i++ }') === 400);
sec_report('nested loops share an overall cap', sec_runcontrol('for ($j=0..999) { for ($k=0..999) { $i++ } }') <= 12000);
sec_report('nested while loops share an overall cap', sec_runcontrol('while (1) { while (1) { $i++ } }') <= 1000);
sec_report('where..else runs the else branch when cut off', sec_runcontrol('$a = 1 where $a==2 else $i = 7') === 7);
sec_report('where finds a value', sec_runcontrol('$i = $i + 1 where $i==3') === 3);
sec_report('budget is reset for each block of code', sec_runcontrol('while (1) { $i++ }') === 200 && sec_runcontrol('while (1) { $i++ }') === 200);

//runs question code, returns what it echoed
function sec_runout($code) {
    ob_start();
    eval(interpret('control', '', $code));
    return ob_get_clean();
}
$o = sec_runout('for ($i=1..100) { $a = 3 where $b>1 }');
sec_report('nested where that never succeeds: each message once', substr_count($o, 'where not met') === 1 && substr_count($o, 'nested where') === 1);
$o = sec_runout('for ($i=1..100) { while (1) { $a = 1 } }');
sec_report('nested while that never ends: each message once', substr_count($o, 'while not terminated') === 1 && substr_count($o, 'nested while') === 1);
$o = sec_runout('for ($i=1..20) { for ($j=1..1000) { $a = 1 } }');
sec_report('nested for that blows the total reports once', substr_count($o, 'nested for') === 1);
sec_report('nested loop hitting its limit does not stop its siblings', sec_runcontrol('for ($j=1..3) { while (1) { $i++ } }') === 600);
sec_report('adjacent nested loops each get a full per-loop budget', sec_runcontrol('for ($j=1..1) { while (1) { $i++ }'."\n".'while (1) { $i++ } }') === 400);
$n = sec_runcontrol('for ($j=1..100) { while (1) { $i++ } }');
sec_report('shared total still stops nested loops of that kind', $n > 900 && $n <= 1000);

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