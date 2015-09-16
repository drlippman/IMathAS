<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Get Student Detail Count';
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
    <div class="align-copy-course back-ground-display">
    <h2><?php echo AppUtility::t('Enrollments from ');?><?php echo date('M j, Y',$start)?> to <?php echo date('M j, Y',$end)?></h2>
    <p><?php echo AppUtility::t('This will contain list all students who last accessed the course between those dates.');?></p>
    <p><?php echo AppUtility::t('Courses marked with');?><sup>*</sup><?php echo AppUtility::t(' have more than one instructor, and the enrollments have already been counted earlier so will be omitted.');?></p>
    <?php
    if($body == AppConstant::NUMERIC_ONE)
    {
        echo $message;

    }else
    {
            $lastGroup = '';  $grpCnt = 0; $grpData = '';  $lastUser = ''; $userData = '';
            $seenCid = array();
            foreach($query as $row)
            {
                if ($row['LastName'].', '.$row['FirstName']!=$lastUser) {
                    if ($lastUser != '') {
                        $grpData .= '<li><b>'.$lastUser.'</b><ul>';
                        $grpData .= $userData;
                        $grpData .= '</ul></li>';
                    }
                    $userData = '';
                    $lastUser = $row['LastName'].', '.$row['FirstName'];
                }
                if ($row[0] != $lastGroup) {
                    if ($lastGroup != '') {
                        echo "<p><b>$lastGroup</b>: $grpCnt";
                        echo '<ul>'.$grpData.'</ul></p>';
                    }
                    $grpCnt = 0;  $grpData = '';
                    $lastGroup = $row['name'];
                }
                $userData .= "<li>".$row['cname'].' ('.$row['id'].'): <b>'.$row['COUNT(DISTINCT s.id)'].'</b>';
                if (!in_array($row['id'],$seenCid)) {
                    $grpCnt += $row['COUNT(DISTINCT s.id)'];
                    $seenCid[] = $row['id'];
                } else {
                    $userData .= "<sup>*</sup>";
                }
                $userData .= "</li>";

            }
            $grpData .= '<li><b>'.$lastUser.'</b><ul>';
            $grpData .= $userData;
            $grpData .= '</ul></li>';
            $userData = '';
            $lastUser = $row['LastName'].', '.$row['FirstName'];
            echo "<p><b>$lastGroup</b>: $grpCnt";
            echo '<ul>'.$grpData.'</ul></p>';
    }
    ?>
</div></div>
<style type="text/css">
    ul {
        list-style-type: none;
    }
</style>