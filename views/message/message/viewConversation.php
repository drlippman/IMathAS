<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = 'Message Conversation';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid='.$messages[0]['courseId']]];
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid='.$messages[0]['courseId']]];
$this->params['breadcrumbs'][] = $this->title;
$currentLevel = 0;
?>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="../../../web/js/jquery.min.js" type="text/javascript"></script>
    <link rel="stylesheet" href="<?php echo AppUtility::getHomeURL()?>css/forums.css"
    <link rel="stylesheet" href="<?php echo AppUtility::getHomeURL()?>css/imascore.css" type="text/css"/>
    <link rel="stylesheet" href="<?php echo AppUtility::getHomeURL()?>css/default.css" type="text/css"/>
    <link rel="stylesheet" href="<?php echo AppUtility::getHomeURL()?>css/handheld.css"
          media="handheld,only screen and (max-device-width:480px)"/>

    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL()?>js/general.js"></script>
    <script type="text/javascript" src="<?php echo AppUtility::getHomeURL()?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
    <script src="<?php echo AppUtility::getHomeURL()?>js/ASCIIsvg_min.js" type="text/javascript"></script>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist"></ul>
            <div class="clear"></div>
        </div>
    </div>

    <div class="midwrapper">
        <?php $sent = $_GET['message'];
        if($sent != 1) { ?>
         <p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message='.$sent.'&id=' . $messages[0]['id']); ?>">Back to Message</a></p>
        <?php }?>
        <?php $sent = $_GET['message'];
        if($sent == 1) { ?>
            <p><a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message='.$sent.'&id=' . $messages[0]['id']); ?>">Back to Message</a></p>
        <?php }?>

        <button onclick="expandall()" class="btn btn-primary">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary">Show All</button>
        <button onclick="hideall()" class="btn btn-primary">Hide All</button>
        <br><br>


        <?php foreach($messages as $index => $message){ ?>
        <?php if($message['level'] != 0 && $message['level'] < $currentLevel)
        { ?>
            </div>
        <?php }?>
    <?php if($message['level'] != 0 && $message['level'] > $currentLevel)
            { ?>
                <div class="forumgrp" id="block<?php echo $index-1 ?>">

           <?php }?>
            <div class=block><span class="leftbtns"><img class="pointer" id="butb<?php echo $index ?>" src="<?php echo AppUtility::getHomeURL()?>img/collapse.gif" onClick="toggleshow(<?php echo $index ?>)"/> </span>
                <span class=right>

                 <?php if($message['receiveId'] != $message['senderId']) { ?>
                     <a href = "<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $message['id']); ?>" > Reply</a >
                 <?php } ?>
                 <input type=button class="btn btn-primary" id="buti<?php echo $index ?>" value="Hide" onClick="toggleitem(<?php echo $index ?>)">
                </span>
                <b><?php echo $message['title'] ?></b><br/>Posted by: <a
                href="mailto:<?php echo '#' ?>"><?php echo $message['senderName'] ?></a>, <?php echo date('M d, o g:i a', $message['msgDate']) ?>
            <span style="color:red;">New</span>
            </div>
            <div class="blockitems" id="item<?php echo $index ?>"><p><?php echo $message['message'] ?></p></div>


            <?php if($index == (count($messages)-1))
            {
                for($i = $message['level']; $i > 0; $i--){?>
                    </div>
            <?php } }?>
        <?php
            $currentLevel = $message['level'];
        $cnt = (count($messages) - 1);
        ?>
    <input type="hidden" id="cnt" value="<?php echo $cnt ?>">
    <?php      }?>

    </div>
</div>
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
        var bcnt =  $( "#cnt" ).val();
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
        var bcnt =  $( "#cnt" ).val();
        for (var i = 0; i <= bcnt; i++) {
            var node = document.getElementById('block' + i);
            var butn = document.getElementById('butb' + i);
            node.className = 'hidden';
            //     butn.value = 'Expand';
            //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
            butn.src = imasroot + '/img/expand.gif';
        }
    }

    function showall() {
        var icnt =  $( "#cnt" ).val();
        for (var i = 0; i <= icnt; i++) {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('buti' + i);
            node.className = "blockitems";
            buti.value = "Hide";
        }
    }
    function hideall() {
        var icnt =  $( "#cnt" ).val();
        for (var i = 0; i <= icnt; i++) {
            var node = document.getElementById('item' + i);
            var buti = document.getElementById('buti' + i);
            node.className = "hidden";
            buti.value = "Show";
        }
    }
</script>
