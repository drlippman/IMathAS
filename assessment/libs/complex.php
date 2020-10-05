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
array_push($allowedmacros,"cx_add","cx_arg", "cx_conj", "cx_div", "cx_format2pol", "cx_format2std", "cx_modul",
               "cx_plot" ,"cx_mul", "cx_quadRoot", "cx_pow", "cx_polEu","cx_pol2std", "cx_root", "cx_std2pol", "cx_sub");
                         



//--------------------------------------------Modulus----------------------------------------------------
// Function: cx_modul(num)
// Returns the modulus (absolute value) of a complex number, which is sqrt(Re^2 + Im^2). 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// 
// Returns:
// The modulus of a complex number as a float. 


function cx_modul(array $num) {
    if (count($num)!=1) { echo "cx_modul expects 1 complex number as an input"; return "";}
    
    $sq=$num[0][0]**2+$num[0][1]**2;
    $r= sqrt($sq);
   
    return $r;
}

//---------------------------------------------Argument (theta)---------------------------------------------
// Function: cx_arg(num, [argin = "rad"])
// Returns the argument (angle theta) of a complex number in radian. 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// 
// Returns:
// The agument of a complex number in radian (or degree) as a float. 

function cx_arg(array $num,string $argin="rad") {
    if (count($num)!=1) { echo "cx_arg expects 1 complex number as an input"; return "";}
    
    $re=$num[0][0];
    $im=$num[0][1];
    $r= cx_modul($num);
    $th1=asin(abs($im/$r));

    if ($re>=0 && $im>=0){
            $theta=$th1;
    }    
        else if($re<0 && $im<0){
            $theta=pi() +$th1;
        }
        else if($re<0 && $im>=0){
            $theta=pi() -$th1;
        }
        else {
            $theta=2*pi() -$th1;
        }
    if ($argin=="deg"){
        $th2=rad2deg($theta);
    }
        else{
            $th2=$theta;
        }
   
    return $th2;
}



//-------------------------------------------------Conjugate----------------------------------------------
// Function: cx_conj(num)
// Returns the conjugate of a complex number: conjugate of (a+bi)-->(a-bi)
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
//
// Returns:
// The conjugate of a complex number in an array (same format as the input).

function cx_conj(array $num) {
    if (count($num)!=1) { echo "cx_conj expects 1 complex number as an input"; return "";}
    
    $re=$num[0][0];
    $im=$num[0][1];
    $im2=-$im;
    $st= array([$re,$im2]); 
   
   return $st;
}

//------------------------------------------------Polar form --------------------------------------------
// Function: cx_std2pol(num,[argin = "rad"])
// Converts the standard form to polar form and returns the modulus and the argument as a paired value.
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// 
// Returns:
// The modulus and the argument of a complex number as a paired value in an array: array([mod, arg]).

function cx_std2pol(array $num, string $argin="rad") {
    if (count($num)!=1) { echo "cx_std2pol expects 1 complex number as an input"; return "";}
    
    $r= cx_modul($num);
    if ($argin=="deg"){
        $th1=cx_arg($num,"deg");
    }
        else{
            $th1=cx_arg($num);
        }
    
    $polar=array([$r,$th1]);    
        
    return $polar;
}

//--------------------------------------------------Euler's formula-----------------------------------------
// Function: cx_polEu(num,[roundto=12])
// Returns the polar form with the Euler's formula notation: z = r*e^i*theta as a string for displaying answer.
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// roundto: Optional - number of decimal places to which values should be rounded off; 
//          default is 12 decimal places. 
// 
// Returns:
// The polar form with the Euler's formula notation with angle in radian as a string. 

function cx_polEu(array $num,int $roundto=12) {
    if (count($num)!=1) { echo "cx_polEu expects 1 complex number as an input"; return "";}
    $r= cx_modul($num);
    $th1= cx_arg($num);
    $th1= round($th1/pi(),$roundto);  
    $r=round($r,$roundto); 
    
    $th2=makexxpretty("$th1 pi"); 
    $polar=makexpretty("$r e^($th2 i)");    
        
    return $polar;
}

//---------------------------------------------Standard form-----------------------------------------------
// Function: cx_pol2std(num, [argin = "rad"])
// Converts the polar form to standard form and returns the real and imaginary parts as a paired value 
// in an array.
//
// Parameters:
// num: An array of modulus and argument of a complex number in polar form given in square brackets: 
//      $num = array([mod, arg]).
// argin: Optional - Unit for the argument; default is "rad" for radian. For argument in degree, argin = "deg".
// 
// Returns:
// The real and imaginary parts as a paired value in an array: array([Re, Im]).

