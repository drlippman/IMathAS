<?php

use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
$errorCode = $exception->getCode();
?>
<div class="padding-one-em tab-content shadowBox"">
<!--<div class=" text-gray-background">-->
<div class="site-error">

<!--    <div class="col-sm-12 col-md-12">-->
        <div class="col-md-6 col-md-offset-3 col-sm-7 col-sm-offset-3 center">
            <img src="<?php echo AppUtility::getHomeURL().'Uploads/oops.jpg'?>" class="width-thirty-per" >
        </div>
<!--    </div>-->
    <div class="col-md-offset-3 col-md-6 col-sm-offset-3 col-sm-7 center">
        <?php if($errorCode == 2){ ?>
                <p>
                    Page not found!!!
                </p>
        <?php }else if($errorCode == 1){?>
                <p>
                    An internal server error occurred.
                </p>zzz
        <?php }?>
        <p>
            Please contact us if you think this is a server error. Thank you.
        </p>
    </div>
<!--</div>-->

</div>
</div>