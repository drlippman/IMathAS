<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = 'Message Conversation';
$this->params['breadcrumbs'][] = $this->title;

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../../../web/js/jquery.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="../../../web/css/imascore.css" type="text/css"/>
    <link rel="stylesheet" href="../../../web/css/default.css" type="text/css"/>
    <link rel="stylesheet" href="../../../web/css/handheld.css"
          media="handheld,only screen and (max-device-width:480px)"/>

    <link rel="shortcut icon" href="/favicon.ico"/>
    <script type="text/javascript" src="../../../web/js/general.js"></script>
    <script type="text/javascript" src="../../../web/js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
    <script src="../../../web/js/ASCIIsvg_min.js" type="text/javascript"></script>
</head>
<body>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist"></ul>
            <div class="clear"></div>
        </div>
    </div>
    <div class="midwrapper">
    <p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?id=' . $messages->id); ?>">Back to Message</a></p>
        <button onclick="expandall()" class="btn btn-primary">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary">Show All</button>
        <button onclick="hideall()" class="btn btn-primary">Hide All</button><br><br>

        <div class=block><span class="leftbtns"><img class="pointer" id="butb0" src="../../../web/img/collapse.gif"
                                                     onClick="toggleshow(0)"/> </span><span class=right><a
                    href="<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $messages->id); ?>">Reply</a>
                <input type=button class="btn btn-primary" id="buti0" value="Hide" onClick="toggleitem(0)">
</span>
            <b><?php echo $messages->title ?></b><br/>Posted by: <a
                href="mailto:<?php echo $fromUser->email ?>"><?php echo ucfirst($fromUser->FirstName) . ' ' . ucfirst($fromUser->LastName) ?></a>, <?php echo date('M d, o g:i a', $messages->senddate) ?>
            <span style="color:red;">New</span>
        </div>
        <div class="blockitems" id="item0"><p><?php echo $messages->message ?></p></div>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
    function toggleshow(bnum) {
        var node = document.getElementById('block' + bnum);
        var butn = document.getElementById('butb' + bnum);
        if (node.className == 'forumgrp') {
            node.className = 'hidden';
            //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
            //       butn.value = 'Expand';
            butn.src = imasroot + '/img/expand.gif';
        } else {
            node.className = 'forumgrp';
            //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
            //       butn.value = 'Collapse';
            butn.src = imasroot + '/img/collapse.gif';
        }
    }
    function toggleitem(inum) {
        var node = document.getElementById('item' + inum);
        var butn = document.getElementById('buti' + inum);
        if (node.className == 'blockitems') {
            node.className = 'hidden';
            butn.value = 'Show';
        } else {
            node.className = 'blockitems';
            butn.value = 'Hide';
        }
    }
    function expandall() {
        for (var i = 0; i < bcnt; i++) {
            var node = document.getElementById('block' + i);
            var butn = document.getElementById('butb' + i);
            node.className = 'forumgrp';
            //     butn.value = 'Collapse';
            //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
            butn.src = imasroot + '/img/collapse.gif';
        }
    }
    function collapseall() {
        for (var i = 0; i < bcnt; i++) {
            var node = document.getElementById('block' + i);
            var butn = document.getElementById('butb' + i);
            node.className = 'hidden';
            //     butn.value = 'Expand';
            //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
            butn.src = imasroot + '/img/expand.gif';
        }
    }

    function showall() {
        for (var i = 0; i < icnt; i++) {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('buti' + i);
            node.className = "blockitems";
            buti.value = "Hide";
        }
    }
    function hideall() {
        for (var i = 0; i < icnt; i++) {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('buti' + i);
            node.className = "hidden";
            buti.value = "Show";
        }
    }
</script>
