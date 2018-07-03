<?php

@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");

require("../init.php");
if ($myrights<100) { exit; }
error_reporting(E_ALL);
function minify($c) {
	//$min = httpPost('https://javascript-minifier.com/raw', array('input'=>$c));
	//alt:
	$min = httpPost('https://closure-compiler.appspot.com/compile', array('js_code'=>$c, 'compilation_level'=>'SIMPLE_OPTIMIZATIONS', 'output_info'=>'compiled_code', 'output_format'=>'text'));

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
        if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
         	 $ishttps = true;
        } else {
         	 $ishttps = false;
        }
		$options = array(
	    'http' => array(
	        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	        'method'  => 'POST',
	        'content' => http_build_query($data)
	    ), 
	    'ssl' => array(
	    	    'verify_peer' => $ishttps,
	    ),
		);
		$context  = stream_context_create($options);
		$response = file_get_contents($url, false, $context);
		return $response;
}

//build assessment javascript min file
$g = minify(file_get_contents("../javascript/general.js"));	
$m = minify(file_get_contents("../javascript/mathjs.js"));
$a = minify(file_get_contents("../javascript/AMhelpers.js"));
$c = minify(file_get_contents("../javascript/confirmsubmit.js"));
$d = minify(file_get_contents("../javascript/drawing.js"));
$e = minify(file_get_contents("../javascript/eqntips.js"));

if (trim($g)=='' || trim($m)=='' || trim($a)=='' || trim($c)=='' || trim($d)=='' || trim($e)=='') {
	echo "One or minimizations failed";
	exit;
}
$c = $g."\n".$m."\n".$a."\n".$c."\n".$d."\n".$e;
file_put_contents("../javascript/assessment_min.js", $c);
echo "Wrote assessment_min<br>";

$mc = file_get_contents("../javascript/mathquill.js")."\n";
$me = file_get_contents("../javascript/mathquilled.js")."\n";
$ma = file_get_contents("../javascript/AMtoMQ.js")."\n";

if (trim($mc)=='' || trim($me)=='' || trim($ma)=='') {
	echo "One or minimizations failed";
	exit;
}
$c = $mc."\n".$me."\n".$ma;
file_put_contents("../javascript/MQbundle_min.js", minify($c));
echo "Wrote MQbundle_min<br>";
?>
