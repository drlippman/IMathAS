<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'List Wrong Lib Flag';
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

        }else
        {
            if($data)
            {
                foreach($data as $row)
                {
                    echo implode('@',$row).'<br/>';
                }
            }else
            {
                echo AppUtility::t('No Wrong Library Flag Available');
            }
        }?>

    </div>
</div>
