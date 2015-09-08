<?php
use app\components\AppUtility;
?>
<?php $courseId = Yii::$app->session->get('courseId'); ?>
<div class="index-header">
    <div class="left-side left-float">
        <div class="small-link">
            <?php for($i = 0; $i < count($link_title); $i++)
            { if($i == 0)
                  {?>
                    <a href="<?php echo AppUtility::getURLFromHome('message','message/index?cid='.$courseId)?>"><img class="back-arrow" src="../../img/backarrow.png"></a>
            <?php } ?>
                <a href="<?php echo isset($link_url[$i]) ?  $link_url[$i] : ""; ?>"><?php echo isset($link_title[$i]) ?  $link_title[$i] : ""; ?> </a>
                <?php if($i != (count($link_title)-1)){ echo ">>";}?>
            <?php } ?>
        </div>
    </div>
    <div class="clear-both"></div>
</div>

