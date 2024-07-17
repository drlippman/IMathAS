<?php
    require_once '../init.php';
    if ($myrights < 100) { exit; }
    require_once __DIR__ . "/../includes/sanitize.php";
?>
<!DOCTYPE html>
<?php 
if (isset($CFG['locale'])) {
    echo '<html lang="'.$CFG['locale'].'">';
} else {
    echo '<html lang="en">';
}
?>
<head><title>Interpreter test</title></head>
<body>
<form action="checkint.php" method=post>
<textarea name=txt cols=80 rows=10><?php $cleaned = $_POST['txt']??''; echo Sanitize::encodeStringForDisplay($cleaned);?></textarea>
<BR>
<input type=submit value=submit>
</form>
<?php
	//just a development testing program, to test question interpreter
	$mathfuncs = array("sin","cos","tan","sinh","cosh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count");
	$allowedmacros = $mathfuncs;
	require_once 'interpret5.php';
	require_once "macros.php";
	if (isset($_POST['txt'])) {
		//echo "Post: $cleaned<BR>\n";
		$res = interpret('answer','numfunc',$cleaned);
        echo '<pre>';
		echo str_replace("\n","<BR>",$res);
        echo '</pre>';
		//eval("\$res = {$_POST['txt']};");
		//echo "$res<BR>\n";
	}
?>
</body>
</html>
