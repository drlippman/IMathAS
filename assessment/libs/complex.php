<?php

# Library name: complex
# Functions to handle operations for complex numbers.  Version 1.  October 2020
# Author:  Amir Tavangar
#          Humber College, Toronto, ON

# Include loadlibrary("complex") to import the library in your questions.

# The functions in this library can be used to handle operations for complex numbers such as
    # Converting from standard to polar form and vice versa
    # finding modulus, argument, and conjugate
    # Multiplication, division, raising to a power, and taking the nth root
    # Finding real or complex roots of quadratic equations
    

global $allowedmacros;
array_push($allowedmacros,"cx_add","cx_arg", "cx_conj","cx_cubicRoot", "cx_div", "cx_format2pol", "cx_format2std", "cx_matrixreduce", "cx_modul",
                        "cx_mul", "cx_quadRoot", "cx_prettyquadRoot",  "cx_plot",
                        "cx_pow", "cx_polEu","cx_pol2std", "cx_root", "cx_std2pol", "cx_sub");
                         



//--------------------------------------------Modulus----------------------------------------------------
// Function: cx_modul(num, [roundto = 12])
// Returns the modulus (absolute value) of a complex number, which is sqrt(Re^2 + Im^2). 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The modulus of a complex number as a float. 


function cx_modul(array $num, int $roundto=12) {

    if (!cx_is_ok($num)) { echo "cx_modul expects 1 complex number as an input in the form [Re, Im]"; return "";}
    
    $sq=$num[0]**2+$num[1]**2;
    $r= round(sqrt($sq),$roundto);
   
    return $r;
}

//---------------------------------------------Argument (theta)---------------------------------------------
// Function: cx_arg(num, [argin = "rad", roundto = 12])
// Returns the argument (angle theta) of a complex number in radian. 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The agument of a complex number in radian (or degree) as a float. 

function cx_arg(array $num, string $argin="rad", int $roundto=12) {

    if (!cx_is_ok($num)) { echo "cx_arg expects 1 complex number as an input"; return "";}
    
    $re=$num[0];
    $im=$num[1];

    /*
    $r= cx_modul($num);
    
    if ($r==0){
        $theta=0;
    } else {
        $th1 = asin(abs($im/$r));
        if ($re>=0 && $im>=0){
            $theta=$th1;
        }    
        else if($re<=0 && $im<=0){
            $theta = pi() + $th1;
        }
        else if($re<=0 && $im>=0){
            $theta = pi() - $th1;
        }
        else {
            $theta = 2*pi() - $th1;
        }
    }
    */
    $theta = atan2($im,$re);
    // change to make angles consistent with old code
    if ($theta < 0) {
        $theta += 2*pi();
    }
    
    if ($argin=="deg"){
        $th2=rad2deg($theta);
    }
        else{
            $th2=$theta;
        }
   
    return round($th2, $roundto);
}



//-------------------------------------------------Conjugate----------------------------------------------
// Function: cx_conj(num, [roundto = 12])
// Returns the conjugate of a complex number: conjugate of (a+bi)-->(a-bi)
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The conjugate of a complex number in an array (same format as the input).

function cx_conj(array $num, int $roundto=12) {

    if (!cx_is_ok($num)) { echo "cx_conj expects 1 complex number as an input"; return "";}
    
    $re=$num[0];
    $im=$num[1];
    $im2=-$im;
    $st=[round($re, $roundto),round($im2, $roundto)];
   
   return $st;
}

//------------------------------------------------Polar form --------------------------------------------
// Function: cx_std2pol(num,[argin = "rad", roundto = 12])
// Converts the standard form to polar form and returns the modulus and the argument as a paired value.
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The modulus and the argument of a complex number as a paired value in an array: [mod, arg].

function cx_std2pol(array $num, string $argin="rad", int $roundto= 12) {
    
    if (!cx_is_ok($num)) { echo "cx_std2pol expects 1 complex number as an input"; return "";}
    
    $r= round(cx_modul($num),$roundto);
    if ($argin=="deg"){
        $th1=round(cx_arg($num,"deg"),$roundto);
    }
        else{
            $th1=round(cx_arg($num),$roundto);
        }
    
    $polar=[$r,$th1];    
        
    return $polar;
}

