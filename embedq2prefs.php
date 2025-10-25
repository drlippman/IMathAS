<?php
$init_skip_csrfp = true;
require_once "./init_without_validate.php";

$path = 'path=' . ($imasroot == '' ? '/' : $imasroot);
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $secure = '';
} else {
    $secure = '; Secure; SameSite=None;';
}
?>
<html>
<head>
    <title><?php echo _('Embedded Questions User Preferences');?></title>
    <script>
        function savepref() {
            var selects = document.getElementsByTagName("select");
            var prefs = {};
            for (var i=0; i<selects.length; i++) {
                prefs[selects[i].id] = selects[i].value;
            }
            let date = new Date();
            date.setFullYear(date.getFullYear() + 10);
            document.cookie = "embedq2userprefs="+encodeURIComponent(JSON.stringify(prefs))
                + "; expires="+date.toUTCString()+"; <?php echo $path,$secure; ?>";
            document.getElementById("savenote").innerText = "<?php echo _('Saved');?>";
        }
    </script>
</head>
<body>
<?php
// set existing user preferences
$prefdefaults = array(
    'mathdisp' => 7, //default is MJ3
    'graphdisp' => 1,
    'drawentry' => 1,
    'useed' => 1,
    'livepreview' => 1
);

// override via cookie if set
if (!empty($_COOKIE["embedq2userprefs"])) {
    $prefcookie = json_decode($_COOKIE["embedq2userprefs"], true);
}
$_SESSION['userprefs'] = array();
foreach ($prefdefaults as $key => $def) {
    if (!empty($prefcookie) && isset($prefcookie[$key])) {
        $_SESSION['userprefs'][$key] = filter_var($prefcookie[$key], FILTER_SANITIZE_NUMBER_INT);
    } else {
        $_SESSION['userprefs'][$key] = $def;
    }
}

require_once "includes/userprefs.php";

showUserPrefsForm(true);

echo '<p>'._('After saving preferences, close this page and go back and reload the page you were viewing for the new preferences to be utilized.').'</p>';

echo '<button type=button onclick="savepref()">'._('Save Preferences').'</button>';
echo '<div id="savenote" aria-live="polite"></div>';
?>
    </body>
</html>
