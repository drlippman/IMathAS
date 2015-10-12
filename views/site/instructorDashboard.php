<?php
use app\components\AppUtility;
?>
    <title></title>
    <meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge"/>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <meta name="viewport">

<div class=mainbody>
    <div class="headerwrapper"></div>
    <div class="midwrapper">
        <?php echo $this->render('_fullMenu'); ?>
        <div class="pagetitle" id="headerhome"><h2>Welcome to
                IMathAS, <?php echo AppUtility::getFullName($user->FirstName, $user->LastName); ?></h2></div>
        <div id="homefullwidth">
            <?php echo $this->render('_courseTeaching'); ?>
            <?php echo $this->render('_courseTutoring');?>
            <?php echo $this->render('_courseTaking'); ?>
        </div>
        <div class="clear"></div>
    </div>
    <div class="footerwrapper"></div>
</div>

