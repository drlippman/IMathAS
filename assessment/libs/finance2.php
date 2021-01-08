<?php
# Library name: finance2
# Financial Functions.  Version Pan2020.  September 2020
# Author:  Amir Tavangar
#          Humber College, Toronto, ON

# Include loadlibrary("finance2") to import the library in your questions.

# The functions in this library can be used to compute almost all compound and annuity problems as well as 
# different business investment decisions methods such as
    # Simple, general, ordinary annuities, and annuity due
    # Net present value (NPV) method
    # Internal Rate of Return (IRR) method
    # Interest and principal portions of payments in an amortization schedule
# It also includes TVMs() function, which includes an iframe of a TVM solver in problems capable of computing
# all types of annuities.

global $allowedmacros;
array_push($allowedmacros,"fin_FV","fin_PV","fin_PMT","fin_Nper","fin_IY","fin_IRR","fin_NPV",
                            "fin_iPMT","fin_pPMT","fin_DBD","fin_TVM");


//------------------------------------------fin_FV-------------------------------------------
// Function: fin_FV(IY,Nper,PMT,PY,CY,[PV,type])
// Returns the future value of a loan or investment based on a constant interest rate.
// 
// Parameters:
// IY:  Nominal interest rate per year as a percent
//      Example:  2.35 should be entered for nominal rate of 2.35%.
// Nper:Total number of payments (for annuties) or 
//      compounding periods (for compound interest) in the loan/investment term
// PMT: The size of periodic payment; should be entered as a negative value for cash outflow 
//      and positive value for cash inflow.
// PY:  The number of payments per year. Should be the same value as CY for compound interest 
//      problems where PMT=0; should be entered 1 for annual payment
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// CY:  The number of interest compounding periods per year; should be entered 1 for compounded annually
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// PV:  Optional - The present value (principal) of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Returns future value: a positive value for cash inflow and negative value for cash outflow.

function fin_FV(float $IY, float $Nper, float $PMT, int $PY, int $CY, float $PV=0,int $type=0) {
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    if ($Nper<0) { echo 'error: Nper cannot be negative'; return '';}
    
    $i = $IY/($CY*100);
    if ($PMT===0){
            $c=1;
    }   else{
            $c = $CY/$PY;
    }
      
    $i2 = ((1+$i)**$c)-1;
    
    if ($i == 0 ) {
            $FV = -($PV + ($PMT * $Nper));
    }   
        else {
            $FV = -((($PMT*(1+$i2*$type)*(((1+$i2)**$Nper)-1))/$i2)+$PV*(1+$i2)**$Nper);
    }
    
    $FV = round($FV,9);
    return $FV;   
}

//-------------------------------------------fin_PV-------------------------------------------
// Function: fin_PV(IY,Nper,PMT,PY,CY,[FV,type])
// Returns the presente value of a loan or investment based on a constant interest rate.
// 
// Parameters:
// IY:  Nominal interest rate per year as a percent
//      Example:  2.35 should be entered for nominal rate of 2.35%.
// Nper:Total number of payments (for annuties) or 
//      compounding periods (for compound interest) in the loan/investment term
// PMT: The size of periodic payment; should be entered as a negative value for cash outflow 
//      and positive value for cash inflow.
// PY:  The number of payments per year. Should be the same value as CY for compound interest 
//      problems where PMT=0; should be entered 1 for annual payment
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// CY:  The number of interest compounding periods per year; should be entered 1 for compounded annually
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:  Optional - The future value of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Returns present value: a positive value for cash inflow and negative value for cash outflow.

function fin_PV(float $IY, float $Nper, float $PMT, int $PY, int $CY, float $FV=0,int $type=0) {
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    if ($Nper<0) { echo 'error: Nper cannot be negative'; return '';}
    
    $i = $IY/($CY*100);
    if ($PMT===0){
            $c=1;
    }   else{
            $c = $CY/$PY;
    }
      
    $i2 = ((1+$i)**$c)-1;
    
    if ($i == 0 ) {
            $PV = -($FV + ($PMT * $Nper));
    }   
        else {
            $PV = -((($PMT*(1+$i2*$type)*(1-((1+$i2)**-$Nper)))/$i2)+$FV*(1+$i2)**-$Nper);
    }
    
    $PV = round($PV,9);
    return $PV;   
}

