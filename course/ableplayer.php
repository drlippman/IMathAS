<?php
require '../includes/sanitize.php';
require '../config.php';
?>
<!DOCTYPE html>
<?php if (isset($CFG['locale'])) {
	echo '<html lang="'.$CFG['locale'].'">';
} else {
	echo '<html lang="en">';
}
if (!isset($_GET['video'])) {
    echo 'Need video';
    exit;
}
?>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Video</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<link rel="stylesheet" href="../javascript/ableplayer/build/ableplayer.min.css" type="text/css"/>
<script src="../javascript/ableplayer/build/ableplayer.dist.js"></script>
<style>
body {margin: 0; padding: 0;}
</style>
<script>
function resizeAblePlayer() {
    var wrap = $(".able-wrapper");
    if (!AblePlayer.lastCreated || !AblePlayer.lastCreated.playerCreated) {
        setTimeout(resizeAblePlayer, 100);
        return;
    }
    if (wrap.height() > window.innerHeight) {
        var barh = wrap.find(".able-player.able-video").height();
        if (wrap.find(".able-captions-below")) {
            barh += wrap.find(".able-captions-below").height();
        }
        wrap.width(wrap.width()*(window.innerHeight - barh)/(wrap.height() - barh));
        AblePlayer.lastCreated.resizePlayer();
    }
}
$(function() {
    resizeAblePlayer();
});
</script>
</head>
<body>
<video id="video1" 
    data-able-player
    data-youtube-nocookie="true" 
    data-youtube-id="<?php echo Sanitize::encodeStringForDisplay($_GET['video']);?>">
<?php
if (isset($_GET['captions'])) {
    echo '<track kind="captions" src="' . Sanitize::url($_GET['captions']) . '" />';
}
if (isset($_GET['descriptions'])) {
    echo '<track kind="descriptions" src="' . Sanitize::url($_GET['descriptions']) . '" />';
}
?>  
</video>
</body>
</html>