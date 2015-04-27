<?php
use yii\helpers\Html;
//echo $this->render('_toolbar');
//\app\components\AppUtility::dump($assessment);
?>
<link rel="stylesheet" href="../../../web/css/_leftSide.css"/>
<link rel="stylesheet" href="../../../web/css/assessment.css"/>

<?php echo $this->render('_toolbar');?>

<div class="needed">
<?php echo $this->render('_leftSide');?>
</div>

<div class="centercontent" >
<div class="margin-top">
    <div class="inactivewrapper " onmouseover="this.className='activewrapper' "onmouseout="this.className='inactivewrapper'">
        <div class=item>
            <div class=icon style="background-color: #1f0;">?</div>
                <div class=title>
                    <b><a href="<?php echo Yii::$app->homeUrl.'course/course/index?cid='.$assessment->courseid?>"><?php echo $assessment->name ?></a></b>
                    <BR><?php echo \app\components\AppUtility::formatDate($assessment->enddate); ?>
                </div>
            <div class=itemsum>
                <p><?php echo $assessment->summary ?></p>
            </div>
        </div>
    </div>
</div>