function cx_pol2std(array $num, string $argin="rad") {
    if (count($num)!=1) { echo "cx_pol2std expects 1 complex number as an input"; return "";}

    $mod=$num[0][0];
    $arg=$num[0][1];
    if ($argin=="deg"){
        $arg=deg2rad($arg);
    }
            
    $re=round($mod*cos($arg),3);
    $im=round($mod*sin($arg),3);
    
    $st= array([$re , $im]);    
        
    return $st;
}

//---------------------------------------------Complex Addition----------------------------------------
// Function: cx_add(num)
// Returns the sum of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to add 2-3i, -5i, and sqrt(2)-i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// 
// Returns:
// The real and imaginary parts of the sum as a paired value in an array: array([Re, Im]).

function cx_add(array $num) {
    
    $ret=0;
    $imt=0;
    $counter=count($num);
    for ($i=0; $i < $counter; $i++){
        
        $re=$num[$i][0];
        $im=$num[$i][1];
        
        $ret+= $re;
        $imt+= $im;
    }

    $st= array([$ret , $imt]);    
        
    return $st;
}

//---------------------------------------------Complex Subtraction----------------------------------------
// Function: cx_sub(num)
// Returns the difference of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to subtract -5i, and sqrt(2)-i from 2-3i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// 
// Returns:
// The real and imaginary parts of the difference as a paired value in an array: array([Re, Im]).

function cx_sub(array $num) {
    
    $ret=0;
    $imt=0;
    $counter=count($num);
    for ($i=1; $i < $counter; $i++){
        
        $re=$num[$i][0];
        $im=$num[$i][1];
        
        $ret+= $re;
        $imt+= $im;
    }
    $re=$num[0][0]-$ret;
    $im=$num[0][1]-$imt;
    $st= array([$re , $im]);    
        
    return $st;
}

//---------------------------------------------Complex multiplication----------------------------------------
// Function: cx_mul(num)
// Returns the product of input complex numbers in standard form as a paired value in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given as paired values in square brackets.
//       For example, to multiply 2-3i, -5i, and sqrt(2)-i, num = array([2,-3], [0,-5], [sqrt(2),-1]).
// 
// Returns:
// The real and imaginary parts of the product as a paired value in an array: array([Re, Im]).

function cx_mul(array $num) {
    
    $rt=1;
    $tht=0;
    $counter=count($num);
    for ($i=0; $i < $counter; $i++){
        
        $re=$num[$i][0];
        $im=$num[$i][1];
        $sq=$re**2+$im**2;
        $r1= sqrt($sq);
     
        $th1=asin(abs($im/$r1));

        if ($re>=0 && $im>=0){
                $theta=$th1;
        }    
            else if($re<0 && $im<0){
                $theta=pi() +$th1;
            }
            else if($re<0 && $im>=0){
                $theta=pi() -$th1;
            }
            else {
                $theta=2*pi() -$th1;
            }
    
        $rt=$rt*$r1;
        $tht+=$theta;
    }

    $re_p=$rt*cos($tht);
    $im_p=$rt*sin($tht); 
    
    $st= array([$re_p , $im_p]);    
        
    return $st;
}

//-------------------------------------------------Complex power--------------------------------------------
// Function: cx_pow(num, exp)
// Returns the power of a complex number in standard form as a paired value in an array. 
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// exp: exponent to which the complex number is raised.
// 
// Returns:
// The real and imaginary parts of the power as a paired value in an array: array([Re, Im]).

function cx_pow(array $num, $pow) {
    if (count($num)!=1) { echo "cx_pow expects 1 complex number as an input"; return "";}
    $r1= cx_modul($num);
    $th1=cx_arg($num);
    $r=$r1**$pow;
    $tht=$th1*$pow;
    
    $re=$r*cos($tht);
    $im=$r*sin($tht);  
        
    $st= array([$re , $im]);   
        
    return $st;
}

//-----------------------------------------------Complex nth Roots--------------------------------------------
// Function: cx_root(num, n)
// Returns an array of the nth roots of a complex number in standard form.  
//
// Parameters:
// num: An array of real and imaginary parts of a complex number given in square brackets: num = array([Re, Im]).
// n: The nth root. For example, for the cubic roots n=3.
// 
// Returns:
// An array of a paired value of real and imaginary parts of the nth roots. 

function cx_root(array $num,int $root) {
    if (count($num)!=1) { echo "cx_root expects 1 complex number as an input"; return "";}
    $r1= cx_modul($num);
    $th1= cx_arg($num);
    $r=$r1**(1/$root);
    $A=array();
    
    for ($k=0; $k<$root; $k++){
        
        $tht=$th1+2*pi()*$k/$root;
        $re_f=$r*cos($tht);
        $im_f=$r*sin($tht);
        $A[$k]=[$re_f , $im_f];
    }
    return $A;
}
    

