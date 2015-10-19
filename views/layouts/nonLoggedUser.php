
<?php
use yii\helpers\Html;
use app\assets\AppAsset;
use app\components\AppUtility;
use app\components\AppConstant;
/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link href='<?php echo AppUtility::getHomeURL(); ?>css/master.css?<?php echo AppConstant::VERSION_NUMBER ?>' rel='stylesheet' type='text/css'>
    <link href='<?php echo AppUtility::getHomeURL(); ?>css/imascoreResponsive.css?<?php echo AppConstant::VERSION_NUMBER ?>' rel='stylesheet' type='text/css'>
</head>
<body>
<?php $this->beginBody() ?>
<div class="header-content">
    <?php  echo $this->render('_headerNonLoggedUsers'); ?>
</div>
<div class="clear-both"></div>
<div class="master-wrap">

    <div class="master-container">
        <div class="container-upper-blue">
            <div class="graphic-math-img">
            </div>
            <div class="clear-both"></div>
        </div>
        <div class="container-lower-white">
            <div class="master-items-container">
                <div class="page-content">
                    <div id="flash-message">
                        <?php
                        $flashes = Yii::$app->session->getAllFlashes();
                        if (isset($flashes)) {
                            foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
                                echo '<div class="alert alert-' . $key . '">' . $message . "</div>\n";
                            }
                        }
                        ?>
                    </div>
                    <?php echo $content; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
<div class="clear-both"></div>
</body>
<?php echo $this->render('_footer'); ?>
</html>
<?php $this->endPage() ?>
<!--<script>-->
<!--    $(document).ready(function(){-->
<!--        setMinHeightToContainer();-->
<!--    });-->
<!---->
<!--    function setMinHeightToContainer() {-->
<!--        var lowerContainer = $( ".container-lower-white" ).height();-->
<!--        var windowLength = $( window ).height();-->
<!--        var heightMin = lowerContainer<windowLength?windowLength-60:lowerContainer+120;-->
<!--        $(".master-wrap").css('min-height', heightMin+"px");-->
<!--    }-->
<!---->
<!--</script>-->
