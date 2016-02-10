<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t(' Send New Message',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Message',false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id,AppUtility::getHomeURL() . 'message/message/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo AppUtility::t('Message:',false);?><?php echo $this->title ?></div>
        </div>

    </div>
</div>
<div class="item-detail-content">
    <?php if($userRights->rights == 100 || $userRights->rights == 20) {
        echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course]);
    } elseif($userRights->rights == 10){
        echo $this->render("../../course/course/_toolbarStudent", ['course' => $course]);
    }?>
</div>
<input type="hidden" class="send-msg" value="<?php echo $course->id ?>">
<input type="hidden" class="send-userId" value="<?php echo $course->ownerid ?>">
<div class="tab-content shadowBox col-md-12 col-sm-12 padding-left-zero padding-right-zero">
    <?php $form = ActiveForm::begin([
        'id' => 'login-form',
        'options' => [],
        'action' => '',
        'fieldConfig' => [
            'template' => "",
            'labelOptions' => [],
        ],
    ]); ?>
    <div class="inner-reply-msg-content col-md-12 col-sm-12 padding-left-zero padding-right-zero padding-top-bottom-one-pt-five-em">
            <div class="drop-down col-md-12 col-sm-12 padding-top">
                <span class="col-md-1 col-sm-1"><?php echo AppUtility::t('To')?></span>
                <?php if($newTo)
                {?>
                        <input type="hidden" id="newTo" value="1">
                        <input type="hidden" id="newUserId" value="<?php echo $loginid?>">
                        <span class="col-md-4 col-sm-4"><strong><?php echo ucfirst($username->FirstName) . " " . ucfirst($username->LastName);?></strong>
            <?php }else
                    {?>
                    <span class="col-md-4 col-sm-4">
                    <select name="seluid" class="dropdown form-control" id="seluid" onchange="changeSubject()">
                        <option value="0">Select Recipient</option>
                        <?php foreach ($users as $user) { ?>
                        <option value="<?php echo $user['id'] ?>">
                            <?php echo ucfirst($user['LastName']).", ".ucfirst($user['FirstName']); ?>
                            </option><?php } ?>
                    </select>
                        <span id="receiver" class="error-message col-md-10 col-sm-10 padding-left-zero"></span>
                        <?php }?>
                </span>
            </div>

            <div class="col-md-12 col-sm-12 padding-top">
                <span class="col-md-1 col-sm-1"><?php echo AppUtility::t('Subject')?></span>
                <span class="col-md-11 col-sm-11 padding-right-thirty" >
                    <input  type="text" size="50" id="subject" class="textbox subject form-control" onchange="changeSubject()" required  maxlength="60"/>
                </span>
                <span id="subject-error" class="error-message subject-error-message col-md-10 col-sm-10 col-md-offset-1 col-sm-offset-1"></span>
            </div>

            <div class="col-md-12 col-sm-12 padding-top">
                <span class="col-md-1 col-sm-1"><?php echo AppUtility::t('Message')?></span>
                <?php echo "<span class='left col-md-11 col-sm-11'>
                    <div class='editor col-md-12 col-sm-12 padding-left-zero send-message-textarea'>
                <textarea id='message' name='message' rows='12' cols='15'>";
                echo "</textarea>
                </div></span><br>"; ?>
            </div>
            <div class="header-btn hide-hover col-md-6 col-sm-6 col-sm-offset-1 padding-left-twenty-eight padding-top-twenty">
                <div id="message-send-btn" class="btn btn-primary1 btn-color white-color"><img class = "small-icon" src="<?php echo AppUtility::getAssetURL()?>img/newzmessg.png">&nbsp;Send Message</div>
            </div>
    </div>
</div>