//-------------------------------------------fin_PMT------------------------------------------
// Function: fin_PMT(IY,Nper,PV,PY,CY,[FV,type])
// Returns the periodic payment of a loan or investment based on a constant interest rate.
// 
// Parameters:
// IY:  Nominal interest rate per year as a percent
//      Example:  2.35 should be entered for nominal rate of 2.35%.
// Nper:Total number of payments (for annuties) or 
//      compounding periods (for compound interest) in the loan/investment term
// PV:  The present value (principal) of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow.
// PY:  The number of payments per year. Should be the same value as CY for compound interest 
//      problems where PMT=0; should be entered 1 for annual payment
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// CY:  The number of interest compounding periods per year; should be entered 1 for compounded annually
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:  Optional - The future value of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Returns the periodic payment: a positive value for cash inflow and negative value for cash outflow.

function fin_PMT(float $IY, float $Nper, float $PV, int $PY, int $CY, float $FV=0, int $type=0) {
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    if ($Nper<=0) { echo 'error: Nper should be positive'; return '';}
    
    $i = $IY/($CY*100);
    $c=$CY/$PY;
    $i2 = ((1+$i)**$c)-1;
    
    if ($i == 0 ) {
            $PMT = -($FV + $PV)/$Nper;
    }   
        else {
            $ex=(1+$i2)**-$Nper;
            $den_pv0 = (1-$ex)*(1+$i2*$type);
            $pv0 = $PV*$i2/$den_pv0;
            $den_fv0 =(((1+$i2)**$Nper)-1)*(1+$i2*$type);
            $fv0 = $FV*$i2/$den_fv0;
            $PMT = -($pv0+$fv0);
    }
    
    $PMT = round($PMT,9);
    return $PMT;   
}

//---------------------------------------------fin_Nper----------------------------------------
// Function: fin_Nper(IY,PMT,PV,PY,CY,[FV,type])
// Returns the number of payments/compounding periods for an investment/loan based on periodic, 
// constant payments and a constant interest rate.
// 
// Parameters:
// IY:  Nominal interest rate per year as a percent
//      Example:  2.35 should be entered for nominal rate of 2.35%.
// PMT: The size of periodic payment; should be entered as a negative value for cash outflow 
//      and positive value for cash inflow.
// PV:  The present value (principal) of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow.
// PY:  The number of payments per year. Should be the same value as CY for compound interest 
//      problems where PMT=0; should be entered 1 for annual payment
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// CY:  The number of interest compounding periods per year; should be entered 1 for compounded annually
//      2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:  Optional - The future value of a loan or investment; should be entered 
//      as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Returns the number of payments/compounding periods in the loan or investment term.

function fin_Nper(float $IY, float $PMT, float $PV, int $PY, int $CY, float $FV=0, int $type=0) {
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
        
    $i = $IY/($CY*100);
    if ($PMT===0){
        $c=1;
    }   else{
        $c = $CY/$PY;
    }
    $i2 = ((1+$i)**$c)-1;
    
    if ($i == 0 ) {
            $Nper = -($FV + $PV)/$PMT;
    }
        elseif($PMT==0){
            $Nper=(log(abs($FV/$PV)))/(log(1+$i2));
        }
     
        else {
            
            $den_pv0 = log(1+$i2);
            $s_pv=($i2*$PV)/($PMT*(1+$i2*$type));
            $num_pv0 = log(1-abs($s_pv));

            $den_fv0 = log(1+$i2);
            $s_fv=($i2*$FV)/($PMT*(1+$i2*$type));
            $num_fv0 = log(1+abs($s_fv));
            
            $Nper = -$num_pv0/$den_pv0 +$num_fv0/ $den_fv0;
            
    }
    
    $Nper = round($Nper,9);
    return $Nper;   
}

//-------------------function for Newton's iteration needed for IY Calculation---------------
// Adapted from python code of Numpy-financial on Github

//  Using Newton's iteration until the change is less than tolerance (1e-6)
//  for all values or a maximum of 100 iterations is reached.
//  Newton's rule is
//  r_{n+1} = r_{n} - g(r_n)/g'(r_n) where
//  g(r) is the formula
//  g'(r) is the derivative with respect to r.

