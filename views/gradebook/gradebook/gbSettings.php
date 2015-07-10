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
<form id="theform" method=post action="gb-settings?cid=<?php echo $course->id;?>" onsubmit="prepforsubmit()">
    <span class=form>Calculate total using:</span>
	<span class=formright>
<!--        hardcoded checked in radio list Insert logic for checked in radio list      -->
		<input type=radio name=useweights value="0" id="usew0" checked onclick="swapweighthdr(0)"/><label for="usew0">points earned / possible</label><br/>
		<input type=radio name=useweights value="1" id="usew1" onclick="swapweighthdr(1)"/><label for="usew1">category weights</label>
	</span>
    <br class=form />
    <p><a href="#" onclick="toggleadv(this);return false">Edit view settings</a></p>

    <fieldset id="viewfield"><legend>Default gradebook view:</legend>

        <span class="form">Gradebook display:</span>
	<span class="formright">
		Order: <select name="orderby" id="orderby">
            <option value="0" selected="">by end date, old to new</option>
            <option value="4">by end date, new to old</option>
            <option value="6">by start date, old to new</option>
            <option value="8">start date, new to old</option>
            <option value="2">alphabetically</option>
            <option value="10">by course page order, offline at end</option>
            <option value="12">by course page order reversed, offline at start</option>
        </select>
		<br>
		<input type="checkbox" name="grouporderby" value="1" id="grouporderby"><label for="grouporderby">Group by category first</label>
	</span><br class="form">

        <span class="form">Default user order:</span>
	<span class="formright">
		<select name="usersort" id="usersort">
            <option value="0" selected="">Order by section (if used), then Last name</option>
            <option value="1">Order by Last name</option>
        </select>
	</span><br class="form">

        <span class="form">Links show:</span>
	<span class="formright">
		<select name="gbmode100" id="gbmode100">
            <option value="0" selected="">Full Test</option>
            <option value="1">Question Breakdown</option>
        </select>
	</span><br class="form">

        <span class="form">Default show by availability: </span>
	<span class="formright">
		<select name="gbmode1" id="gbmode1">
            <option value="0">Past Due Items</option>
            <option value="3">Past &amp; Attempted Items</option>
            <option value="4">Available Items Only</option>
            <option value="1" selected="">Past &amp; Available Items</option>
            <option value="2">All Items</option>
        </select>
	</span><br class="form">

        <span class="form">Not Counted (NC) items: </span>
	<span class="formright">
		<select name="gbmode10" id="gbmode10">
            <option value="0">Show NC items</option>
            <option value="1">Show NC items not hidden from students</option>
            <option value="2" selected="">Hide NC items</option>
        </select>
	</span><br class="form">

        <span class="form">Locked Students:</span>
	<span class="formright">
		<input type="radio" name="gbmode200" value="0" id="lockstu0" checked=""><label for="lockstu0">Show</label>
		<input type="radio" name="gbmode200" value="2" id="lockstu2"><label for="lockstu2">Hide</label>
	</span><br class="form">

        <span class="form">Default Colorization:</span>
	<span class="formright">
	<select name="colorize" id="colorize">
        <option value="0" selected="">No Color</option>
        <option value="50:60">red ≤ 50%, green ≥ 60%</option>
        <option value="50:70">red ≤ 50%, green ≥ 70%</option>
        <option value="50:75">red ≤ 50%, green ≥ 75%</option>
        <option value="50:80">red ≤ 50%, green ≥ 80%</option>
        <option value="50:85">red ≤ 50%, green ≥ 85%</option>
        <option value="50:90">red ≤ 50%, green ≥ 90%</option>
        <option value="50:95">red ≤ 50%, green ≥ 95%</option>
        <option value="60:70">red ≤ 60%, green ≥ 70%</option>
        <option value="60:75">red ≤ 60%, green ≥ 75%</option>
        <option value="60:80">red ≤ 60%, green ≥ 80%</option>
        <option value="60:85">red ≤ 60%, green ≥ 85%</option>
        <option value="60:90">red ≤ 60%, green ≥ 90%</option>
        <option value="60:95">red ≤ 60%, green ≥ 95%</option>
        <option value="70:75">red ≤ 70%, green ≥ 75%</option>
        <option value="70:80">red ≤ 70%, green ≥ 80%</option>
        <option value="70:85">red ≤ 70%, green ≥ 85%</option>
        <option value="70:90">red ≤ 70%, green ≥ 90%</option>
        <option value="70:95">red ≤ 70%, green ≥ 95%</option>
        <option value="75:80">red ≤ 75%, green ≥ 80%</option>
        <option value="75:85">red ≤ 75%, green ≥ 85%</option>
        <option value="75:90">red ≤ 75%, green ≥ 90%</option>
        <option value="75:95">red ≤ 75%, green ≥ 95%</option>
        <option value="80:85">red ≤ 80%, green ≥ 85%</option>
        <option value="80:90">red ≤ 80%, green ≥ 90%</option>
        <option value="80:95">red ≤ 80%, green ≥ 95%</option>
        <option value="85:90">red ≤ 85%, green ≥ 90%</option>
        <option value="85:95">red ≤ 85%, green ≥ 95%</option>
        <option value="-1:-1">Active</option>
    </select>
	</span><br class="form">

        <br class="form">
        <span class="form">Totals columns show on:</span>
	<span class="formright">
		<input type="radio" name="gbmode1000" value="0" id="totside0" checked=""><label for="totside0">Right</label>
		<input type="radio" name="gbmode1000" value="1" id="totside1"><label for="totside1">Left</label>
	</span><br class="form">

        <span class="form">Average row shows on:</span>
	<span class="formright">
		<input type="radio" name="gbmode1002" value="0" id="avgloc0" checked=""><label for="avgloc0">Bottom</label>
		<input type="radio" name="gbmode1002" value="2" id="avgloc2"><label for="avgloc2">Top</label>
	</span><br class="form">

        <span class="form">Include details:</span>
	<span class="formright">
		<input type="checkbox" name="gbmode4000" value="4" id="llcol"><label for="llcol">Last Login column</label><br>
		<input type="checkbox" name="gbmode400" value="4" id="duedate"><label for="duedate">Due Date in column headers, and column in single-student view</label><br>
		<input type="checkbox" name="gbmode40" value="4" id="lastchg"><label for="lastchg">Last Change column in single-student view</label>
	</span><br class="form">

        <span class="form">Totals to show students:</span>
	<span class="formright">
		<input type="checkbox" name="stugbmode1" value="1" id="totshow1" checked=""><label for="totshow1">Past Due</label><br>
		<input type="checkbox" name="stugbmode2" value="2" id="totshow2" checked=""><label for="totshow2">Past Due and Attempted</label><br>
		<input type="checkbox" name="stugbmode4" value="4" id="totshow4" checked=""><label for="totshow4">Past Due and Available</label><br>
		<input type="checkbox" name="stugbmode8" value="8" id="totshow8"><label for="totshow8">All (including future)</label><br>
	</span><br class="form">
    </fieldset>


    <fieldset><legend>Gradebook Categories</legend>
        <table class="gb"><thead><tr><th>Category Name</th><th>Display<sup>*</sup></th><th>Scale (optional)</th><th>Drops &amp; Category total</th><th id="weighthdr">Fixed Category Point Total (optional)<br>Blank to use point sum</th><th>Remove</th></tr></thead><tbody id="cattbody"><tr class="grid" id="catrow0"><td>Default</td><td><select name="hide[0]" id="hide[0]">
                        <option value="1">Hidden</option>
                        <option value="0" selected="">Expanded</option>
                        <option value="2">Collapsed</option>
                    </select>
                </td><td>Scale <input type="text" size="3" name="scale[0]" value=""> (<input type="radio" name="st[0]" value="0" checked="1">points <input type="radio" name="st[0]" value="1">percent)<br>to perfect score<br><input type="checkbox" name="chop[0]" value="1" checked="1"> no total over <input type="text" size="3" name="chopto[0]" value="100">%</td><td>Calc total: <select name="calctype[0]" id="calctype0"><option value="0" selected="selected">point total</option><option value="1">averaged percents</option></select><br><input type="radio" name="droptype[0]" value="0" onclick="calctypechange(0,0)" checked="1">Keep All<br><input type="radio" name="droptype[0]" value="1" onclick="calctypechange(0,1)">Drop lowest <input type="text" size="2" name="dropl[0]" value="0"> scores<br> <input type="radio" name="droptype[0]" value="2" onclick="calctypechange(0,1)">Keep highest <input type="text" size="2" name="droph[0]" value="0"> scores</td><td><input type="text" size="3" name="weight[0]" value=""></td><td></td></tr><tr class="grid" id="catrow1"><td><input type="text" name="name[1]" value="newCat"></td><td><select name="hide[1]" id="hide[1]">
                        <option value="1">Hidden</option>
                        <option value="0" selected="">Expanded</option>
                        <option value="2">Collapsed</option>
                    </select>
                </td><td>Scale <input type="text" size="3" name="scale[1]" value=""> (<input type="radio" name="st[1]" value="0" checked="1">points <input type="radio" name="st[1]" value="1">percent)<br>to perfect score<br><input type="checkbox" name="chop[1]" value="1" checked="1"> no total over <input type="text" size="3" name="chopto[1]" value="100">%</td><td>Calc total: <select name="calctype[1]" id="calctype1"><option value="0" selected="selected">point total</option><option value="1">averaged percents</option></select><br><input type="radio" name="droptype[1]" value="0" onclick="calctypechange(1,0)" checked="1">Keep All<br><input type="radio" name="droptype[1]" value="1" onclick="calctypechange(1,1)">Drop lowest <input type="text" size="2" name="dropl[1]" value="0"> scores<br> <input type="radio" name="droptype[1]" value="2" onclick="calctypechange(1,1)">Keep highest <input type="text" size="2" name="droph[1]" value="0"> scores</td><td><input type="text" size="3" name="weight[1]" value=""></td><td><a href="#" onclick="removeexistcat(1);return false;">Remove</a></td></tr></tbody></table><p><input type="button" value="Add New Category" onclick="addcat()"></p></fieldset>

    <div class="submit"><input type="submit" name="submit" value="Save Changes"></div>

    <p class="small"><sup>*</sup>When a category is set to Expanded, both the category total and all items in the category are displayed.<br> When a category is set to Collapsed, only the category total is displayed, but all the items are still counted normally.<br>When a category is set to Hidden, nothing is displayed, and no items from the category are counted in the grade total. </p>
    <p class="small"><sup>*</sup>If you drop any items, a calc type of "average percents" is required. If you are using a points earned / possible scoring system and use the "average percents" method in a category, the points for the category may be a somewhat arbitrary value.</p>


</form>
