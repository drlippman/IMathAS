<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use app\components\AppConstant;
use app\components\AppUtility;
$this->title = 'AddOutComes';
$this->params['breadcrumbs'][] = $this->title;
?>

<div><h3>Course Outcomes</h3></div>
<div class="cpmid">
<a href="#">View Outcomes Map</a> |
<a href="#">View Outcomes Report</a>
</div>
<div class="breadcrumb">Use colored boxes to drag-and-drop order and move outcomes inside groups.
<input type="button" class="btn btn-primary" id="Save-changes"  value="Save Changes">
<input type="hidden" id="course-id" value="<?php echo $courseid?>">
</div>
<div><ul id="qviewtree" class="qview"></ul></div>
<button  onclick=addoutcomegrp() class="btn btn-primary ">Add Outcome Group</button>
<button  onclick=addoutcome() class="btn btn-primary ">Add Outcome</button>
<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>

<script>

    var cnt=0;
    function addoutcomegrp(){
        var html = '<li class="blockli" id="newgrp"><span class=icon style="background-color:#66f">G</span>';
        html += '<input class="outcome" type="text" size="60" id="newgrp'+cnt+'" > ';
        html += '<a href="#" onclick=removeoutcomegrp(this);return false> Delete</a></li>';
        $("#qviewtree").append(html);
        $("#newgrp"+cnt).focus();
        cnt++;

    }
    function addoutcome() {
        var html = '<li id="newocnt"><span class=icon style="background-color:#0f0">O</span>';
        html += '<input class="outcome" type="text" size="60" id="new'+cnt+'">';
        html += '<a href="#" onclick=removeoutcome(this);return false> Delete</a></li>';
        $("#qviewtree").append(html);
        $("#new"+cnt).focus();
        cnt++;

    }
    function removeoutcome(el)
    {

        var deleteoutcome = $(el).parent();
        deleteoutcome.find("li").each(function(){
           deleteoutcome.before($(el));
        });
        deleteoutcome.remove();
    }
    function removeoutcomegrp(el)
    {

        var deleteoutcome = $(el).parent();
        deleteoutcome.find("li").each(function(){
            deleteoutcome.before($(el));
        });
        deleteoutcome.remove();
    }
    $('#Save-changes').click(function()
    {
     var outcomearray= [];
        var els = $(".outcome");
        var courseid = $('#course-id').val();
        $('#Save-changes').parent().append('<span id="submitnotice" style="color:red;">Saving Changes...</span>');
        for (var i=0; i<els.length; i++)
        {
            var outcome = $('#new' + i).val();
            outcomearray.push(outcome);
        }
        jQuerySubmit('get-outcome-ajax',{outcomearray:outcomearray,courseid:courseid},'outcomeResponse');
     });

    function outcomeResponse(response)
    {
        $('#submitnotice').remove();
    }
</script>