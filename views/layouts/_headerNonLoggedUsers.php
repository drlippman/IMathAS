<header class="header-wraper">

    <?php
    use yii\bootstrap\Nav;
    use yii\bootstrap\NavBar;

    $basePath = '/site/';
    $imgPath = \app\components\AppUtility::getAssetURL().'img/';
    NavBar::begin([
        'brandLabel' => 'OpenMath',
        'brandUrl' => Yii::$app->homeUrl.'site/login',
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' =>['class' => 'navbar-nav notification navbar-right'],
        'encodeLabels' => false,
        'items' => [
            Yii::$app->user->isGuest ?
                ['label' => 'Diagnostics', 'url' => [$basePath.'diagnostics']]:'',
            Yii::$app->user->isGuest ?
                ['label' => ''] :
                ['label' => Html::encode((ucfirst(Yii::$app->user->identity->FirstName)) .' '. Html::encode(ucfirst(Yii::$app->user->identity->LastName))),
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post'], 'class' => 'user-alignment'],

        ],
    ]);
    NavBar::end();
    ?>
</header>
</div>
<script>
</script>