function gDiv($r, $n, $p, $x,$y, $w){
    // Evaluate g(i_n)/g'(r_n), where 
    // g= FV + PV*(1+r)**Nper + PMT*(1+r*type)/r * ((1+r)**Nper - 1)
    $t1 = ($r+1)**$n;
    $t2 = ($r+1)**($n-1);
    $g = $y + $t1*$x + $p*($t1 - 1) * ($r*$w + 1) / $r;
    $gp = ($n*$t2*$x - $p*($t1 - 1) * ($r*$w + 1) / ($r**2) + $n*$p*$t2 * ($r*$w + 1) / $r + $p*($t1 - 1) * $w/$r);
    
    return ($g / $gp);

}

//-----------------------------------------------fin_IY-----------------------------------------
// Function: fin_IY(Nper,PMT,PV,PY,CY,[FV,type])
// Returns the nominal interest rate per year in percent 
// 
// Parameters:
// Nper: Total number of payments (for annuties) or 
//       compounding periods (for compound interest) in the loan/investment term
// PMT:  The size of periodic payment; should be entered as a negative value for cash outflow 
//       and positive value for cash inflow.
// PV:   The present value (principal) of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow.
// PY:   The number of payments per year. Should be the same value as CY for compound interest 
//       problems where PMT=0; should be entered 1 for annual payment, 2 for semi-annually, 
//       4 for quarterly, 12 for monthly, 365 for daily.
// CY:   The number of interest compounding periods per year; should be entered 1 for compounded annually
//       2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:   Optional - The future value of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Returns the nominal interest rate per year in percent 

function fin_IY(float $Nper, float $PMT, float $PV, int $PY, int $CY, float $FV=0, int $type=0) {
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    $tol=1e-6;  
    $rn = 0.1;
    $iterator = 0;
    $close = False;
    $maxiter=100;
    while (($iterator < $maxiter) && ($close==False)){
        $rnp1 = $rn - gDiv($rn, $Nper, $PMT, $PV, $FV, $type);
        $diff = abs($rnp1-$rn);
        if ($diff<$tol){$close=True;}
        $iterator += 1;
        $rn = $rnp1;
    }
    if ($close==False){ echo 'error: IY cannot be found'; return $rn;}
        
       else {
            $I=((($rn+1)**($PY/$CY)) -1)*$CY*100;
            return $I;
    }
}

//-------------------------------------------fin_iPMT------------------------------------------
// Function: iPMT(per,IY,Nper,PV,PY,CY,[FV,type])
// Returns interest portion of a payment or a period (a range of payments). 
// 
// Parameters:

// per:  Array of two elements for the range of payments for which interest portion is calculated.
//       for in terest portion of a single payment, both values in the array should be the same.
// IY:   Nominal interest rate per year as a percent
//       Example: 2.35 should be entered for nominal rate of 2.35%.
// Nper: Total number of payments (for annuties) or 
//       compounding periods (for compound interest) in the loan/investment term
// PV:   The present value (principal) of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow.
// PY:   The number of payments per year. Should be the same value as CY for compound interest 
//       problems where PMT=0; should be entered 1 for annual payment, 2 for semi-annually, 
//       4 for quarterly, 12 for monthly, 365 for daily.
// CY:   The number of interest compounding periods per year; should be entered 1 for compounded annually
//       2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:   Optional - The future value of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Interest portion of a payment or a period (a range of payments) 

function fin_iPMT(array $per,float $IY,float $Nper, float $PV, int $PY, int $CY, float $FV=0, int $type=0){
    if (count($per)!=2) {echo 'array size must be 2: cannot compute'; return '';}
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    $pmt=round(fin_PMT($IY,$Nper,$PV,$PY,$CY,$FV=0,$type=0),3);
    $i=$IY/($CY*100);
    $i2=((1+$i)**($CY/$PY))-1;

    $ipmt=array();
    $ipmt[0]=0;
    $ppmt=array();
    $ppmt[0]=0;
    $bal=abs($PV);
    $upto=$per[1];
    $intsum=array();
    $intsum[0]=0;
    $prnsum=array();
    $prnsum[0]=0;
    for ($j=1;$j<=$upto;$j++){
        $ipmt[$j]=round($bal*$i2,3);
        $intsum[$j]= round($intsum[$j-1]+$ipmt[$j],3);
        $ppmt[$j]=abs($pmt)-$ipmt[$j];
        $prnsum[$j]= round($prnsum[$j-1]+$ppmt[$j],3);
        $bal=round($bal-$ppmt[$j],3);
    }
    $k1=$per[0]-1;
    $k2=$per[1];
    $intpaid=$intsum[$k2]-$intsum[$k1];
    $prnpaid=$prnsum[$k2]-$prnsum[$k1];

    return $intpaid;
}

