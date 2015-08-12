<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t(' Send New Message',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Message:',false);?><?php echo $this->title ?></div>
        </div>
        <div class="pull-left header-btn hide-hover">
            <a href="#"id="mess" class="btn btn-primary1 pull-right  btn-color"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/newzmessg.png">&nbsp;Send Message</a>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
<input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
<div class="tab-content shadowBox">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => [],
        'action' => '',
        'fieldConfig' => [
            'template' => "",
            'labelOptions' => [],
        ],
    ]); ?>
    <div class="inner-reply-msg-content">
        <div class="drop-down col-sm-12 padding-top">
            <span class="col-sm-1"><?php echo AppUtility::t('To')?></span>
            <?php if($newTo)
            {?>
                    <input type="hidden" id="newTo" value="1">
                    <input type="hidden" id="newUserId" value="<?php echo $loginid?>">
                    <span class="col-sm-4"><strong><?php echo ucfirst($username->FirstName) . " " . ucfirst($username->LastName);?></strong>
        <?php }else
                {?>
                <span class="col-sm-4">
                <select name="seluid" class="dropdown form-control" id="seluid">
                    <option value="0">Select a recipient</option>
                    <?php foreach ($users as $user) { ?>
                    <option value="<?php echo $user['id'] ?>">
                        <?php echo ucfirst($user['LastName']).", ".ucfirst($user['FirstName']); ?>
                        </option><?php } ?>
                </select>
                    <?php }?>
            </span>
        </div>

        <div class="col-sm-12 padding-top">
            <span class="col-sm-1"><?php echo AppUtility::t('Subject')?></span>
            <span class="col-sm-4"><?php echo '<input class="textbox subject form-control" type="text" maxlength="100" >'; ?></span>
        </div>

        <div class="col-sm-12 padding-top">
            <span class="col-sm-1"><?php echo AppUtility::t('Message')?></span>
            <?php echo "<span class='left col-sm-11'><div class= 'editor'>
        <textarea id='message' name='message' style='width: 70%;' rows='12' cols='15'>";
            echo "</textarea></div></span><br>"; ?>
        </div>

    </div>


</div>