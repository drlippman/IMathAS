<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Approve Pending Instructor Accounts';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities']]);?>
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
    <div class="" style="margin-left: 40px">
        <?php
        if($body == AppConstant::NUMERIC_ONE)
        {
            echo $message;

        }
        else{?>
            <?php if(empty($findPendingUser))
            {

                AppUtility::t('No one to approve');
            }
            else{                ?>

                    <h2>Account Approval</h2>
                    <form method="post" action="<?php echo AppUtility::getURLFromHome('utilities','utilities/approve-pending-req?go=true&amp;skipn='.$offset);?>">
                    <input type="hidden" name="id" value="<?php echo $findPendingUser['id']?>">
                    <input type="hidden" name="email" value="<?php echo $findPendingUser['email']?>">
                    <p><b><?php AppUtility::t('Username:');?>&nbsp;</b><?php echo $findPendingUser['SID'];?><br/><b><?php AppUtility::t('Name:');?>&nbsp;</b><?php echo $findPendingUser['LastName'].', '. $findPendingUser['FirstName'];?>&nbsp;<?php echo '('.$findPendingUser['email'].')'?></p>
                        <?php
                        if ($details != '')
                        {
                            echo "<p>$details</p>";
                            if (preg_match('/School:(.*?)<br/',$details,$matches))
                            {
                                echo '<p><a target="checkver" href="https://www.google.com/search?q='.urlencode($findPendingUser['FirstName'].' '.$findPendingUser['LastName'].' '.$matches[1]).'">Search</a></p>';
                            }
                        }?>
                        <p><?php AppUtility::t('Group: ');
                            ?>
                            <select name="group"><option value="-1"><?php AppUtility::t('New Group');?></option>
                                <?php
                                if($groupsName)
                                {
                                    foreach($groupsName as $grpName)
                                    {
                                        echo '<option class="form-control" value="'.$grpName['id'].'">'.$grpName['name'].'</option>';
                                    }
                                }
                                ?>
                            </select><br><br>
                            <?php echo AppUtility::t('New group:',false);
                            ?>
                            <input class="form-control-utility" type="text" name="newgroup" size="40"/></p>
                            <br>
                            <div class="form-group">
                                <span><input type="submit" name="approve" value="Approve" /></span>
                                <span class="padding-left-one-em"><input type="submit" name="deny" value="Deny" /></span>
                                <span class="padding-left-one-em"><input type="submit" name="skip" value="Skip" /></span>
                            </div>
                    </form>
            <?php }?>
    <?php  }?>

    </div>
</div>