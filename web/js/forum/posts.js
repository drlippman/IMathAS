var imasroot = $('.home-path').val();
var bcnt = $('.bcnt-value').val();
var icnt = $('.icnt-value').val();
function toggleshow(bnum) {
    var node = document.getElementById('block'+bnum);
    var butn = document.getElementById('butb'+bnum);
    if (node.className == 'forumgrp') {
    node.className = 'hidden';
    //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
//       butn.value = 'Expand';
butn.src = imasroot+'/img/expand.gif';
} else {
    node.className = 'forumgrp';
    //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
//       butn.value = 'Collapse';
butn.src = imasroot+'/img/collapse.gif';
}
}
function toggleitem(inum) {
    var node = document.getElementById('item'+inum);
    var butn = document.getElementById('buti'+inum);
    if (node.className == 'blockitems') {
    node.className = 'hidden';
    butn.value = 'Show';
    } else {
    node.className = 'blockitems';
    butn.value = 'Hide';
    }
}
function expandall() {
    for (var i=0;i<bcnt;i++) {
        var node = document.getElementById('block'+i);
    var butn = document.getElementById('butb'+i);
    node.className = 'forumgrp';
    //     butn.value = 'Collapse';
    //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
butn.src = imasroot+'/img/collapse.gif';
}
}
function collapseall() {
    for (var i=0;i<bcnt;i++) {
    var node = document.getElementById('block'+i);
    var butn = document.getElementById('butb'+i);
    node.className = 'hidden';
    //     butn.value = 'Expand';
    //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
butn.src = imasroot+'/img/expand.gif';
}
}

function showall() {
//    alert(icnt);
    for (var i=0;i<icnt;i++) {
    var node = document.getElementById('item'+i);
    var buti = document.getElementById('buti'+i);
    node.className = "blockitems";
    buti.value = "Hide";
    }
}
function hideall() {
//    alert(icnt);
    for (var i=0;i<icnt;i++) {
    var node = document.getElementById('item'+i);
    var buti = document.getElementById('buti'+i);
    node.className = "hidden";
    buti.value = "Show";
    }
}
function savelike(el) {
    var like = (el.src.match(/gray/))?1:0;
    var postid = el.id.substring(8);
    $(el).parent().append('<img style="vertical-align: middle" src="../img/updating.gif" id="updating"/>');
    $.ajax({
    url: "recordlikes.php",
//    data: {cid:<?php echo $courseId;?>, postid: postid, like: like},
dataType: "json"
}).done(function(msg) {
    if (msg.aff==1) {
    el.title = msg.msg;
    $('#likecnt'+postid).text(msg.cnt>0?msg.cnt:'');
    el.className = "likeicon"+msg.classn;
    if (like==0) {
    el.src = el.src.replace("liked","likedgray");
    } else {
    el.src = el.src.replace("likedgray","liked");
    }
}
$('#updating').remove();
});
}

