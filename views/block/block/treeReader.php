<?php
use app\components\AppUtility;
$imasroot = AppUtility::getURLFromHome('block', 'block/tree-reader?cid='.$courseId.'&folder='.$params['folder'].'&recordbookmark=" + id');
$imasroot1 = AppUtility::getHomeURL();

$this->title = $blockName;
$this->params['breadcrumbs'][] = ['label' => $course->name, 'url' => ['/course/course/course?cid='.$course->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="item-detail-header">
    <?php echo $this->render("../../itemHeader/_indexWithLeftContent",['link_title'=>['Home',$course->name], 'link_url' => [AppUtility::getHomeURL().'site/index',AppUtility::getHomeURL().'course/course/course?cid='.$course->id], 'page_title' => $this->title]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div id="leftcontent" style="width: 250px; margin-left: 20px">
<img id="navtoggle" src="<?php echo $imasroot1;?>img/collapse.gif"  onclick="toggletreereadernav()"/>
<ul id="leftcontenttext" class="nomark" style="margin-left:5px; font-size: 90%;">
<?php
$ul = $printList;
echo $ul[0];
?>
</ul>
<div id="bmrecout" style="display:none;"></div>
</div>
<div id="centercontent" style="margin-left: 260px;">
    <iframe id="readerframe" name="readerframe" style="width:100%; border:1px solid #ccc;" src="<?php echo ($openitem=='')?$foundfirstitem:$foundopenitem; ?>"></iframe>
</div>


<script type="text/javascript">
    $(document).ready(function(){
        addLoadEvent(resizeiframe);
    });
    function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById("b"+id);
	if (node.className.match("show")) {
		node.className = node.className.replace(/show/,"hide");
		button.innerHTML = "+";
	} else {
		node.className = node.className.replace(/hide/,"show");
		button.innerHTML = "-";
	}
}
function resizeiframe() {
	var windowheight = document.documentElement.clientHeight;
	var theframe = document.getElementById("readerframe");
	var framepos = findPos(theframe);
	var height =  (windowheight - framepos[1] - 15);
	theframe.style.height =height + "px";
}

function recordlasttreeview(id) {
	var url = "'.$imasroot.' ";
	basicahah(url, "bmrecout");
}
var treereadernavstate = 1;
function toggletreereadernav() {
	if (treereadernavstate == 1) {
        document.getElementById("leftcontent").style.width = "28px";
		document.getElementById("leftcontenttext").style.display = "none";
		document.getElementById("centercontent").style.marginLeft = "30px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/collapse/,"expand");
	} else {
		document.getElementById("leftcontent").style.width = "250px";
		document.getElementById("leftcontenttext").style.display = "";
		document.getElementById("centercontent").style.marginLeft = "260px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/expand/,"collapse");
	}
	resizeiframe();
	treereadernavstate = (treereadernavstate+1)%2;
}
function updateTRunans(aid, status) {
	var urlbase = "'.$imasroot1.'";
	if (status==0) {
		document.getElementByI1d("aimg"+aid).src = urlbase+"/img/q_fullbox.gif";
	} else if (status==1) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_halfbox.gif";
	} else {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_emptybox.gif";
	}
}
//addLoadEvent(resizeiframe);

</script>