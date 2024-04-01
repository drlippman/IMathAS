<?php
require_once __DIR__ . "/init_without_validate.php";
header('P3P: CP="ALL CUR ADM OUR"');
ini_set('session.gc_maxlifetime', $CFG['GEN']['sessionmaxlife'] ?? 432000);
if ($_SERVER['HTTP_HOST'] != 'localhost') {
	session_set_cookie_params(0, '/', '.'.implode('.',array_slice(explode('.',Sanitize::domainNameWithPort($_SERVER['HTTP_HOST'])),isset($CFG['GEN']['domainlevel'])?$CFG['GEN']['domainlevel']:-2)));
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
	echo '<body>',_('Session established. Go back to your LMS and try again.'),'</body>';
} else {
?>
<body onload="redirect()">
<?php echo _("Redirecting you back to your LMS...<br/>If you aren't redirected in 5 seconds, %s click here %s."),"<a href=\"$redir\">","</a>" ?>
</body>
<?php
}
?>
</html>
