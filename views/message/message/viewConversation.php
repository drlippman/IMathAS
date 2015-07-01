<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Message Conversation';
if ($userRights->rights > AppConstant::STUDENT_RIGHT) {

    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/instructor/instructor/index?cid=' . $course->id]];
} else {
    $this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/index?cid=' . $course->id]];
}
$this->params['breadcrumbs'][] = ['label' => 'Messages', 'url' => ['/message/message/index?cid=' . $course->id]];
$this->params['breadcrumbs'][] = $this->title;
$currentLevel = AppConstant::ZERO_VALUE;
?>
<?php
AppUtility::includeCSS('forums.css');
AppUtility::includeCSS('imascore.css');
AppUtility::includeCSS('default.css');
AppUtility::includeCSS('handheld.css');
AppUtility::includeJS('general.js'); ?>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<?php AppUtility::includeJS('ASCIIsvg_min.js');?>
<div class=mainbody>
    <div class="headerwrapper">
        <div id="navlistcont">
            <ul id="navlist"></ul>
            <div class="clear"></div>
        </div>
    </div>

    <div class="midwrapper">
        <?php $sent = $messageId;
        if ($sent != AppConstant::NUMERIC_ONE) {
            ?>
            <p>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message=' . $sent . '&id=' . $messages[0]['id'] . '&cid=' . $course->id); ?>">Back
                    to Message</a></p>
        <?php } ?>
        <?php $sent = $messageId;
        if ($sent == AppConstant::NUMERIC_ONE) {
            ?>
            <p>
                <a href="<?php echo AppUtility::getURLFromHome('message', 'message/view-message?message=' . $sent . '&id=' . $messages[0]['id'] . '&cid=' . $course->id); ?>">Back
                    to Message</a></p>
        <?php } ?>
        <button onclick="expandall()" class="btn btn-primary">Expand All</button>
        <button onclick="collapseall()" class="btn btn-primary">Collapse All</button>
        <button onclick="showall()" class="btn btn-primary">Show All</button>
        <button onclick="hideall()" class="btn btn-primary">Hide All</button>
        <br><br>
        <?php
        $DivCount = AppConstant::ZERO_VALUE;
        foreach ($messages as $index => $message){
        ?>
        <?php
        if ($message['level'] != AppConstant::ZERO_VALUE && $message['level'] < $currentLevel)
        {
        $DivCount--;
        for ($i = $currentLevel;$message['level'] < $i;$i--){
        ?>
    </div>
    <?php
    }
    ?>
    <?php } ?>
    <?php if ($message['level'] != AppConstant::ZERO_VALUE && $message['level'] > $currentLevel)
    {
    $DivCount++;?>
    <div class="forumgrp" id="block<?php echo $index - AppConstant::NUMERIC_ONE ?>">

        <?php } ?>
        <div class=block><span class="leftbtns"><img class="pointer" id="butb<?php echo $index ?>"
                                                     src="<?php echo AppUtility::getHomeURL() ?>img/collapse.gif"
                                                     onClick="toggleshow(<?php echo $index ?>)"/> </span>
                <span class=right>

                 <?php if ($user['id'] != $message['senderId']) { ?>
                     <a href="<?php echo AppUtility::getURLFromHome('message', 'message/reply-message?id=' . $message['id'] . '&cid=' . $course->id); ?>">
                         Reply</a>
                 <?php } ?>
                    <input type=button class="btn btn-primary" id="buti<?php echo $index ?>" value="Hide"
                           onClick="toggleitem(<?php echo $index ?>)">
                </span>
            <b><?php echo $message['title'] ?></b><br/>Posted by: <a
                href="mailto:<?php echo '#' ?>"><?php echo $message['senderName'] ?></a>, <?php echo date('M d, o g:i a', $message['msgDate']) ?>
            <span style="color:red;">New</span>
        </div>
        <div class="blockitems" id="item<?php echo $index ?>"><p><?php
                if (($p = strpos($message['message'],'<hr'))!==false) {
                    $message['message'] = substr($message['message'],0,$p).'<a href="#" class="small" onclick="showtrimmedcontent(this,\''.$message['id'].'\');return false;">[Show trimmed content]</a><div id="trimmed'.$message['id'].'" style="display:none;">'.substr($message['message'],$p).'</div>';
                }
                echo $message['message'] ?></p></div>
        <?php if ($index == (count($messages) - AppConstant::NUMERIC_ONE))
        {
        for ($i = $DivCount;$i >= AppConstant::ZERO_VALUE;$i--){
        ?>
    </div>
<?php
}
} ?>
    <?php
    $currentLevel = $message['level'];
    $messageCount = (count($messages) - AppConstant::NUMERIC_ONE);
    ?>
    <input type="hidden" id="messageCount" value="<?php echo $messageCount ?>">
    <?php } ?>
</div>
