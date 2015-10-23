<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t($pagetitle, false);
$this->params['breadcrumbs'][] = $this->title;
?>
<?php $imasroot = AppUtility::getAssetURL();?>
<!--Get current time-->
<div class="item-detail-header">
    <?php if(!empty($curBreadcrumb)){?>
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), AppUtility::t('Admin', false), $curBreadcrumb], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getURLFromHome('admin','admin/index')]]); ?>
    <?php }else{ ?>
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getURLFromHome('admin','admin/index')]]); ?>
    <?php } ?>
</div>
<!--Course name-->
<div class="title-container">
    <div class="row">
        <div class="col-sm-12">
            <div class=" col-sm-10" style="right: 30px;">
                <div class="vertical-align title-page"><?php echo AppUtility::t($pagetitle) ?><a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL().'docs/help.php?section=managequestionset' ?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a></div>
            </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox manage-question-set-shadowbox margin-top-fourty">
    <?php
    $address = AppUtility::getHomeURL().'question/question';

    if ($overwriteBody==1) {
    echo $body;
    } else {
    ?>
    <script type="text/javascript">
        function previewq(formn,loc,qn) {
            var addr = '<?php AppUtility::getHomeURL() ?>test-question?cid=<?php echo $cid ?>&checked=0&qsetid='+qn+'&loc=qo'+loc+'&formn='+formn;
            previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
            previewpop.focus();
        }

        var baseaddr = '<?php echo $address ?>';

        function doaction(todo,id) {
            var addrmod = baseaddr+'/mod-data-set?cid=<?php echo $cid ?>&id=';
            var addrtemp = baseaddr+'/mod-data-set?cid=<?php echo $cid ?>&template=true&id=';
            var addrmq = baseaddr+'/manage-question-set?cid=<?php echo $cid ?>';
            if (todo=="mod") {
                addr = addrmod+id;
            } else if (todo=="temp") {
                addr = addrtemp+id;
            } else if (todo=="tr") {
                addr = addrmq+'&transfer='+id;
            }

            if (todo == 'del'){
                var html ='<div><p>Are you SURE you want to delete this question from the Question Set?</p></div>';
                html +='<div><p> This will make it unavailable to all users.</p></div>';
                html +='<div><p>If it is currently being used in an assessment, it will mess up that assessment.</p></div>';
                $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                    modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                    width: 'auto', resizable: false,
                    closeText: "hide",
                    buttons: {
                        "Cancel": function () {
                            $(this).dialog('destroy').remove();
                            $('.form-control-for-question').val("0");
                            return false;
                        },
                        "confirm": function () {
                            $(this).dialog("close");
                            addr = addrmq+'&remove='+id+'&confirmed=true';
                            window.location= addr;
                            return true;
                        }
                    },
                    close: function (event, ui) {
                        $(this).remove();
                    },
                    open: function(){
                        jQuery('.ui-widget-overlay').bind('click',function(){
                            jQuery('#dialog').dialog('close');
                        })
                    }
                });
            }else{
                window.location = addr;
            }
        }

        var curlibs = '<?php echo $searchlibs ?>';

        function libselect() {
            window.open('library-tree?cid=<?php echo $cid ?>&libtree=popup&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
        }

    </script>

    <?php
    if (isset($transfer)) {
    ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>&transfer=<?php echo $transfer ?>">
            <div class="col-md-12 padding-left-thirty padding-top-five"><div class="col-md-12 padding-left-zero">
                <span class="floatleft select-text-margin">Transfer to</span>
<div class="col-md-3 floatleft">
    <?php AppUtility::writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>
</div>
                </div>
            <div class="col-md-12 padding-top-fifteen padding-left-zero padding-left-one">
                <input type=submit value="Transfer">
                <input type=button value="Nevermind" class="margin-left-fifteen secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </div>
                </div>
        </form>

    <?php
    } else if (isset($params['chglib'])) {
    ?>

        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name=chglib value="true">
            <input type=hidden name=qtochg value="<?php echo $clist ?>">
            <div class="col-md-12 padding-left-thirty">
            <div class="col-sm-12 padding-left-zero">What do you want to do with these questions?</div>
            <div class="col-sm-12 padding-left-zero margin-top-ten"><input type=radio name="action" value="0" onclick="chglibtoggle(this)" checked="checked"/><span class="margin-left-five">Add to libraries, keeping any existing library assignments</span></div>
            <div class="col-sm-12 padding-left-zero margin-top-five"><input type=radio name="action" value="1" onclick="chglibtoggle(this)"/><span class="margin-left-five">Add to libraries, removing existing library assignments</span></div>
            <div class="col-sm-12 padding-left-zero margin-top-five"><input type=radio name="action" value="2" onclick="chglibtoggle(this)"/><span class="margin-left-five">Remove library assignments</span></div>
            <div class="col-sm-12 padding-left-zero margin-top-five" id="chglibhelp" style="font-weight: bold;">
                <span>Select libraries to add these questions to.</span>
            </div>

            <?php $libtreeshowchecks = false; include("questionLibraries.php"); ?>


            <div class="col-sm-12 padding-left-zero margin-top-ten margin-bottom-fifteen">
                <input type=submit value="Make Changes">
                <input type=button value="Nevermind" class="secondarybtn margin-left-ten" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </div>
            </div>
        </form>
    <?php
    } else if (isset($params['template'])) {
    ?>

        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name=template value="true">

            <p>
                This page will create new copies of these questions.  It is recommended that you place these new copies in a
                different library that the questions are currently are in, so you can distinguish the new versions from the originals.
            </p>
            <p>Select the library into which to put the new copies:</p>

            <input type=hidden name=qtochg value="<?php echo $clist ?>">

            <?php include("libtree.php"); ?>

            <p>
                <input type=submit value="Template Questions">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else if (isset($params['license'])) {
        ?>

        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name="license" value="true">

            <input type=hidden name=qtochg value="<?php echo $clist ?>">
                <div class="col-sm-12 manage-qset-cng-licen">
                    <div class="padding-twenty col-sm-12 margin-bottom-ten text-background-color background-text-padding">
                        <div class="col-sm-12 padding-left-zero">This will allow you to change the license or attribution on questions, if you have the rights to change them</div>
                        <div class="col-sm-12 padding-left-zero">Note:  Be cautious when changing licenses or attribution on questions.  Some important things to note
                            <ul>
                                <li>If questions are currently copyrighted or contain copyrighted content, you CAN NOT change the license
                                    unless you have removed all copyrighted material from the question.</li>
                                <li>If questions are licensed under the IMathAS Community License or a Creative Commons license, you CAN NOT
                                    change the license unless you are the creator of the questions and all questions it was previously derived from.</li>
                                <li>If the question currently has additional attribution listed, you CAN NOT remove that attribution unless
                                    you have removed from the question all parts that require the attribution.</li>
                            </ul>
                        </div>
                    </div>
                    <div style="color:red;">
                        In short, you should only be changing license if the questions are your original works, not built on top of existing
                        community work.
                    </div>
                    <div class="margin-top-fifteen col-sm-12 padding-left-zero">
                        <span class="col-sm-2 padding-left-zero select-text-margin">License:</span>
                        <span class="display-inline-block col-sm-6 padding-left-zero">
                            <select class="form-control" name="sellicense">
                                <option value="-1">Do not change license</option>
                                <option value="0">Copyrighted</option>
                                <option value="1">IMathAS / WAMAP / MyOpenMath Community License</option>
                                <option value="2">Public Domain</option>
                                <option value="3">Creative Commons Attribution-NonCommercial-ShareAlike</option>
                                <option value="4">Creative Commons Attribution-ShareAlike</option>
                            </select>
                        </span>
                    </div>
                    <div class="margin-top-fifteen col-sm-12 padding-left-zero">
                        <span class="col-sm-2 padding-left-zero select-text-margin">Other Attribution</span>
                        <span class="display-inline-block col-sm-6 padding-left-zero">
                            <select class="form-control" name="otherattribtype">
                                <option value="-1">Do not change attribution</option>
                                <option value="0">Replace existing attribution</option>
                                <option value="1">Append to existing attribution</option>
                            </select>
                        </span>
                    </div>
                    <div class="margin-top-fifteen col-sm-12 padding-left-zero">
                        <span class="col-sm-2 padding-left-zero select-text-margin">Additional Attribution</span>
                        <span class="col-sm-6 padding-left-zero">
                            <input class="form-control display-inline-block" type="text" size="80" name="addattr" />
                        </span>
                    </div>
                    <div class="col-sm-6 margin-top-fifteen padding-left-zero">
                        <input type=submit value="Change License / Attribution">
                        <input type=button value="Nevermind" class="secondarybtn margin-left-ten" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
                    </div>
                </div>
        </form>
    <?php
    } else if (isset($params['chgrights'])) {
        ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name="chgrights" value="true">
            <div class="col-md-12 padding-left-thirty">
            <div>
                This will allow you to change the use rights of the selected questions, if you can change those rights.
            </div>
            <div class="margin-top-fifteen">
                <span>Select the new rights for these questions</span>
                <span class="display-inline-block margin-left-fifteen">
                    <select class="form-control" name="newrights">
                        <option value="4">Allow use and modifications by all</option>
                        <option value="3">Allow use by all and modifications by group</option>
                        <option value="2" selected="selected">Allow use, use as template, no modifications</option>
                        <option value="0">Private</option>
                    </select>
                </span>
            </div>

            <input type="hidden" name="qtochg" value="<?php echo $clist ?>">
            <div class="margin-top-fifteen">
                <input type=submit value="Change Rights">
                <input type=button value="Nevermind" class="secondarybtn margin-left-fifteen" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </div>
            </div>
        </form>
    <?php
    } else if (isset($params['remove'])) {
        ?>
        <div>Are you SURE you want to delete these questions from the Question Set.  This will make them unavailable
        to all users.  If any are currently being used in an assessment, it will mess up that assessment.</div>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>&confirmed=true">
            <input type=hidden name=remove value="<?php echo $rlist ?>">
            <div class="margin-top-fifteen">
                <input type=submit value="Really Delete">
                <input type=button value="Nevermind" class="secondarybtn margin-left-fifteen" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </div>
        </form>
    <?php
    } else if (isset($params['transfer'])) {
        ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name=transfer value="<?php echo $tlist ?>">
            <div class="col-md-12 padding-left-thirty">
            <div class="col-md-12 padding-left-zero select-text-margin"> <span class="floatleft">Transfer question ownership to</span>

                <span class="display-inline-block width-twenty-four-per margin-left-fifteen col-md-4"><?php AppUtility::writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?></span>
            </div>
            <div class="col-md-12 padding-top-fifteen padding-left-zero">
                <input type=submit value="Transfer">
                <input type=button value="Nevermind" class="secondarybtn margin-left-ten" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </div>
                </div>
        </form>
    <?php
    } else { //DEFAULT DISPLAY

        echo "<div class='col-sm-12 padding-left-right-thirty'>".$page_adminMsg."</div>";?>

        <div class='col-sm-12 padding-left-right-thirty'>
            <?php echo"<form method=post action=\"manage-question-set?cid=$cid\">\n";
                echo "<div class='col-sm-12 margin-top-fifteen padding-right-zero padding-left-zero margin-bottom-twenty'>
                        <div class='col-sm-10 select-text-margin padding-left-right-zero'>
                            <span>In Libraries</span>
                            <span id=\"libnames\">$lnames</span>
                            <input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">
                        </div>";
                        echo '<div class="col-sm-2 padding-right-zero">
                                    <input class="floatright" type="button" value="Select Libraries" onClick="GB_show(\'Library Select\',\'library-tree?cid='.$cid.'&libtree=popup&libs=\'+curlibs,500,500)" />
                              </div></div>';

            echo "<div class='col-sm-12 padding-left-right-zero'>
                        <div class='col-sm-3 floatleft display-inline-block padding-left-zero padding-bottom-fifteen'>Search
                            <input id='manage-question-set-search' class='margin-left-five form-control width-sixty-four-per display-inline-block' type=text size=15 name=search value=\"$search\">
                        </div>"; ?>
            <?php if ($isadmin){ ?>
                        <div class='floatleft select-text-margin padding-right-six-pt-three-per padding-bottom-fifteen'>
                            <?php } else {?>
                <div class='floatleft select-text-margin padding-right-seventeen-per padding-bottom-fifteen'>
                    <?php } ?>
                        <?php echo"<div class='floatleft'> <input type=checkbox name=\"searchall\" value=\"1\" ";
                        if ($searchall==1) {echo "checked=1";}
                        echo "/><span class='margin-left-five'>Search all libs</span></div> <div class='floatleft margin-left-fifteen'><input type=checkbox name=\"searchmine\" value=\"1\" ";
                        if ($searchmine==1) {echo "checked=1";}
                        echo "/><span class='margin-left-five'>Mine only</span></div> ";
                        if ($isadmin) {
                            echo "<div class='floatleft margin-left-fifteen'><input type=checkbox name=\"hidepriv\" value=\"1\" ";
                            if ($hidepriv==1) {echo "checked=1";}
                            echo "/><span class='margin-left-five'>Hide Private </span></div>";
                        }
                        echo '</div>

                            <div class="floatleft display-inline-block padding-right-nineteen padding-bottom-fifteen moz-btn-padding">
                                <input type=submit value="Search" title="List or search selected libraries padding-bottom-fifteen">
                            </div>';
                        echo "<div class='floatleft display-inline-block padding-right-nineteen padding-bottom-fifteen moz-btn-padding'>
                                <input type=button value=\"Add New Question\" onclick=\"window.location='mod-data-set?cid=$cid'\">
                              </div>"; ?>
                        <div class="floatleft padding-bottom-fifteen margin-top-minus-five">
                            <div class="with-selected with-selected-dropdown moz-drop-down-width">
                                <ul class="nav nav-tabs nav-justified manage-question-with-selected-dropdown sub-menu">
                                    <li class="dropdown">
                                        <a class="manage-question-with-selected dropdown-toggle grey-color-link" data-toggle="dropdown" href="#">With selected
                                            <span class="caret right-aligned"></span>
                                        </a>
                                        <ul class="dropdown-menu with-selected">
                                            <li>
                                                <a id="manage-question-chglib">
                                                    <i class="fa fa-fw padding-right-five"></i>
                                                    <span><?php AppUtility::t('Library Assignment'); ?></span>
                                                </a>
                                            </li>
                                            <li>
                                                <a id="manage-question-license">
                                                    <i class="fa fa-credit-card"></i>
                                                    <span class="padding-left-three"><?php AppUtility::t('License'); ?></span>
                                                </a>
                                            </li>
                                            <li>
                                                <a id="manage-question-chgrights">
                                                    <i class="fa fa-hand-o-up padding-right-five"></i>
                                                    <?php AppUtility::t('Change Rights'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a id="manage-question-remove">
                                                    <i class="fa fa-trash-o fa-fw padding-right-five"></i>
                                                    <?php AppUtility::t('Delete'); ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a id="manage-question-transfer">
                                                    <i class="fa fa-exchange"></i>
                                                    <span class="padding-left-five"><?php AppUtility::t('Transfer'); ?></span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        <div class='col-sm-12 manage-ques-set-table padding-left-right-thirty'>
        <?php  echo "<form id=\"selform\" method=post action=\"manage-question-set?cid=$cid\">\n";?>
            <input class="display-none" type="submit" name="chglib" id="chglibButton">
            <input class="display-none" type="submit" name="license" id="licenseButton">
            <input class="display-none" type="submit" name="chgrights" id="chgrightsButton">
            <input type="hidden" name="manage_ques_some_name">
            <input class="display-none" type="submit" name="transfer" id="transferButton">
    <?php if (!$isadmin && !$isgrpadmin) {
            echo "<div class='col-sm-12 padding-bottom-fifteen padding-left-right-zero'> (Delete and Transfer only applies to your questions)</div>";
        } else if ($isgrpadmin) {
            echo "<div class='col-sm-12 padding-bottom-fifteen padding-left-right-zero'>(Delete and Transfer only apply to group's questions)</div>";
        }
        echo "<div class='overflow-x-auto width-hundread-per table-float-left'><table class='col-sm-12 manage-question-table table table-bordered table-striped data-table' id=myTable><thead>\n";
        echo "<tr>
                   <th class='text-align-center'>
                   <div class='checkbox override-hidden padding-left-twenty-five'>
                            <input type='checkbox' name='manage-question-header-checked' value=''>
                   </div></th>
                   <th>Description</th><th>&nbsp;</th><th>&nbsp;</th><th>Action</th><th>Type</th><th>Times Used</th><th>Last Mod</th>";
        if ($isadmin || $isgrpadmin) { echo "<th>Owner</th>";} else {echo "<th>Mine</th>";}
        if ($searchall==1) {
            echo "<th>Library</th>";
        } else if ($searchall==0) {
            echo '<th><span onmouseover="tipshow(this,\'Flag a question if it is in the wrong library\')" onmouseout="tipout()">Wrong Lib</span></th>';
        }
        echo "</tr>\n";
        echo "</thead><tbody id='manage-question-set-table'>\n";
        $alt = 0;
        $ln = 1;
        for ($j=0; $j<count($page_libstouse); $j++) {
            if ($searchall==0) {
                if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                echo '<td></td>';
                echo '<td colspan="8">';
                echo '<b>'.$lnamesarr[$page_libstouse[$j]].'</b>';
                echo '</td><td></td></tr>';
            }
            for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
                $qid =$page_libqids[$page_libstouse[$j]][$i];
                if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                    echo '<td class="text-align-center width-five-per">'.$page_questionTable[$qid]['checkbox'].'</td>';
                echo '<td class="word-break-break-all width-twenty-per">'.$page_questionTable[$qid]['desc'].'</td>';
                echo '<td class="nowrap width-five-per"><div';
                if ($page_questionTable[$qid]['cap']) {echo ' class="ccvid"';}
                echo '>'.$page_questionTable[$qid]['extref'].'</div></td>';
                echo '<td class="width-ten-per">'.$page_questionTable[$qid]['preview'].'</td>';
                echo '<td class="width-twelve-per">'.$page_questionTable[$qid]['action'].'</td>';
                echo '<td class="width-eight-per">'.$page_questionTable[$qid]['type'].'</td>';
                echo '<td class="c width-five-per">'.$page_questionTable[$qid]['times'].'</td>';
                echo '<td class="width-eight-per">'.$page_questionTable[$qid]['lastmod'].'</td>';
                echo '<td class="word-break-break-all width-ten-per">'.$page_questionTable[$qid]['mine'].'</td>';
                if ($searchall==1) {
                    echo '<td class="c width-seven-per">'.$page_questionTable[$qid]['lib'].'</td>';
                } else if ($searchall==0) {
                    if ($page_questionTable[$qid]['junkflag']==1) {
                        echo "<td class='c width-seven-per'><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot"."img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
                    } else {
                        echo "<td class='c width-seven-per'><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot"."img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
                    }
                }
                $ln++;
            }
        }

        echo "</tbody></table></div>\n";
        echo "<script type=\"javascript\">\n";
        echo "initSortTable('myTable',Array(false,'S',false,false,false,'S','N','D'";
        echo ",'S',false";
        echo "),true);\n";
        echo "</script>\n";
        echo "</form>\n";
        echo "<p></p>\n";?>
    </div>
    <?php }
    }
    ?>
    <input type="hidden" id="junk-flag" value="<?php echo AppUtility::getURLFromHome('question','question/save-lib-assign-flag'); ?>"/>
</div>
