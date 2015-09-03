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
            <?php echo AppUtility::t('Emulate user with userid:', false)?><input type="text" size="30" name="uid"/>
            <input type="submit" style="width: 60px; height: 26px" value="Go"/>

        <?php }elseif($form == 'rescue'){?>

              <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/rescue-course')?>">
              <?php echo AppUtility::t('Recover lost items in course ID: ', false)?><input type="text" size="30" name="cid"/>
                  <input type="submit" style="width: 60px; height: 26px" value="Go"/>

        <?php }elseif($form == 'lookup'){?>
                  <?php if(!empty($params['LastName']) || !empty($params['FirstName']) || !empty($params['SID']) || !empty($params['email'])){
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
                      echo '<li>Reset Password to <input type="text" name="newpw"/> <input type="submit" style="width: 60px; height: 26px" value="Go"/></li>';

                  }?>
                  <?php
                    if($queryForCourse)
                    {
                        echo '<li>Enrolled as student in: <ul>';
                        foreach($queryForCourse as $data)
                        {
                            echo  '<li><a target="_blank" href="'.AppUtility::getURLFromHome('course','course/index?cid='.$data['id']).'">'.$data['name'].'(ID '.$data['id'].')</a></li>';
                        }
                        echo '</ul></li>';
                    }
                    if($queryFromCourseForTutor)
                    {
                        echo '<li>Teacher in: <ul>';
                        foreach($queryFromCourseForTutor as $singleTutor)
                        {
                            echo '<li><a target="_blank" href="../course/course.php?cid='.$singleTutor['id'].'">'.$singleTutor['name'].' (ID '.$singleTutor['id'].')</a></li>';

                        }
                        echo '</ul></li>';
                    }

                    if($queryFromCourseForTeacher)
                    {
                        echo '<li>Teacher in: <ul>';
                        foreach($queryFromCourseForTeacher as $singleTeacher)
                        {
//                            echo '<li><a target="_blank" href="../course/course.php?cid='.$singleTeacher['id'].'">'.$singleTeacher['name'].' (ID '.$singleTeacher['id'].')</a></li>';
                            echo  '<li><a target="_blank" href="'.AppUtility::getURLFromHome('instructor','instructor/index?cid='.$singleTeacher['id']).'">'.$singleTeacher['name'].'(ID '.$singleTeacher['id'].')</a></li>';
                        }
                        echo '</ul></li>';

                    }
                    if($queryForLtiUser)
                    {
                        echo '<li>LTI connections: <ul>';
                        foreach($queryForLtiUser as $singleLtiUser)
                        {
                            echo '<li>'.$singleLtiUser['org'].' <a href="utils.php?removelti='.$singleLtiUser['id'].'">Remove connection</a></li>';
                        }
                    }
                    echo '</ul>';
                    echo '</form>';

                }else{?>

                      <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=lookup');?>">
                          <?php echo AppUtility::t('Look up user:', false)?>
                          <p></p><div class="align-lookup"><input type="text" size="30" placeholder="LastName" name="LastName"></div>
                          <div class="align-lookup"><input type="text" size="30" placeholder="FirstName" name="FirstName"></div>
                          <div class="align-lookup"><input type="text" size="30" placeholder="UserName" name="SID"></div>
                          <div class="align-lookup"><input type="text" size="30" placeholder="Email" name="email"></div>
                          <div class="align-lookup"><input type="submit" style="width: 60px; height: 26px" value="Go"></div>
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
          <li> <a href="#">Approve Pending Instructor Accounts</a><br/></li>
          <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?debug=true');?>">Enable Debug Mode</a><br/></li>
          <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/replace-video');?>">Replace YouTube videos</a><br/></li>
          <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=rescue');?>">Recover lost items</a><br/></li>
          <li><a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=emu');?>">Emulate User</a><br/></li>
          <li><a href="listextref.php">List ExtRefs</a><br/></li>
          <li><a href="updateextref.php">Update ExtRefs</a><br/></li>
          <li><a href="listwronglibs.php">List WrongLibFlags</a><br/></li>
          <li><a href="updatewronglibs.php">Update WrongLibFlags</a><br/></li>
          <li><a href="blocksearch.php">Search Block titles</a><br/></li>
          <li><a href="itemsearch.php">Search inline/linked items</a><br/></li>
          <li><a href="../calcqtimes.php">Update question usage data (slow)</a><br/></li>
 <?php }?>
    </div>
<div>
