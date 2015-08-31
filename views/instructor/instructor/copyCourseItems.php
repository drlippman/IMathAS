<?php
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Copy Course Items', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name], 'link_url' => [AppUtility::getHomeURL()  . 'site/index', AppUtility::getHomeURL() . 'instructor/instructor/index?cid=' . $course->id]]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="item-detail-content"></div>
<input type="hidden" id="url" value="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id.'&loadothers=true')?>">
<div class="tab-content shadowBox">
<br>
<div class="align-copy-course">
<?php
    if ($overwriteBody==1)
    {
        echo $message;
    }else
    {
        if(!isset($loadToOthers))
        {

        }
        if(isset($action) && $action == 'selectcalitems')
        {?>

            <form id="qform" method=post action="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id.'&action=copycalitems')?>">
                <input type="hidden" name="courseId" id="url" value="<?php echo $course->id ?>">
            <input type=hidden name=ekey id=ekey value="<?php echo $params['ekey'] ?>">
            <input type=hidden name=ctc id=ctc value="<?php echo $params['ctc'] ?>">
            <h4><?php AppUtility::t('Select Calendar Items to Copy');?></h4>
            <?php AppUtility::t('Check: ');?><a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php AppUtility::t('All');?></a>
            <a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php AppUtility::t('None');?></a>
            <table cellpadding=5 class=gb>
                <thead>
                <tr><th></th><th><?php AppUtility::t('Date');?></th><th><?php AppUtility::t('Tag');?></th><th><?php AppUtility::t('Text');?></th></tr>
                </thead>
                <tbody>
                <?php
                $alt=0;
                for ($i = 0 ; $i<(count($calItems)); $i++) {
                    if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                    ?>
                    <td>
                        <input type=checkbox name='checked[]' value='<?php echo $calItems[$i][0];?>' checked="checked"/>
                    </td>
                    <td class="nowrap"><?php echo date("m/d/Y",$calItems[$i][1]); ?></td>
                    <td><?php echo $calItems[$i][2]; ?></td>
                    <td><?php echo $calItems[$i][3]; ?></td>
                    </tr>
                <?php
                }
                ?>
                </tbody>
            </table>
            <p><?php AppUtility::t('Remove all existing calendar items?');?><input type="checkbox" name="clearexisting" value="1" /></p>
            <p><input type=submit value="Copy Calendar Items"></p>
            </form>
        <?php }else if(isset($action) && $action == 'select'){?>

                <form id="qform" method=post action="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id.'&action=copy')?>">
                    <input type="hidden" name="courseId" id="url" value="<?php echo $course->id ?>">
                <input type=hidden name=ekey id=ekey value="<?php echo $params['ekey'] ?>">
                    <input type=hidden name=ctc id=ctc value="<?php echo $params['ctc'] ?>">
                    <?php AppUtility::t('What to copy: ');?><select name="whattocopy" onchange="updatetocopy(this)">
                        <option value="all"><?php AppUtility::t('Copy whole course');?></option>
                        <option value="select"><?php AppUtility::t('Select items to copy');?></option>
                    </select>
                    <?php
                    if ($params['ekey']=='')
                    {?>
                        &nbsp;<a class="small" target="_blank" href="<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$params['ctc'])?>"><?php AppUtility::t('Preview source course');?></a>
                   <?php }?>
                    <div id="allitemsnote">
                        <p><input type=checkbox name="copyofflinewhole"  value="1"/><?php AppUtility::t('Copy offline grade items');?></p>
                        <p><?php AppUtility::t('Copying the whole course will also copy (and overwrite) course settings, gradebook categories, outcomes, and rubrics.To change these options, choose "Select items to copy" instead.');?></p>
                    </div>
                    <div id="selectitemstocopy" style="display:none;">
                    <h4><?php AppUtility::t('Select Items to Copy');?></h4>
                    <?php AppUtility::t('Check: ');?><a href="#" onclick="return chkAllNone('qform','checked[]',true)"><?php AppUtility::t('All');?></a>
                    <a href="#" onclick="return chkAllNone('qform','checked[]',false)"><?php AppUtility::t('None');?></a>
                        <table cellpadding=5 class=gb>
                            <thead>
                            <?php
                            if ($PicIcons)
                            {?>
                                <tr><th></th><th><?php AppUtility::t('Title');?></th><th><?php AppUtility::t('Summary');?></th></tr>
                      <?php } else {?>
                                <tr><th></th><th><?php AppUtility::t('Type');?></th><th><?php AppUtility::t('Title');?></th><th><?php AppUtility::t('Summary');?></th></tr>
                            <?php }?>
                            </thead>
                            <tbody>
                            <?php
                            $alt=0;

                      for ($i = 0 ; $i<(count($ids)); $i++) {
                                if ($alt==0) {echo "		<tr class=even>"; $alt=1;} else {echo "		<tr class=odd>"; $alt=0;}
                                echo '<td>';
                                if (strpos($types[$i],'Block')!==false) {
                                    echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}' ";
                                    echo "onClick=\"chkgrp(this.form, '{$ids[$i]}', this.checked);\" ";
                                    echo '/>';
                                } else {
                                    echo "<input type=checkbox name='checked[]' value='{$ids[$i]}' id='{$parents[$i]}.{$ids[$i]}' ";
                                    echo '/>';
                                }
                            ?>
                            </td>

                            <?php
                            $tdpad = 16*strlen($prespace[$i]);

                            if ($PicIcons) {
                                echo '<td style="padding-left:'.$tdpad.'px"><img alt="'.$types[$i].'" title="'.$types[$i].'" src="'.$imasroot.'/img/';
                                switch ($types[$i]) {
                                    case 'Calendar': echo $CFG['CPS']['miniicons']['calendar']; break;
                                    case 'InlineText': echo $CFG['CPS']['miniicons']['inline']; break;
                                    case 'LinkedText': echo $CFG['CPS']['miniicons']['linked']; break;
                                    case 'Forum': echo $CFG['CPS']['miniicons']['forum']; break;
                                    case 'Wiki': echo $CFG['CPS']['miniicons']['wiki']; break;
                                    case 'Block': echo $CFG['CPS']['miniicons']['folder']; break;
                                    case 'Assessment': echo $CFG['CPS']['miniicons']['assess']; break;
                                    case 'Drill': echo $CFG['CPS']['miniicons']['drill']; break;
                                }
                                echo '" class="floatleft"/><div style="margin-left:21px">'.$names[$i].'</div></td>';
                            } else {

                                echo '<td>'.$prespace[$i].$names[$i].'</td>';
                                echo '<td>'.$types[$i].'</td>';
                            }
                            ?>
                            <td><?php echo $sums[$i] ?></td>
                            </tr>
                            <?php
                }
                ?>
                            </tbody>
                        </table>
                        <p> </p>
                        <fieldset><legend><?php AppUtility::t('Options');?></legend>
                        <table>
                        <tbody>
                        <tr><td class="r"><?php AppUtility::t('Copy course settings?');?></td><td><input type=checkbox name="copycourseopt"  value="1"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Copy gradebook scheme and categories');?><br/>(<i><?php AppUtility::t('will overwrite current scheme');?></i>)? </td><td>
                        <input type=checkbox name="copygbsetup" value="1"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Set all copied items as hidden to students?');?></td><td><input type="checkbox" name="copyhidden" value="1"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Copy offline grade items?');?></td><td> <input type=checkbox name="copyoffline"  value="1"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Remove any withdrawn questions from assessments?');?></td><td> <input type=checkbox name="removewithdrawn"  value="1" checked="checked"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Use any suggested replacements for old questions?');?></td><td> <input type=checkbox name="usereplaceby"  value="1" checked="checked"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Copy rubrics? ');?></td><td><input type=checkbox name="copyrubrics"  value="1" checked="checked"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Copy outcomes? ');?></td><td><input type=checkbox name="copyoutcomes"  value="1" /></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Select calendar items to copy?');?></td><td> <input type=checkbox name="selectcalitems"  value="1"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Copy "display at top" instructor forum posts? ');?></td><td><input type=checkbox name="copystickyposts"  value="1" checked="checked"/></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Append text to titles?');?></td><td> <input type="text" name="append"></td></tr>
                        <tr><td class="r"><?php AppUtility::t('Add to block:');?></td><td>
                        <?php
                        AppUtility::writeHtmlSelect ("addto",$page_blockSelect['val'],$page_blockSelect['label'],$selectedVal=null,$defaultLabel="Main Course Page",$defaultVal="none",$actions=null);
                        ?>
                            </td></tr>
                        </tbody>
                        </table>
                        </fieldset>
                    </div>
                    <p><input type=submit value="Copy Items"></p>
                </form>
        <?php } else if (isset($loadToOthers)) {?>
                <?php
                    if ($pageHasGroups) {
                        $lastTeacher = 0;
                    $lastgroup = -1;
                    foreach($courseGroupResults as $line)
                    {
                        if ($line['groupid']!=$lastgroup) {
                        if ($lastgroup!=-1)
                        {
                            echo "				</ul>\n			</li>\n";
                            echo "			</ul>\n		</li>\n";
                            $lastTeacher = 0;
                        }?>
                <li class=lihdr>
                    <span class=dd>-</span>
					<span class=hdr onClick="toggle('g<?php echo $line['groupid'] ?>')">
						<span class=btn id="bg<?php echo $line['groupid'] ?>">+</span>
					</span>
					<span class=hdr onClick="toggle('g<?php echo $line['groupid'] ?>')">
						<span id="ng<?php echo $line['groupid'] ?>" ><?php echo $grpNames[$line['groupid']] ?></span>
					</span>
                    <ul class=hide id="g<?php echo $line['groupid'] ?>">

                        <?php
                        $lastgroup = $line['groupid'];
                        }
                        if ($line['userid']!=$lastTeacher) {
                        if ($lastTeacher!=0) {
                            echo "				</ul>\n			</li>\n";
                        }
                        ?>
                        <li class=lihdr>
                            <span class=dd>-</span>
					<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
						<span class=btn id="b<?php echo $line['userid'] ?>">+</span>
					</span>
					<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
						<span id="n<?php echo $line['userid'] ?>" ><?php echo $line['LastName'] . ", " . $line['FirstName'] . "\n" ?>
						</span>
					</span>
                            <a href="mailto:<?php echo $line['email'] ?>"><?php AppUtility::t('Email');?></a>
                            <ul class=hide id="<?php echo $line['userid'] ?>">
                                <?php
                                $lastteacher = $line['userid'];
                                }
                                ?>
                                <li>
                                    <span class=dd>-</span>
                                    <?php
                                    echo '<input type="radio" name="ctc" value="'.$line['id'].'" '.(($line['copyrights']<2)?'class="copyr"':'').'>';
                                    echo $line['name'];
                                    if ($line['copyrights']<2) {
                                        echo "&copy;\n";
                                    } else {?>
                                        <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$params['ctc'])?>" target="_blank"><?php AppUtility::t('Preview')?></a>
                                    <?php }
                                    ?>
                                </li>
                                <?php
                                }
                                ?>

                            </ul>
                        </li>
                    </ul>
                </li>
                </ul>
                </li>
                    <?php
                    } else{
                        echo '<li>No other users</li>';
                    }

        } else {?>
            <script>


                var othersloaded = false;


                var ahahurl = $('#url').val();
                function loadothers() {
                    if (!othersloaded) {
                        //basicahah(ahahurl, "other");
                        $.ajax({url:ahahurl, dataType:"html"}).done(function(resp)
                        {
                            $('#other').html(resp);
                            $("#other input:radio").change(function() {
                                if ($(this).hasClass("copyr")) {
                                    $("#ekeybox").show();
                                } else {
                                    $("#ekeybox").hide();
                                }
                            });
                        });
                        othersloaded = true;
                    }
                }
            </script>
            <h4><?php AppUtility::t('Select a course to copy items from');?></h4>
            <form method=post action="<?php echo AppUtility::getURLFromHome('instructor','instructor/copy-course-items?cid='.$course->id.'&action=select')?>">
                <input type="hidden" name="courseId" id="url" value="<?php echo $course->id ?>">
                <?php AppUtility::t('Course List');?>
                <ul class=base>
                    <li><span class=dd>-</span>
                        <input type=radio name=ctc value="<?php echo $course->id ?>" checked=1><?php AppUtility::t('This Course');?></li>
                    <li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('mine')">
					<span class=btn id="bmine">+</span>
				</span>
				<span class=hdr onClick="toggle('mine')">
					<span id="nmine" ><?php AppUtility::t('My Courses');?></span>
				</span>
                        <ul class=hide id="mine">
                            <?php
                            //my items
                            for ($i=0;$i<count($page_mineList['val']);$i++) {
                                ?>

                                <li><span class=dd>-</span>
                                    <input type=radio name=ctc value="<?php echo $page_mineList['val'][$i] ?>"><?php echo $page_mineList['label'][$i] . "\n" ?>
                                </li>
                            <?php
                            }
                            ?>
                        </ul>
                    </li>
                    <li class=lihdr><span class=dd>-</span>
				<span class=hdr onClick="toggle('grp')">
					<span class=btn id="bgrp">+</span>
				</span>
				<span class=hdr onClick="toggle('grp')">
					<span id="ngrp" ><?php AppUtility::t("My Group's Courses")?></span>
				</span>
                        <ul class=hide id="grp">

                            <?php
                            //group's courses
                            if (count($courseTreeResult)>0) {
                            foreach($courseTreeResult as $line)
                            {
                            if ($line['userid']!=$lastTeacher) {
                            if ($lastTeacher!=0) {
                                echo "				</ul>\n			</li>\n";
                            }
                            ?>
                            <li class=lihdr>
                                <span class=dd>-</span>
						<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
							<span class=btn id="b<?php echo $line['userid'] ?>">+</span>
						</span>
						<span class=hdr onClick="toggle(<?php echo $line['userid'] ?>)">
							<span id="n<?php echo $line['userid'] ?>"><?php echo $line['LastName'] . ", " . $line['FirstName'] . "\n" ?>
							</span>
						</span>
                                <a href="mailto:<?php echo $line['email'] ?>"><?php AppUtility::t('Email')?></a>
                                <ul class=hide id="<?php echo $line['userid'] ?>">
                                    <?php
                                    $lastTeacher = $line['userid'];
                                    }
                                    ?>
                                    <li>
                                        <span class=dd>-</span>
                                        <?php
                                        echo '<input type="radio" name="ctc" value="'.$line['id'].'" '.(($line['copyrights']<2)?'class="copyr"':'').'>';
                                        echo $line['name'];
                                        if ($line['copyrights']<1) {
                                            echo "&copy;\n";
                                        } else {?>
                                            <a href="<?php echo AppUtility::getURLFromHome('instructor','instructor/index?cid='.$line['id'])?>" target=\"_blank\">Preview</a>
                                        <?php }
                                        ?>
                                    </li>
                                    <?php
                                    }
                                    echo "						</ul>\n					</li>\n";
                                    echo "				</ul>			</li>\n";
                                    } else {
                                        echo "				</ul>\n			</li>\n";
                                    }
                                    ?>
                                    <li class=lihdr>
                                        <span class=dd>-</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span class=btn id="bother">+</span>
				</span>
				<span class=hdr onClick="toggle('other');loadothers();">
					<span id="nother" ><?php AppUtility::t("Other's Courses");?></span>
				</span>
                                        <ul class=hide id="other">

                                            <?php

                                            echo "<li>".AppUtility::t('Loading...')."</li>			</ul>\n		</li>\n";


                                            if (count($courseTemplateResults)>0) {
                                            ?>
                                            <li class=lihdr>
                                                <span class=dd>-</span>
			<span class=hdr onClick="toggle('template')">
				<span class=btn id="btemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('template')">
				<span id="ntemplate" ><?php AppUtility::t('Template Courses')?></span>
			</span>
                                                <ul class=hide id="template">

                                                    <?php
                                                    foreach($courseTemplateResults as $row)
                                                    {
                                                        ?>
                                                        <li>
                                                            <span class=dd>-</span>
                                                            <?php
                                                            echo '<input type="radio" name="ctc" value="'.$row['id'].'" '.(($row['copyrights']<2)?'class="copyr"':'').'>';
                                                            echo $row['name'];
                                                            if ($row['copyrights']<2) {
                                                                echo "&copy;\n";
                                                            } else {?>
                                                                <a href="<?php AppUtility::getURLFromHome('instructor','instructor/index?cid='.$row['id'])?>" target="_blank"><?php AppUtility::t('Preview')?></a>
                                                            <?php }
                                                            ?>
                                                        </li>

                                                    <?php
                                                    }
                                                    echo "			</ul>\n		</li>\n";
                                                    }
                                                    if (count($groupTemplateResults)>0) {
                                                    ?>
                                                    <li class=lihdr>
                                                        <span class=dd>-</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span class=btn id="bgtemplate">+</span>
			</span>
			<span class=hdr onClick="toggle('gtemplate')">
				<span id="ngtemplate" ><?php AppUtility::t('Group Template Courses')?></span>
			</span>
                                                        <ul class=hide id="gtemplate">

                                                            <?php
                                                            foreach($groupTemplateResults as $row) {
                                                                ?>
                                                                <li>
                                                                    <span class=dd>-</span>
                                                                    <input type=radio name=ctc value="<?php echo $row['id'] ?>">
                                                                    <?php echo $row['name'] ?>
                                                                    <?php
                                                                    if ($row['copyrights ']<1) {
                                                                        echo "&copy;\n";
                                                                    } else {?>
                                                                        <a href="<?php AppUtility::getURLFromHome('instructor','instructor/index?cid='.$row['id'])?>" target=\"_blank\">Preview</a>
                                                                    <?php }
                                                                    ?>
                                                                </li>

                                                            <?php
                                                            }
                                                            echo "			</ul>\n		</li>\n";
                                                            }
                                                            ?>
                                                        </ul>

                                                        <p id="ekeybox" style="display:none;"><?php AppUtility::t('For courses marked with')?>
                                                             &copy;<?php AppUtility::t(', you must supply the course enrollment key to show permission to copy the course.')?><br/>
                                                            <?php AppUtility::t('Enrollment key: ')?><input type=text name=ekey id=ekey size=30></p>
                                                        <input type=submit value="<?php AppUtility::t('Select Course Items')?>">
                                                        <p>&nbsp;</p>
            </form>
        <?php }?>
 <?php }?>
