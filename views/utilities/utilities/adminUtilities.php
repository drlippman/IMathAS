<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
if(!$form){
    $this->title = AppUtility::t('Admin Utilities',false);
}else
{
    if($form == 'rescue')
    {
        $this->title = AppUtility::t('Recovered Items',false);
    }
    elseif($form == 'emu')
    {
        $this->title = AppUtility::t('Emulated Users',false);
    }
    elseif($form == 'lookup')
    {
        $this->title = AppUtility::t('User Lookup',false);
    }
}

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php if(!$form){?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index']]); ?>
    <?php }else{?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities']]);?>
    <?php }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<div class="tab-content shadowBox">
    <br>
    <div class="align-copy-course">
        <?php
        if($body == AppConstant::NUMERIC_ONE)
        {
            echo $message;
        }
        if(isset($form))
        {
        if($form == 'emu'){?>
        <form method="post" action="<?php echo AppUtility::getURLFromHome('admin','admin/actions?action=emulateuser');?>">
            <?php echo AppUtility::t('Emulate user with userid', false)?><input style="margin-left: 5px" class="form-control-utility" type="text" size="30" name="uid"/>
            <input type="submit" style="width: 60px; height: 32px; border-radius: 2px;margin-left: 5px" value="Go"/>
            <?php }elseif($form == 'rescue'){?>
            <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/rescue-course')?>">
                <?php echo AppUtility::t('Recover lost items in course ID', false)?><input style="margin-left: 5px" class="form-control-utility" type="text" size="30" name="cid"/>
                <input type="submit" style="width: 60px; height: 32px; border-radius: 2px;margin-left: 5px" value="Go"/>
                <?php }elseif($form == 'lookup'){?>
                <?php if(!empty($params['LastName']) || !empty($params['FirstName']) || !empty($params['SID']) || !empty($params['email']))
                {
                    if(!$queryForUser)
                    {
                        echo '<h4>No results found</h4>';
                    }else
                    {
                        foreach($queryForUser as $user)
                        {

                            echo '<p><b>'.$user['LastName'].', '.$user['FirstName'].'</b></p>';
                            echo '<form method="post" action="../../admin/admin/actions?action=resetpwd&id='.$user['id'].'">';
                            echo '<ul><li>Username: <a href="../../admin/admin/index?showcourses='.$user['id'].'">'.$user['SID'].'</a></li>';
                            echo '<li>ID: '.$user['id'].'</li>';
                            if ($user['name']!=null)
                            {
                                echo '<li>Group: '.$user['name'].'</li>';
                            }
                            echo '<li>Email: '.$user['email'].'</li>';
                            echo '<li>Last Login: '.AppUtility::tzdate("n/j/y g:ia", $user['lastaccess']).'</li>';
                            echo '<li>Rights: '.$user['rights'].'</li>';
                            echo '<li>Reset Password to <input type="text" class="form-control-1" name="newpw"/> <input class="form-control-utility" type="submit" style="width: 60px; height: 32px; border-radius: 2px; margin-left: 5px" value="Go"/></li>';?>
                            <?php
                            if($countOfCourseData > 0)
                            {
                                echo '<li>Enrolled as student in <ul>';

                                foreach($queryForCourse as $key=>$data)
                                {
                                    if($key == $user['id'])
                                    {
                                        foreach($data as $d)
                                        { ?>
                                            <li><a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid=' .$d['id'])?>" target="_blank" ><?php echo $d['name'].'(ID '.$d['id'].')' ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a> </li>
                                      <?php  }
                                    }
                                }
                                echo '</ul></li>';
                            }
                            if($countTutorData > 0)
                            {
                                echo '<li>Tutor in <ul>';
                                foreach($queryFromCourseForTutor as $key=>$singleTutor)
                                {
                                    if($key == $user['id'] && $singleTutor)
                                    {
                                        foreach($singleTutor as $tutor)
                                        { ?>
                                            <li><a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid=' .$tutor['id'])?>" target="_blank" ><?php echo $tutor['name'].' (ID '.$tutor['id'].')' ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a> </li>
                                        <?php }
                                    }
                                }
                                echo '</ul></li>';
                            }

                            if($countOfTeacher > 0)
                            {
                                echo '<li>Teacher in <ul>';
                                foreach($queryFromCourseForTeacher as $key=>$singleTeacher)
                                {
                                    if($key == $user['id'])
                                    {
                                        foreach($singleTeacher as $teacher)
                                        { ?>
                                            <li><a href="<?php echo AppUtility::getURLFromHome('course','course/course?cid=' .$teacher['id'])?>" target="_blank" ><?php echo $teacher['name'].'(ID '.$teacher['id'].')' ?>&nbsp;<img src="<?php echo AppUtility::getHomeURL() ?>img/extlink.png"/></a> </li>
                                      <?php  }
                                    }
                                }
                                echo '</ul></li>';
                            }
                            if($countOfLti > 0)
                            {
                                echo '<li>LTI connections <ul>';
                                foreach($queryForLtiUser as $key=>$singleLtiUser)
                                {
                                    if($key == $user['id'] && $singleLtiUser)
                                    {
                                        foreach($singleLtiUser as $user)
                                        {
                                            echo '<li>'.$user['org'].' <a href="utils?removelti='.$user['id'].'">Remove connection</a></li>';
                                        }
                                    }
                                }
                                echo '</ul></li>';
                            }
                            echo '</ul>';
                            echo '</form>';
                        }
                    }
            }else{?>
                <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=lookup');?>">
                    <?php echo AppUtility::t('Look up user:', false)?>
                    <p></p><div class="align-lookup">
                     <input class="form-control-utility" type="text" size="30" placeholder="LastName" name="LastName"></div>
                    <div class="align-lookup">
                     <input class="form-control-utility" type="text" size="30" placeholder="FirstName" name="FirstName"></div>
                    <div class="align-lookup">
                        <input class="form-control-utility" type="text" size="30" placeholder="UserName" name="SID"></div>
                    <div class="align-lookup">
                    <input class="form-control-utility" type="text" size="30" placeholder="Email" name="email"></div>
                    <div class="align-lookup">
                    <input class="form-control-utility" type="submit" style="width: 60px; height: 26px" value="Go"></div>
                    <?php }?>
                    <?php } ?>
                    <?php }else{
                        if (isset($debug))
                        {
                            echo '<p>Debug Mode Enabled - Error reporting is now turned on.</p>';
                        }?>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=lookup');?>">User lookup</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/get-student-count');?>">Get Student Count</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/get-student-detail-count');?>">Get Detailed Student Count</a><br/></li>
                        <li> <a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/approve-pending-req')?>">Approve Pending Instructor Accounts</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?debug=true');?>">Enable Debug Mode</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/replace-video');?>">Replace YouTube videos</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=rescue');?>">Recover lost items</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=emu');?>">Emulate User</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/list-external-ref');?>">List ExtRefs</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/update-external-ref');?>">Update ExtRefs</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/list-wrong-lib-flag');?>">List WrongLibFlags</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/update-wrong-lib-flag');?>">Update WrongLibFlags</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/block-search');?>">Search Block titles</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/item-search');?>">Search inline/linked items</a><br/></li>
                        <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/update-questions-data');?>">Update question usage data (slow)</a><br/></li>
                    <?php }?>
    </div>
    <div>
