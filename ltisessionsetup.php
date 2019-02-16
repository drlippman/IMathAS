<?php
require_once(__DIR__ . "/init_without_validate.php");
header('P3P: CP="ALL CUR ADM OUR"');
if (isset($sessionpath) && $sessionpath!='') { session_save_path($sessionpath);}
ini_set('session.gc_maxlifetime',86400);
$hostparts = explode('.',Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']));
if ($_SERVER['HTTP_HOST'] != 'localhost' && !is_numeric($hostparts[count($hostparts)-1])) {
	 session_set_cookie_params(0, '/', '.'.implode('.',array_slice($hostparts,isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
}

session_start();
$redir = Sanitize::url($_GET['redirect_url']);
?>
<!DOCTYPE html>
<html>
<head>
<script type="text/javascript">
function redirect() {
	window.location = "<?php echo $redir; ?>";
}
</script>
</head>
<?php
if (empty($redir)) {  
	echo '<body>Session established. Go close this tab or window and go back to your LMS and try again.</body>';
} else {
?>
<body onload="redirect()">
Redirecting you back to your LMS...<br/>
If you aren't redirected in 5 seconds, 
<a href="<?php echo $redir; ?>">click here</a>.
</body>
<?php
}
?>
</html>
