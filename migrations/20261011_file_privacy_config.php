<?php
/*
 * Private file storage: requires signing secrets in config.php and a
 * /privfilestore directory (local storage only) that the web server cannot
 * serve directly.
 */

$configFileLocation = __DIR__ . '/../config.php';

if (!file_exists($configFileLocation)) {
	return true;
}

$originalConfig = file_get_contents($configFileLocation);

if (strpos($originalConfig, 'filepresecret') === false || strpos($originalConfig, 'filesigsecret') === false) {
	?>
	<p style='color: red;'>Manual update required for file: <?php echo $configFileLocation ?></p>
	<p>Private files (forum attachments, message attachments, assessment file uploads, and most
	text editor uploads) are now served through getfile.php with signed URLs.
	Add the following to your <b>config.php</b>. These secrets were randomly generated for you, 
	but you can use any minimum 16-character secret:</p>
	<div style="display: table-cell;">
	<pre style="border: 1px solid; padding: 5px;">$CFG['GEN']['filepresecret'] = '<?php echo bin2hex(random_bytes(24)); ?>';
$CFG['GEN']['filesigsecret'] = ['<?php echo bin2hex(random_bytes(24)); ?>'];</pre>
	</div>
	<p>Do not change filepresecret later. To rotate filesigsecret, add a new secret at the front of the
	array and keep the old ones in the array. Until these are set, new file uploads to private areas will fail.</p>
	<?php
}

require_once __DIR__ . '/../includes/filehandler.php';
if (getfilehandlertype('filehandlertype') == 'local') {
	$privdir = __DIR__ . '/../privfilestore';
	if (!is_dir($privdir)) {
		echo "<p style='color: red;'>Create the directory <b>privfilestore</b> in the IMathAS root, writable by the web server, and block direct web access to it. ";
		echo "An .htaccess file is provided for Apache; for nginx add <code>location /privfilestore/ { deny all; }</code>.</p>";
	} else if (!file_exists($privdir . '/.htaccess')) {
		echo "<p style='color: blue;'>privfilestore/.htaccess is missing. Make sure the web server denies direct access to /privfilestore/ (nginx: <code>location /privfilestore/ { deny all; }</code>).</p>";
	}
}

return true;
