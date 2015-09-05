function libselect() {
    window.open('library-tree?libtree=popup&cid=<?php echo $course->id;?>&selectrights=1&libs='+curlibs+'&locklibs='+locklibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
    if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
        libs = libs.substring(2);
    }
    document.getElementById("libs").value = libs;
    curlibs = libs;
}
function setlibnames(libn) {
    if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
        libn = libn.substring(11);
    }
    document.getElementById("libnames").innerHTML = libn;
}
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