//--------------------------------------------------Euler's formula-----------------------------------------
// Function: cx_polEu(num,[argin = "rad", roundto = 12])
// Returns the polar form with the Euler's formula notation: z = r*e^i*theta as a string for displaying answer.
//
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: 
//      num = array([Re, Im], [Re2, Im2], ...). A Single complex number can also be input in the form [Re, Im].
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places. 
// 
// Returns:
// The polar form with the Euler's formula notation as a string. If num has more than one complex number, 
// the function returns an array of strings: array("r1*e^i*theta1", "r2*e^i*theta2", ...).


function cx_polEu(array $num, string $argin="rad", int $roundto=12) {
    if (!is_array($num[0])) {
        $num = array($num);
      }
    for ($i=0;$i<count($num);$i++){
        if (!cx_is_ok($num[$i])) { echo 'cx_polEu invalid input'; return '';}
    }

    if (!function_exists('reduceradical')) {
        require_once(__DIR__.'/radicals.php');
      }
    
    $A=array();  
    
    if ($argin=="deg"){
        for ($i=0;$i<count($num);$i++){
            
            $th1=round(cx_arg($num[$i],"deg"),$roundto);
            $th2=$th1;
            $sq=round($num[$i][0]**2+$num[$i][1]**2,12);
            $r= reduceradical($sq); 
            $A[$i]=makexpretty("$r e^($th2 i)");
        }
        
    }
        else {
            for ($i=0;$i<count($num);$i++){
            
                $th0= cx_arg($num[$i]);
                $th1= round($th0/pi(),$roundto);
                $th2=makexxpretty("$th1 pi");
                $sq=round($num[$i][0]**2+$num[$i][1]**2,12);
                $r= reduceradical($sq); 
                $A[$i]=makexpretty("$r e^($th2 i)");
            }
       }
          
    if (count($num)>1){
        $ans=$A;
    }    else{
            $ans=$A[0];
        }

    return $ans;
        
}    

//---------------------------------------------Standard form-----------------------------------------------
// Function: cx_pol2std(num, [argin = "rad", roundto = 12])
// Converts the polar form to standard form and returns the real and imaginary parts as a paired value 
// in an array.
//
// Parameters:
// num: An array of modulus and argument of a complex number in polar form: 
//      $num = [mod, arg] or array(mod, arg).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The real and imaginary parts as a paired value in an array: [Re, Im].

function cx_pol2std(array $num, string $argin="rad", int $roundto= 12) {
    if (!cx_is_ok($num)) { echo "cx_pol2std expects 1 complex number as an input"; return "";}

    $mod=$num[0];
    $arg=$num[1];
    if ($argin=="deg"){
        $arg=deg2rad($arg);
    }
            
    $re=round($mod*cos($arg),$roundto);
    $im=round($mod*sin($arg),$roundto);
    
    $st= [$re , $im];  
        
    return $st;
}

//---------------------------------------------Complex Addition----------------------------------------
// Function: cx_add(num, [roundto=12])
// Returns the sum of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to add 2-3i, -5i, and sqrt(2)-i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
//
// Returns:
// The real and imaginary parts of the sum as a paired value in an array: [Re, Im].

function cx_add(array $num, int $roundto=12) {
    
    
    $ret=0;
    $imt=0;
    $counter=count($num);
    for ($i=0; $i < $counter; $i++){

        if (!cx_is_ok($num[$i])) { echo "cx_add expects complex numbers in the form [Re,Im]"; return "";}
        
        $re=$num[$i][0];
        $im=$num[$i][1];
        
        $ret+= $re;
        $imt+= $im;
    }

    $st= [round($ret,$roundto) , round($imt,$roundto)];    
        
    return $st;
}

//---------------------------------------------Complex Subtraction----------------------------------------
// Function: cx_sub(num, [roundto = 12])
// Returns the difference of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to subtract -5i, and sqrt(2)-i from 2-3i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
// 
// Returns:
// The real and imaginary parts of the difference as a paired value in an array: [Re, Im].

