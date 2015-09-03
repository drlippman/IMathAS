<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Update External Reference';
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

    <?php
    if($body == AppConstant::NUMERIC_ONE)
    {
        echo $message;

    }
    if(isset($params['data']))
    {
        foreach($questions as $question)
        {
            if (!isset($info[$question['uniqueid']])) {continue;}
            if (trim($question['extRef'])!=trim($info[$question['uniqueid']][1]))
            {
                if ($question['extRef']=='')
                {
                    echo "Found new extref.  Adding...<br/>";
                }
                else
                {
                    if ($question['lastmoddate']>$info[$question['uniqueid']][0])
                    {
                         echo 'Local more recent '.$question['id'].': '.$question['extref']. ' vs. '.$info[$question['uniqueid']][1].'.  Skipping.<br/>';
                    }else
                    {
                        echo 'Import more recent '.$question['id'].': '.$question['extref']. ' vs. '.$info[$question['uniqueid']][1].'.   Updating.<br/>';

                    }

                }

            }
        }
    }else{?>
        <b><?php AppUtility::t('Do NOT use this unless you know what you are doing.');?></b>
        <form method="post"><textarea name="data" rows="30" cols="80"></textarea>
        <input type="submit"></form>
        <br>
    <?php }?>
</div>
</div>
