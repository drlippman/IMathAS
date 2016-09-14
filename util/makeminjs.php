<?php
require("../validate.php");
if ($myrights<100) { exit; }
error_reporting(E_ALL);
function minify($c) {
	//$min = httpPost('https://javascript-minifier.com/raw', array('input'=>$c));
	//alt:
	$min = httpPost('http://closure-compiler.appspot.com/compile', array('js_code'=>$c, 'compilation_level'=>'SIMPLE_OPTIMIZATIONS', 'output_info'=>'compiled_code', 'output_format'=>'text'));

	return $min;
}
function httpPost($url, $data)
{
    /*$curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
		*/
		$options = array(
	    'http' => array(
	        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	        'method'  => 'POST',
	        'content' => http_build_query($data)
	    )
		);
		$context  = stream_context_create($options);
		$response = file_get_contents($url, false, $context);
		return $response;
}

//build assessment javascript min file
$c = minify(file_get_contents("../javascript/general.js"))."\n";
$c .= minify(file_get_contents("../javascript/mathjs.js"))."\n";
$c .= minify(file_get_contents("../javascript/AMhelpers.js"))."\n";
$c .= minify(file_get_contents("../javascript/confirmsubmit.js"))."\n";
$c .= minify(file_get_contents("../javascript/drawing.js"))."\n";
$c .= minify(file_get_contents("../javascript/eqntips.js"))."\n";

file_put_contents("../javascript/assessment_min.js", $c);
echo "Wrote assessment_min<br>";

$c = file_get_contents("../javascript/mathquill.js")."\n";
$c .= file_get_contents("../javascript/mathquilled.js")."\n";
$c .= file_get_contents("../javascript/AMtoMQ.js")."\n";

file_put_contents("../javascript/MQbundle_min.js", minify($c));
echo "Wrote MQbundle_min<br>";
?>
