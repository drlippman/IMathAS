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
<?php
global $newMsgCnt, $newPostCnt, $data;
global $page_newmessagelist,$page_newpostlist;
$courseId = Yii::$app->session->get('courseId');
$actionPath = Yii::$app->controller->action->id;

$messageCount = Yii::$app->session->get('messageCount');
$postCount = Yii::$app->session->get('postCount');
$totalCount = $messageCount + $postCount;

if($actionPath == 'dashboard')
{
    $messageCount = count($page_newmessagelist);
    $postCount = count($page_newpostlist);
    $totalCount =  $messageCount + $postCount;
}

// $user = Yii::$app->session->get('user');
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
                                Yii::$app->session->removeFlash($key);
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
<input type="hidden" class="base-path" value="<?php echo AppUtility::getBasePath();?>">
<?php $this->endBody() ?>
<div class="clear-both"></div>
</body>
<?php echo $this->render('_footer'); ?>
</html>
<?php $this->endPage() ?>
<script>
    jQuery(document).ready(function() {
    
    setMinHeightToContainer();
    
});


function setMinHeightToContainer() {
    
    var lowerContainer = jQuery(".container-lower-white").height();

    var windowLength = jQuery(window).height();

    var heightMin = lowerContainer < windowLength ? windowLength - 60 : lowerContainer + 120;
    jQuery(".master-wrap").css('min-height', heightMin+"px");
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