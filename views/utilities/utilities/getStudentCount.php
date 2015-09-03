<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Get Student Count';
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
                echo "<p>Active users since 7/10/11</p>";

                echo "<p>Student count: ".$studentCount;

                echo "<p>Active enrollments in $days Days</p>";

                echo "<p>Student count: ".$stuCount;

                echo "<p>Active users in $days Days</p>";

                echo "<p>Student count: ".$queryForStu;

                echo "</p><p>Teacher count: ".$teacherCnt."</p>";

                echo "<p>Active student association (by course owner)</p>";
                $lastGroup = '';
                $grpCnt = AppConstant::NUMERIC_ZERO;
                $grpData = '';
                if($stuName)
                {

                        foreach($stuName as $singleName)
                        {

                            if($singleName['name'] != $lastGroup)
                            {
                                if($lastGroup != '')
                                {
                                    echo "<b>$lastGroup</b>: $grpCnt<br/>";
                                    echo $grpData;
                                }
                                $grpCnt = 0;
                                $grpData = '';
                                $lastGroup = $singleName['name'];
                            }
                            $grpData .= "{$singleName['lastname']}:  {$singleName['id']}<br/>";
                            $grpCnt += $singleName['id'];
                        }
                }
                    echo "<b>$lastGroup</b>: $grpCnt<br/>";
                    echo $grpData;



                    echo "<p>Active students last hour: ";

                    echo $queryByDistinctCnt."</p>";

                    if(isset($email) && $user->rights > AppConstant::GROUP_ADMIN_RIGHT)
                    {
                        echo "<p>";
                        if($userEmail)
                        {
                            foreach($userEmail as $data)
                            {
                                echo $data['email']."; ";
                            }
                        }
                        echo "</p>";
                    }
        }
        ?>

    </div>
</div>