function cx_sub(array $num, int $roundto=12) {
    
    if (!cx_is_ok($num[0])) { echo "cx_sub expects complex numbers in the form [Re,Im]"; return "";}
    
    $ret=0;
    $imt=0;
    $counter=count($num);
    for ($i=1; $i < $counter; $i++){

        if (!cx_is_ok($num[$i])) { echo "cx_sub expects complex numbers in the form [Re,Im]"; return "";}
        
        $re=$num[$i][0];
        $im=$num[$i][1];
        
        $ret+= $re;
        $imt+= $im;
    }
    $re=$num[0][0]-$ret;
    $im=$num[0][1]-$imt;
    $st= [round($re,$roundto) , round($im,$roundto)];    
        
    return $st;
}

//---------------------------------------------Complex multiplication----------------------------------------
// Function: cx_mul(num, [roundto = 12])
// Returns the product of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to multiply 2-3i, -5i, and sqrt(2)-i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
// 
// Returns:
// The real and imaginary parts of the product as a paired value in an array: [Re, Im].

function cx_mul(array $num, int $roundto=12) {
    
    $rt=1;
    $tht=0;
    $counter=count($num);
    for ($i=0; $i < $counter; $i++){

        if (!cx_is_ok($num[$i])) { echo "cx_mul expects complex numbers in the form [Re,Im]"; return "";}
        
        $rt = $rt*cx_modul($num[$i]);
        $tht += cx_arg($num[$i]);
    }

    $re_p=$rt*cos($tht);
    $im_p=$rt*sin($tht); 
    
    $st= [round($re_p, $roundto) , round($im_p,$roundto)];    
        
    return $st;
}

//-------------------------------------------------Complex power--------------------------------------------
// Function: cx_pow(num, exp, [roundto=12])
// Returns the power of a complex number in standard form as a paired value in an array. 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// exp: exponent to which the complex number is raised.
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
// 
// Returns:
// The real and imaginary parts of the power as a paired value in an array: [Re, Im].

function cx_pow(array $num, $pow, int $roundto=12) {
    
    if (!cx_is_ok($num)) { echo "cx_pow expects 1 complex number as an input in the form [Re,Im]"; return "";}
    
    $r1= cx_modul($num);
    $th1=cx_arg($num);
    $r=$r1**$pow;
    $tht=$th1*$pow;
    
    $re=$r*cos($tht);
    $im=$r*sin($tht);  
        
    $st= [round($re,$roundto) , round($im,$roundto)];   
        
    return $st;
}

//-----------------------------------------------Complex nth Roots--------------------------------------------
// Function: cx_root(num, n, [roundto=12])
// Returns an array of the nth roots of a complex number in standard form.  
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in the form: 
//      num = [Re, Im] or num = array(Re, Im).
// n: The nth root. For example, for the cubic roots n=3.
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
// 
// Returns:
// An array of paired values of real and imaginary parts of the nth roots: array([Re1,Im1], [Re2,Im2],...). 

function cx_root(array $num, int $root, int $roundto=12) {
    
    if (!cx_is_ok($num)) { echo "cx_root expects 1 complex number as an input in the form [Re,Im]"; return "";}
    
    $r1= cx_modul($num);
    $th1= cx_arg($num);
    $r=$r1**(1/$root);
    $A=array();
    
    for ($k=0; $k<$root; $k++){
        
        $tht = ($th1+2*pi()*$k)/$root;
        $re_f = $r*cos($tht);
        $im_f = $r*sin($tht);
        $A[$k] = [round($re_f,$roundto) , round($im_f,$roundto)];
    }
    return $A;
}
    

//-------------------------------------------------Complex division-----------------------------------------
// Function: cx_div(num, [roundto=12])
// Returns the quotient of two complex numbers in standard form as a paired values in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of two complex numbers (dividend, divisor) given as paired values
//      in square brackets. For example, to divide 2-3i by sqrt(2)-i, num = array([2,-3], [sqrt(2),-1]). 
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places.
// 
// Returns:
// The quotient of two complex numbers in standard form an array: [Re, Im]

