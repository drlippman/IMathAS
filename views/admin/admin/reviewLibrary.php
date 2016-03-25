<?php
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'Remove Library';
global $temp;
?>
<div class="item-detail-header">
        <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home','Admin', 'Manage Libraries'], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'admin/admin/index', AppUtility::getHomeURL().'admin/admin/manage-lib?cid=admin'], 'page_title' => $this->title]); ?>
</div>
<div class="title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>

<div class="tab-content shadowBox non-nav-tab-item">
<?php if ($overwriteBody == 1) {
    echo $body;
} else { //DISPLAY BLOCK HERE
    ?>
    <?php
    if (!isset($_REQUEST['lib'])) {
        ?>
        <script>
            var curlibs = '<?php echo $inlibs ?>';
            function libselect() {
                window.open('libtree.php?libtree=popup&type=radio&selectrights=1&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
            }
            function setlib(libs) {
                if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
                    libs = libs.substring(2);
                }
                document.getElementById("lib").value = libs;
                curlibs = libs;
            }
            function setlibnames(libn) {
                if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
                    libn = libn.substring(11);
                }
                document.getElementById("libnames").innerHTML = libn;
            }
        </script>

        <form method=post action="review-library?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=0">
            <?php AppUtility::t('Library to review')?>
            <span id="libnames"><?php echo $lnames ?></span>
            <input type=hidden name="lib" id="lib" size="10" value="<?php echo $inlibs ?>">
            <input type=button value="Select Libraries" onClick="libselect()"><br/>
            <input type=submit value=Submit>
        </form>

    <?php

    } elseif ((isset($params['remove']) || isset($params['delete'])) && !isset($params['confirm']))  {
        ?>
        <form method=post action="review-library?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
            <?php echo $page_ConfirmMsg; ?>
            <p><input type=submit name="confirm" value="Confirm">
                <input type=button value="Cancel" class="secondarybtn" onclick="window.location='review-library?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>'"></p>
        </form>

    <?php

    } else { //DEFAULT DISPLAY HERE
       if ($offset > AppConstant::NUMERIC_ZERO)
        {
            $last = $offset -1;
            $page_lastLink =  "<a href=\"review-library?cid=$courseId&source=$source&offset=$last&lib=$lib\">Last</a> ";
        } else {
            $page_lastLink = "Last ";
        }

        if ($offset < $cnt-1)
        {
            $next = $offset +1;
            $page_nextLink = "<a href=\"review-library?cid=$courseId&source=$source&offset=$next&lib=$lib\">Next</a>";
        } else {
            $page_nextLink = "Next";
        } ?>
        <div class="col-md-12 col-sm-12 print-test-header" style="margin-left: 0"><?php echo $page_lastLink . " | " . $page_nextLink; ?></div><BR class=form><BR class=form>

        <div class="col-md-12 col-sm-12 padding-left-zero">
      <?php  if ($myq || $myLib)
        { ?>
          <?php  if ($myq) { ?>
                <input type=hidden name=delete value="Delete">
                <input type="hidden" id="admin" value="<?php echo $cid ?>">
                <div class="col-md-1 col-sm-1 padding-right-zero">
                    <input type=submit name=delete value=Delete onclick=deleteLibQuestion(<?php echo $source;?>,<?php echo $offset;?>,<?php echo $lib;?>)></div>
          <?php  }
            if ($myLib) { ?>
                <input type=hidden name=remove value="Remove from Library">
                <input type="hidden" id="admin" value="<?php echo $cid ?>">
                <div class="col-md-1 col-sm-1">
                    <input type=submit name=remove value="Remove" onclick=removeLibQuestion(<?php echo $source;?>,<?php echo $offset;?>,<?php echo $lib;?>)></div><BR class=form>

          <?php  }
        }
        ?>
        </div>
        <p style="color: red;"><?php echo $page_updatedMsg; ?></p>

        <div class="col-md-12 col-sm-12"><h4><?php echo $qsetid ?>: <?php echo $lineQSet['description'] ?></h4></div>

        <div class="col-md-12 col-sm-12"><?php echo  $page_deleteForm; ?></div>
        <div class="col-md-12 col-sm-12"><?php echo  $page_lastScore; ?></div>

        <form method=post action="review-library?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>" onsubmit="doonsubmit()">
            <input type=hidden name=seed value="<?php echo $seed ?>">

            <?php
            unset($lastanswers);
            displayq(0,$qsetid,$seed,true,true,0);
            echo $temp;
            ?>
            <BR class=form><br/>
<!--            <div class="col-md-12"><input type=submit value="Submit"></div>-->
            <div class="col-md-12 col-sm-12 header-btn floatleft">
                <button class="btn btn-primary page-settings" type="submit" value="Submit"><i class="fa fa-share header-right-btn"></i><?php echo 'Submit' ?></button>
            </div>
        </form>
        <?php
        if ($source == AppConstant::NUMERIC_ZERO) {
            echo "	<BR class=form><BR class=form><div class='col-md-12 col-sm-12'><a href=\"review-library?cid=$cid&offset=$offset&lib=$lib&source=1\">View/Modify Question Code</a></div><BR class='form'><BR class='form'>\n";
        } else {
            ?>
            <BR class="form"><BR class="form"><div class="col-md-12 col-sm-12">
                <a href="review-library?cid=<?php echo $cid ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>&source=0">
                    <?php AppUtility::t("Don't show Question Code")?>
                </a>
            </div><BR class="form"><BR class="form">
            <form method=post action="review-library?cid=<?php echo $cid ?>&source=<?php echo $source ?>&offset=<?php echo $offset ?>&lib=<?php echo $lib ?>">
                <div class="col-md-12 col-sm-12"><?php echo $page_canModifyMsg; ?></div>

                <script>
                    function swapentrymode() {
                        var butn = document.getElementById("entrymode");
                        if (butn.value=="2-box entry") {
                            document.getElementById("qcbox").style.display = "none";
                            document.getElementById("abox").style.display = "none";
                            document.getElementById("control").rows = 20;
                            butn.value = "4-box entry";
                        } else {
                            document.getElementById("qcbox").style.display = "block";
                            document.getElementById("abox").style.display = "block";
                            document.getElementById("control").rows = 10;
                            butn.value = "2-box entry";
                        }
                    }
                    function incboxsize(box) {
                        document.getElementById(box).rows += 1;
                    }
                    function decboxsize(box) {
                        if (document.getElementById(box).rows > 1)
                            document.getElementById(box).rows -= 1;
                    }
                </script>

<!--               <div class="col-md-12"><input type=submit name="update" value="Update"></div> <BR class="form"><BR class="form">-->
                <div class="col-md-12 col-sm-12 header-btn floatleft">
                    <button class="btn btn-primary page-settings" type="submit" name="update" value="Update"><i class="fa fa-share header-right-btn"></i><?php echo 'Update' ?></button>
                </div><BR class="form"><BR class="form">

               <div class="col-md-12 col-sm-12 padding-left-zero">
                    <div class="col-md-2 col-sm-2"><?php AppUtility::t('Description')?></div>
                    <div class="col-md-4 col-sm-4"><textarea class="text-area-alignment" cols=60 rows=4 name=description <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['description'];?></textarea></div><BR class=form>
                </div>
                <BR class="form"><br/>
                <div class="col-md-12 col-sm-12 padding-left-zero">
                    <div class="col-md-2 col-sm-2"><?php AppUtility::t('Question type')?></div>
                    <div class="col-md-4 col-sm-4"><select class="form-control" name=qtype <?php if (!$myq) echo "disabled=\"disabled\"";?>>
                        <option value="number" <?php if ($lineQSet['qtype']=="number") {echo "SELECTED";} ?>><?php AppUtility::t('Number')?></option>
                        <option value="calculated" <?php if ($lineQSet['qtype']=="calculated") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Number')?></option>
                        <option value="choices" <?php if ($lineQSet['qtype']=="choices") {echo "SELECTED";} ?>><?php AppUtility::t('Multiple-Choice')?></option>
                        <option value="multans" <?php if ($lineQSet['qtype']=="multans") {echo "SELECTED";} ?>><?php AppUtility::t('Multiple-Answer')?></option>
                        <option value="matching" <?php if ($lineQSet['qtype']=="matching") {echo "SELECTED";} ?>><?php AppUtility::t('Matching')?></option>
                        <option value="numfunc" <?php if ($lineQSet['qtype']=="numfunc") {echo "SELECTED";} ?>><?php AppUtility::t('Function')?></option>
                        <option value="string" <?php if ($lineQSet['qtype']=="string") {echo "SELECTED";} ?>><?php AppUtility::t('String')?></option>
                        <option value="essay" <?php if ($lineQSet['qtype']=="essay") {echo "SELECTED";} ?>><?php AppUtility::t('Essay')?></option>
                        <option value="draw" <?php if ($lineQSet['qtype']=="draw") {echo "SELECTED";} ?>><?php AppUtility::t('Drawing')?></option>
                        <option value="ntuple" <?php if ($lineQSet['qtype']=="ntuple") {echo "SELECTED";} ?>><?php AppUtility::t('N-Tuple')?></option>
                        <option value="calcntuple" <?php if ($lineQSet['qtype']=="calcntuple") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated N-Tuple')?></option>
                        <option value="matrix" <?php if ($lineQSet['qtype']=="matrix") {echo "SELECTED";} ?>><?php AppUtility::t('Numerical Matrix')?></option>
                        <option value="calcmatrix" <?php if ($lineQSet['qtype']=="calcmatrix") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Matrix')?></option>
                        <option value="interval" <?php if ($lineQSet['qtype']=="interval") {echo "SELECTED";} ?>><?php AppUtility::t('Interval')?></option>
                        <option value="calcinterval" <?php if ($lineQSet['qtype']=="calcinterval") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Interval')?></option>
                        <option value="complex" <?php if ($lineQSet['qtype']=="complex") {echo "SELECTED";} ?>><?php AppUtility::t('Complex')?></option>
                        <option value="calccomplex" <?php if ($lineQSet['qtype']=="calccomplex") {echo "SELECTED";} ?>><?php AppUtility::t('Calculated Complex')?></option>
                        <option value="file" <?php if ($lineQSet['qtype']=="file") {echo "SELECTED";} ?>><?php AppUtility::t('File Upload')?></option>
                        <option value="multipart" <?php if ($lineQSet['qtype']=="multipart") {echo "SELECTED";} ?>><?php AppUtility::t('Multipart')?></option>
                    </select>
                </div><BR class=form><BR class="form">
                <div class="col-md-12 col-sm-12">
                    <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL()?>help.php?section=writingquestions','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Writing Questions Help</a>
                    <a href="#" onclick="window.open('<?php echo AppUtility::getHomeURL()?>/assessment/libs/libhelp.php','Help','width=400,height=300,toolbar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))">Macro Library Help</a><BR></div><BR class="form"><BR class="form">
                <div class="col-md-12 col-sm-12">    <?php AppUtility::t('Switch to')?>
                    <input type=button id=entrymode value="<?php if ($twobx) {echo "4-box entry";} else {echo "2-box entry";}?>" onclick="swapentrymode()" <?php if ($lineQSet['qcontrol']!='' || $lineQSet['answer']!='') echo "DISABLED"; ?>/><BR class="form">
                </div><BR class="form"><BR class="form">
                <div id=ccbox class="col-md-12 col-sm-12">
                    <b><?php AppUtility::t('Common Control')?>
                    <span class=pointer onclick="incboxsize('control')">[+]</span>
                    <span class=pointer onclick="decboxsize('control')">[-]</span></b><BR>
                    <textarea class="text-area-alignment" cols=60 rows=<?php if ($twobx) {echo "20";} else {echo "10";}?> id=control name=control <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['control'];?></textarea>
                </div><BR class="form">
                <div id=qcbox <?php if ($twobx) {echo "style=\"display: none;\"";}?> class="col-md-12 col-sm-12">
                    <?php AppUtility::t('Question Control')?>
                    <span class=pointer onclick="incboxsize('qcontrol')">[+]</span>
                    <span class=pointer onclick="decboxsize('qcontrol')">[-]</span><BR>
                    <textarea class="text-area-alignment" cols=60 rows=10 id=qcontrol name=qcontrol <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['qcontrol'];?></textarea>
                </div><BR class="form">
                <div id=qtbox class="col-md-12 col-sm-12">
                    <b><?php AppUtility::t('Question Text')?>
                    <span class=pointer onclick="incboxsize('qtext')">[+]</span>
                    <span class=pointer onclick="decboxsize('qtext')">[-]</span></b><BR>
                    <textarea class="text-area-alignment" cols=60 rows=10 id=qtext name=qtext <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['qtext'];?></textarea>
                </div><BR class="form">
                <div id=abox <?php if ($twobx) {echo "style=\"display: none;\"";}?>>
                    <?php AppUtility::t('Answer')?>
                    <span class=pointer onclick="incboxsize('answer')">[+]</span>
                    <span class=pointer onclick="decboxsize('answer')">[-]</span><BR>
                    <textarea class="text-area-alignment" cols=60 rows=10 id=answer name=answer <?php if (!$myq) echo "readonly=\"readonly\"";?>><?php echo $lineQSet['answer'];?></textarea>
                </div><BR class="form">

                    <div class="col-md-12 col-sm-12 header-btn floatleft">
                        <button class="btn btn-primary page-settings" type="submit" name="update" value="Update"><i class="fa fa-share header-right-btn"></i><?php echo 'Update' ?></button><br/><br class="form">
                    </div>
            </form><BR class="form">

        <?php
        }
    }
}
?>
