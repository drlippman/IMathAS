<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Admin Utilities';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index']]); ?>
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
            <?php echo AppUtility::t('Emulate user with userid:', false)?><input type="text" size="5" name="uid"/>
            <input type="submit" value="Go"/>

        <?php }elseif($form == 'rescue'){?>

              <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/rescue-course')?>">
              <?php echo AppUtility::t('Recover lost items in course ID: ', false)?><input type="text" size="5" name="cid"/>
              <input type="submit" value="Go"/>

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
                      echo '<li>Reset Password to <input type="text" name="newpw"/> <input type="submit" value="'._('Go').'"/></li>';

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
                          <div class="align-lookup"><input type="submit"  value="Go"></div>
                <?php }?>
       <?php } ?>
<?php }else{
         if (isset($debug))
        {
            echo '<p>Debug Mode Enabled - Error reporting is now turned on.</p>';
        }?>
        <a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=lookup');?>">User lookup</a><br/>
        <a href="getstucnt.php">Get Student Count</a><br/>
        <a href="getstucntdet.php">Get Detailed Student Count</a><br/>
        <a href="'.$imasroot.'/admin/approvepending.php">Approve Pending Instructor Accounts</a><br/>
        <a href="utils.php?debug=true">Enable Debug Mode</a><br/>
        <a href="replacevids.php">Replace YouTube videos</a><br/>
        <a href="<?php echo AppUtility::getURLFromHome('utilities','utilities/admin-utilities?form=rescue');?>">Recover lost items</a><br/>
        <a href="utils.php?form=emu">Emulate User</a><br/>
        <a href="listextref.php">List ExtRefs</a><br/>
        <a href="updateextref.php">Update ExtRefs</a><br/>
        <a href="listwronglibs.php">List WrongLibFlags</a><br/>
        <a href="updatewronglibs.php">Update WrongLibFlags</a><br/>
        <a href="blocksearch.php">Search Block titles</a><br/>
        <a href="itemsearch.php">Search inline/linked items</a><br/>
        <a href="../calcqtimes.php">Update question usage data (slow)</a><br/>
 <?php }?>
    </div>
<div>
