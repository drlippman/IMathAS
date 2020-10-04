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
array_push($allowedmacros,"cx_argDeg", "cx_argRad", "cx_conj", "cx_div", "cx_img", "cx_mul", "cx_modul",
            "cx_quadRoot","cx_polDeg","cx_pow", "cx_polRad", "cx_prettypolRad", "cx_polEu", "cx_prettypolDeg",
            "cx_prettyargRad", "cx_real", "cx_root", "cx_std");



//--------------------------------------------Modulus----------------------------------------------------
// Function: cx_modul(Re,Im, [roundto=3])
// Returns the modulus (absolute value) of a complex number, which is sqrt(Re^2 + Im^2). 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// roundto: Optional - number of decimal places to which modulus should be rounded off; 
//          default is 3 decimal places.
// 
// Returns:
// The modulus of a complex number. 


function cx_modul(float $re,float $im,int $roundto=3) {
    $sq=$re**2+$im**2;
    $r= sqrt($sq);
   
    return round($r,$roundto);
}

//---------------------------------------------Argument (theta) in radian----------------------------------
// Function: cx_argRad(Re,Im)
// Returns the argument (angle theta) of a complex number in radian. 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The agument of a complex number in radian. 

function cx_argRad(float $re,float $im) {
    
    $r= cx_modul($re,$im);
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
   
    return $theta;
}

//------------------------------------------Pretty Argument in radian--------------------------------------
// Function: cx_prettyargRad(Re,Im)
// Returns the argument (angle theta) of a complex number in radian as a multiple of pi in a string format. 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The argument of a complex number in radian as a multiple of pi.

function cx_prettyargRad(float $re,float $im) {
    
    $r= cx_modul($re,$im);
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
    //$theta=round($theta,3);
        
    $tt= round($theta/pi(),3);   
    //$tt=decimaltofraction($tt);
    $rr=makexxpretty("$tt pi");      
    
    return $rr;
}

//------------------------------------------Argument or theta in degree------------------------------------
// Function: cx_argDeg(Re,Im)
// Returns the argument (angle theta) of a complex number in degree. 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The argument of a complex number in degree. 

function cx_argDeg(float $re,float $im) {
    
    $r= cx_modul($re,$im);
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
       
    $th2=rad2deg($theta);
         
    return round($th2,1);
}

//-------------------------------------------------Conjugate----------------------------------------------
// Function: cx_conj(Re,Im)
// Returns the conjugate of a complex number: conjugate of (a+bi)-->(a-bi)
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The conjugate of a complex number

function cx_conj(float $re,float $im) {
   
   $st=makexxpretty("$re - $im i"); 
   return $st;
}

//------------------------------------------------Polar form in degree-------------------------------------
// Function: cx_polDeg(Re,Im)
// Converts the standard form to polar form and returns the modulus and the argument (degree) as a paired value.
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The modulus and the argument, degree, of a complex number as a paired value. 

function cx_polDeg(float $re,float $im) {
    
    $r= cx_modul($re,$im);
    $th1=cx_argDeg($re,$im);
    $polar="($r,$th1&deg)";    
        
    return $polar;
}

//-----------------------------------------Pretty string of Polar form in degree-----------------------------
// Function: cx_prettypolDeg(Re,Im)
// Returns polar form of a complex number in the form of z = r(cos(t) + isin(t)) as a string. 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// Polar form of a complex number; angle in degree. 

function cx_prettypolDeg(float $re,float $im) {
    
    $r= cx_modul($re,$im);
    $th1=cx_argDeg($re,$im);
    $polar=makexpretty("$r (cos($th1 &deg)+isin($th1 &deg))");    
        
    return $polar;
}

//----------------------------------------------Polar form in radian-----------------------------------------
// Function: cx_polRad(Re,Im)
// Converts the standard form to polar form and returns the modulus and the argument (radian) as a paired value.
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The modulus and the argument, in radian, of a complex number as a paired value. 

