<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = AppUtility::t('Search Items',false);
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
        else
        {
            echo '<h4>Search through inline and link text items</h4>';
            echo '<form method="post"><p>Search <input class="form-control-utility" type="text" name="search" size="40" value="'.htmlentities(stripslashes($params['search'])).'"> <input style="border-radius: 2px; height: 32px" type="submit" value="Search"/></p>';
            if(isset($params['search']))
            {
                echo '<p>';
                echo '<input type="submit" style="border-radius: 2px; height: 32px" name="submit" value="Message"></p><p>';
                echo "Count: ".count($searchResult);
                $lastPerson = '';
                if($searchResult)
                {
                    foreach($searchResult as $row)
                    {
                        $thisPerson = $row['LastName'].', '.$row['FirstName'];
                        if ($thisPerson != $lastPerson)
                        {
                            echo '<br/><input type="checkbox" name="checked[]" value="'.$row['id'].'" checked="checked"> '.$thisPerson .' ('.$row['groupname'].')';
                            $lastPerson= $thisPerson;
                        }?>
                        <a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid='.$row['cid'])?>" target="_blank"><?php echo $row['cid']?></a>
              <?php }
                }
                echo '</p>';
            }
            echo '</form>';
        }
        ?>
    </div>
</div>
