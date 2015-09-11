<?php
use yii\helpers\Html;
use app\components\AppUtility;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
$this->title = AppUtility::t(' Add Questions',false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name,'Add/Remove Question'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'instructor/instructor/index?cid='.$course->id.'&aid='.$params['aid'] ,AppUtility::getHomeURL().'question/question/add-questions?cid='.$course->id.'&aid='.$params['aid']] ,'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $addMode ?><?php echo AppUtility::t(' QuestionSet Question',false);?></div>
        </div>
        <div class="pull-left header-btn hide-hover">
<!--            <a href="#"id="mess" class="btn btn-primary1 pull-right  btn-color"><img class = "small-icon" src="--><?php //echo AppUtility::getAssetURL()?><!--img/newzmessg.png">&nbsp;Send Message</a>-->
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../instructor/instructor/_toolbarTeacher", ['course' => $course, 'section' => '']);?>
</div>
<div class="tab-content shadowBox padding-bottom-thirty">
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

    if (isset($params['qid'])) {
        echo "<div class='col-md-12 margin-left-minus-four'><p><a href=\"mod-data-set?id={$params['id']}&cid=$course->id&aid={$params['aid']}&template=true&makelocal={$params['qid']}\">Template this question</a> for use in this assessment.  ";
        echo "This will let you modify the question for this assessment only without affecting the library version being used in other assessments.</p></div>";
    }
    if (!$myq) {
        echo "<p>This question is not set to allow you to modify the code.  You can only view the code and make additional library assignments</p>";
    }
    ?>
    <form class="margin-left-minus-twenty" enctype="multipart/form-data" method=post action="mod-data-set?process=true<?php
    if (isset($params['cid'])) {
        echo "&cid=$course->id";
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
    <div class="col-md-12 margin-top-twenty">
       <div class="col-md-2"> Description: </div>
        <div class="col-md-10"><textarea class="form-control" cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['description'];?></textarea></div>
    </div>
    <div class="col-md-12 margin-top-twenty">
        <div class="col-md-2">Author:</div>
        <div class="col-md-10"><?php echo $line['author']; ?> <input type="hidden" name="author" value="<?php echo $author; ?>"></div>
    </div>
    <div class="col-md-12 margin-top-twenty">
        <?php
        if (!isset($line['ownerid']) || isset($params['template']) || $line['ownerid']==$userId || ($line['userights']==3 && $line['groupid']==$groupId) || $isAdmin || ($isGrpAdmin && $line['groupid']==$groupId)) {
            echo '<div class="col-md-2 select-text-margin">Use Rights:</div> <div class="col-md-10"><select class="width-sixty-per form-control" name="userights" id="userights">';
            echo "<option value=\"0\" ";
            if ($line['userights']==AppConstant::NUMERIC_ZERO) {echo "SELECTED";}
            echo ">Private</option>\n";
            echo "<option value=\"2\" ";
            if ($line['userights']==AppConstant::NUMERIC_TWO) {echo "SELECTED";}
            echo ">Allow use by all</option>\n";
            echo "<option value=\"3\" ";
            if ($line['userights']==AppConstant::NUMERIC_THREE) {echo "SELECTED";}
            echo ">Allow use by all and modifications by group</option>\n";
            echo "<option value=\"4\" ";
            if ($line['userights']==AppConstant::NUMERIC_FOUR) {echo "SELECTED";}
            echo ">Allow use by all and modifications by all</option>\n";
            echo '</select></div><br/>'; ?>
    </div>
    <div class="col-md-12 margin-top-twenty">
            <?php
            echo '<div class="col-md-2 select-text-margin">License:</div> <div class="col-md-10"><select class="width-sixty-per form-control" name="license" id="license" onchange="checklicense()">';
            echo '<option value="0" '.($line['license']==AppConstant::NUMERIC_ZERO?'selected':'').'>Copyrighted</option>';
            echo '<option value="1" '.($line['license']==AppConstant::NUMERIC_ONE?'selected':'').'>IMathAS / WAMAP / MyOpenMath Community License (GPL + CC-BY)</option>';
            echo '<option value="2" '.($line['license']==AppConstant::NUMERIC_TWO?'selected':'').'>Public Domain</option>';
            echo '<option value="3" '.($line['license']==AppConstant::NUMERIC_THREE?'selected':'').'>Creative Commons Attribution-NonCommercial-ShareAlike</option>';
            echo '<option value="3" '.($line['license']==AppConstant::NUMERIC_FOUR?'selected':'').'>Creative Commons Attribution-ShareAlike</option>';
            echo '</select><span id="licensewarn" style="color:red;font-size:80%;"></span> </div>';
            ?>
    </div>
    <div class="col-md-12 margin-top-twenty">
            <?php
            if ($line['otherattribution']=='') {
                echo '<br/><a class="margin-left-fifteen" href="#" onclick="$(\'#addattrspan\').show();$(this).hide();return false;">Add additional attribution</a>';
                echo '<span id="addattrspan" style="display:none;">';
            } else {
                echo '<br/><span id="addattrspan">';
            }
            echo '<div class="floatleft margin-left-fifteen select-text-margin">Additional Attribution:</div> <div class="floatleft margin-left-three-pt-eight-per"><input class="form-control" type="text" size="80" name="addattr" value="'.htmlentities($line['otherattribution']).'"/></div>';
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
        <div class="col-md-12 margin-top-twenty">
           <div class="col-md-2 select-text-margin"> My library assignments:</div> <div class="col-md-1 select-text-margin"><span id="libnames"><?php echo $lnames;?></span></div><input type=hidden name="libs" id="libs" size="10" value="<?php echo $inlibs;?>">
           <div class="col-md-2"> <input type=button value="Select Libraries" onClick="libselect()"></div>
        </div>
        <div class="col-md-12 margin-top-twenty">
           <div class="col-md-2 select-text-margin"> Question type:</div> <div class="col-md-10"> <select class="width-sixty-per form-control" name=qtype <?php if (!$myq) echo "disabled=\"disabled\"";?>>
                <option value="number" <?php if ($line['qtype']=="number") {echo "SELECTED";} ?>>Number</option>
                <option value="calculated" <?php if ($line['qtype']=="calculated") {echo "SELECTED";} ?>>Calculated Number</option>
                <option value="choices" <?php if ($line['qtype']=="choices") {echo "SELECTED";} ?>>Multiple-Choice</option>
                <option value="multans" <?php if ($line['qtype']=="multans") {echo "SELECTED";} ?>>Multiple-Answer</option>
                <option value="matching" <?php if ($line['qtype']=="matching") {echo "SELECTED";} ?>>Matching</option>
                <option value="numfunc" <?php if ($line['qtype']=="numfunc") {echo "SELECTED";} ?>>Function</option>
                <option value="string" <?php if ($line['qtype']=="string") {echo "SELECTED";} ?>>String</option>
                <option value="essay" <?php if ($line['qtype']=="essay") {echo "SELECTED";} ?>>Essay</option>
                <option value="draw" <?php if ($line['qtype']=="draw") {echo "SELECTED";} ?>>Drawing</option>
                <option value="ntuple" <?php if ($line['qtype']=="ntuple") {echo "SELECTED";} ?>>N-Tuple</option>
                <option value="calcntuple" <?php if ($line['qtype']=="calcntuple") {echo "SELECTED";} ?>>Calculated N-Tuple</option>
                <option value="matrix" <?php if ($line['qtype']=="matrix") {echo "SELECTED";} ?>>Numerical Matrix</option>
                <option value="calcmatrix" <?php if ($line['qtype']=="calcmatrix") {echo "SELECTED";} ?>>Calculated Matrix</option>
                <option value="interval" <?php if ($line['qtype']=="interval") {echo "SELECTED";} ?>>Interval</option>
                <option value="calcinterval" <?php if ($line['qtype']=="calcinterval") {echo "SELECTED";} ?>>Calculated Interval</option>
                <option value="complex" <?php if ($line['qtype']=="complex") {echo "SELECTED";} ?>>Complex</option>
                <option value="calccomplex" <?php if ($line['qtype']=="calccomplex") {echo "SELECTED";} ?>>Calculated Complex</option>
                <option value="file" <?php if ($line['qtype']=="file") {echo "SELECTED";} ?>>File Upload</option>
                <option value="multipart" <?php if ($line['qtype']=="multipart") {echo "SELECTED";} ?>>Multipart</option>
                <option value="conditional" <?php if ($line['qtype']=="conditional") {echo "SELECTED";} ?>>Conditional</option>
            </select></div>
        </div>
        <div class="col-md-12 margin-top-twenty">
           <div class="col-md-2"> <a href="#" onclick="window.open('<?php echo AppUtility::getAssetURL();?>docs/help.php?section=writingquestions','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Writing Questions Help</a></div>
           <div class="col-md-3"> <a href="#" onclick="window.open('<?php echo AppUtility::getAssetURL();?>libs/libhelp.php','Help','width='+(.35*screen.width)+',height='+(.7*screen.height)+',toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width*.6))">Macro Library Help</a>
           </div>
        </div>
        <div class="col-md-12 margin-top-twenty">
            <div class="col-md-2 select-text-margin">Switch to:</div>
            <div class="col-md-10">
                <input type=button id=entrymode value="<?php if ($twobx) {echo "4-box entry";} else {echo "2-box entry";}?>" onclick="swapentrymode()" <?php if ($line['qcontrol']!='' || $line['answer']!='') echo "DISABLED"; ?>/>
                    <?php if (!isset($params['id'])) {
                    echo ' <a href="mod-tutorial-question?'.$_SERVER['QUERY_STRING'].'">Tutorial Style editor</a>';
                    }?>
            </div>
        </div>
        <div id=ccbox class="col-md-12 margin-top-twenty">
          <div class="col-md-12">
              <div class="floatleft select-text-margin"> Common Control: <span class=pointer onclick="incboxsize('control')">[+]</span><span class=pointer onclick="decboxsize('control')">[-]</span></div>
              <div class="floatleft margin-left-thirty-eight">  <input type=submit value="Save">
                     <input type=submit name=test value="Save and Test Question">
              </div>
          </div>
        </div>
        <div class="col-md-12 margin-top-twenty">
         <div class="col-md-10 col-md-offset-2">  <textarea class="form-control" cols=60 rows=<?php if ($twobx) {echo min(35,max(20,substr_count($line['control'],"\n")+1));} else {echo "10";}?> id=control name=control <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['control']);?></textarea></div>
        </div>
        <div id=qcbox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
                <div class="col-md-12 margin-left-sixteen margin-top-twenty">
                   <div class="floatleft select-text-margin"> Question Control: <span class=pointer onclick="incboxsize('qcontrol')">[+]</span><span class=pointer onclick="decboxsize('qcontrol')">[-]</span></div>
                   <div class="floatleft margin-left-thirty-eight"> <input type=submit value="Save">
                    <input type=submit name=test value="Save and Test Question">
                   </div>
                </div>
            <div class="col-md-10 col-md-offset-2 margin-top-twenty"> <textarea class="margin-left-ten form-control" style="width: 100%" cols=60 rows=10 id=qcontrol name=qcontrol <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['qcontrol'];?></textarea> </div>
        </div>
        <div id='qtbox' class="col-md-12 margin-top-twenty">
           <div class="col-md-12">
               <div class="floatleft select-text-margin">Question Text: <span class=pointer onclick="incboxsize('qtext')">[+]</span><span class=pointer onclick="decboxsize('qtext')">[-]</span></div>
               <div class="floatleft margin-left-five-ptfour-per"><input type="button" onclick="toggleeditor('qtext')" value="Toggle Editor"/>
                    <input type=submit value="Save">
                    <input type=submit name=test value="Save and Test Question">
               </div>
           </div>
        </div>
        <div class="col-md-12 margin-top-twenty">
           <div class="col-md-10 col-md-offset-2"> <textarea class="form-control" cols=60 rows=<?php echo min(35,max(10,substr_count($line['qtext'],"\n")+1));?> id="qtext" name="qtext" <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['qtext']);?></textarea></div>
        </div>
        <div id=abox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
            <div class="col-md-12 margin-left-sixteen margin-top-twenty">
                <div class="floatleft select-text-margin"> Answer: <span class=pointer onclick="incboxsize('answer')">[+]</span><span class=pointer onclick="decboxsize('answer')">[-]</span></div>
                <div class="floatleft margin-left-eight-pt-five-per">
                    <input type=submit value="Save">
                    <input type=submit name=test value="Save and Test Question">
                </div>
            </div>
            <div class="col-md-10 col-md-offset-2 margin-top-twenty"> <textarea class="margin-left-ten form-control" style="width: 100%" cols=60 rows=10 id=answer name=answer <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $line['answer'];?></textarea></div>
        </div>

        <?php
        if ($line['solution']=='') {
            echo '<div class="col-md-12 left-fifteen margin-top-twenty"><a href="#" onclick="$(this).parent().hide();$(\'#solutionwrapper\').show();return false;">Add a detailed solution</a></div>';
            echo '<div id="solutionwrapper" class="col-md-12" style="display:none;">';
        } else {
            echo '<div id="solutionwrapper">';
        }
        ?>
    <div class="col-md-12 margin-top-twenty">
       <div class="floatleft select-text-margin"> Detailed Solution:
         <span class=pointer onclick="incboxsize('solution')">[+]</span><span class=pointer onclick="decboxsize('solution')">[-]</span>
       </div>
       <div class="floatleft margin-left-thirty-eight">
            <input type="button" onclick="toggleeditor('solution')" value="Toggle Editor"/>
            <input type=submit value="Save">
            <input type=submit name=test value="Save and Test Question">
       </div>
    </div>
    <div class="col-md-12">
        <div class="col-md-10 col-md-offset-2 right-ten margin-top-twenty">
            <input type="checkbox" name="usesrand" value="1" <?php if (($line['solutionopts']&1)==1) {echo 'checked="checked"';};?>
                   onclick="$('#userandnote').toggle()">
            Uses random variables from the question.
            <span id="userandnote" <?php if (($line['solutionopts']&1)==1) {echo 'style="display:none;"';}?>>
            <i>Be sure to include the question you are solving in the text</i>
            </span><br/>
            <input type="checkbox" name="useashelp" value="2" <?php if (($line['solutionopts']&2)==2) {echo 'checked="checked"';};?>>
            Use this as a "written example" help button<br/>
            <input type="checkbox" name="usewithans" value="4" <?php if (($line['solutionopts']&4)==4) {echo 'checked="checked"';};?>>
            Display with the "Show Answer"<br/>
        <textarea class="form-control margin-top-twenty width-hundread-three-per" cols=60 rows=<?php echo min(35,max(10,substr_count($line['solution'],"\n")+1));?> id="solution" name="solution" <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo str_replace(array(">","<"),array("&gt;","&lt;"),$line['solution']);?></textarea>
        <?php echo '</div></div></div>' ?>

