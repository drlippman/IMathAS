<?php
require_once(__DIR__ . "/init_without_validate.php");
header('P3P: CP="ALL CUR ADM OUR"');
ini_set('session.gc_maxlifetime',86400);
if ($_SERVER['HTTP_HOST'] != 'localhost') {
	session_set_cookie_params(0, '/', '.'.implode('.',array_slice(explode('.',Sanitize::domainNameWithPort($_SERVER['HTTP_HOST'])),isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
}
session_start();
$redir = Sanitize::fullUrl($_GET['redirect_url']);
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
<body onload="redirect()">
Redirecting you back to your LMS...<br/>
If you aren't redirected in 5 seconds, 
<a href="<?php echo $redir; ?>">click here</a>.
</body>
</html>
