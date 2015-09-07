<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'MassEnd';

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php
    if($calledFrom == 'lu')
    {
        //LinkToListUser
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false),AppUtility::t('ListUser', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities','#']]);
    }
    elseif($calledFrom == 'gb')
    {
        //LinkToGradeBook
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false),AppUtility::t('GradeBook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities','#']]);
    }
    elseif($calledFrom == 'itemsearch')
    {
        echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false),AppUtility::t('Admin', false),AppUtility::t('Util', false),AppUtility::t('ItemSearch', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index',AppUtility::getHomeURL() . 'admin/admin/index',AppUtility::getHomeURL() . 'utilities/utilities/admin-utilities','#']]);
    }
    ?>
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
        <?php if(!isset($params['message']))
        {

            $sendType = (isset($params['posted']))?$params['posted']:$params['submit']; //E-mail or Message
            $useEditor = "message";
            $pageTitle = "Send Mass $sendType";
            if (count($params['checked'])==0)
            {
                echo AppUtility::t('No users selected. ',false);
                if ($calledFrom=='lu')
                {
                    echo "<a href='#'>Try again</a>\n";
                } else if ($calledFrom=='gb')
                {
                    echo "<a href='#'>Try again</a>\n";
                }
            }
            else{
                echo '<div id="headermasssend" class="pagetitle">';
                echo "<h3>Send Mass $sendType</h3>\n";
                echo '</div>';
                if ($calledFrom=='lu')
                {
                    echo "<form method=post action=''>\n";
                } else if ($calledFrom=='gb')
                {
                    echo "<form method=post action=''>\n";
                } else if ($calledFrom=='itemsearch')
                {
                    echo "<form method=post action='".AppUtility::getURLFromHome('utilities','utilities/item-search?masssend='.$sendType)."'>\n";
                }
                echo "<span class=form><label for=\"subject\">Subject:</label></span>";
                echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
                echo "<span class=form><label for=\"message\">Message:</label></span>";
                echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70> </textarea></div></span><br class=form>\n";
                echo "<p><i>Note:</i> <b>FirstName</b> and <b>LastName</b> can be used as form-mail fields that will autofill with each students' first/last name</p>";
                echo "<span class=form><label for=\"self\">Send copy to:</label></span>";
                echo "<span class=formright><input type=radio name=self id=self value=\"none\">Only Students<br/> ";
                echo "<input type=radio name=self id=self value=\"self\" checked=checked>Students and you<br/> ";
                echo "<input type=radio name=self id=self value=\"allt\">Students and all instructors of this course</span><br class=form>\n";
                if ($sendType=='Message')
                {
                    echo '<span class="form"><label for="savesent">Save in sent messages?</label></span>';
                    echo '<span class="formright"><input type="checkbox" name="savesent" checked="checked" /></span><br class="form" />';
                }
                echo "<span class=form><label for=\"limit\">Limit send: </label></span>";
                echo "<span class=formright>";
                echo "to students who haven't completed: ";
                echo "<select name=\"aidselect\" id=\"aidselect\">\n";
                echo "<option value=\"0\">Don't limit - send to all</option>\n";
                if($assessmentData)
                {
                    foreach($assessmentData as $line)
                    {
                        echo "<option value=\"{$line['id']}\" ";
                        if (isset($aid) && ($aid == $line['id']))
                        {
                            echo "SELECTED";
                        }
                        echo ">{$line['name']}</option>\n";
                    }
                }
                echo "</select>\n";
                echo "<input type=hidden name=\"tolist\" value=\"" . implode(',',$params['checked']) . "\">\n";
                echo "</span><br class=form />\n";
                echo "<div class=submit><input type=submit value=\"Send $sendType\"></div>\n";
                echo "</form>\n";
                echo '<p>Unless limited, message will be sent to:<ul>';
                if($detailsOfUser)
                {
                    foreach($detailsOfUser as $row)
                    {
                        echo "<li>{$row['LastName']}, {$row['FirstName']} ({$row['SID']})</li>";
                    }
                }
                echo '</ul>';
            }
        }?>

    </div>
</div>