<div id=imgbox class="col-md-12">
    <input type="hidden" name="MAX_FILE_SIZE" value="500000" />
   <div class="col-md-12 margin-top-twenty right-ten">
       <div class="col-md-2 right-five"> Image file:</div>
       <div class="col-md-3"> <input class="image-file-input" type="file" name="imgfile"/></div>
       <div class="col-md-3">
           <span class="select-text-margin">assign to variable:</span>
          <div class="col-md-6 floatright"> <input class="form-control" type="text" name="newimgvar" size="6"/></div>
       </div>
       <div class="col-md-4">
            <div class="col-md-3 select-text-margin left-eight">Description:</div>
            <div class="col-md-9 padding-right-zero"><input class="margin-left-twenty-seven form-control" type="text" size="20" name="newimgalt" value=""/></div>
       </div>
   </div>
    <?php
    if (isset($images['vars']) && count($images['vars'])>0) {
        echo "Images:<br/>\n";
        foreach ($images['vars'] as $id=>$var) {
            if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                $urlimg = $urlmode."s3.amazonaws.com/{$GLOBALS['AWSbucket']}/qimages/{$images['files'][$id]}";
            } else {
                $urlimg = AppUtility::getAssetURL()."Uploads/qimages/{$images['files'][$id]}";
            }
            echo "Variable: <input type=\"text\" name=\"imgvar-$id\" value=\"\$$var\" size=\"10\"/> <a href=\"$urlimg\" target=\"_blank\">View</a> ";
            echo "Description: <input type=\"text\" size=\"20\" name=\"imgalt-$id\" value=\"{$images['alttext'][$id]}\"/> Delete? <input type=checkbox name=\"delimg-$id\"/><br/>";
        }
    }
    ?>
    <div class="col-md-12 right-ten margin-top-twenty">
        <div class="col-md-2 right-five select-text-margin">Help button: </div>
        <div class="col-md-4 padding-left-zero">
            <div class="col-md-2 select-text-margin">Type:</div>
            <div class="col-md-10"><select class="form-control" name="helptype">
                    <option value="video">Video</option>
                    <option value="read">Read</option>
                </select>
            </div>
        </div>
        <div class="col-md-4"><div class="col-md-2 select-text-margin">URL:</div> <div class="col-md-10"> <input class="form-control floatleft" type="text" name="helpurl" size="30" /></div></div>
    </div>
    <?php
    if (count($extref)>0) {
        echo "Help buttons:<br/>";
        for ($i=0;$i<count($extref);$i++) {
            $extrefpt = explode('!!',$extref[$i]);
            echo 'Type: '.ucfirst($extrefpt[0]);
            if ($extrefpt[0]=='video' && count($extrefpt)>2 && $extrefpt[2]==1) {
                echo ' (cc)';
            }
            echo ', URL: <a href="'.$extrefpt[1].'">'.$extrefpt[1]."</a>.  Delete? <input type=\"checkbox\" name=\"delhelp-$i\"/><br/>";
        }
    }
    if ($myRights==100) {
        echo '<div class="col-md-12 margin-top-twenty"><div class="floatleft select-text-margin">Mark question as deprecated and suggest alternative? <input type="checkbox" name="doreplaceby" ';
        if ($line['replaceby']!=0) {
            echo 'checked="checked"';
        }
        echo '/> </div> <div class="floatleft margin-left-thirty-eight">Suggested replacement ID: <input class="form-control suggested-replace-id" type="text" size="5" name="replaceby" value="';
        if ($line['replaceby']>0) {
            echo $line['replaceby'];
        }
        echo '"/></div>  <div class="floatleft margin-left-thirty-eight select-text-margin"> Do not use this unless you know what you\'re doing</div></div>';
    }
    if ($line['deleted']==1 && ($myRights==100 || $ownerid==$userId)) {
        echo '<p>This question is currently marked as deleted. <label><input type="checkbox" name="undelete"> Un-delete question</p>';
    }
    ?>
</div>
<div class="col-md-12 save-question margin-left-fifteen margin-top-twenty">
    <input type=submit value="Save">
    <input type=submit name=test value="Save and Test Question">
</div>
    </form>
    </div>
</div>