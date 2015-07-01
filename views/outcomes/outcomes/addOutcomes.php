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
<input type="hidden" id="course-id" value="<?php echo $courseId?>">
</div>
<div><ul id="qviewtree" class="qview"></ul></div>
<button  onclick=addOutcomeGrp() class="btn btn-primary ">Add Outcome Group</button>
<button  onclick=addOutcome() class="btn btn-primary ">Add Outcome</button>
<style type="text/css">.drag {color:red; background-color:#fcc;} .icon {cursor: pointer;} ul.qview li {padding: 3px}</style>



<script>
    $(document).ready(function ()
    {
        var courseId = $('#course-id').val();
        jQuerySubmit('get-outcome-data-ajax',{courseId:courseId},'outcomeDataResponse');
    });
    var cnt=0;
    function addOutcomeGrp(){
        var html = '<li class="blockli" id="newgrp"><span class=icon style="background-color:#66f">G</span>';
        html += '<input class="outcomeGrp" type="text" size="60" id="newgrp'+cnt+'" > ';
        html += '<a href="#" onclick=removeOutcomeGrp(this);return false> Delete</a></li>';
        $("#qviewtree").append(html);
        $("#newgrp"+cnt).focus();
        cnt++;
    }
    function addOutcome() {
        var html = '<li id="newocnt"><span class=icon style="background-color:#0f0">O</span>';
        html += '<input class="outcome" type="text" size="60" id="new'+cnt+'">';
        html += '<a href="#" onclick=removeOutcome(this);return false> Delete</a></li>';
        $("#qviewtree").append(html);
        $("#new"+cnt).focus();
        cnt++;
    }
    function removeOutcome(el)
    {
        var deleteOutcome = $(el).parent();
        deleteOutcome.find("li").each(function(){
           deleteOutcome.before($(el));
        });
        deleteOutcome.remove();
    }
    function removeOutcomeGrp(el)
    {

        var deleteOutcome = $(el).parent();
        deleteOutcome.find("li").each(function(){
            deleteOutcome.before($(el));
        });
        deleteOutcome.remove();
    }
    $('#Save-changes').click(function()
    {
     var outcomeArray= [];
        var outcomeGrpArray= [];
        var els = $(".outcome");
        var groupLen = $(".outcomeGrp");
        var courseId = $('#course-id').val();
        $('#Save-changes').parent().append('<span id="submitnotice" style="color:red;">Saving Changes...</span>');
        for (var i=0; i<els.length; i++)
        {
            var outcome = $('#new' + i).val();
            outcomeArray.push(outcome);
        }
        for (var j=0; j<groupLen.length; j++)
        {
            var outcomeGrp = $('#newgrp' + j).val();
            outcomeGrpArray.push(outcomeGrp);
        }
        if(outcomeArray.length > 0)
        {
            jQuerySubmit('get-outcome-ajax',{outcomeArray:outcomeArray,courseId:courseId},'outcomeResponse');
        }
        if(outcomeGrpArray.length > 0)
        {
            jQuerySubmit('get-outcome-grp-ajax',{outcomeGrpArray:outcomeGrpArray,courseId:courseId},'outcomeGrpResponse');
        }

     });

    function outcomeResponse(response)
    {
        $('#submitnotice').remove();
    }
    function outcomeGrpResponse(response)
    {
        $('#submitnotice').remove();
    }
    function outcomeDataResponse(response)
    {
        response = JSON.parse(response);
        var html="";
        if(response.status == 0)
        {
            var outcomeGrp = response.data.courseOutcome;
            var outcome = response.data.outcomeData;
            $.each(outcomeGrp, function(index,group)
            {
                html += '<li class="blockli" id="newgrp">';
                html +='<span class=icon style="background-color:#66f">G</span>';
                html += '<input class="outcomeGrp" type="text" size="60" id="newgrp" value="'+group+'" > ';
                html += '<a href="#" onclick=removeOutcomeGrp(this);return false> Delete</a></li>';
            });
            $("#qviewtree").append(html);

            $.each(outcome, function(index,group)
            {
                html = '<li id="newocnt">';
                html +='<span class=icon style="background-color:#0f0">O</span>';
                html += '<input class="outcome" type="text" size="60" id="new" value="'+group.name+'">';
                html += '<a href="#" onclick=removeOutcome(this);return false> Delete</a></li>';
                $("#qviewtree").append(html);
            });
        }
    }post
</script>