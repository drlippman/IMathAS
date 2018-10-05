<!DOCTYPE html>
<?php
	$dbsetup = true; //prevents connection to database
	include("../init_without_validate.php");
?>
<html>
<head>
<title><?php echo $installname; ?></title>
<link rel="stylesheet" href="<?php echo $imasroot;?>/infopages.css" type="text/css">
<style type="text/css">
img {
	vertical-align: middle;
}
</style>
</head>
<body>
<?php
	$pagetitle = "Entering Answers";
	require("../infoheader.php");
?>

<img class="floatleft" src="<?php echo $imasroot;?>/img/typing.jpg" alt="Picture of typing"/>

<div class="content">
<h3>Answer Types</h3>
<p class="ind">
Each question requests a specific type of answer.  Usually a question will display a hint
at the end of the question as to what type of answer is expected.  In addition to 
multiple choice questions and other standard types, this system also features several mathematical
answer types.  Read on for suggestions on entering answers for these types.</p>

<h3>Numerical Answers</h3>
<p class="ind">
Some question ask for numerical answers.  Acceptable answers include whole numbers, integers (negative numbers), and decimal values. 
</p>
<p class="ind">
In special cases, you may need to enter DNE for "Does not exist", oo for infinity, or -oo for negative infinity.
</p>
<p class="ind">
If your answer is not an exact value, you'll want to enter at least 3 decimal places unless the problem specifies otherwise.
</p>

<h3>Fractions and Mixed Numbers</h3>
<p class="ind">
Some questions ask for fractions or mixed number answers.
</p>
<p class="ind">Enter fractions like 2/3 for <img src="answerimgs/23.gif">. If not specified, the fraction 
does not need to be reduced, so <img src="answerimgs/24.gif"> is considered the same as <img src="answerimgs/12.gif">.</p>
<p class="ind">
For a reduced fraction answer, be sure to reduce your fraction to lowest terms. 
If <img src="answerimgs/12.gif"> is the correct answer, <img src="answerimgs/24.gif"> is not reduced, and would be marked wrong.</p>

<p class="ind">For both fraction and reduced fraction type problems, improper 
fractions are OK, like <img src="answerimgs/103.gif">, but mixed numbers will not be accepted.</p>

<p class="ind">
For a mixed number problem, enter your answer like 5_1/3 for <img src="answerimgs/513.gif">. Improper 
fractions will not be accepted. Also, be sure to reduce the fractional portion of the mixed number to lowest terms.</p>

<h3>Calculations</h3>
<p class="ind">Some questions allow calculations be entered as answers.  You can also enter whole numbers, 
negative numbers, or decimal numbers.  If you enter a decimal value, be sure to give <i>at least</i> 3 decimal places.</p>

<p class="ind">
Alternatively, you can enter mathematical expressions.  Some examples:
<table class=bordered><tr><th>Enter</th><th>To get</th></tr>
<tr><td>sqrt(4)</td><td><img src="answerimgs/s4.gif"> = 2</td></tr>
<tr><td>2/(5-3)</td><td><img src="answerimgs/2o5m3.gif"> = 1</td></tr>
<tr><td>3^2</td><td><img src="answerimgs/3s.gif"> = 9</td></tr>
<tr><td>sin(pi)</td><td><img src="answerimgs/sinpi.gif"> = 0</td></tr>
<tr><td>arctan(1)</td><td><img src="answerimgs/arctan1.gif"> (note: tan^-1(1) will not work)</td></tr>
<tr><td>log(100)</td><td><img src="answerimgs/log100.gif"> = 2</td></tr>
</table>
<p class="ind">Note that when entering functions like sqrt and sin, use parentheses around the input.  sin(3) is ok, but sin3 is not.
</p>
<p class="ind">You can use the Preview button to see how the computer is interpreting what you have entered</p>

<h3>Algebraic Expressions</h3>
<p class="ind">Some questions ask for algebraic expression answers.  With these types of questions, 
be sure to use the variables indicated.  In your answer, you can also use mathematical expressions for numerical calculations.</p>

<p class="ind">
Examples:
<table class=bordered>
<tr><th>Type</th><th>To get</th></tr>
<tr><td>-3x^2+5</td><td><img src="answerimgs/m3xs.gif"></td></tr>
<tr><td>(2+x)/(3-x)</td><td><img src="answerimgs/2pxo3mx.gif"></td></tr>
<tr><td>sqrt(x-5)</td><td><img src="answerimgs/sxm5.gif"></td></tr>
<tr><td>3^(x+7)</td><td><img src="answerimgs/3txp7.gif"></td></tr>
<tr><td>1/(x(x+1))</td><td><img src="answerimgs/1oxtxp1.gif"></td></tr>
<tr><td>5/3x+2/3</td><td><img src="answerimgs/53x.gif"></td></tr>
<tr><td>sin(pi/3x)</td><td><img src="answerimgs/sinpio3x.gif"></td></tr>
<tr><td>ln(x)/ln(7)</td><td><img src="answerimgs/lnoln.gif"></td></tr>
<tr><td>arcsin(x)</td><td><img src="answerimgs/arcsinx.gif"></td></tr>
</table>
</p>
<p class="ind">
With any function like sqrt, log, or sin, be sure to put parentheses around the input:  sqrt(x+3) is OK, but sqrtx+3 or sqrt x+3 is not.</p>

<p class="ind">Note that the shorthand notation sin^2(x) will display correctly but not evaluate correctly; use (sin(x))^2 instead.</p>

<p class="ind">
Unless the problem gives specific directions, any algebraically equivalent expression is acceptable.  
For example, if the answer was <img src="answerimgs/f1.gif">, then <img src="answerimgs/f2.gif"> and <img src="answerimgs/f3.gif"> would also be acceptable.</p>

<p class="ind">
You can use the Preview button to see how the computer is interpreting your answer.  If you see "syntax ok", it means the computer
can understand what you typed (though it may not be the correct answer).  If you see "syntax error", you may be missing a
parenthese or have used the wrong variables in your answer.</p>


</div>

</body>
</html>
