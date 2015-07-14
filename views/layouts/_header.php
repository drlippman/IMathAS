<header class="header-wraper">
<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;

$basePath = '/site/';
NavBar::begin([
    'brandLabel' => 'OpenMath',
    'brandUrl' => Yii::$app->homeUrl.'site/login',
    'options' => [
        'class' => 'navbar-inverse navbar-fixed-top',
    ],
]);
echo '<div class="dropdown dropdown-class">
        <img src="../../img/class.png">
        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">My Classes
        <span class="caret"></span></button>

      </div>';
echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Home', 'url' => [$basePath.'login']]:
            ['label' => 'Home', 'url' => [$basePath.'dashboard']],
        Yii::$app->user->isGuest ?
            ['label' => 'Diagnostics', 'url' => [$basePath.'diagnostics']]:'',
        Yii::$app->user->isGuest ?
            ['label' => ''] :
            ['label' => 'Logout (' . ucfirst(Yii::$app->user->identity->FirstName) .' '.ucfirst(Yii::$app->user->identity->LastName) .')',
                'url' => ['/site/logout'],
                'linkOptions' => ['data-method' => 'post']],
    ],
]);
NavBar::end();
?>

</header>