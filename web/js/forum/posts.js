var imasroot = $('.home-path').val();
var bcnt = $('.bcnt-value').val();
var icnt = $('.icnt-value').val();
var cid = $('#course-id').val();
function toggleshow(bnum) {
    var node = document.getElementById('block'+bnum);
    var butn = document.getElementById('butb'+bnum);
    if (node.className == 'forumgrp') {
    node.className = 'hidden';
    //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
//       butn.value = 'Expand';
butn.src = imasroot+'img/expand.gif';
} else {
    node.className = 'forumgrp';
    //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
//       butn.value = 'Collapse';
butn.src = imasroot+'img/collapse.gif';
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
butn.src = imasroot+'img/collapse.gif';
}
}
function collapseall() {
    for (var i=0;i<bcnt;i++) {
    var node = document.getElementById('block'+i);
    var butn = document.getElementById('butb'+i);
    node.className = 'hidden';
butn.src = imasroot+'img/expand.gif';
}
}

function showall() {
    for (var i=0;i<icnt;i++) {
    var node = document.getElementById('item'+i);
    var buti = document.getElementById('buti'+i);
    node.className = "blockitems";
    buti.value = "Hide";
    }
}
function hideall() {
    for (var i=0;i<icnt;i++) {
    var node = document.getElementById('item'+i);
    var buti = document.getElementById('buti'+i);
    node.className = "hidden";
    buti.value = "Show";
    }
}
function savelike(el) {
    var cid = $(".courseid").val();
    var like = $('.likecnt').val();
    var postid = el.id.substring(8);
    $(el).parent().append('<img style="vertical-align: middle" src="../../img/updating.gif" id="updating"/>');
    $.ajax({
        url: "record-likes",
        data: {cid: cid, postid: postid, like: like},
        dataType: "json"
}).done(function(msg) {
    if (msg.aff==1) {
    $('.likecnt').val(msg.cnt);
    el.title = msg.msg;
    $('#likecnt'+postid).text(msg.cnt>0?msg.cnt:'');
    if (like>=0) {
        el.className = "likeicon"+msg.classn;
    } else {
        el.className = "likeicon";
    }
}
        
$('#updating').remove();
});
}

$("a[name=remove]").on("click", function (event) {
    event.preventDefault();
    var threadid = $(this).attr("data-var");
    var parentId = $(this).attr("data-parent");
    var checkPostOrThread = 0;
    if(parentId == 0){
        var html = '<div><p>Are you SURE you want to remove this thread and all replies?</p></div>';
    }else{
        var html = '<div><p>Are you SURE you want to remove this post?</p></div>';
    }

    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto',resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                $(this).dialog("close");
                var threadId = threadid;
                jQuerySubmit('mark-as-remove-ajax', {threadId:threadId,checkPostOrThread:checkPostOrThread,parentId:parentId}, 'markAsRemoveSuccess');
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
});

function markAsRemoveSuccess(response) {
    var forumid = $("#forum-id").val();
    var courseid = $("#course-id").val();
    var result = JSON.parse(response);
    var threadId = $("#thread-id").val();
    if(result.data == 0)
    {
        window.location = "thread?cid="+courseid+"&forum="+forumid;
    }else if(result.data != 0)
    {
        window.location = "post?courseid="+courseid+"&threadid="+threadId+"&forumid="+forumid;
    }

}
var  flag =0;
function changeProfileImage(element,id)
{
    if(flag == 0 )
    {
        element.style.width = "105px";
        element.style.height = "120px";
        flag =1;
    }else
    {
        element.style.width = "47px";
        element.style.height = "47px";
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
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }
}