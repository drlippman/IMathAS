<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t(' Add Questions',false);
$cname= $course->name;
if($courseId == 'admin') {
   $cname = 'Admin';
}
$this->params['breadcrumbs'][] = $this->title;
$urlmode = AppUtility::urlMode();
?>
<div class="item-detail-header">
    <?php if($params['cid'] == "admin"){ ?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$cname,'ManageQuestionSet'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index',AppUtility::getHomeURL().'question/question/manage-question-set?cid=admin'] ,'page_title' => $this->title]); ?>
    <?php } else{ ?>
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$cname,'Add/Remove Question'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index',AppUtility::getHomeURL().'question/question/add-questions?cid='.$courseId.'&aid='.$params['aid']] ,'page_title' => $this->title]); ?>
    <?php }?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $addMode ?><?php echo AppUtility::t(' QuestionSet Question',false);?></div>
        </div>
    </div>
</div>
<div class="item-detail-content margin-top-fourty">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox padding-bottom-thirty col-md-12 col-sm-12 padding-left-right-zero">
    <br>
    <div class="shadow-content modify-data-shadow-box">
    <?php
    $pagetitle = "Question Editor";
    $placeinhead = '';
    if ($sessionData['mathdisp']==1 || $sessionData['mathdisp']==2 || $sessionData['mathdisp']==3) {
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

        if (isset($params['id'])) {
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
        echo '<p class="color-red col-md-12 col-sm-12 padding-left-twelve">This question has been marked for deletion.  This might indicate there is an error in the question. ';
        echo 'It is recommended you discontinue use of this question when possible</p>';
    }
    if (isset($inUseCount) && $inUseCount['qidCount'] > 0) {
        echo '<p class="color-red col-md-12 col-sm-12 padding-left-twelve">This question is currently being used in ';
        if ($inUseCount['qidCount'] > 1) {
            echo $inUseCount['qidCount'].' assessments that are not yours.  ';
        } else {
            echo 'one assessment that is not yours.  ';
        }
        echo 'In consideration of the other users, if you want to make changes other than minor fixes to this question, consider creating a new version of this question instead.  </p>';
    }

    if (isset($params['qid'])) {
        echo "<div class='col-md-12 col-sm-12 margin-left-minus-four'><p><a href=\"mod-data-set?id={$params['id']}&cid=$courseId&aid={$params['aid']}&template=true&makelocal={$params['qid']}\">Template this question</a> for use in this assessment.  ";
        echo "This will let you modify the question for this assessment only without affecting the library version being used in other assessments.</p></div>";
    }
    if (!$myq) {
        echo "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
    }
    ?>
    <form class="margin-left-minus-twenty" enctype="multipart/form-data" method=post action="mod-data-set?process=true<?php
    if (isset($params['cid'])) {
        echo "&cid=$courseId";
    }
    if (isset($params['aid'])) {
        echo "&aid={$params['aid']}";
    }
    if (isset($params['id']) && !isset($params['template'])) {
        echo "&id={$params['id']}";
    }
    if (isset($params['template'])) {
        echo "&templateid={$params['id']}";
    }
    if (isset($params['makelocal'])) {
        echo "&makelocal={$params['makelocal']}";
    }
    if ($frompot==AppConstant::NUMERIC_ONE) {
        echo "&frompot=1";
    }
    ?>">
    <input type="hidden" name="hasimg" value="<?php echo $line['hasimg'];?>"/>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
       <div class="col-md-2 col-sm-3"><?php AppUtility::t('Description')?>  </div>
        <div class="col-md-10 col-sm-9"><textarea class="form-control max-width-hundred-per" cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['description'];?></textarea></div>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
        <div class="col-md-2 col-sm-3"><?php AppUtility::t('Author')?></div>
        <div class="col-md-10 col-sm-9"><?php echo $line['author']; ?> <input type="hidden" name="author" value="<?php echo $author; ?>"></div>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
        <?php
        if (!isset($line['ownerid']) || isset($params['template']) || $line['ownerid']==$userId || ($line['userights']==3 && $line['groupid']==$groupId) || $isAdmin || ($isGrpAdmin && $line['groupid']==$groupId)) {
            echo '<div class="col-md-2 col-sm-3 select-text-margin">Use Rights</div> <div class="col-md-10 col-sm-9"><select class="width-sixty-per form-control" name="userights" id="userights">';
            echo "<option value=\"2\" ";
        if ($line['userights']==AppConstant::NUMERIC_TWO) {echo "SELECTED";}
            echo ">Allow use by all</option>\n";
            echo "<option value=\"4\" ";
        if ($line['userights']==AppConstant::NUMERIC_FOUR) {echo "SELECTED";}
            echo ">Allow use by all and modifications by all</option>\n";
            echo "<option value=\"3\" ";
        if ($line['userights']==AppConstant::NUMERIC_THREE) {echo "SELECTED";}
            echo ">Allow use by all and modifications by group</option>\n";
            echo "<option value=\"0\" ";
        if ($line['userights']==AppConstant::NUMERIC_ZERO) {echo "SELECTED";}
            echo ">Private</option>\n";
            echo '</select></div><br/>'; ?>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
            <?php
            echo '<div class="col-md-2 col-sm-3 select-text-margin">License</div> <div class="col-md-10 col-sm-9"><select class="width-sixty-per form-control" name="license" id="license" onchange="checklicense()">';
            echo '<option value="0" '.($line['license']==AppConstant::NUMERIC_ZERO?'selected':'').'>Copyrighted</option>';
            echo '<option value="3" '.($line['license']==AppConstant::NUMERIC_THREE?'selected':'').'>Creative Commons Attribution-NonCommercial-ShareAlike</option>';
            echo '<option value="3" '.($line['license']==AppConstant::NUMERIC_FOUR?'selected':'').'>Creative Commons Attribution-ShareAlike</option>';
            echo '<option value="1" '.($line['license']==AppConstant::NUMERIC_ONE?'selected':'').'>IMathAS / WAMAP / MyOpenMath Community License (GPL + CC-BY)</option>';
            echo '<option value="2" '.($line['license']==AppConstant::NUMERIC_TWO?'selected':'').'>Public Domain</option>';

            echo '</select><span id="licensewarn" style="color:red;font-size:80%;"></span> </div>';
            ?>
    </div>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
            <?php
            if ($line['otherattribution']=='') {
                echo '<a class="margin-left-fifteen" href="#" onclick="$(\'#addattrspan\').show();$(this).hide();return false;">Add additional attribution</a>';
                echo '<span id="addattrspan" style="display:none;">';
            } else {
                echo '<br/><span id="addattrspan">';
            }
            echo '<div class="col-md-2 col-sm-3 select-text-margin">Additional Attribution</div>
            <div class="col-md-10 col-sm-9">
            <input class="form-control width-sixty-per" type="text" size="80" name="addattr" value="'.htmlentities($line['otherattribution']).'"/>
            </div>';
            if ($line['otherattribution']!='') {
                echo '<br/><span style="color:red;font-size:80%">You should only modify the attribution if you are SURE you are removing all portions of the question that require the attribution</span>';
            }
            echo '</span>';
        }
        ?>
    </div>
        <script>
            var curlibs = '<?php echo $inlibs;?>';
            var locklibs = '<?php echo $locklibs;?>';

        </script>
        <div class="col-md-12 col-sm-12 padding-top-twenty">
           <div class="col-md-2 col-sm-3 select-text-margin"><?php AppUtility::t('My library assignments')?> </div>
            <div class="col-md-6 col-sm-6 padding-left-zero">
                <div class="col-md-3 col-sm-3 select-text-margin"><span id="libnames"><?php echo $lnames;?></span></div>
                <input type=hidden name="libs" id="libs" size="10" value="<?php echo $inlibs;?>">
                <div class="col-md-2 col-sm-2">
                    <input type=button value="Select Libraries" onClick="#">
               </div>
           </div>
        </div>
        <div class="col-md-12 col-sm-12 padding-top-twenty">
           <div class="col-md-2 col-sm-3 select-text-margin"><?php AppUtility::t('Question type')?> </div> <div class="col-md-10 col-sm-9"> <select class="width-sixty-per form-control" name=qtype <?php if (!$myq) echo "disabled=\"disabled\"";?>>
                <option value="calccomplex" <?php if ($line['qtype']=="calccomplex") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Complex') ?></option>
                <option value="calcinterval" <?php if ($line['qtype']=="calcinterval") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Interval') ?></option>
                <option value="calcmatrix" <?php if ($line['qtype']=="calcmatrix") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Matrix') ?></option>
                <option value="calcntuple" <?php if ($line['qtype']=="calcntuple") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated N-Tuple') ?></option>
                <option value="calculated" <?php if ($line['qtype']=="calculated") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Number') ?></option>
                <option value="complex" <?php if ($line['qtype']=="complex") {echo "SELECTED";} ?>><?php AppUtility::t('Complex') ?></option>
                <option value="conditional" <?php if ($line['qtype']=="conditional") {echo "SELECTED";} ?>><?php AppUtility::t('Conditional') ?></option>
                <option value="draw" <?php if ($line['qtype']=="draw") {echo "SELECTED";} ?>><?php AppUtility::t('Drawing') ?></option>
                <option value="essay" <?php if ($line['qtype']=="essay") {echo "SELECTED";} ?>><?php AppUtility::t('Essay')?></option>
                <option value="file" <?php if ($line['qtype']=="file") {echo "SELECTED";} ?>><?php AppUtility::t('File Upload') ?></option>
                <option value="numfunc" <?php if ($line['qtype']=="numfunc") {echo "SELECTED";} ?>><?php AppUtility::t('Function') ?></option>
                <option value="interval" <?php if ($line['qtype']=="interval") {echo "SELECTED";} ?>><?php AppUtility::t('Interval') ?></option>
                <option value="matching" <?php if ($line['qtype']=="matching") {echo "SELECTED";} ?>><?php AppUtility::t('Matching') ?></option>
                <option value="multipart" <?php if ($line['qtype']=="multipart") {echo "SELECTED";} ?>><?php AppUtility::t('Multipart')?></option>
                <option value="multans" <?php if ($line['qtype']=="multans") {echo "SELECTED";} ?>><?php AppUtility::t('Multiple-Answer') ?></option>
                <option value="choices" <?php if ($line['qtype']=="choices") {echo "SELECTED";} ?>><?php AppUtility::t('Multiple-Choice') ?></option>
                <option value="ntuple" <?php if ($line['qtype']=="ntuple") {echo "SELECTED";} ?>><?php AppUtility::t('N-Tuple')?></option>
                <option value="number" <?php if ($line['qtype']=="number") {echo "SELECTED";} ?>><?php AppUtility::t('Number')?></option>
                <option value="matrix" <?php if ($line['qtype']=="matrix") {echo "SELECTED";} ?>><?php AppUtility::t('Numerical Matrix')?></option>
                <option value="string" <?php if ($line['qtype']=="string") {echo "SELECTED";} ?>><?php AppUtility::t('String') ?></option>
            </select></div>
        </div>
        <div class="col-md-12 col-sm-12 padding-top-twenty">
            <div class="col-md-2 col-sm-3">
            <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL();?>question/question/help?section=writingquestions','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Writing Questions Help</a>
           </div>
           <div class="col-md-3 col-sm-3">
               <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL();?>question/question/micro-lib-help','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Macro Library Help</a>
           </div>
        </div>
        <div class="col-md-12 col-sm-12 padding-top-twenty">
            <div class="col-md-2 col-sm-3 select-text-margin"><?php AppUtility::t('Switch to')?></div>
            <div class="col-md-10 col-sm-9">
                <input type=button id=entrymode value="<?php if ($twobx) {echo "4-box entry";} else {echo "2-box entry";}?>" onclick="swapentrymode()" <?php if ($line['qcontrol']!='' || $line['answer']!='') echo "DISABLED"; ?>/>
                    <?php if (!isset($params['id'])) {
                    echo ' <a class="padding-left-one-em" href="mod-tutorial-question?'.$_SERVER['QUERY_STRING'].'">Tutorial Style editor</a>';
                    }?>
            </div>
        </div>
        <div class="col-md-12 col-sm-12">
        <div id=ccbox class="col-md-12 col-sm-12 padding-top-twenty">
              <div class="col-md-2 col-sm-3 select-text-margin padding-left-zero">
                  <?php AppUtility::t('Common Control')?>
                  <span class=pointer onclick="incboxsize('control')">[+]</span><span class=pointer onclick="decboxsize('control')">[-]</span>
              </div>
              <div class="col-md-6 col-sm-6 padding-left-pt-four-em">
                  <input type=submit value="Save">
                  <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
              </div>
        </div></br>
        <div class="col-md-10 col-sm-9 col-md-offset-2 col-sm-offset-3 padding-top-twenty">
            <textarea class="max-width-hundred-per form-control" cols=60 rows=<?php if ($twobx) {echo min(35,max(20,substr_count($line['control'],"\n")+1));} else {echo "10";}?> id=control name=control <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['control']);?></textarea>
        </div>
        </div>
        <div id=qcbox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
                <div class="col-md-12 col-sm-12 margin-left-sixteen padding-top-twenty">
                   <div class="col-md-2 col-sm-3 select-text-margin padding-left-zero"> <?php AppUtility::t('Question Control')?> <span class=pointer onclick="incboxsize('qcontrol')">[+]</span><span class=pointer onclick="decboxsize('qcontrol')">[-]</span></div>
                   <div class="col-md-6 col-sm-6 padding-left-zero"> <input type=submit value="Save">
                    <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
                   </div>
                </div>
            <div class="col-md-10 col-sm-9 col-md-offset-2 col-sm-offset-3 padding-top-twenty"> <textarea class="margin-left-ten form-control" style="width: 100%" cols=60 rows=10 id=qcontrol name=qcontrol <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['qcontrol'];?></textarea> </div>
        </div>
        <div id='qtbox' class="col-md-12 col-sm-12 padding-top-twenty">
           <div class="col-md-12 col-sm-12">
               <div class="col-md-2 col-sm-3 select-text-margin padding-left-zero">
                   <?php AppUtility::t('Question Text') ?>
                   <span class=pointer onclick="incboxsize('qtext')">[+]</span>
                   <span class=pointer onclick="decboxsize('qtext')">[-]</span>
               </div>
               <div class="col-md-8 col-sm-8 padding-left-pt-four-em"><input type="button" onclick="toggleeditor('qtext')" value="Toggle Editor"/>
                    <input class="margin-left-fifteen" type=submit value="Save">
                    <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
               </div>
           </div>
        </div></br>

        <div class="col-md-12 col-sm-12 padding-top-twenty">
           <div class="col-md-10 col-sm-9 col-md-offset-2 col-sm-offset-3"> <textarea class="max-width-hundred-per form-control" cols=60 rows=<?php echo min(35,max(10,substr_count($line['qtext'],"\n")+1));?> id="qtext" name="qtext" <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['qtext']);?></textarea></div>
        </div>
    <?php echo $errorMsg;
               $outputMsg;?>
        <div id=abox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
            <div class="col-md-12 col-sm-12 margin-left-sixteen padding-top-twenty">
                <div class="col-md-2 col-sm-3 select-text-margin padding-left-zero">
                    <?php AppUtility::t('Answer')?> <span class=pointer onclick="incboxsize('answer')">[+]</span>
                    <span class=pointer onclick="decboxsize('answer')">[-]</span>
                </div>
                <div class="floatleft col-md-6 col-sm-6 padding-left-zero">
                    <input type=submit value="Save">
                    <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
                </div>
            </div>
            <div class="col-md-10 col-sm-9 col-md-offset-2 col-sm-offset-3 padding-top-twenty"> <textarea class="margin-left-ten form-control" style="width: 100%" cols=60 rows=10 id=answer name=answer <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['answer'];?></textarea></div>
        </div>

        <?php
        if ($line['solution']=='') {
            echo '<div class="col-md-12 col-sm-12 left-fifteen padding-top-twenty"><a href="#" onclick="$(this).parent().hide();$(\'#solutionwrapper\').show();return false;">Add a detailed solution</a></div>';
            echo '<div id="solutionwrapper" class="col-md-12 col-sm-12" style="display:none;">';
        } else {
            echo '<div id="solutionwrapper">';
        }
        ?>
    <div class="col-md-12 col-sm-12 padding-top-twenty">
       <div class="col-md-2 col-sm-3 select-text-margin padding-left-zero"><?php AppUtility::t('Detailed Solution')?>
         <span class=pointer onclick="incboxsize('solution')">[+]</span><span class=pointer onclick="decboxsize('solution')">[-]</span>
       </div>
       <div class="col-md-6 col-sm-8">
            <input type="button" onclick="toggleeditor('solution')" value="Toggle Editor"/>
            <input class="margin-left-fifteen" type=submit value="Save">
            <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
       </div>
    </div>
    <br>
    <div class="col-md-12 col-sm-12">
        <div class="col-md-10 col-sm-9 col-md-offset-2 col-sm-offset-3 padding-top-twenty">
            <input type="checkbox" name="usesrand" value="1" <?php if (($line['solutionopts']&1)==1) {echo 'checked="checked"';};?>
                   onclick="$('#userandnote').toggle()">
            <?php AppUtility::t('Uses random variables from the question.')?>
            <span id="userandnote" <?php if (($line['solutionopts']&1)==1) {echo 'style="display:none;"';}?>>
            <i><?php AppUtility::t('Be sure to include the question you are solving in the text')?></i>
            </span><br/>
            <input type="checkbox" name="useashelp" value="2" <?php if (($line['solutionopts']&2)==2) {echo 'checked="checked"';};?>>
            <?php AppUtility::t('Use this as a "written example" help button')?><br/>
            <input type="checkbox" name="usewithans" value="4" <?php if (($line['solutionopts']&4)==4) {echo 'checked="checked"';};?>>
            <?php AppUtility::t('Display with the "Show Answer"')?><br/>
        <textarea class="form-control padding-top-twenty width-hundread-three-per max-width-hundread-three-per margin-top-ten" cols=60 rows=<?php echo min(35,max(10,substr_count($line['solution'],"\n")+1));?> id="solution" name="solution" <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['solution']);?></textarea>
        <?php echo '</div></div></div>' ?>

