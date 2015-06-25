$(document).ready(function () {
    var tagValue = $("#tag-id").val();
    if(tagValue == 0){
        $('#unflag-link').hide();
    }else{
        $('#flag-link').hide();
    }

    $("a[name=remove]").on("click", function () {
        var threadid = $(this).attr("data-var");

        var checkPostOrThread = 0;

        var html = '<div><p>Are you sure? This will remove your thread.</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "confirm": function () {
                    $(this).dialog("close");
                    var threadId = threadid;
                    jQuerySubmit('mark-as-remove-ajax', {threadId:threadId,checkPostOrThread:checkPostOrThread}, 'markAsRemoveSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });
});

function toggleshow(bnum) {
    var node = document.getElementById('block' + bnum);
    var butn = document.getElementById('butb' + bnum);
    if (node.className == 'forumgrp') {
        node.className = 'hidden';
        //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
        //       butn.value = 'Expand';
        butn.src = '../../img/expand.gif';
    } else {
        node.className = 'forumgrp';
        //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
        //       butn.value = 'Collapse';
        butn.src = '../../img/Collapse.gif';
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
        butn.src = '../../img/Collapse.gif';
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
        butn.src = '../../img/expand.gif';
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
function markAsRemoveSuccess(response) {
    var forumid = $("#forum-id").val();
    var courseid = $("#course-id").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forumid="+forumid;
    }

}
function changeImage(checkFlagValue, rowId) {

    if(checkFlagValue == false){
        $('#flag-link').hide();
        $('#unflag-link').show();
    }
    if(checkFlagValue ==true ){
        $('#unflag-link').hide();
        $('#flag-link').show();
    }
    var row = {rowId: rowId};
    jQuerySubmit('change-image-ajax', row, 'changeImageSuccess');

}
function changeImageSuccess(response) {
    var forumid = $("#forum-id").val();
    var courseid = $("#course-id").val();
    var result = JSON.parse(response);
    if(result.status == 0)
    {
        window.location = "thread?cid="+courseid+"&forumid="+forumid;
    }
}
function markAsUnreadPost(){
    var threadId = $("#thread-id").val();
    var userId = $("#user-id").val();
    rowId = -1;
    var row = {rowId: rowId,userId:userId,threadId:threadId};
    jQuerySubmit('change-image-ajax', row, 'changeImageSuccess');
}
var  flag =0;
function changeProfileImage(element,id)
{
    if(flag == 0 )
    {
       element.style.width = "100px";
        element.style.height = "105px";
        document.getElementById(id).style.height = "109px";
        flag =1;
    }else
    {
        element.style.width = "47px";
        element.style.height = "47px";
        document.getElementById(id).style.height = "10%";
        flag=0;
    }

}

function saveLikes(el,element,id,threadid,type)
{

    var courseid =$('#course-id').val();
    if(element == true)
    {

      like = 0;
        $(el).parent().append('<img style="vertical-align: middle" src="../../img/updating.gif" id="updating"/>');
     jQuerySubmit('like-post-ajax',{id:id,threadid:threadid,type:type,like:like},'likepostresponse');

    }
    else
    {

        like =1;
        $(el).parent().append('<img style="vertical-align: middle" src="../../img/updating.gif" id="updating"/>');
        jQuerySubmit('like-post-ajax',{id:id,threadid:threadid,type:type,like:like},'likepostresponse');

    }

}

function likepostresponse(response)
{
    response = JSON.parse(response);
    $('#updating').remove();
    console.log(response);
    if(response.status == 0)
    {
        window.location.reload();

    }
}

function countPopup(id,threadid,type)
{
    jQuerySubmit('data-like-post-ajax',{id:id,threadid:threadid,type:type},'showPopup');

}

function showPopup(response)
{

    response = JSON.parse(response);

    if(response.status == 0)
    {
        var countData = response.data.displayCountData;

        var html = '<div id="postid"><p>Post Likes : </p></div><p>';
        $.each(countData, function (index, data) {
         html += '<pre><span class="col-lg-12 pull-left " >'+ data.userName +'</span></pre>'
         });
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }

            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    }
}
