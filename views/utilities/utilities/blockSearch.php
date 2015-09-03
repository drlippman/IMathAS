<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Search Block Titles';
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
        <form method="post">
            <p>Search:<input type="text" name="search" size="40" value="<?php htmlentities(stripslashes($params['search']))?>">
             <input type="submit" value="Search"/></p>
        <?php
        if($body == AppConstant::NUMERIC_ONE)
        {
            echo $message;

        }
        if (isset($params['search']))
        {
            echo '<p>';

            foreach($blockTitles as $singleBlock)
            {
                if (count($det)>0){ ?>
<!--                    <a target="_blank" href="#">--><?php //echo $det[1]?><!--</a>&nbsp;--><?php //AppUtility::t('in');?><!--&nbsp;--><?php //echo $singleBlock['name']?><!--<br/>-->
                    <?php echo '<a target="_blank" href="#">'.$det[1].'</a> in'.$singleBlock['name'].'<br/>';?>
                <?php }
            }
            echo '</p>';
        }
        ?>

    </div>
</div>