<div id=imgbox class="col-md-12 col-sm-12">
    <input type="hidden" name="MAX_FILE_SIZE" value="500000" />
   <div class="col-md-12 col-sm-12 padding-top-twenty right-ten display-inline-block">
       <div class="col-md-2 col-sm-3 right-five display-inline-block"><?php AppUtility::t('Image file')?></div>
       <div class="col-md-3 col-sm-3 display-inline-block"> <input class="image-file-input" type="file" name="imgfile"/></div>
   </div>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-fifteen">
        <span class="col-md-2 col-sm-3 select-text-margin"><?php AppUtility::t('Assign to variable')?></span>
        <div class="col-md-10 col-sm-9 display-inline">
                <input class="form-control display-inline-block width-sixty-one-per" type="text" name="newimgvar" size="6"/>
        </div>
    </div>
    <div class="col-md-12 col-sm-12 padding-left-zero padding-top-fifteen">
        <div class="col-md-2 col-sm-3 select-text-margin display-inline-block"><?php AppUtility::t('Description')?></div>
        <div class="col-md-10 col-sm-9 display-inline">
            <input class="form-control width-sixty-one-per display-inline-block" type="text" size="20" name="newimgalt" value=""/>
        </div>
    </div>
    <?php
    if (isset($images['vars']) && count($images['vars'])>0) {
        echo "<div class='col-md-12 col-sm-12 padding-left-zero'>
        <div class='col-md-2 col-sm-2'>Images</div>";
        foreach ($images['vars'] as $id=>$var) {
            if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                $urlimg = $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$images['files'][$id]}";
            } else {
                $urlimg = AppUtility::getAssetURL()."Uploads/qimages/{$images['files'][$id]}";
            }
            echo "</div>";
            echo "<div class='col-md-12 col-sm-12 padding-left-zero padding-bottom-twenty'>
                        <div class='col-md-2 col-sm-2 display-inline-block select-text-margin'>Variable</div>
                        <div class='col-md-10 col-sm-10 display-inline'>
                            <input class='form-control width-sixty-one-per display-inline-block' type=\"text\" name=\"imgvar-$id\" value=\"\$$var\" size=\"10\"/>
                            <a class='padding-left-twenty-five' href=\"$urlimg\" target=\"_blank\">View</a>
                        </div>
                  </div> ";
            echo "<div class='col-md-12 col-sm-12 padding-left-zero'>
                    <div class='col-md-2 col-sm-2 display-inline-block floatleft select-text-margin'>Description</div>
                    <div class='col-md-10 col-sm-10 display-inline'>
                        <input class='floatleft form-control width-sixty-one-per display-inline-block' type=\"text\" size=\"20\" name=\"imgalt-$id\" value=\"{$images['alttext'][$id]}\"/>
                        <span class='select-text-margin padding-left-twenty-eight floatleft'>Delete?</span>
                        <input class='select-text-margin margin-left-ten' type=checkbox name=\"delimg-$id\"/>
                    </div>
                    </div>";
        }
    }
    ?>
    <div class="col-md-12 col-sm-12 right-ten padding-top-twenty">
        <div class="col-md-2 col-sm-3 right-five select-text-margin display-inline-block floatleft"><?php AppUtility::t('Help button')?></div>
        <div class="col-md-4 col-sm-4 padding-left-zero display-inline-block floatleft">
            <div class="col-md-2 col-sm-2 select-text-margin display-inline-block padding-left-thirteen"><?php AppUtility::t('Type')?></div>
            <div class="col-md-10 col-sm-10 display-inline-block"><select class="form-control" name="helptype">
                    <option value="video"><?php AppUtility::t('Video') ?></option>
                    <option value="read"><?php AppUtility::t('Read')?></option>
                </select>
            </div>
        </div>
        <div class="col-md-4 col-sm-4 display-inline-block floatleft"><div class="col-md-2 col-sm-2 select-text-margin display-inline-block"><?php AppUtility::t('URL')?></div> <div class="col-md-10 col-sm-10 display-inline-block"> <input class="form-control floatleft" type="text" name="helpurl" size="30" /></div></div>
    </div>
    <?php
    if (count($extref)>0) {
        echo "<div class='col-md-12 col-sm-12 padding-left-zero padding-top-fifteen'>
        <div class='col-md-2 col-sm-2'>Help buttons</div>
        <div class='col-md-10 col-sm-10'>";
        for ($i=0;$i<count($extref);$i++) {
            $extrefpt = explode('!!',$extref[$i]);
            echo '<span>Type</span><span class="padding-left-five"> '.ucfirst($extrefpt[0]).'</span>';
            if ($extrefpt[0]=='video' && count($extrefpt)>2 && $extrefpt[2]==1) {
                echo ' (cc)';
            }
            echo ', <span>URL<span> <span><a href="'.$extrefpt[1].'">'.$extrefpt[1]."</a>
            <span class='padding-left-ten'>Delete?</span> <input class='margin-left-five' type=\"checkbox\" name=\"delhelp-$i\"/></span>";
        }
        echo '</div></div>';
    }
    if ($myRights==100) {
        echo '<div class="col-md-offset-2 col-md-10 col-sm-9 col-sm-offset-3 padding-top-fifteen display-inline-block">
        <div class="col-ms-12 col-sm-12 select-text-margin display-inline-block padding-left-zero">Mark question as deprecated and suggest alternative? <input type="checkbox" name="doreplaceby" ';
        if ($line['replaceby']!=0) {
            echo 'checked="checked"';
        }
        echo '/> </div>
         <div class="col-md-12 col-sm-12  padding-left-zero padding-top-one-em">
        <div class="display-inline-block col-md-5 col-sm-12 padding-left-zero">
        <span class="floatleft select-text-margin">Suggested replacement ID</span>
        <span class="padding-left-one-em"><input class="form-control suggested-replace-id" type="text" size="5" name="replaceby" value="';
        if ($line['replaceby']>0) {
            echo $line['replaceby'];
        }
        echo '"/></span>
        </div>
        <div class="col-md-7 col-sm-12 select-text-margin display-inline-block padding-left-right-zero"> Do not use this unless you know what you\'re doing</div></div>
        </div>';
    }
    if ($line['deleted']==1 && ($myRights==100 || $ownerid==$userId)) {
        echo '<p>This question is currently marked as deleted. <label><input type="checkbox" name="undelete"> Un-delete question</p>';
    }
    ?>
</div>
<div class="col-md-offset-2 col-md-10 col-sm-offset-3 col-sm-10 padding-top-twenty display-inline-block">
    <div class="col-md-12 col-md-12 padding-left-ten">
        <input type=submit value="Save">
        <input class="margin-left-fifteen" type=submit name=test value="Save and Test Question">
    </div>
</div>
    </form>
    </div>
</div>