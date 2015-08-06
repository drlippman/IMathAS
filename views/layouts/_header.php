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
        <button class="btn btn-primary dropdown-toggle" type="submit" data-toggle="dropdown">My Classes
        <span class="caret"></span></button>
      </div>';
echo Nav::widget([
    'options' =>['class' => 'navbar-nav notification navbar-right'],
    'encodeLabels' => false,
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Notifications ', 'url' => [$basePath.'login'], 'options' => ['class' => 'notification-alignment',
]]:
            ($totalCount >0 ?['label' =>'Notifications'.'('.$totalCount.')',
                'items' =>
                [
                    ($messageCount>0 ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => '../../message/message/index?cid='.$courseId] : ['label' => 'Message', 'url' => '../../message/message/index?cid='.$courseId]),
                    '<li class="divider"></li>',
                    ($postCount>0 ? ['label' => 'Forum'.'('.$postCount.')', 'url' => '../../forum/forum/search-forum?cid='.$courseId] :['label' => 'Forum', 'url' => '../../forum/forum/search-forum?cid='.$courseId]),
                ],
                'url' => [$basePath.'dashboard'], 'options' => ['class' => 'notification-alignment']] :

                ['label' =>'Notifications',
                'items' =>
                    [
                        ($messageCount>0 ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => '../../message/message/index?cid='.$courseId] : ['label' => 'Message', 'url' => '../../message/message/index?cid='.$courseId]),
                        '<li class="divider"></li>',
                        ($postCount>0 ? ['label' => 'Forum'.'('.$postCount.')', 'url' => '../../forum/forum/search-forum?cid='.$courseId] :['label' => 'Forum', 'url' => '../../forum/forum/search-forum?cid='.$courseId]),
                    ],
                'url' => [$basePath.'dashboard'], 'options' => ['class' => 'notification-alignment']] ),

        Yii::$app->user->isGuest ?
            ['label' => 'Diagnostics', 'url' => [$basePath.'diagnostics']]:'',
        Yii::$app->user->isGuest ?
            ['label' => ''] :
            ['label' => (ucfirst(Yii::$app->user->identity->FirstName) .' '.ucfirst(Yii::$app->user->identity->LastName)),
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

