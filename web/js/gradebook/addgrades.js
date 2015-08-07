$(document).ready(function () {
    createDataTable('student-data');
    $('.student-data').DataTable();
    togglefeedbackTextFields(-1);
    checkAssessmentSnapshot();
    quickSearchAjax();
});
function appendPrependReplaceText(value) {
    var feedback_txt = document.getElementById("feedback_txt").value;
    if (value == 1) {
        $(".feedback-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback + feedback_txt);
        });

    } else if (value == 2) {
        $(".feedback-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback_txt);
        });
    } else if (value == 3) {
        $(".feedback-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback_txt + feedback);
        });
    }
}
function togglefeedbackTextFields(value) {

var form = document.getElementsByTagName('textarea');
    for (i = 0; i < form.length; i++) {
        elementValue = form[i];
        if (elementValue.type == 'textarea') {
            if (elementValue.rows == 1 && value == -1) {
                elementValue.rows = 4;
                $('#expand-button').hide();
                $('#shrink-button').show();
            } else {
                elementValue.rows = 1;
                $('#shrink-button').hide();
                $('#expand-button').show();
            }
        }
    }
}

var quickaddshowing = false;
function togglequickadd(el) {
    if (!quickaddshowing) {
        document.getElementById("quickadd").style.display = "";
        $(el).html(_("Hide Quicksearch Entry"));
        quickaddshowing = true
    } else {
        document.getElementById("quickadd").style.display = "none";
        $(el).html(_("Show Quicksearch Entry"));
        quickaddshowing = false;
    }
}

function checkAssessmentSnapshot() {

    $(".assessment_snapshot").change(function () {
        if ($(".assessment_snapshot:checked").val()) {
            $(".change-assessment-snapshot-content").show();
            $(".change-non-assessment-snapshot-content").hide();
        } else {
            $(".change-assessment-snapshot-content").hide();
            $(".change-non-assessment-snapshot-content").show();
        }
    });

    if ($("#assessment_snapshot:checked").val() == undefined) {
        $(".change-assessment-snapshot-content").hide();
        $(".change-non-assessment-snapshot-content").show();
    }
    else {
        $(".change-assessment-snapshot-content").show();
        $(".change-non-assessment-snapshot-content").hide();
    }
}

studentDetails = [];
var courseId = $('#course-id').val();
jQuerySubmit('quick-search-ajax',{courseId:courseId},'quickSearchAjax');
//jQuerySubmit('quick-search-ajax',{ },'abcd');
function quickSearchAjax(response){
    response = JSON.parse(response);
    studentDetails = response.data;

    $(function() {

        $( "#project" ).autocomplete({
            minLength: 0,
            source: studentDetails,
            focus: function( event, ui ) {

                $( "#project" ).val( ui.item.label );
                //console.log(dd);
                return false;
            },
            select: function( event, ui ) {
                $( "#project" ).val( ui.item.label );

                $( "#project-id" ).val( ui.item.value );
                //$( "#project-description" ).html( ui.item.desc );


                return false;
            }
        })
            .autocomplete( "instance" )._renderItem = function( ul, item ) {

            //function quickSearchSuccess()
            //{
            //
            //}
            return $( "<li>" )
                .append( "<a>" + item.label + "</a>" )
                .appendTo( ul );
//alert('njo');

        };

    });
}