//-------------------------------------------------Complex division-----------------------------------------
// Function: cx_div(num)
// Returns the quotient of two complex numbers in standard form as a paired values in an array. 
// 
// Parameters:
// num: An array of real and imaginary parts of two complex numbers (dividend, divisor) given as paired values
//      in square brackets. For example, to divide 2-3i by sqrt(2)-i, num = array([2,-3], [sqrt(2),-1]). 
// 
// Returns:
// The quotient of two complex numbers in standard form as a paired values in an array.

function cx_div(array $num) {
    if (count($num)!=2) { echo "cx_Div expects 2 complex numbers"; return "";}
    $rt=1;
    $tht=0;
    $N=array($num[0]);
    $D=array($num[1]);
    $D_conj=cx_conj($D);
    
    $rn= cx_modul($N);
    $thn= cx_arg($N);
    $rconj= cx_modul($D_conj);
    $thconj= cx_arg($D_conj);
    
    $den=($D[0][0])**2 +($D[0][1])**2;
    
    $r=$rn*$rconj/$den;
    $tht=$thn+$thconj;
    $re=$r*cos($tht);
    $im=$r*sin($tht);  
        
    $st= array([$re , $im]);     
        
    return $st;
}

//-------------------------------------Quadratic real and complex roots--------------------------------------
// Function: cx_quadRoot(a,b,c)
// Returns an array of roots of the quadratic equation f(x) = ax^2 + bx + c. Real roots are returned
// as an array([r1],[r2]) ordered from the smallest to largest, and the complex roots are returned 
// as an array([Re1,Im1], [Re2,Im2]). 
// 
// Parameters:
// a: The numerical coefficient of x^2
// b: The numerical coefficient of x    
// c: The constant
//
// Returns:
// An array of roots (either real or complex) of the quadratic equation.

function cx_quadRoot(float $a, float $b, float $c){
    $d=$b**2 - 4*$a*$c;
    if ($d<0){

        $re= -$b/(2*$a);
        $im= sqrt(abs($d))/(2*$a);
        $im2=-$im;
        $st=array([$re,$im],[$re,$im2]);
    }
        else {
            $r1=round(((-$b-sqrt($d))/(2*$a)),3);
            $r2=round(((-$b+sqrt($d))/(2*$a)),3);
            $st=array([$r1],[$r2]);
        }

    return $st;

}

//------------------------------------------Formating to standard form-------------------------------------
// Function: cx_format2std(num, [roundto=3])
// Returns a string form of a complex number in standard form: a + b i. 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: num = array([Re, Im]).
//      num array can include more than one complex number. 
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places. 
//
// Returns:
// A complex number in standard form as a string. If num has more than one complex number, the function returns
// an array of the standard forms as strings: answer = array("a1+b1 i", "a2+b2 i", ...).

function cx_format2std(array $num,int $roundto=3) {

    $A=array();
    for ($i=0;$i<count($num);$i++){
        
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
// Function: cx_format2pol(num, [argin="deg" , roundto=3])
// Returns a string form of a complex number in polar form: r (cos(t) + isin(t)). 
// 
// Parameters:
// num: An array of real and imaginary parts of complex numbers given in square brackets: num = array([Re, Im]).
//      num array can include more than one complex number. 
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places. 
// argin: Optional - Unit for the argument; default is "deg" for degree. For argument in radian, argin = "rad".
//
// Returns:
// A complex number in standard form as a string. If num has more than one complex number, the function returns
// an array of the polar forms as strings: answer = array("r1 (cos(t1) + isin(t1))", "r2 (cos(t2) + isin(t2))", ...).

function cx_format2pol(array $num, string $argin="rad", int $roundto=3) {

    $A=array();
    for ($i=0;$i<count($num);$i++){
        $num1=array($num[$i]);
        $r= round(cx_modul($num1),$roundto);

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
//-------------------------------Plot a complex number-NOT TESTED---------------------------------
# To display a complex number. Can be used to find z from plot as well as in solutions.
function cx_plot(array $num){
    
    $x=$num[0][0];
    $y=$num[0][1];

    $r=cx_modul($num);
    $m=$y/$x;
    $th=cx_arg($num);
    $thd=$th;

    if (abs($x)>abs($y)){$g=abs($x)+2;} 
        else{$g=abs($y)+2;}

    $z=makexxpretty("$x + $y i");
    $plot=showplot(array("text,$x*(1-0.4),$y*(1-0.5),r","text,$x/2,$y*(1+0.1),Re",
    "text,$x*(1+0.1),$y/2,Im","$m*x,blue,0,$x","[($r/3)*cos(t),($r/3)*sin(t)],red,0,$th",
    "dot,$x,$y,,red,z = $z,above","dot,0,0,,black,t=$thd,aboveright", "x=$x,red,0,$y,,,,dash",
    "y=$y,red,0,$x,,,,dash"),-$g,$g,-$g,$g,1,1,400,400);

    return $plot;
}
?>