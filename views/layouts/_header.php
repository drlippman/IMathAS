<header class="header-wraper">
<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use app\models\Student;
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
    'options' =>['class' => 'navbar-nav user-menu navbar-right'],
    'encodeLabels' => false,
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Diagnostics', 'url' => [$basePath.'diagnostics']]:'',
        Yii::$app->user->isGuest ?
            ['label' => ''] :
            ['label' => '<img class="small-icon" src="../../img/user.png">&nbsp;'.(ucfirst(Yii::$app->user->identity->FirstName) .' '.ucfirst(Yii::$app->user->identity->LastName)),
                'items' =>
                    [
                        ['label' => 'Account Setting',
                            array('class' => 'dropdown-submenu'),
                            'items' =>
                                [
                                    ['label' => 'Change UserInfo', 'url' => ['/site/change-user-info']],
                                ],
                        ],
                        ['label' => 'Help', 'url' => '#'],
                        ['label' => 'Logout', 'url' => ['/site/logout'],'linkOptions' => ['data-method' => 'post'], 'class' => 'user-alignment'],
                     ],
                'url' => ['#'],
                'linkOptions' => [''], 'class' => 'user-alignment'
            ],


    ],
]);


echo Nav::widget([
    'options' =>['class' => 'navbar-nav notification navbar-right'],
    'encodeLabels' => false,
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Notifications ', 'url' => [$basePath.'login'], 'options' => ['class' => 'notification-alignment',
]]:
            ($totalCount >0 ?['label' =>'<img class="small-icon" src="../../img/notifctn.png">&nbsp;Notifications&nbsp;'.'<div class="circle"><div class="notification msg-count">'.$totalCount.'</div></div>',
                'items' =>
                [
                    ($messageCount>0 ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => '../../message/message/index?newmsg=1&cid='.$courseId] : ['label' => 'Message', 'url' => '../../message/message/index?cid='.$courseId]),
                    '<li class="divider"></li>',
                    ($postCount>0 ? ['label' => 'Forum'.'('.$postCount.')', 'url' => '../../forum/forum/new-post?cid='.$courseId] :['label' => 'Forum', 'url' => '../../forum/forum/search-forum?cid='.$courseId]),
                ],
                'url' => '#', 'options' => ['class' => 'notification-alignment']] :

                ['label' =>'<img class="small-icon" src="../../img/notifctn.png">&nbsp;Notifications',
                'items' =>
                    [
                        ($messageCount>0 ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => '../../message/message/index?cid='.$courseId] : ['label' => 'Message', 'url' => '../../message/message/index?cid='.$courseId]),
                        '<li class="divider"></li>',
                        ($postCount>0 ? ['label' => 'Forum'.'('.$postCount.')', 'url' => '../../forum/forum/search-forum?cid='.$courseId] :['label' => 'Forum', 'url' => '../../forum/forum/search-forum?cid='.$courseId]),
                    ],
                'url' => '#', 'options' => ['class' => 'notification-alignment']] ),

         ],
]);

if($user->rights == \app\components\AppConstant::ADMIN_RIGHT){
echo Nav::widget([
    'options' =>['class' => 'navbar-nav myclasses margin-left'],
    'encodeLabels' => false,

    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'My Classes', 'url' => [$basePath.'login'], 'options' => ['class' => '',]]:
            ['label' =>'<img class="small-icon" src="../../img/myClass.png">&nbsp;&nbsp;&nbsp;My Classes&nbsp;',

                'items' => \app\models\Course::getGetMyClasses($user->id),
                'url' => [$basePath.'dashboard'], 'options' => ['class' => '']]
            ],
]);
}elseif($user->rights == \app\components\AppConstant::STUDENT_RIGHT)
{
    echo Nav::widget([
        'options' =>['class' => 'navbar-nav myclasses margin-left'],
        'encodeLabels' => false,

        'items' => [
            Yii::$app->user->isGuest ?
                ['label' => 'My Classes', 'url' => [$basePath.'login'], 'options' => ['class' => '',]]:
                ['label' =>'<img class="small-icon" src="../../img/myClass.png">&nbsp;&nbsp;&nbsp;My Classes&nbsp;',
                'items' =>\app\models\Student::getMyClassesForStudent($user->id),
                 'url' => [$basePath.'dashboard'], 'options' => ['class' => '']]
        ],
    ]);

}
NavBar::end();
?>
</header>
</div>
<script>
</script>

