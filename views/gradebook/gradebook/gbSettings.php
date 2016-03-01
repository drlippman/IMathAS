<?php
use app\components\AppUtility;
use app\assets\AppAsset;
$this->title = AppUtility::t('Gradebook Settings', false);
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false), $course->name, AppUtility::t('Gradebook', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index', AppUtility::getHomeURL() . 'course/course/course?cid=' . $course->id, AppUtility::getHomeURL() . 'gradebook/gradebook/gradebook?cid=' . $course->id]]); ?>
</div>
<div class="title-container padding-bottom-two-em">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?>
                <a href='#' onClick="window.open('<?php echo AppUtility::getURLFromHome('site', 'helper-guide?section=gbSettings'); ?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"><i class="fa fa-question fa-fw help-icon"></i></a>
            </div>
        </div>
    </div>
</div>
<div class="item-detail-content">
    <?php echo $this->render("../../course/course/_toolbarTeacher", ['course' => $course, 'section' => 'gradebook']); ?>
</div>

<div class="tab-content shadowBox"">
    <div class="inner-content-gradebook">
        <form id="theform" method=post action="gb-settings?cid=<?php echo $course->id;?>" onsubmit="prepForSubmit()">
            <div class="col-md-12 col-sm-12">
                <div class="col-md-3 col-sm-4 "><?php AppUtility::t('Calculate total using')?></div>
                <div class="col-md-9 col-sm-8">
                    <input type=radio name=useweights value="0" id="usew0" <?php AppUtility::writeHtmlChecked($useWeights,0);?> onclick="swapWeightHdr(0)"/><span for="usew0">&nbsp;<?php AppUtility::t(' points earned / possible')?></span><br/>
                    <input type=radio name=useweights value="1" id="usew1" <?php AppUtility::writeHtmlChecked($useWeights,1);?> onclick="swapWeightHdr(1)"/><span for="usew1">&nbsp;<?php AppUtility::t(' category weights')?></span>
                </div>
            </div>
            <p class="col-md-12 col-sm-12"><a class="padding-left-fifteen" href="#" onclick="toggleAdv(this);return false"><?php AppUtility::t('Edit view settings')?></a></p>

            <fieldset class="col-md-12 col-sm-12" id="viewfield"><legend><?php AppUtility::t('Default gradebook view')?></legend>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class="col-md-3 col-sm-4"><?php AppUtility::t('Gradebook display')?></span>
                        <span class="col-md-1 col-sm-1">
                            <?php
                            $orderVal = array(0,4,6,8,2,10,12);
                            $orderLabel = array('by end date, old to new', 'by end date, new to old', 'by start date, old to new', 'start date, new to old', 'alphabetically', 'by course page order, offline at end', 'by course page order reversed, offline at start');
                            AppUtility::t('Order: ')
                            ?>
                        </span>
                        <span class="col-md-4 col-sm-7">
                            <?php
                            AppUtility::writeHtmlSelect("orderby", $orderVal, $orderLabel, $gbScheme['orderby']&~1);
                            ?>
                            <span class="col-md-12 col-sm-12 padding-left-zero padding-top-one-em"><input type="checkbox" name="grouporderby" value="1" id="grouporderby" <?php AppUtility::writeHtmlChecked($gbScheme['orderby']&1, 1);?>/><span for="grouporderby">Group by category first</span></span>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class="col-md-3 col-sm-4">Default user order</span>
                        <span class="col-md-5 col-sm-8">
                        <?php
                        $orderVal = array(0,1);
                        $orderLabel = array('Order by section (if used), then Last name','Order by Last name');
                        AppUtility::writeHtmlSelect("usersort", $orderVal, $orderLabel, $gbScheme['usersort']);
                        ?>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Links show</span>
                        <span class= 'col-md-5 col-sm-8'>
                            <?php
                            $orderVal = array(0,1);
                            $orderLabel = array('Full Test','Question Breakdown');
                            AppUtility::writeHtmlSelect("gbmode100", $orderVal, $orderLabel, $links);
                            ?>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Default show by availability </span>
                        <span class='col-md-5 col-sm-8'>
                            <?php
                            $orderVal = array(0,3,4,1,2);
                            $orderLabel = array('Past Due Items','Past &amp; Attempted Items','Available Items Only','Past &amp; Available Items','All Items');
                            AppUtility::writeHtmlSelect("gbmode1", $orderVal, $orderLabel, $availShow);
                            ?>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Not Counted (NC) items </span>
                        <span class='col-md-5 col-sm-8'>
                            <?php
                            $orderVal = array(0,1,2);
                            $orderLabel = array('Show NC items','Show NC items not hidden from students','Hide NC items');
                            AppUtility::writeHtmlSelect("gbmode10", $orderVal, $orderLabel, $hideNc);
                            ?>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Locked Students</span>
                        <span class='col-md-5 col-sm-8'>
                            <input type=radio name="gbmode200" value="0"  id="lockstu0" <?php AppUtility::writeHtmlChecked($hideLocked, 0);?>/>&nbsp;<span for="lockstu0">Show</span>
                            <input type=radio name="gbmode200" value="2"  id="lockstu2" <?php AppUtility::writeHtmlChecked($hideLocked, 2);?>/>&nbsp;<span for="lockstu2">Hide</span>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Default Colorization</span>
                        <span class='col-md-5 col-sm-8'>
                        <?php AppUtility::writeHtmlSelect("colorize", $colorVal, $colorLabel, $colorize); ?>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Totals columns show on</span>
                        <span class='col-md-5 col-sm-8'>
                            <input type=radio name="gbmode1000" value="0" id="totside0" <?php AppUtility::writeHtmlChecked($totOnLeft, 0);?>/>&nbsp;<span for="totside0">Right</span>
                            <input type=radio name="gbmode1000" value="1" id="totside1" <?php AppUtility::writeHtmlChecked($totOnLeft, 1);?>/>&nbsp;<span for="totside1">Left</span>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                        <span class='col-md-3 col-sm-4'>Average row shows on</span>
                        <span class='col-md-5 col-sm-8'>
                            <input type=radio name="gbmode1002" value="0" id="avgloc0" <?php AppUtility::writeHtmlChecked($avgOnTop, 0);?>/>&nbsp;<span for="avgloc0">Bottom</span>
                            <input type=radio name="gbmode1002" value="2" id="avgloc2" <?php AppUtility::writeHtmlChecked($avgOnTop, 2);?>/>&nbsp;<span for="avgloc2">Top</span>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                            <span class='col-md-3 col-sm-4'>Include details</span>
                        <span class='col-md-6 col-sm-8'>
                            <input type="checkbox" name="gbmode4000" value="4" id="llcol" <?php AppUtility::writeHtmlChecked($lastLogin,true);?>/>&nbsp;<span for="llcol">Last Login column</span><br/>
                            <input type="checkbox" name="gbmode400" value="4" id="duedate" <?php AppUtility::writeHtmlChecked($includeDuDate,true);?>/>&nbsp;<span for="duedate">Due Date in column headers, and column in single-student view</span><br/>
                            <input type="checkbox" name="gbmode40" value="4" id="lastchg" <?php AppUtility::writeHtmlChecked($includeLastChange,true);?>/>&nbsp;<span for="lastchg">Last Change column in single-student view</span>
                        </span>
                    </span>
                    <span class="col-md-12 col-sm-12 padding-bottom-one-em">
                            <span class='col-md-3 col-sm-4'>Totals to show students</span>
                        <span class='col-md-5 col-sm-8'>
                            <input type="checkbox" name="stugbmode1" value="1" id="totshow1" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 1, 1);?>/>&nbsp;<span for="totshow1">Past Due</span><br/>
                            <input type="checkbox" name="stugbmode2" value="2" id="totshow2" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 2, 2);?>/>&nbsp;<span for="totshow2">Past Due and Attempted</span><br/>
                            <input type="checkbox" name="stugbmode4" value="4" id="totshow4" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 4, 4);?>/>&nbsp;<span for="totshow4">Past Due and Available</span><br/>
                            <input type="checkbox" name="stugbmode8" value="8" id="totshow8" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 8, 8);?>/>&nbsp;<span for="totshow8">All (including future)</span><br/>
                        </span>
                    </span>
            </fieldset>

            <fieldset><legend>Gradebook Categories</legend>
                <?php
                    $r = explode(',', $gbScheme['defaultcat']);
                    $row['name'] = 'Default';
                    $row['scale'] = $r[0];
                    $row['scaletype'] = $r[1];
                    $row['chop'] = $r[2];
                    $row['dropn'] = $r[3];
                    $row['weight'] = $r[4];
                    $row['hidden'] = $r[5];
                    if (isset($r[6])) {
                        $row['calctype'] = $r[6];
                    } else {
                        $row['calctype'] = $row['dropn']==0?0:1;
                    }
                    echo '<span class="col-sm-12 col-md-12">';
                    echo "<table class='table table-bordered table-striped table-hover data-table'><thead>";
                    echo "<tr>
                    <th class='col-md-2'>Category Name</th>
                    <th class='col-md-2'>Display<sup>*</sup></th>
                    <th class='col-md-3'>Scale (optional)</th>
                    <th class='col-md-3'>Drops &amp; Category total</th>
                    <th class='col-md-1' id=weighthdr>";
                    if ($useWeights==0) {
                        echo "Fixed Category Point Total (optional)<br/>Blank to use point sum";
                    } else if ($useWeights==1) {
                        echo "Category Weight (%)";
                    }
                    echo '</th>
                    <th class="col-md-1">Remove</th>
                    </tr></thead><tbody id="cattbody">';
                    disprow(0, $row, $hideLabel, $hideVal);
                    foreach($gbCategory as $category){
                    disprow($category['id'], $category, $hideLabel, $hideVal);
                    }
                    echo "</tbody></table>";
                    echo '</span>';
                    echo '<span class="col-sm-12 col-md-12"><p><input type="button" class ="btn btn-primary" value="'.AppUtility::t('Add New Category', false).'" onclick="addCat()" /></p></span>';
                    ?>
            </fieldset>

                <div class="submit padding-top-one-em"><input class ="btn btn-primary save-btn" id="save-btn" type=submit name=submit value="<?php AppUtility::t('Save Changes')?>"/></div>
        </form>
            <p class="small"><sup>*</sup>When a category is set to Expanded, both the category total and all items in the category are displayed.<br/>
            When a category is set to Collapsed, only the category total is displayed, but all the items are still counted normally.<br/>
            When a category is set to Hidden, nothing is displayed, and no items from the category are counted in the grade total. </p>
            <p class="small"><sup>*</sup>If you drop any items, a calc type of "average percents" is required. If you are using a points earned / possible
            scoring system and use the "average percents" method in a category, the points for the category may be a somewhat arbitrary value.</p>
    <?php
        function disprow($id,$row, $hideLabel, $hideVal) {

            //name,scale,scaletype,chop,drop,weight
            echo "<tr class=grid id=\"catrow$id\"><td>";
            if ($id>0) {
                echo "<input type=text name=\"name[$id]\" value=\"{$row['name']}\"/>";
            } else {
                echo $row['name'];
            }
            "</td>";
            echo '<td>';
            AppUtility::writeHtmlSelect("hide[$id]",$hideVal,$hideLabel,$row['hidden']);
            echo '</td>';
            echo "<td>Scale <input class='form-control display-inline-block width-twenty-five-per' type=text size=3 name=\"scale[$id]\" value=\"";
            if ($row['scale']>0) {
                echo $row['scale'];
            }
            echo "\"/><br/>(<input type=radio name=\"st[$id]\" value=0 ";
            if ($row['scaletype']==0) {
                echo "checked=1 ";
            }
            echo "/>"; ?>
            &nbsp;points <?php
            echo "<input type=radio name=\"st[$id]\" value=1 ";
            if ($row['scaletype']==1) {
                echo "checked=1 ";
            }
            echo "/>";?> &nbsp;percent <?php
            echo " )<br/>to perfect score<br/>";
            echo "<input type=checkbox name=\"chop[$id]\" value=1 ";
            if ($row['chop']>0) {
                echo "checked=1 ";
            }
            echo "/> no total over <input class='form-control display-inline-block width-twenty-five-per' type=text size=3 name=\"chopto[$id]\" value=\"";
            if ($row['chop']>0) {
                echo round($row['chop']*100);
            } else {
                echo "100";
            }
            echo "\"/>%</td>";
            echo "<td>";
            echo '<div>';
            echo 'Calc total: <select class="form-control display-inline-block width-sixty-per" name="calctype['.$id.']" id="calctype'.$id.'" ';
            if ($row['dropn']!=0) { echo 'disabled="true"';}
            echo '><option value="0" ';
            if ($row['calctype']==0) {echo 'selected="selected"';}
            echo '>point total</option><option value="1" ';
            if ($row['calctype']==1) {echo 'selected="selected"';}
            echo '>averaged percents</option></select> ';
            echo '</div>';
            echo "<input type=radio name=\"droptype[$id]\" value=0 onclick=\"calcTypeChange($id,0)\" ";
            if ($row['dropn']==0) {
                echo "checked=1 ";
            }
            echo "/>";?>&nbsp;Keep All<?php
            echo " <br/><input type=radio name=\"droptype[$id]\" value=1 onclick=\"calcTypeChange($id,1)\" ";
            if ($row['dropn']>0) {
                echo "checked=1 ";
            }
            $absr4=abs($row['dropn']);
            echo "/>";?>&nbsp;Drop lowest<?php
            echo "  <input class='form-control display-inline-block width-twenty-five-per' type=text size=2 name=\"dropl[$id]\" value=\"$absr4\"/> scores<br/> <input type=radio name=\"droptype[$id]\" value=2 onclick=\"calcTypeChange($id,1)\" ";
            if ($row['dropn']<0) {
                echo "checked=1 ";
            }
            echo "/>";?>&nbsp;Keep highest<?php
            echo "  <input class='form-control display-inline-block width-twenty-five-per' type=text size=2 name=\"droph[$id]\" value=\"$absr4\"/> scores</td>";
            echo "<td><input class='form-control display-inline-block width-ninety-per' type=text size=3 name=\"weight[$id]\" value=\"";
            if ($row['weight']>-1) {
                echo $row['weight'];
            }
            echo "\"/></td>";
            if ($id!=0) {
                echo "<td><a href=\"#\" id=\"remove-category\" onclick=\"removeExistCat($id);return false;\">Remove</a></td></tr>";
            } else {
                echo "<td></td></tr>";
            }
        }

        ?>

    </div>
</div>

