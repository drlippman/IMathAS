<?php

/**
 * This file handles Simple LTI filter.  It is based on the Moodle filter 
 * produced by the author listed below.
 * 
 * @author Jordi Piguillem
 * 
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package simplelti
 */

/**
 * This functions makes the requests to the server
 * 
 * @param string $url				Server URL
 * @param string $password			Remote Tool password
 * @param string $params			Request params
 * 
 * @return string		Contains server response
 * 
 */
function simplelti_request($url, $password, $params){
	
	$post = curl_init($url);
	curl_setopt($post, CURLOPT_HEADER, 1);
	curl_setopt($post, CURLOPT_POST, 1);
	curl_setopt($post, CURLOPT_POSTFIELDS, $params);
	curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
	
	$response = curl_exec($post);
	curl_close($post);

	return $response;	
}

/**
 * This functions proceses the response and prints it
 * 
 * @param $response				Server response
 * 
 */
function simplelti_print_response($response){
	$prefwidth = "95%";
	$prefheight = "95%";
	$pattern = '/<status>(\w+)<\/status>/';
	if (preg_match($pattern, $response, $launchresponse)){
		if ($launchresponse[1] == 'success'){
			$pattern = '/<type>(\w+)<\/type>/';
			preg_match($pattern, $response, $launchresponse);
			switch ($launchresponse[1]){
				case 'widget':
					$pattern = '/<widget>(.*?)<\/widget>/sm';
					preg_match($pattern, $response, $launchresponse);					
					$response = simplelti_format_widget($launchresponse[1]);
					break;
				case 'iFrame':
					$pattern = '/<launchUrl>(.*?)<\/launchUrl>/';
					preg_match($pattern, $response, $launchresponse);
					$response = simplelti_format_iFrame($launchresponse[1],
						$prefwidth, $prefheight);
					break;
				case 'post':
					$pattern = '/<launchUrl>(.*?)<\/launchUrl>/';
					preg_match($pattern, $response, $launchresponse);
					$response = simplelti_format_post($launchresponse[1],
						$prefwidth, $prefheight);
					break;
				default:
					return "Unknown type";
					break;
			}
			
		} else {
			return "Connection error";
		}

	}
	
	return $response;
}

/**
 * This function generates the params for the request
 * 
 * @param secret		The password
 * 
 * @return string 		Contains all params need for request
 * 
 * @TODO: Create a generic function. Useful for filter (or block)
 */
function simplelti_get_request_params($secret){
	if ($GLOBALS['myrights']>90) {
		$role = 'Administrator';	
	} else if (isset($GLOBALS['teacherid'])) {
		$role = 'Instructor';	
	} else {
		$role = 'Student';
	}
	$date = simplelti_get_date();
	$nonce = simplelti_nonce();
	$digest = simplelti_digest($nonce, $date, $secret);
	$params =
		'action='.urlencode('launchresolve') . '&' .
		'sec_nonce='.urlencode($nonce) . '&' .
		'sec_created='.urlencode($date) . '&' .
		'sec_digest='.urlencode($digest) . '&' .
		'user_id='.urlencode($GLOBALS['userid']) . '&' .
		'user_role='.urlencode($role) . '&' .
		'user_displayid='.urlencode($GLOBALS['username']) . '&' .
		'course_id='.urlencode($_GET['cid']) . '&' .
		'course_name='.urlencode($GLOBALS['coursename']) . '&' .
		'launch_targets='.urlencode('widget,iframe,post');
	
	return $params;
}

/**
 * This function returns a formated widget
 * 
 *  @param string $wigdet		widget from curl response
 *  
 *  @return string				formated widget, ready to print
 *  
 */
function simplelti_format_widget($widget){
	$widget = html_entity_decode($widget);
	
	return $widget;
}

/**
 * This function returns a formated iframe
 * 
 *  @param string $url		url from curl response
 *  @param int $height		activity prefered height
 *  
 *  @return string			formated iframe, ready to print
 *  
 */
function simplelti_format_iframe($url, $width, $height){
	
	return '
	<iframe src="'.$url.'"
      height="'.$height.'" width="'.$width.'" scrolling="auto" frameborder="1" transparency>
      <p>Error</p>
    </iframe>';
}

/**
 * This function returns a formated post
 * 
 *  @param string $url		url from curl response
 *  
 *  @return string			formated post, ready to print
 *  
 */
function simplelti_format_post($url, $width, $height){
	if ($GLOBALS['myrights']>90) {
		$role = 'Administrator';	
	} else if (isset($GLOBALS['teacherid'])) {
		$role = 'Instructor';	
	} else {
		$role = 'Student';
	}
	$date = simplelti_get_date();
	$nonce = simplelti_nonce();
	$digest = simplelti_digest($nonce, $date, $secret);
	$params =
		'url='.urlencode($url) . '&' .
		'sec_nonce='.urlencode($nonce) . '&' .
		'sec_created='.urlencode($date) . '&' .
		'sec_digest='.urlencode($digest) . '&' .
		'user_id='.urlencode($GLOBALS['userid']) . '&' .
		'user_role='.urlencode($role) . '&' .
		'course_id='.urlencode($_GET['cid']);
	
	$url = '<iframe src="'.$imasroot.'/filter/simplelti/post.php?'. $params . '
      height="'.$height.'" width="'.$width.'" scrolling="auto" frameborder="1" transparency>
      <p>Error</p>
    </iframe>';
	return $url;	
}

/**
 * This functions returns GMT time in ISO8601 format
 * 
 * i.e.:2008-07-23T13:29:07Z
 * 
 * @return string			GMT time in ISO8601 format
 */
function simplelti_get_date(){
	return gmdate("Y-m-d\TH:i:s\Z", time());
	
}

/**
 * Generates a nonce for remote connection
 * 
 * @return string			Contains a nonce for connection
 */
function simplelti_nonce(){
	return base64_encode(mt_rand().'-'.mt_rand()/time().'\\'.mt_rand());
}

/**
 * Creates a digest for remote connection
 * 
 * @param string $nonce			A nonce
 * @param string $date			Time correctly formated
 * @param string $password		Remote tool password
 * 
 * @return string				Generated digest
 */
function simplelti_digest($nonce, $date, $password){
	$concat = $nonce . $date . $password;
	$sha1 = sha1($concat,true);	
	return base64_encode($sha1);
}

?>
