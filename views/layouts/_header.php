<header class="header-wraper">
<?php
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use app\models\Student;
use app\models\Course;
use \app\components\AppConstant;
use app\components\AppUtility;
//$CID111 = AppUtility::getDataFromSession('courseId');
$basePath = '/site/';
$imgPath = AppUtility::getAssetURL().'img/';
NavBar::begin([
    'brandLabel' => 'OpenMath',
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar-inverse navbar-fixed-top',
    ],
]);
/*
 * User Setting Drop-Down
 */
echo Nav::widget([
    'options' =>['class' => 'navbar-nav user-menu navbar-right'],
    'encodeLabels' => false,
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Diagnostics', 'url' => [$basePath.'diagnostics']]:'',
        Yii::$app->user->isGuest ?
            ['label' => ''] :
            ['label' => '<img class="small-icon" src="'.AppUtility::getAssetURL().'img/user.png">&nbsp;'.(ucfirst(Yii::$app->user->identity->FirstName) .' '.ucfirst(Yii::$app->user->identity->LastName)),
                'items' =>
                    [
                        ['label' => 'Account Setting','url' => ['/site/form?action=chguserinfo']],
                        ['label' => 'Help', 'url' => '#'],
                        ['label' => 'Logout', 'url' => ['/site/logout'],'linkOptions' => ['data-method' => 'post'], 'class' => 'user-alignment'],
                     ],
                'url' => ['#'],
                'linkOptions' => [''], 'class' => 'user-alignment'
            ],


    ],
]);

/*Notification Drop-Down*/
echo Nav::widget([
    'options' =>['class' => 'navbar-nav notification navbar-right'],
    'encodeLabels' => false,
    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'Notifications ', 'url' => [$basePath.'login'], 'options' => ['class' => 'notification-alignment',
]]:
            ($totalCount > AppConstant::NUMERIC_ZERO ?['label' =>'<img class="small-icon" src="'.AppUtility::getAssetURL().'img/notifctn.png">&nbsp;Notifications&nbsp;'.'<div class="circle"><div class="notification msg-count">'.$totalCount.'</div></div>',
                'items' =>
                [
                    ($messageCount> AppConstant::NUMERIC_ZERO ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => AppUtility::getHomeURL().'message/message/index?newmsg=1&cid='.$courseId] : ['label' => 'Message', 'url' => AppUtility::getHomeURL().'message/message/index?cid='.$courseId]),
                    '<li class="divider"></li>',
                    ($postCount> AppConstant::NUMERIC_ZERO ? ['label' => 'Forum'.'('.$postCount.')', 'url' => AppUtility::getHomeURL().'forum/forum/new-post?cid='.$courseId] :['label' => 'Forum', 'url' => AppUtility::getHomeURL().'forum/forum/search-forum?cid='.$courseId]),
                ],
                'url' => '#', 'options' => ['class' => 'notification-alignment']] :

                ['label' =>'<img class="small-icon" src="'.AppUtility::getAssetURL().'img/notifctn.png">&nbsp;Notifications',
                'items' =>
                    [
                        ($messageCount> AppConstant::NUMERIC_ZERO ? ['label' => 'Message'.'('.$messageCount.')' , 'url' => AppUtility::getHomeURL().'message/message/index?cid='.$courseId] : ['label' => 'Message', 'url' => AppUtility::getHomeURL().'message/message/index?cid='.$courseId]),
                        '<li class="divider"></li>',
                        ($postCount> AppConstant::NUMERIC_ZERO ? ['label' => 'Forum'.'('.$postCount.')', 'url' => AppUtility::getHomeURL().'forum/forum/search-forum?cid='.$courseId] :['label' => 'Forum', 'url' => AppUtility::getHomeURL().'forum/forum/search-forum?cid='.$courseId]),
                    ],
                'url' => '#', 'options' => ['class' => 'notification-alignment']] ),

         ],
]);

/*
 * My Classes Drop-Down For Teacher
 */
if($user['rights'] >= AppConstant::TEACHER_RIGHT)
{
echo Nav::widget([
    'options' =>['class' => 'navbar-nav myclasses margin-left'],
    'encodeLabels' => false,

    'items' => [
        Yii::$app->user->isGuest ?
            ['label' => 'My Classes', 'url' => [$basePath.'login'], 'options' => ['class' => '',]]:
            ['label' =>'<b class="my-Classes"  <img class="small-icon" src="'.AppUtility::getAssetURL().'img/myClass.png">&nbsp;&nbsp;&nbsp;My Classes&nbsp;</b>',

                'items' => Course::getGetMyClasses($user->id),
                'url' => [$basePath.'dashboard'], 'options' => ['class' => '']]
            ],
]);
}
/*
 * My Classes Drop-Down For Student
 */
elseif($user['rights'] == AppConstant::STUDENT_RIGHT)
{
    echo Nav::widget([
        'options' =>['class' => 'navbar-nav myclasses margin-left'],
        'encodeLabels' => false,

        'items' => [
            Yii::$app->user->isGuest ?
                ['label' => 'My Classes', 'url' => [$basePath.'login'], 'options' => ['class' => '',]]:
                ['label' =>'<img class="small-icon" src="'.AppUtility::getAssetURL().'img/myClass.png">&nbsp;&nbsp;&nbsp;My Classes&nbsp;',
                'items' =>Student::getMyClassesForStudent($user->id),
                 'url' => [$basePath.'dashboard'], 'options' => ['class' => '']]
        ],
    ]);

}
NavBar::end();
?>
</header>
</div>

