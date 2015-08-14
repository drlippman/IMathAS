<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t(' Add Questions',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name,AppUtility::t('Add/Remove Questions',false),AppUtility::t('Modify Questions',false)],
        'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id,
            AppUtility::getHomeURL() . 'question/question/add-question?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $addMode ?><?php echo AppUtility::t(' QuestionSet Question:',false);?></div>
        </div>
        <div class="pull-left header-btn hide-hover">
<!--            <a href="#"id="mess" class="btn btn-primary1 pull-right  btn-color"><img class = "small-icon" src="--><?php //echo AppUtility::getAssetURL()?><!--img/newzmessg.png">&nbsp;Send Message</a>-->
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox">
    <br><br><?php
    $pagetitle = "Question Editor";
    $placeinhead = '';
    if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==2 || $sessiondata['mathdisp']==3) {
    //these scripts are used by the editor to make image-based math work in the editor
    $placeinhead .= '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
        if ($mathdarkbg) {$placeinhead .=  'var mathbg = "dark";';}
        $placeinhead .= '</script>';
//    $placeinhead .= "<script src=".AppUtility::getHomeURL().'/ASCIIMathTeXImg_min.js?ver=082911'." type=\"text/javascript\"></script>";
    }
//    $placeinhead .= '<script type="text/javascript" src="'.AppUtility::getHomeURL().'/js/editor/tiny_mce.js?v=082911"></script>';
    $placeinhead .= '<script type="text/javascript">
        var editoron = 0; var seditoron = 0;
        var coursetheme = "'.$coursetheme.'";';
        if (!isset($CFG['GEN']['noFileBrowser'])) {
            $placeinhead .= 'var fileBrowserCallBackFunc = "fileBrowserCallBack";';
        } else {
            $placeinhead .= 'var fileBrowserCallBackFunc = null;';
        }

        if (isset($_GET['id'])) {
            $placeinhead .= 'var originallicense = '.$line['license'].';';
        } else {
            $placeinhead .= 'var originallicense = -1;';
        }

        $placeinhead .= 'function toggleeditor(el) {
        var qtextbox =  document.getElementById(el);
        if ((el=="qtext" && editoron==0) || (el=="solution" && seditoron==0)) {
            qtextbox.rows += 3;
            qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\\/span>/g,"$1");
            qtextbox.value = qtextbox.value.replace(/`(.*?)`/g,\'<span class="AM" title="$1">`$1`</span>\');
            initeditor("exact",el,1);
        } else {
            tinyMCE.execCommand("mceRemoveControl",true,el);
            qtextbox.rows -= 3;
            qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\\/span>/g,"$1");
        }
        if (el=="qtext") {
            editoron = 1 - editoron;
            document.cookie = "qeditoron="+editoron;
        } else if (el=="solution") {
            seditoron = 1 - seditoron;
            document.cookie = "seditoron="+seditoron;
        }
        }
        addLoadEvent(function(){if (document.cookie.match(/qeditoron=1/)) {
            var val = document.getElementById("qtext").value;
            if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("qtext");}
        }});
        addLoadEvent(function(){if (document.cookie.match(/seditoron=1/)) {
            var val = document.getElementById("solution").value;
            if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("solution");}
        }});

        function checklicense() {
            var lic = $("#license").val();
            console.log(lic+","+originallicense);
            var warn = "";
            if (originallicense>-1) {
                if (originallicense==0 && lic != 0) {
                    warn = "'._('If the original question contained copyrighted material, you should not change the license unless you have removed all the copyrighted material').'";
                } else if ((originallicense == 1 ||  originallicense == 3 ||  originallicense == 4) && lic != originallicense) {
                    warn = "'._('The original license REQUIRES that all derivative versions be kept under the same license. You should only be changing the license if you are the creator of this questions and all questions it was derived from').'";
                }
            }
            $("#licensewarn").html("<br/>"+warn);
        }
    </script>';
    if (strpos($line['control'],'end stored values - Tutorial Style')!==false) {
        echo '<p>This question appears to be a Tutorial Style question.  <a href="mod-tutorial-question?'.$_SERVER['QUERY_STRING'].'">Open in the tutorial question editor</a></p>';
    }

    if ($line['deleted']== AppConstant::NUMERIC_ONE) {
        echo '<p style="color:red;">This question has been marked for deletion.  This might indicate there is an error in the question. ';
        echo 'It is recommended you discontinue use of this question when possible</p>';
    }

    if (isset($inusecnt) && $inusecnt>0) {
        echo '<p style="color:red;">This question is currently being used in ';
        if ($inusecnt>1) {
            echo $inusecnt.' assessments that are not yours.  ';
        } else {
            echo 'one assessment that is not yours.  ';
        }
        echo 'In consideration of the other users, if you want to make changes other than minor fixes to this question, consider creating a new version of this question instead.  </p>';

    }
    ?>
</div>