//-------------------------------------------fin_pPMT------------------------------------------
// Function: pPMT(per,IY,Nper,PV,PY,CY,[FV,type])
// Returns principal portion of a payment or a period (a range of payments). 
// 
// Parameters:

// per:  Array of two elements for the range of payments for which interest portion is calculated.
//       for in terest portion of a single payment, both values in the array should be the same.
// IY:   Nominal interest rate per year as a percent
//       Example: 2.35 should be entered for nominal rate of 2.35%.
// Nper: Total number of payments (for annuties) or 
//       compounding periods (for compound interest) in the loan/investment term
// PV:   The present value (principal) of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow.
// PY:   The number of payments per year. Should be the same value as CY for compound interest 
//       problems where PMT=0; should be entered 1 for annual payment, 2 for semi-annually, 
//       4 for quarterly, 12 for monthly, 365 for daily.
// CY:   The number of interest compounding periods per year; should be entered 1 for compounded annually
//       2 for semi-annually, 4 for quarterly, 12 for monthly, 365 for daily.
// FV:   Optional - The future value of a loan or investment; should be entered 
//       as a negative value for cash outflow and positive value for cash inflow; default = 0. 
// type: Optional - The type of annuity. Should be eneterd 1 for annuity due and 
//       0 (default) for a general annuity. 
// 
// Returns:
// Principal portion of a payment or a period (a range of payments) 