function cx_div(array $num, int $roundto=12) {
    if (count($num)!=2) { echo "cx_Div expects 2 complex numbers in the form [Re,Im]"; return "";}

    for ($i=0; $i<count($num); $i++){
        if (!cx_is_ok($num[$i])) { echo "cx_Div expects complex numbers in the form [Re,Im]"; return "";}
    }
        
    $rt=1;
    $tht=0;
    $N=$num[0];
    $D=$num[1];
    $D_conj=cx_conj($D);
    
    $rn= cx_modul($N);
    $thn= cx_arg($N);
    $rconj= cx_modul($D_conj);
    $thconj= cx_arg($D_conj);
    
    $den=($D[0])**2 +($D[1])**2;
    if ($den==0) { echo "Division by zero! cx_Div expects the second complex number to have nonzero modulus"; return "";}
    
    $r=$rn*$rconj/$den;
    $tht=$thn+$thconj;
    $re=$r*cos($tht);
    $im=$r*sin($tht);  
        
    $st= [round($re,$roundto) , round($im,$roundto)];     
        
    return $st;
}

//-------------------------------------Quadratic real and complex roots--------------------------------------
// Function: cx_quadRoot(a,b,c, [roundto = 12, $disp = False])
// Returns an array of roots of the quadratic equation f(x) = ax^2 + bx + c. Real roots are returned
// as an array([r1],[r2]) ordered from the smallest to largest, and the complex roots are returned 
// as an array([Re1,Im1], [Re2,Im2]). 
// 
// Parameters:
// a: The numerical coefficient of x^2
// b: The numerical coefficient of x    
// c: The constant
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 12 decimal places. 
// disp: If set to true, the function returns the string version of the roots for display, which should not be used for calculation.   

// Returns:
// An array of roots (either real or complex) of the quadratic equation.

function cx_quadRoot(float $a, float $b, float $c, int $roundto = 12, $disp = False){
    $d=$b**2 - 4*$a*$c;
    if ($d<0){

        $re= round(-$b/(2*$a),$roundto);
        $im= round(sqrt(abs($d))/(2*$a),$roundto);
        $im2=-$im;
        $st=array([$re,$im],[$re,$im2]);
    }
        else {
            $r1=round(((-$b-sqrt($d))/(2*$a)),$roundto);
            $r2=round(((-$b+sqrt($d))/(2*$a)),$roundto);
            $st=array([$r1],[$r2]);
        }

    if ($disp == True){
        $st=cx_format2std($st,$roundto);
    }

    return $st;

}

//---------------------------------------------------Cubic real and complex roots------------------------------------------
// Function: cx_cubicRoot(poly, [disp = False, roundto = 12])
// Returns an array of roots of the cubic equation f(x) = ax^3 + bx^2 + cx + d. Real roots are returned
// as an array([r1],[r2],[r3]) and complex roots are return as an array([r1],[Re1,Im1], [Re1,Im1]).
// 
// Parameters:
// poly: The array of coefficients: [a,b,c,d]
// disp: If set to true, the function returns the string version of the roots for display, which should not be used for calculation.   
// roundto: Optional - number of decimal places to which the roots should be rounded off; default is 12 decimal places. 
//
// Returns:
// An array of roots (either real or complex) of the cubic equation.

#internal function: long devision of a cubic function by x-a; used for cx_cubicRoot()
function cubicdivide($divident, $divisor){
	$q = [$divident[0],$divident[1]+$divisor[1]*$divident[0],$divident[2]+($divisor[1]*($divident[1]+$divisor[1]*$divident[0]))];
	$r = $divident[3]+($divisor[1]*($divident[2]+$divisor[1]*($divident[1]+$divisor[1]*$divident[0])));
	if (abs($r)<1e-9) {$r=0;}
	return ([$q,$r]);
}

#internal function: cubic root of real numbers (negative and positive)
function amirCube($num){
	if ($num<0){
		$num=-$num;
		$cube=-$num**(1/3);
	} else{
		$cube=$num**(1/3);
	}
	return($cube);
}

