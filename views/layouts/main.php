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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link href='<?php echo AppUtility::getHomeURL(); ?>css/master.css?<?php echo AppConstant::VERSION_NUMBER ?>' rel='stylesheet' type='text/css'>
</head>
<?php $courseId = Yii::$app->session->get('courseId'); ?>
<?php $messageCount = Yii::$app->session->get('messageCount');
$postCount = Yii::$app->session->get('postCount');
$totalCount = $messageCount + $postCount;
$user = Yii::$app->session->get('user');
?>
<body>
<?php $this->beginBody() ?>
<div class="header-content">
    <?php
    echo $this->render('_header',['courseId' =>$courseId,'messageCount' => $messageCount,'totalCount' => $totalCount,'postCount' => $postCount,'user' => $user]); ?>
</div>
<div class="clear-both"></div>
<div class="master-wrap">
    <div class="master-container">
        <div class="container-upper-blue" style="background-color:#ffffff">
            <div class="graphic-math-img1">
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
<input type="hidden" class="home-path" value="<?php echo AppUtility::getHomeURL();?>">
<?php $this->endBody() ?>
<div class="clear-both"></div>
</body>

</html>
<?php $this->endPage() ?>
<script>
    $(document).ready(function() {

        setMinHeightToContainer();

    });


    function setMinHeightToContainer() {

        var lowerContainer = $(".container-lower-white").height();

        var windowLength = $(window).height();

        var heightMin = lowerContainer < windowLength ? windowLength - 60 : lowerContainer + 120;
//        $(".master-wrap").css('min-height', heightMin+"px");
    }
</script>
<script type="javascript">
    noMathRender = false;
    var usingASCIIMath = true;
    var AMnoMathML = true;
    var MathJaxCompatible = true;
    function rendermathnode(node) {
        MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]);
    }
</script>