</div>
</div>


<script>

function chkgrp(frm, arr, mark)
{
        var els = frm.getElementsByTagName("input");
        for (var i = 0; i < els.length; i++) {
            var el = els[i];
            if (el.type=='checkbox' && (el.id.indexOf(arr+'.')==0 || el.id.indexOf(arr+'-')==0 || el.id==arr)) {
                el.checked = mark;
            }
        }
}
function updatetocopy(el) {
    if (el.value=="all") {
        $("#selectitemstocopy").hide();$("#allitemsnote").show();
    } else {
        $("#selectitemstocopy").show();$("#allitemsnote").hide();
    } }

$(function() {
    $("input:radio").change(function() {
        if ($(this).hasClass("copyr")) {
            $("#ekeybox").show();
        } else {
            $("#ekeybox").hide();
        }
    });
});
function chkAllNone(frmid, arr, mark, skip){
    var frm = document.getElementById(frmid);
    for (i = 0; i <= frm.elements.length; i++) {
        try{
            if ((arr=='all' && frm.elements[i].type=='checkbox') || frm.elements[i].name == arr) {
                if (skip && frm.elements[i].className==skip) {
                    frm.elements[i].checked = !mark;
                } else {
                    frm.elements[i].checked = mark;
                }

            }
        } catch(er) {}
    }
    return false;
}

</script>