# Finding roots of cubic polynomial
function cx_cubicRoot( array $poly, $disp = False, int $roundto = 12){
    
    $ct = count($poly);
        
    if ($ct<3 || $ct>4) { echo 'error: The function must be either quadratic or cubic'; return '';}

    //Cubic
    if ($ct == 4) {

        if ($poly[0]==0) { echo 'error: The leading coefficient must be nonzero'; return '';}
        elseif ($poly[0]!=1){
            $a0=1;
            $a1=$poly[1]/$poly[0];
            $a2=$poly[2]/$poly[0];
            $a3=$poly[3]/$poly[0];
        } else {
            $a0=$poly[0];
            $a1=$poly[1];
            $a2=$poly[2];
            $a3=$poly[3];
        }

        $Q = (3*($a2)-$a1**2)/9;
        $R = (9*$a1*$a2-27*$a3-2*$a1**3)/54;
        $delta = ($Q)**3 + ($R)**2;
        
        if ($delta<0) {

            $dsq = sqrt(-$delta);
            $Scube = [$R, $dsq];
            $Tcube = [$R,-$dsq];
            $S = cx_root($Scube,3);
            $T = cx_root($Tcube,3);
            $x1 = cx_add([$S[1],$T[1],[-$a1/3,0]])[0];
            
        } else{

            $S = amirCube($R + sqrt($delta));       
            $T = amirCube($R - sqrt($delta));   
            $x1 = $S+$T-$a1/3;
            }

        $q1 = cubicdivide($poly,[1,$x1]);	
        $xq = cx_quadRoot($q1[0][0],$q1[0][1],$q1[0][2],$roundto);
        $x2 = $xq[0];
        $x3 = $xq[1];
        $x = [[round($x1,$roundto)],$x2,$x3];
        

        //Quadratic
    } 	else {
            if ($poly[0]==0) { echo 'error: The leading coefficient must be nonzero'; return '';}
            $x = cx_quadRoot($poly[0],$poly[1],$poly[2],$roundto);
    }

    if ($disp==True){
        $x = cx_format2std($x,$roundto);
    }

    return($x);
    
    }



//------------------------------------------Formating to standard form-------------------------------------
// Function: cx_format2std(num, [roundto=3])
// Returns a string form of a complex number in standard form: a + b i. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: 
//      num = array([Re, Im], [Re2, Im2], ...). A Single complex number can also be input in the form [Re, Im].
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places. 
//
// Returns:
// A complex number in standard form as a string. If num has more than one complex number, the function returns
// an array of the standard forms as strings: answer = array("a1+b1 i", "a2+b2 i", ...).

function cx_format2std(array $num,int $roundto=3) {
    
    if (!is_array($num[0]) && count($num)==2) { // only gave one number
        $num = array($num);
      }

    $A=array();
    for ($i=0;$i<count($num);$i++){
        if (!cx_is_ok($num[$i])) { echo 'invalid input to cx_format2std'; return '';}
        $re=round($num[$i][0],$roundto);
        $im=round($num[$i][1],$roundto);

        $A[$i]=makexxpretty("$re + $im i");
    }
    
    if (count($num)>1){
        $ans=$A;
    }    else{
            $ans=$A[0];
        }

    return $ans;
      
}

//------------------------------------------Formating to polar form---------------------------------------
// Function: cx_format2pol(num, [argin="rad" , roundto=3])
// Returns a string form of a complex number in polar form: r (cos(t) + isin(t)). 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: num = array([Re, Im]).
//      num array can include more than one complex number. 
// argin: Optional - Unit for the argument; default is "rad" for degree. For argument in radian, argin = "deg".
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places. 
// Returns:
// A complex number in standard form as a string. If num has more than one complex number, the function returns
// an array of the polar forms as strings: answer = array("r1 (cos(t1) + isin(t1))", "r2 (cos(t2) + isin(t2))", ...).

function cx_format2pol(array $num, string $argin="rad", int $roundto=3) {
    if (!function_exists('reduceradical')) {
        require_once(__DIR__.'/radicals.php');
      }

    if (!is_array($num[0]) && count($num)==2) {
        $num = array($num);
      }
    
    $A=array();
    for ($i=0;$i<count($num);$i++){
        if (!cx_is_ok($num[$i])) { echo 'invalid input to cx_format2pol'; return '';}

        $num1=$num[$i];
        $sq=round($num[$i][0]**2+$num[$i][1]**2,12);
        $r= reduceradical($sq);
        //$r= round(cx_modul($num1),$roundto);

        if ($argin=="rad"){
                $th0= cx_arg($num1);
                $th1= round($th0/pi(),$roundto);  
                $th1=makexxpretty("$th1 pi"); 
        }
            else {
                $th1=round(cx_arg($num1,"deg"),$rounto);
        }
        
        $A[$i]=makexpretty("$r (cos($th1)+isin($th1))");
    }
    
    if (count($num)>1){
        $ans=$A;
    }    else{
            $ans=$A[0];
        }

    return $ans;
      
}