function fin_pPMT(array $per,float $IY,float $Nper, float $PV, int $PY, int $CY, float $FV=0, int $type=0){
    if (count($per)!=2) {echo 'array size must be 2: cannot compute'; return '';}
    if ($CY<=0 or $PY<=0) { echo 'error: CY and PY must be positive'; return '';}
    if ($type!=0 && $type!=1) { echo 'error: type gets either 1 for annuity due (beginning) or 0 for general annuity (end)'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    $pmt=round(fin_PMT($IY,$Nper,$PV,$PY,$CY,$FV=0,$type=0),3);
    $i=$IY/($CY*100);
    $i2=((1+$i)**($CY/$PY))-1;

    $ipmt=array();
    $ipmt[0]=0;
    $ppmt=array();
    $ppmt[0]=0;
    $bal=abs($PV);
    $upto=$per[1];
    $intsum=array();
    $intsum[0]=0;
    $prnsum=array();
    $prnsum[0]=0;
    for ($j=1;$j<=$upto;$j++){
        $ipmt[$j]=round($bal*$i2,3);
        $intsum[$j]= round($intsum[$j-1]+$ipmt[$j],3);
        $ppmt[$j]=abs($pmt)-$ipmt[$j];
        $prnsum[$j]= round($prnsum[$j-1]+$ppmt[$j],3);
        $bal=round($bal-$ppmt[$j],3);
    }
    $k1=$per[0]-1;
    $k2=$per[1];
    $intpaid=$intsum[$k2]-$intsum[$k1];
    $prnpaid=$prnsum[$k2]-$prnsum[$k1];

    return $prnpaid;
}

//--------------------------------------------fin_IRR--------------------------------------------
// Function: fin_IRR(CF,FR)
// Returns the Internal Rate of Return (IRR), in percent, of a series of cash flows, which is the 
// average periodically compounded rate of return that gives a net present value (NPV) of 0.
// IRR is the lowest rate of return from an investment that is acceptable to justify the investment.  
// Both CF and FR arrays must be the same size.  
// 
// Parameters: 
// CF:  Array of cash flows; cash outflows (investments) should be entered as negative values;
//      cash inflows (expected returns on investment) should be enetered as positive values.
// FR:  Array of frequency of each cash flow; 
//      initial cash flow is negative (cash outflow) and has a frequency of 1.
// 
// Returns:
// The Internal Rate of Return (IRR) in percent.

function fin_IRR(array $CF, array $FR){
    if (count($CF)!=count($FR)) {echo 'array sizes do not match: cannot compute'; return '';}
    include("solvers.php");
    
    $f="0";
    $co=count($FR);
    $counter=0;
    //for ($x = 0; $x <= 10; $x++)
    for ($i=0;$i<=$co;$i++){
        $b=$FR[$i]+$counter-1;
        for($j=$counter;$j<=$b;$j++){
            $f="$f + $CF[$i] (1+x)^-$j";
    }
        $counter+=  $FR[$i];
    }

    $n=discretenewtons($f,-2,2);
    return $n*100;
}

//----------------------------------------------fin_NPV------------------------------------------
// Function: NPV(CF,FR,IY)
// Returns the Net Present Value (NPV) of a series of cash flows.
// NPV is a method of evaluating the feasibility of an investment by finding the difference of present values 
// of all cash flows. Investment is accepted when NPV>=0 and rejected when NPV<0.
// Both CF and FR arrays must be the same size. 
// 
// Parameters: 
// CF:  Array of cash flows; cash outflows (investments) should be entered as negative values;
//      cash inflows (expected returns on investment) should be enetered as positive values.
// FR:  Array of frequency of each cash flow; 
//      initial cash flow is negative (cash outflow) and has a frequency of 1.
// IY:  The required rate of return in percent.
// 
// Returns:
// The Net Present Value (NPV) of a series of cash flows ($). 

function fin_NPV(array $CF, array $FR, float $IY){
    if (count($CF)!=count($FR)) {echo 'array sizes do not match: cannot compute'; return '';}
    if ($IY<0) { echo 'error: IY cannot be negative'; return '';}
    //include("macros.php");
    
    $f="0";
    $co=count($FR);
    $counter=0;
    //for ($x = 0; $x <= 10; $x++)
    for ($i=0;$i<=$co;$i++){
        $b=$FR[$i]+$counter-1;
        for($j=$counter;$j<=$b;$j++){
            $f="$f + $CF[$i] (1+x)^-$j";
    }
        $counter+=  $FR[$i];
    }

    $npv=evalfunc($f,"x",$IY/100);
    return $npv;
}

//--------------------------------------------fin_DBD---------------------------------------------
// Function: DBD(Date1,Date2)
// Returns the number of days between the given dates (DBD). 
// DBD function can be used for time-value-of-money problems (generally short terms) 
// where the investment or loan term is not given explicitly but rather in a form of two dates. 
//
// Parameters:
// Date1: Starting date in the form of 'YYYY-MM-DD', 'YYYY/MM/DD', or 'MM/DD/YYYY'.
// Date2: End date in the form of 'YYYY-MM-DD', 'YYYY/MM/DD', or 'MM/DD/YYYY'. 
// 
// Returns:
// Returns the number od days between the given dates (DBD). 

function fin_DBD($D1,$D2) {

    $date1=new DateTime($D1);
    $date2=new DateTime($D2);
    $delta=date_diff($date1,$date2,$absolute = FALSE);
    $dbd=$delta->days;
    
    return $dbd;
}


//-------------------------------------------fin_TVM---------------------------------------------
// Function: fin_TVM()
// Returns an iframe for displaying a Time-Value-of-Money (TVM) solver.  
// Provides students with a solver that can be used to compute various values of loans or investments. 
// 
// Parameters: None
// 
// Returns:
// An embeded iframe code for inserting the solver into a question.

function fin_TVM() {
    //return '<iframe src="https://p2.amirtavangar.com" name="TVMsolver" width="420" height="680" frameborder="0" scrolling="no" ><p>Your browser does not support iframes.</p></iframe>';
    return '<iframe src="https://s3-us-west-2.amazonaws.com/oervm/tvm/index.html" name="TVMSolver" width="400" height="500" frameborder="0" scrolling="no" ><p>Your browser does not support iframes.</p></iframe>';
}


?>
