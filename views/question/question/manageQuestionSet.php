<?php
use yii\helpers\Html;
use app\components\AppUtility;
use app\components\AppConstant;
$this->title = AppUtility::t('Manage Question Set', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<?php $imasroot = AppUtility::getHomeURL();?>
<!--Get current time-->
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), AppUtility::t('Admin', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getURLFromHome('admin','admin/index')]]); ?>
</div>
<!--Course name-->
<div class="title-container">
    <div class="row">
        <div class="col-sm-12">
            <div class=" col-sm-6" style="right: 30px;">
                <div class="vertical-align title-page"><?php echo AppUtility::t('Manage Question Set') ?><a href="#" onclick="window.open('/openmath/web/docs/help.php?section=managequestionset','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a></div>
            </div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox">
    <?php
    $address = AppUtility::getHomeURL().'question/question';

    if ($overwriteBody==1) {
    echo $body;
    } else {
    ?>
    <script type="text/javascript">
        function previewq(formn,loc,qn) {
            var addr = '<?php AppUtility::getHomeURL() ?>question/question/test-question?cid=<?php echo $cid ?>&checked=0&qsetid='+qn+'&loc=qo'+loc+'&formn='+formn;
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
            } else if (todo=="del") {
                addr = addrmq+'&remove='+id;
            } else if (todo=="tr") {
                addr = addrmq+'&transfer='+id;
            }
            window.location = addr;
        }

        var curlibs = '<?php echo $searchlibs ?>';

        function libselect() {
            window.open('library-tree?cid=<?php echo $cid ?>&libtree=popup&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
        }
        function setlib(libs) {
            document.getElementById("libs").value = libs;
            curlibs = libs;
        }
        function setlibnames(libn) {
            document.getElementById("libnames").innerHTML = libn;
        }
        function getnextprev(formn,loc) {
            var form = document.getElementById(formn);
            var prevq = 0; var nextq = 0; var found=false;
            var prevl = 0; var nextl = 0;
            for (var e = 0; e < form.elements.length; e++) {
                var el = form.elements[e];
                if (typeof el.type == "undefined") {
                    continue;
                }
                if (el.type == 'checkbox' && el.name=='nchecked[]') {
                    if (found) {
                        nextq = el.value;
                        nextl = el.id;
                        break;
                    } else if (el.id==loc) {
                        found = true;
                    } else {
                        prevq = el.value;
                        prevl = el.id;
                    }
                }
            }
            return ([[prevl,prevq],[nextl,nextq]]);
        }
    </script>

<!--    <div class="breadcrumb">--><?php //echo $curBreadcrumb ?><!--</div>-->
<!--    <div id="headermanageqset" class="pagetitle"><h2>--><?php //echo $pagetitle; echo $helpicon; ?><!--</h2></div>-->

    <?php
    if (isset($params['remove'])) {
        ?>
        Are you SURE you want to delete these questions from the Question Set.  This will make them unavailable
        to all users.  If any are currently being used in an assessment, it will mess up that assessment.
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>&confirmed=true">
            <input type=hidden name=remove value="<?php echo $rlist ?>">
            <p>
                <input type=submit value="Really Delete">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else if (isset($params['transfer'])) {
    ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name=transfer value="<?php echo $tlist ?>">
            Transfer question ownership to:

            <?php AppUtility::writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>

            <p>
                <input type=submit value="Transfer">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else if (isset($params['chglib'])) {
    ?>
        <script type="text/javascript">
            var chgliblaststate = 0;
            function chglibtoggle(rad) {
                var val = rad.value;
                var help = document.getElementById("chglibhelp");
                if (val==0) {
                    help.innerHTML = "Select libraries to add these questions to. ";
                    if (chgliblaststate==2) {
                        initlibtree(false);
                    }
                } else if (val==1) {
                    help.innerHTML = "Select libraries to add these questions to.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
                    if (chgliblaststate==2) {
                        initlibtree(false);
                    }
                } else if (val==2) {
                    help.innerHTML = "Unselect the libraries you want to remove questions from.  The questions will not be deleted; they will be moved to Unassigned if no other library assignments exist.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
                    if (chgliblaststate==0 || chgliblaststate==1) {
                        initlibtree(true);
                    }
                }
                chgliblaststate = val;
            }
        </script>
        <form method=post action="manageqset.php?cid=<?php echo $cid ?>">
            <input type=hidden name=chglib value="true">
            <input type=hidden name=qtochg value="<?php echo $clist ?>">
            What do you want to do with these questions?<br/>
            <input type=radio name="action" value="0" onclick="chglibtoggle(this)" checked="checked"/> Add to libraries, keeping any existing library assignments<br/>
            <input type=radio name="action" value="1" onclick="chglibtoggle(this)"/> Add to libraries, removing existing library assignments<br/>
            <input type=radio name="action" value="2" onclick="chglibtoggle(this)"/> Remove library assignments
            <p id="chglibhelp" style="font-weight: bold;">
                Select libraries to add these questions to.
            </p>

            <?php $libtreeshowchecks = false; include("libtree2.php"); ?>


            <p>
                <input type=submit value="Make Changes">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
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

            <p>This will allow you to change the license or attribution on questions, if you have the rights to change them</p>

            <p>Note:  Be cautious when changing licenses or attribution on questions.  Some important things to note:
            <ul>
                <li>If questions are currently copyrighted or contain copyrighted content, you CAN NOT change the license
                    unless you have removed all copyrighted material from the question.</li>
                <li>If questions are licensed under the IMathAS Community License or a Creative Commons license, you CAN NOT
                    change the license unless you are the creator of the questions and all questions it was previously derived from.</li>
                <li>If the question currently has additional attribution listed, you CAN NOT remove that attribution unless
                    you have removed from the question all parts that require the attribution.</li>
            </ul>
            <p style="color:red;">
                In short, you should only be changing license if the questions are your original works, not built on top of existing
                community work.
            <p>
            <p>
                License: <select name="sellicense">
                    <option value="-1">Do not change license</option>
                    <option value="0">Copyrighted</option>
                    <option value="1">IMathAS / WAMAP / MyOpenMath Community License</option>
                    <option value="2">Public Domain</option>
                    <option value="3">Creative Commons Attribution-NonCommercial-ShareAlike</option>
                    <option value="4">Creative Commons Attribution-ShareAlike</option>
                </select>
            </p>
            <p>Other Attribution: <select name="otherattribtype">
                    <option value="-1">Do not change attribution</option>
                    <option value="0">Replace existing attribution</option>
                    <option value="1">Append to existing attribution</option>
                </select><br/>
                Additional Attribution: <input type="text" size="80" name="addattr" />
            </p>

            <input type=submit value="Change License / Attribution">
            <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else if (isset($params['chgrights'])) {
        ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>">
            <input type=hidden name="chgrights" value="true">

            <p>
                This will allow you to change the use rights of the selected questions, if you can change those rights.
            </p>
            <p>Select the new rights for these questions: <select name="newrights">
                    <option value="0">Private</option>
                    <option value="2" selected="selected">Allow use, use as template, no modifications</option>
                    <option value="3">Allow use by all and modifications by group</option>
                    <option value="4">Allow use and modifications by all</option>
                </select>
            </p>

            <input type="hidden" name="qtochg" value="<?php echo $clist ?>">


            <p>
                <input type=submit value="Change Rights">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else if (isset($params['remove'])) {
        ?>
        Are you SURE you want to delete this question from the Question Set.  This will make it unavailable
        to all users.  If it is currently being used in an assessment, it will mess up that assessment.
        <p>
            <input type=button onclick="window.location='manage-question-set?cid=<?php echo $cid ?>&remove=<?php echo $params['remove'] ?>&confirmed=true'" value="Really Delete">
            <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
        </p>
    <?php
    } else if (isset($params['transfer'])) {
        ?>
        <form method=post action="manage-question-set?cid=<?php echo $cid ?>&transfer=<?php echo $params['transfer'] ?>">
            Transfer to:

            <?php AppUtility::writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>

            <p>
                <input type=submit value="Transfer">
                <input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manage-question-set?cid=<?php echo $cid ?>'">
            </p>
        </form>
    <?php
    } else { //DEFAULT DISPLAY

        echo $page_adminMsg;

        echo "<form method=post action=\"manage-question-set?cid=$cid\">\n";

        echo "In Libraries: <span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">\n";
        //echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> <br>";
        echo '<input type="button" value="Select Libraries" onClick="GB_show(\'Library Select\',\'library-tree?cid='.$cid.'&libtree=popup&libs=\'+curlibs,500,500)" /> <br>';

        echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=checkbox name=\"searchall\" value=\"1\" ";
        if ($searchall==1) {echo "checked=1";}
        echo "/>Search all libs <input type=checkbox name=\"searchmine\" value=\"1\" ";
        if ($searchmine==1) {echo "checked=1";}
        echo "/>Mine only ";
        if ($isadmin) {
            echo "<input type=checkbox name=\"hidepriv\" value=\"1\" ";
            if ($hidepriv==1) {echo "checked=1";}
            echo "/>Hide Private ";
        }

        echo '<input type=submit value="Search" title="List or search selected libraries">';
        echo "<input type=button value=\"Add New Question\" onclick=\"window.location='mod-data-set?cid=$cid'\">\n";
        echo "</form>";

//        echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=082913\"></script>\n";
        echo "<form id=\"selform\" method=post action=\"manage-question-set?cid=$cid\">\n";
        //echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
        echo 'Check: <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',false)">None</a> ';

        echo "With Selected: <input type=submit name=\"transfer\" value=\"Transfer\" title=\"Transfer question ownership\">\n";
        echo "<input type=submit name=\"remove\" value=\"Delete\">\n";
        echo "<input type=submit name=\"chglib\" value=\"Library Assignment\" title=\"Change library assignments\">\n";
        echo "<input type=submit name=\"chgrights\" value=\"Change Rights\" title=\"Change use rights\">\n";
        //echo "<input type=submit name=\"template\" value=\"Template\" title=\"Make a copy of all selected questions\">\n";
        echo "<input type=submit name=\"license\" value=\"License\" title=\"Change license or attribution\">\n";
        if (!$isadmin && !$isgrpadmin) {
            echo "<br/>(Delete and Transfer only applies to your questions)\n";
        } else if ($isgrpadmin) {
            echo "<br/>(Delete and Transfer only apply to group's questions)\n";
        }
        echo "<table id=myTable class=gb><thead>\n";
        echo "<tr><th>&nbsp;</th><th>Description</th><th>&nbsp;</th><th>&nbsp;</th><th>Action</th><th>Type</th><th>Times Used</th><th>Last Mod</th>";
        if ($isadmin || $isgrpadmin) { echo "<th>Owner</th>";} else {echo "<th>Mine</th>";}
        if ($searchall==1) {
            echo "<th>Library</th>";
        } else if ($searchall==0) {
            echo '<th><span onmouseover="tipshow(this,\'Flag a question if it is in the wrong library\')" onmouseout="tipout()">Wrong Lib</span></th>';
        }
        echo "</tr>\n";
        echo "</thead><tbody>\n";
        $alt = 0;
        $ln = 1;
        for ($j=0; $j<count($page_libstouse); $j++) {
            if ($searchall==0) {
                if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                echo '<td></td>';
                echo '<td colspan="8">';
                echo '<b>'.$lnamesarr[$page_libstouse[$j]].'</b>';
                echo '</td></tr>';
            }
            for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
                $qid =$page_libqids[$page_libstouse[$j]][$i];
                if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
                echo '<td>'.$page_questionTable[$qid]['checkbox'].'</td>';
                echo '<td>'.$page_questionTable[$qid]['desc'].'</td>';
                echo '<td class="nowrap"><div';
                if ($page_questionTable[$qid]['cap']) {echo ' class="ccvid"';}
                echo '>'.$page_questionTable[$qid]['extref'].'</div></td>';
                echo '<td>'.$page_questionTable[$qid]['preview'].'</td>';
                echo '<td>'.$page_questionTable[$qid]['action'].'</td>';
                echo '<td>'.$page_questionTable[$qid]['type'].'</td>';
                echo '<td class="c">'.$page_questionTable[$qid]['times'].'</td>';
                echo '<td>'.$page_questionTable[$qid]['lastmod'].'</td>';
                echo '<td class="c">'.$page_questionTable[$qid]['mine'].'</td>';
                if ($searchall==1) {
                    echo '<td>'.$page_questionTable[$qid]['lib'].'</td>';
                } else if ($searchall==0) {
                    if ($page_questionTable[$qid]['junkflag']==1) {
                        echo "<td class=c><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
                    } else {
                        echo "<td class=c><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
                    }
                }
                $ln++;
            }
        }

        echo "</tbody></table>\n";
        echo "<script type=\"text/javascript\">\n";
        echo "initSortTable('myTable',Array(false,'S',false,false,false,'S','N','D'";
        echo ",'S',false";
        echo "),true);\n";
        echo "</script>\n";
        echo "</form>\n";
        echo "<p></p>\n";
    }
    }
    ?>
</div>