function cx_polRad(float $re,float $im) {
    
    $r= cx_modul($re,$im);
    $th1= cx_argRad($re,$im);
    $polar="($r, $th1)";    
        
    return $polar;
}

//---------------------------------------Pretty string of Polar form in radian-------------------------------
// Function: cx_prettypolRad(Re,Im)
// Returns polar form of a complex number in the form of z = r(cos(t) + isin(t)) as a string. 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The polar form of a complex number; angle in radian. 

function cx_prettypolRad(float $re,float $im) {
    
    $r= cx_modul($re,$im);
    $th1=cx_prettyargRad($re,$im);
    $polar=makexpretty("$r (cos($th1)+isin($th1))");    
        
    return $polar;
}

//--------------------------------------------------Euler's formula-----------------------------------------
// Function: cx_polEu(Re,Im)
// Returns the polar form with the Euler's formula notation: z = r e^i*theta as a string.
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// 
// Returns:
// The polar form with the Euler's formula notation with angle in radian. 

function cx_polEu(float $re,float $im) {
    
    $r= cx_modul($re,$im);
    $th1= cx_prettyargRad($re,$im);
    $polar=makexpretty("$r.e^($th1 i)");    
        
    return $polar;
}

//---------------------------------------------Real part of complex number----------------------------------
// Function: cx_real(mod, arg)
// Converts the polar form to standard form and returns the real part of the complex number. 
//
// Parameters:
// mod: The modulus of a complex number.
// arg: The argument (angle theta) of a complex number in degree. 
// 
// Returns:
// The real part of the complex number.

function cx_real(float $mod, $arg) {
    
    $arg=deg2rad($arg);
    $x=round($mod*cos($arg),3);
    $y=round($mod*sin($arg),3);
    
    //$st="($x , $y)";    
        
    return $x;
}

//-----------------------------------------Imaginary part of complex number----------------------------------
// Function: cx_img(mod, arg)
// Converts the polar form to standard form and returns the imaginary part of the complex number. 
//
// Parameters:
// mod: The modulus of a complex number.
// arg: The argument (angle theta) of a complex number in degree. 
// 
// Returns:
// The imaginary part of the complex number.

function cx_img(float $mod, $arg) {
    
    $arg=deg2rad($arg);
    $x=round($mod*cos($arg),3);
    $y=round($mod*sin($arg),3);
    
    //$st="($x , $y)";    
        
    return $y;
}

//------------------------------------------------- Standard form--------------------------------------------
// Function: cx_std(mod, arg)
// Converts the polar form and returns the complex number in standard form as a string: a + bi 
// 
// Parameters:
// mod: The modulus of a complex number.
// arg: The argument (angle theta) of a complex number in degree. 
// 
// Returns:
// The complex number in standard form as a string.

function cx_std(float $mod, $arg) {
    
    $arg=deg2rad($arg);
    $x=round($mod*cos($arg),3);
    $y=round($mod*sin($arg),3);
    
    $st=makexxpretty("$x + $y i");    
        
    return $st;
}

//---------------------------------------------Complex multiplication----------------------------------------
// Function: cx_mul(nums)
// Returns the product of input complex numbers in standard form as a string: a + bi 
// 
// Parameters:
// nums: An array of real and imaginary parts of complex numbers given in lists.
//       For example, to multiply 2-3i, -5i, and sqrt(2)-i, nums = array("2,-3", "0,-5", "sqrt(2),-1").
// 
// Returns:
// The product of input complex numbers in standard form.

function cx_mul(array $val) {
    
    $rt=1;
    $tht=0;
    $counter=count($val);
    for ($i=0; $i < $counter; $i++){
        $j = explode("," , $val[$i]); 
        $r1= cx_modul($j[0],$j[1]);
        $th1=cx_argDeg($j[0],$j[1]);
        $rt=$rt*$r1;
        $tht+=$th1;
    }

    $r=$rt;
    $re=round($r*cos(deg2rad($tht)),3);
    $im=round($r*sin(deg2rad($tht)),3);  
    
    $st=makexxpretty("$re + $im i");    
        
    return $st;
}