//------------------------------String Formatted Quadratic real and complex roots------------------------
// Function: cx_prettyquadRoot(a,b,c)
// Returns an array of the string of roots of the quadratic equation f(x) = ax^2 + bx + c. Real roots are returned
// as an array("r1","r2") ordered from the smallest to largest, and the complex roots are returned 
// as an array("a+bi","a-bi"). This function is suitable for displaying answer (i.e., $showanswer).
// 
// Parameters:
// a: The numerical coefficient of x^2
// b: The numerical coefficient of x    
// c: The constant
//
// Returns:
// An array of formatted string of roots (either real or complex) of the quadratic equation.

function cx_prettyquadRoot(float $a, float $b, float $c){
    
    if (!function_exists('reduceradicalfrac')) {
        require_once(__DIR__.'/radicals.php');
      }
    
    $d=$b**2 - 4*$a*$c;
    
    if ($d<0){
        
            $a2=$a*2;
            $D=makereducedfraction(-$b,$a2);
            $N=reduceradicalfrac(1,-$d,$a2);
            $st=array("$D + $N i","$D - $N i");
    }
        else {
            $r1 = reducequadraticform(-$b, -1, $d, 2*$a);
            $r2 = reducequadraticform(-$b, 1, $d, 2*$a);

            $st=array("$r1","$r2");
        }

    return $st;

}

//-------------------------------------Plot a complex number------------------------------------
// Function: cx_plot(num, [argin = "deg" , roundto = 3, showlabels = True] )
// Returns the plot of a complex number. It can be used to find z from plot as well as in solutions.
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: num = [Re, Im].
//       
// argin: Optional - Unit for the argument; default is "deg" for degree. For argument in radian, argin = "rad".
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places. 
// showlabels: If True (default), labels for the real and imaginary parts, modulus, argument,
//             and z are shown on the plot. If False, no label is displayed. 
//
// Returns:
// The plot of a complex number.

function cx_plot(array $num, string $argin = "deg" ,int $roundto = 3, bool $showlabels = True){
    
    $x=round($num[0],$roundto);
    $y=round($num[1],$roundto);

    $r=cx_modul($num);
    $r_d=round($r,$roundto);
    
    $th=cx_arg($num);
    $thd=round(cx_arg($num,$argin),$roundto);
    $z=makexxpretty("$x + $y i");

    if (abs($x)>abs($y)){$g=abs($x)+2;} 
        else{$g=abs($y)+2;}

        if ($showlabels==False){
            $r_d="";
            $th_d="";
            $re_d="";
            $im_d="";
            $z_d="";
        }
            else {
                $r_d="r";
                $th_d="";
                $re_d="Re";
                $im_d="Im";
                $z_d="z = $z";
                $th_d= "th = $thd";
    
            }

        if ($x==0){$line = "x=0";}
            else{$m=$y/$x; $line = "$m*x";}

        $th_x=$r*(1.2)*abs(cos($th))/3 +0.8;
        $th_y=$r*(1)*abs(sin($th))/3;

    
    $plot=showplot(array("text,$th_x,$th_y,$th_d","text,$x*(1-0.4),$y*(1-0.5),$r_d",
    "text,$x/2,$y*(1+0.1),$re_d","text,$x*(1+0.2),$y*(1+0.2),$z_d",
    "text,$x*(1+0.1),$y/2,$im_d","$line,blue,0,$x","[($r/3)*cos(t),($r/3)*sin(t)],red,0,$th",
    "dot,$x,$y,,red", "x=$x,red,0,$y,,,,dash",
    "y=$y,red,0,$x,,,,dash"),-$g,$g,-$g,$g,1,1,400,400);

    return $plot;
}

