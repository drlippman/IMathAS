<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Replace Video Links';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities']]);?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <br>
    <div class="align-copy-course">

 <?php if( $body == AppConstant::NUMERIC_ONE)
        {
            echo $message;
        }
        if(!empty($params['from']) && ($params['to']))
        {
            if (strlen($from)!=11 || strlen($to)!=11 || preg_match('/[^A-Za-z0-9_\-]/',$from) || preg_match('/[^A-Za-z0-9_\-]/',$to)){ ?>
                <p><?php echo AppUtility::t("Check the video ID formats; they don't appear to be correct")?></p>
                <p><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/replace-video');?>"><?php echo AppUtility::t('Try again')?></p>
            <?php }else{ ?>
                <?php echo AppUtility::t('Inline Texts changed: ');?><?php echo $updatedInlineText;?><br/>
                <?php echo AppUtility::t('Linked texts changed: ');?><?php echo $updatedLinkedText; ?><br/>
                <?php echo AppUtility::t('Lined text summaries changed: ');?><?php echo $updatedLinkedTextSummary;?><br/>
                <?php echo AppUtility::t('Assessment intros changed:');?><?php echo $updatedAssessment;?><br/>
                <?php echo AppUtility::t('Assessment summaries changed:');?><?php echo $updatedAssessmentSummary;?><br/>
                <?php echo AppUtility::t('Question video links changed:');?> <?php echo $updatedQuestionSet;?>
                <p><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities');?>"><?php echo AppUtility::t('Done')?></p>

            <?php }?>
  <?php }else{?>
            <h3><?php echo AppUtility::t('Replace video links');?></h3>
            <p><?php echo AppUtility::t('This will replace video links or question button links anywhere on the system');?></p>
            <form method="post">
            <p><?php echo AppUtility::t('Replace video ID')?>&nbsp;<input class="form-control-utility" type="text" name="from" size="11"/>
            <?php echo AppUtility::t('with video ID ')?>&nbsp;<input class="form-control-utility" type="text" name="to" size="11"/></p>
            <p><input type="submit" value="<?php echo AppUtility::t('Replace');?>"/></p>
            </form>
    <?php }?>

    </div>
</div>