//-------------------------------------------------Complex power--------------------------------------------
// Function: cx_pow(Re, Im, exp)
// Returns the power of a complex number in standard form as a string: a + bi 
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// exp: exponent to which the complex number is raised.
// 
// Returns:
// The power of a complex number in standard form.

function cx_pow(float $re,float $im,int $pow) {
    
    $r1= cx_modul($re,$im);
    $th1=cx_argDeg($re,$im);
    $r=$r1**$pow;
    $tht=$th1*$pow;
    
    $re=round($r*cos(deg2rad($tht)),3);
    $im=round($r*sin(deg2rad($tht)),3);  
        
    $st=makexxpretty("$re + $im i");    
        
    return $st;
}

//-----------------------------------------------Complex nth Roots--------------------------------------------
// Function: cx_root(Re, Im, n)
// Returns an array of the nth roots of a complex number in standard form.  
//
// Parameters:
// Re: Real part of a complex number.
// Im: Imaginary part of a complex number. 
// n: The nth root. For example, for the cubic roots n=3.
// 
// Returns:
// An array of the nth roots of a complex number in standard form. 

function cx_root(float $re,float $im,int $root) {
    
    $r1= cx_modul($re,$im);
    $th1= cx_argDeg($re,$im);
    $r=$r1**(1/$root);
    $A=array();
    
    for ($k=0; $k<$root; $k++){
        
        $tht=$th1+360*$k/$root;
        $re_f=round($r*cos(deg2rad($tht)),3);
        $im_f=round($r*sin(deg2rad($tht)),3);
        $st=makexxpretty("$re_f + $im_f i"); //("$re_f , $im_f");
        $A[$k]=$st;
    }
    return $A;
}
    

//-------------------------------------------------Complex division-----------------------------------------
// Function: cx_div(nums)
// Returns the quotient of two complex numbers in standard form as a string: a + bi 
// 
// Parameters:
// nums: An array of real and imaginary parts of two complex numbers given in lists.
//       For example, to divide -2-3i by sqrt(3)-i, nums = array("-2,-3", "sqrt(3),-1").
// 
// Returns:
// The quotient of two complex numbers in standard form.

function cx_div(array $val) {
    if (count($val)!=2) { echo "comDiv expects 2 complex numbers"; return "";}
    $rt=1;
    $tht=0;
    $com1=explode("," ,$val[0]);
    $com2=explode("," ,$val[1]);
    $conj=cx_conj($com2[0],$com2[1]);
    
    $rn= cx_modul($com1[0],$com1[1]);
    $thn= cx_argDeg($com1[0],$com1[1]);
    $rconj= cx_modul($com2[0],-$com2[1]);
    $thconj= cx_argDeg($com2[0],-$com2[1]);
    
    $den=($com2[0])**2 +($com2[1])**2;
    
    $r=$rn*$rconj/$den;
    $tht=$thn+$thconj;
    $re=round($r*cos(deg2rad($tht)),3);
    $im=round($r*sin(deg2rad($tht)),3);  
        
    $st=makexxpretty("$re + $im i");  
        
    return $st;
}

//-------------------------------------Quadratic real and complex roots--------------------------------------
// Function: cx_quadRoot(a,b,c)
// Returns an array of roots of the quadratic equation f(x) = ax^2 + bx + c. Real roots are returned
// as an array(root1,root2) ordered from the smallest to largest, and the complex roots are returned 
// as an array(a+bi, a-bi). Question type Complex handles both types of solutions where $answerformat = "list".
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

        $re= round(-$b/(2*$a),3);
        $im= round(sqrt(abs($d))/(2*$a),3);
        $st=array(makexxpretty("$re + $im i"),makexxpretty("$re - $im i"));//array($re,$im);
    }
        else {
            $r1=round(((-$b-sqrt($d))/(2*$a)),3);
            $r2=round(((-$b+sqrt($d))/(2*$a)),3);
            $st=array($r1,$r2);
        }

    return $st;


}

?>
