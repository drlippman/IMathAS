<?php
use app\components\AppUtility;
use app\assets\AppAsset;
$this->title = 'Settings';
$this->params['breadcrumbs'][] = ['label' => ucfirst($course->name), 'url' => ['/instructor/instructor/index?cid=' .$course->id]];
$this->params['breadcrumbs'][] = ['label' => 'Gradebook', 'url' => ['/gradebook/gradebook/gradebook?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<div id="headergbsettings" class="pagetitle">
    <h2>Grade Book Settings <img src="<?php echo AppUtility::getAssetURL()?>img/help.gif" alt="Help" onclick="window.open('<?php echo AppUtility::getURLFromHome('site', 'helper-guide?section=gbSettings'); ?>','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"></h2>
</div>
<form id="theform" method=post action="gb-settings?cid=<?php echo $course->id;?>" onsubmit="prepForSubmit()">
    <span class=form>Calculate total using:</span>
    <span class=formright>
		<input type=radio name=useweights value="0" id="usew0" <?php AppUtility::writeHtmlChecked($useWeights,0);?> onclick="swapWeightHdr(0)"/><label for="usew0">points earned / possible</label><br/>
		<input type=radio name=useweights value="1" id="usew1" <?php AppUtility::writeHtmlChecked($useWeights,1);?> onclick="swapWeightHdr(1)"/><label for="usew1">category weights</label>
	</span><br class=form />
    <p><a href="#" onclick="toggleAdv(this);return false">Edit view settings</a></p>

    <fieldset id="viewfield"><legend>Default gradebook view:</legend>

        <span class="form">Gradebook display:</span>
	<span class="formright">
        <?php
        $orderVal = array(0,4,6,8,2,10,12);
        $orderLabel = array('by end date, old to new', 'by end date, new to old', 'by start date, old to new', 'start date, new to old', 'alphabetically', 'by course page order, offline at end', 'by course page order reversed, offline at start');
        echo 'Order: ';
        AppUtility::writeHtmlSelect("orderby", $orderVal, $orderLabel, $gbScheme['orderby']&~1);
        ?>
        <br/>
		<input type="checkbox" name="grouporderby" value="1" id="grouporderby" <?php AppUtility::writeHtmlChecked($gbScheme['orderby']&1, 1);?>/><label for="grouporderby">Group by category first</label>
	</span><br class="form">
        <span class=form>Default user order:</span>
	<span class=formright>
		<?php
        $orderVal = array(0,1);
        $orderLabel = array('Order by section (if used), then Last name','Order by Last name');
        AppUtility::writeHtmlSelect("usersort", $orderVal, $orderLabel, $gbScheme['usersort']);
        ?>
	</span><br class=form />

    <span class=form>Links show:</span>
	<span class=formright>
		<?php
        $orderVal = array(0,1);
        $orderLabel = array('Full Test','Question Breakdown');
        AppUtility::writeHtmlSelect("gbmode100", $orderVal, $orderLabel, $links);
        ?>
	</span><br class=form />

    <span class=form>Default show by availability: </span>
	<span class=formright>
		<?php
        $orderVal = array(0,3,4,1,2);
        $orderLabel = array('Past Due Items','Past &amp; Attempted Items','Available Items Only','Past &amp; Available Items','All Items');
        AppUtility::writeHtmlSelect("gbmode1", $orderVal, $orderLabel, $availShow);
        ?>
	</span><br class=form>

    <span class=form>Not Counted (NC) items: </span>
	<span class=formright>
		<?php
        $orderVal = array(0,1,2);
        $orderLabel = array('Show NC items','Show NC items not hidden from students','Hide NC items');
        AppUtility::writeHtmlSelect("gbmode10", $orderVal, $orderLabel, $hideNc);
        ?>
	</span><br class=form>

        <span class=form>Locked Students:</span>
	<span class=formright>
		<input type=radio name="gbmode200" value="0"  id="lockstu0" <?php AppUtility::writeHtmlChecked($hideLocked, 0);?>/><label for="lockstu0">Show</label>
		<input type=radio name="gbmode200" value="2"  id="lockstu2" <?php AppUtility::writeHtmlChecked($hideLocked, 2);?>/><label for="lockstu2">Hide</label>
	</span><br class=form />

        <span class=form>Default Colorization:</span>
	<span class=formright>
	<?php AppUtility::writeHtmlSelect("colorize", $colorVal, $colorLabel, $colorize); ?>
	</span><br class=form />

        </span><br class=form />
        <span class=form>Totals columns show on:</span>
	<span class=formright>
		<input type=radio name="gbmode1000" value="0" id="totside0" <?php AppUtility::writeHtmlChecked($totOnLeft, 0);?>/><label for="totside0">Right</label>
		<input type=radio name="gbmode1000" value="1" id="totside1" <?php AppUtility::writeHtmlChecked($totOnLeft, 1);?>/><label for="totside1">Left</label>
	</span><br class=form />

        <span class=form>Average row shows on:</span>
	<span class=formright>
		<input type=radio name="gbmode1002" value="0" id="avgloc0" <?php AppUtility::writeHtmlChecked($avgOnTop, 0);?>/><label for="avgloc0">Bottom</label>
		<input type=radio name="gbmode1002" value="2" id="avgloc2" <?php AppUtility::writeHtmlChecked($avgOnTop, 2);?>/><label for="avgloc2">Top</label>
	</span><br class=form />

        <span class=form>Include details:</span>
	<span class=formright>
		<input type="checkbox" name="gbmode4000" value="4" id="llcol" <?php AppUtility::writeHtmlChecked($lastLogin,true);?>/><label for="llcol">Last Login column</label><br/>
		<input type="checkbox" name="gbmode400" value="4" id="duedate" <?php AppUtility::writeHtmlChecked($includeDuDate,true);?>/><label for="duedate">Due Date in column headers, and column in single-student view</label><br/>
		<input type="checkbox" name="gbmode40" value="4" id="lastchg" <?php AppUtility::writeHtmlChecked($includeLastChange,true);?>/><label for="lastchg">Last Change column in single-student view</label>
	</span><br class=form />

        <span class="form">Totals to show students:</span>
	<span class=formright>
		<input type="checkbox" name="stugbmode1" value="1" id="totshow1" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 1, 1);?>/><label for="totshow1">Past Due</label><br/>
		<input type="checkbox" name="stugbmode2" value="2" id="totshow2" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 2, 2);?>/><label for="totshow2">Past Due and Attempted</label><br/>
		<input type="checkbox" name="stugbmode4" value="4" id="totshow4" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 4, 4);?>/><label for="totshow4">Past Due and Available</label><br/>
		<input type="checkbox" name="stugbmode8" value="8" id="totshow8" <?php AppUtility::writeHtmlChecked(($gbScheme['stugbmode']) & 8, 8);?>/><label for="totshow8">All (including future)</label><br/>
	</span><br class="form" />
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
        echo "<table class='gb table table-bordered table-striped table-hover data-table'><thead>";
        echo "<tr><th>Category Name</th><th>Display<sup>*</sup></th><th>Scale (optional)</th><th>Drops &amp; Category total</th><th id=weighthdr>";
        if ($useWeights==0) {
            echo "Fixed Category Point Total (optional)<br/>Blank to use point sum";
        } else if ($useWeights==1) {
            echo "Category Weight (%)";
        }
        echo '</th><th>Remove</th></tr></thead><tbody id="cattbody">';
        disprow(0, $row, $hideLabel, $hideVal);
        foreach($gbCategory as $category){
        disprow($category['id'], $category, $hideLabel, $hideVal);
        }
        echo "</tbody></table>";
        echo '<p><input type="button" class ="btn btn-primary" value="'.AppUtility::t('Add New Category', false).'" onclick="addCat()" /></p>';
        echo '</fieldset>';
        echo '<div class="submit"><input class ="btn btn-primary" type=submit name=submit value="'.AppUtility::t('Save Changes', false).'"/></div>';
        echo "</form>";
        echo '<p class="small"><sup>*</sup>When a category is set to Expanded, both the category total and all items in the category are displayed.<br/> ';
        echo 'When a category is set to Collapsed, only the category total is displayed, but all the items are still counted normally.<br/>';
        echo 'When a category is set to Hidden, nothing is displayed, and no items from the category are counted in the grade total. </p>';
        echo '<p class="small"><sup>*</sup>If you drop any items, a calc type of "average percents" is required. If you are using a points earned / possible ';
        echo 'scoring system and use the "average percents" method in a category, the points for the category may be a somewhat arbitrary value.</p>';

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
        echo "<td>Scale <input type=text size=3 name=\"scale[$id]\" value=\"";
        if ($row['scale']>0) {
            echo $row['scale'];
        }
        echo "\"/> (<input type=radio name=\"st[$id]\" value=0 ";
        if ($row['scaletype']==0) {
            echo "checked=1 ";
        }
        echo "/>points ";
        echo "<input type=radio name=\"st[$id]\" value=1 ";
        if ($row['scaletype']==1) {
            echo "checked=1 ";
        }
        echo "/>percent)<br/>to perfect score<br/>";
        echo "<input type=checkbox name=\"chop[$id]\" value=1 ";
        if ($row['chop']>0) {
            echo "checked=1 ";
        }
        echo "/> no total over <input type=text size=3 name=\"chopto[$id]\" value=\"";
        if ($row['chop']>0) {
            echo round($row['chop']*100);
        } else {
            echo "100";
        }
        echo "\"/>%</td>";
        echo "<td>";
        echo 'Calc total: <select name="calctype['.$id.']" id="calctype'.$id.'" ';
        if ($row['dropn']!=0) { echo 'disabled="true"';}
        echo '><option value="0" ';
        if ($row['calctype']==0) {echo 'selected="selected"';}
        echo '>point total</option><option value="1" ';
        if ($row['calctype']==1) {echo 'selected="selected"';}
        echo '>averaged percents</option></select><br/>';

        echo "<input type=radio name=\"droptype[$id]\" value=0 onclick=\"calcTypeChange($id,0)\" ";
        if ($row['dropn']==0) {
            echo "checked=1 ";
        }
        echo "/>Keep All<br/><input type=radio name=\"droptype[$id]\" value=1 onclick=\"calcTypeChange($id,1)\" ";
        if ($row['dropn']>0) {
            echo "checked=1 ";
        }
        $absr4=abs($row['dropn']);
        echo "/>Drop lowest <input type=text size=2 name=\"dropl[$id]\" value=\"$absr4\"/> scores<br/> <input type=radio name=\"droptype[$id]\" value=2 onclick=\"calcTypeChange($id,1)\" ";
        if ($row['dropn']<0) {
            echo "checked=1 ";
        }
        echo "/>Keep highest <input type=text size=2 name=\"droph[$id]\" value=\"$absr4\"/> scores</td>";
        echo "<td><input type=text size=3 name=\"weight[$id]\" value=\"";
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

