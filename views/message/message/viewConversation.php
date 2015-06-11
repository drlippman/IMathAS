<?php
use yii\helpers\Html;
use app\components\AppUtility;

$this->title = 'Message Conversation';
$this->params['breadcrumbs'][] = ['label' => 'Course', 'url' => ['/instructor/instructor/index?cid=' . $messages[0]['courseId']]];
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid=' . $messages[0]['courseId']]];
$this->params['breadcrumbs'][] = $this->title;
$currentLevel = 0;
?>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="../../../web/js/jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo AppUtility::getHomeURL() ?>css/forums.css"
<link rel="stylesheet" href="<?php echo AppUtility::getHomeURL() ?>css/imascore.css" type="text/css"/>
<link rel="stylesheet" href="<?php echo AppUtility::getHomeURL() ?>css/default.css" type="text/css"/>
<link rel="stylesheet" href="<?php echo AppUtility::getHomeURL() ?>css/handheld.css"
      media="handheld,only screen and (max-device-width:480px)"/>

<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/general.js"></script>
<script type="text/javascript"
        src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script src="<?php echo AppUtility::getHomeURL() ?>js/ASCIIsvg_min.js" type="text/javascript"></script>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist"></ul>
            <div class="clear"></div>
        </div>
    </div>

    <div class="midwrapper">
        <?php $sent = $_GET['message'];
        if ($sent != 1) {
            ?>
            <p>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message=' . $sent . '&id=' . $messages[0]['id']); ?>">Back
                    to Message</a></p>
        <?php } ?>
        <?php $sent = $_GET['message'];
        if ($sent == 1) {
            ?>
            <p>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message=' . $sent . '&id=' . $messages[0]['id']); ?>">Back
                    to Message</a></p>
        <?php } ?>
        <button onclick="expandall()" class="btn btn-primary">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary">Show All</button>
        <button onclick="hideall()" class="btn btn-primary">Hide All</button>
        <br><br>
        <?php
        $DivCount = 0;
        foreach ($messages as $index => $message){
        ?>
        <?php
        if ($message['level'] != 0 && $message['level'] < $currentLevel)
        {
        $DivCount--;
        for ($i = $currentLevel;$message['level'] < $i;$i--){
        ?>
    </div>
    <?php
    }
    ?>
    <?php } ?>
    <?php if ($message['level'] != 0 && $message['level'] > $currentLevel)
    {
    $DivCount++;?>
    <div class="forumgrp" id="block<?php echo $index - 1 ?>">

        <?php } ?>
        <div class=block><span class="leftbtns"><img class="pointer" id="butb<?php echo $index ?>"
                                                     src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"
                                                     onClick="toggleshow(<?php echo $index ?>)"/> </span>
                <span class=right>

                 <?php if ($user['id'] != $message['senderId']) { ?>
                     <a href="<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $message['id']); ?>">
                         Reply</a>
                 <?php } ?>
                    <input type=button class="btn btn-primary" id="buti<?php echo $index ?>" value="Hide"
                           onClick="toggleitem(<?php echo $index ?>)">
                </span>
            <b><?php echo $message['title'] ?></b><br/>Posted by: <a
                href="mailto:<?php echo '#' ?>"><?php echo $message['senderName'] ?></a>, <?php echo date('M d, o g:i a', $message['msgDate']) ?>
            <span style="color:red;">New</span>
        </div>
        <div class="blockitems" id="item<?php echo $index ?>"><p><?php echo $message['message'] ?></p></div>
        <?php if ($index == (count($messages) - 1))
        {
        for ($i = $DivCount;$i >= 0;$i--){
        ?>
    </div>
<?php }
} ?>
    <?php
    $currentLevel = $message['level'];
    $messageCount = (count($messages) - 1);
    ?>
    <input type="hidden" id="messageCount" value="<?php echo $messageCount ?>">
    <?php } ?>

</div>
