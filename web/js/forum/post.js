$(document).ready(function () {

});

function toggleshow(bnum) {
    var node = document.getElementById('block' + bnum);
    var butn = document.getElementById('butb' + bnum);
    if (node.className == 'forumgrp') {
        node.className = 'hidden';
        //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
        //       butn.value = 'Expand';
        butn.src = imasroot + '/img/expand.gif';
    } else {
        node.className = 'forumgrp';
        //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
        //       butn.value = 'Collapse';
        butn.src = imasroot + '/img/collapse.gif';
    }
}
function toggleitem(inum) {
    var node = document.getElementById('item' + inum);
    var butn = document.getElementById('buti' + inum);
    if (node.className == 'blockitems') {
        node.className = 'hidden';
        butn.value = 'Show';
    } else {
        node.className = 'blockitems';
        butn.value = 'Hide';
    }
}
function expandall() {
    var postCount =  $( "#postCount" ).val();
    for (var i = 0; i < postCount; i++) {
        var node = document.getElementById('block' + i);
        var butn = document.getElementById('butb' + i);
        node.className = 'forumgrp';
        //     butn.value = 'Collapse';
        //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
        butn.src = imasroot + '/img/collapse.gif';
    }
}
function collapseall() {
    var postCount =  $( "#postCount" ).val();
    for (var i = 0; i <= postCount; i++) {
        var node = document.getElementById('block' + i);
        var butn = document.getElementById('butb' + i);
        node.className = 'hidden';
        //     butn.value = 'Expand';
        //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
        butn.src = imasroot + '/img/expand.gif';
    }
}
function showall() {
    var postCount =  $( "#postCount" ).val();
    for (var i = 0; i <= postCount; i++) {
        var node = document.getElementById('item' + i);
        var buti = document.getElementById('buti' + i);
        node.className = "blockitems";
        buti.value = "Hide";
    }
}
function hideall() {
    var postCount =  $( "#postCount" ).val();
    for (var i = 0; i <= postCount; i++) {
        var node = document.getElementById('item' + i);
        var buti = document.getElementById('buti' + i);
        node.className = "hidden";
        buti.value = "Show";
    }
}