/*-----------------------------------------------matrixreduce for complex entries-------------------------------------------------------
 Function: cx_matrixreduce(A, [rref = False, disp = False, roundto = 4])
 Returns the row echelon (ref) or reduced row echelon form (rref) of the matrix A; it handles matrices with both real and complex entries. 
 
 Parameters:
 A: Matrix in the form array([row1],[row2],...) where each row can have real entries as a single value or complex entries in the form [Re, Im]
    for example. the 2x2 matrix array([1, 2+3i], [-i, 5]) should be entered as A = array([1, [2,3]], [[0,-1], 5])

 rref: Optional - If true, it returns the reduce row echelon form; the default is false, which returns the row echelon form (ref).
 
 disp: Optional - If set to true, the function returns the string version of the entries for display, which should not be used for calculation. 

 roundto: Optional - number of decimal places to which the entries should be rounded off to in display format (i.e. when disp = true); 
          default is 4 decimal places. 
   

 Returns:
 Returns the row echelon (ref) or reduced row echelon form (rref) of the matrix A in the same format as A. 
 The display format returns the string-formatted entries formatted as a matrix.
*/  

function cx_matrixreduce($A, $rref = False, $disp = False, $roundto = 4) {
	
    include_once("matrix.php");
    if (!isMatrix($A)) { echo 'error: input not a matrix'; return '';}
    
	// number of rows
    $N  = count($A);
    // number of columns
    $M = count($A[0]);
    
	for ($i=0; $i<$N; $i++){
		for ($j=0; $j<$M; $j++){
			if(!is_array($A[$i][$j])){
				$A[$i][$j]=[$A[$i][$j],0];
			}
			if(count($A[$i][$j])==1){
				$A[$i][$j]=[$A[$i][$j][0],0];
			}
		
		}

	}
	
    $r = 0;  $c = 0;
    while ($r < $N && $c < $M) {
		if (cx_modul($A[$r][$c]) == 0) { //swap only if there's a 0 entry $A[$r][$c]==0
			$max = $r;
			for ($i = $r+1; $i<$N; $i++) {
				if (cx_modul($A[$i][$c]) > cx_modul($A[$max][$c])) {
					$max = $i;
				} 
			}
			if ($max != $r) {
				$temp = $A[$r]; $A[$r] = $A[$max]; $A[$max] = $temp;
			}
		}	

	    if (cx_modul($A[$r][$c]) < 1e-10){
	    	$c++;
	    	continue;
	    }
		
	    //scale pivot rows
	    if ($rref==True) {
			if(cx_modul($A[$r][$c])!=0){
		    	$divisor = $A[$r][$c];
			} else {$divisor=[1,0];}
		    for ($j = $c; $j < $M; $j++) {
				$A[$r][$j] = cx_div([$A[$r][$j],$divisor]);
			}
	    }
		
	    for ($i = ($rref?0:$r+1); $i < $N; $i++) {
	    	    if ($i==$r) {continue;}
				if (cx_modul($A[$r][$c])!=0 && cx_modul($A[$i][$c])!=0){
				
					$mult = cx_div([$A[$i][$c],$A[$r][$c]]); 
				} else {$mult=[0,0];}
	    	    if ( cx_modul($mult)==0) {continue;}
	    	    
				for ($j = $c; $j < $M; $j++) {
					if(cx_modul($mult)!=0 && cx_modul($A[$r][$j])!=0){

						$A[$i][$j] = cx_sub([$A[$i][$j],cx_mul([$mult,$A[$r][$j]])]);  
					} else {$A[$i][$j]=$A[$i][$j];}
						
						if (cx_modul($A[$i][$j]) <= 1e-10) {
							$A[$i][$j] = [0,0]; //treat values close to 0 as 0
								}		
	    	    }
	    }

	    $r++; $c++;
    }
	
	if($disp == True){
    
		for ($i=0; $i<$N; $i++){
			
			$A[$i] = cx_format2std($A[$i], $roundto);
		}
    $A = matrixformat($A);
	}
		
    return ($A); 
}	  

// this function checks whether $v is a valid [a,b] complex number
// return is boolean, true if ok
// value is passed by reference, so will rewrite if it's not in the
// right format but is salvagable.
function cx_is_ok(&$v) {
    if (!is_array($v)) {
        if (is_numeric($v)) {
            $v = [$v,0];
            return true;
        } else {
            return false;
        }
    } else if (count($v)==1) {
        $v = [$v[0],0];
        return true;
    } else if (count($v)==2 && is_numeric($v[0]) && is_numeric($v[1])) {
        return true;
    } else {
        return false;
    }
}
?>
