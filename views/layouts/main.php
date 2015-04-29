
<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\components\AppUtility;

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
</head>
<body>

<?php $this->beginBody() ?>
    <div class="wrap">
        <?php
        $basePath = '/site/';
            NavBar::begin([
                'brandLabel' => 'IMathAS',
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                    Yii::$app->user->isGuest ?
                    ['label' => 'Home', 'url' => [$basePath.'index']]:
                        ['label' => 'Home', 'url' => [$basePath.'dashboard']],
                    Yii::$app->user->isGuest ?
                    ['label' => 'About', 'url' => [$basePath.'about']]:'',
                    Yii::$app->user->isGuest ?
                        ['label' => 'Login', 'url' => [$basePath.'login']] :
                        ['label' => 'Logout (' . ucfirst(Yii::$app->user->identity->FirstName) .' '.ucfirst(Yii::$app->user->identity->LastName) .')',
                            'url' => ['/site/logout'],
                            'linkOptions' => ['data-method' => 'post']],
                ],
            ]);
            NavBar::end();
        ?>

        <div class="container">
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],

            ]) ?>
            <?php
            $flashes = Yii::$app->session->getAllFlashes();
            if (isset($flashes)) {
                foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
                    echo '<div class="alert alert-' . $key . '">' . $message . "</div>\n";
                }
            }
            ?>

            <?= $content ?>
        </div>
    </div>



<?php $this->endBody() ?>
</body>
<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; IMathAS <?= date('Y') ?></p>
        <p class="pull-right">Powered by <a href="#">IMathAS</a> &copy; 2006-2015 | David Lippman</p>
    </div>
</footer>
</html>
<?php $this->endPage() ?>