$(document).ready(function () {
    selectCheckBox();
    $('input[name = "categorize-question-header-checked"]:checked').prop('checked', false);
    $('.categorize-question-table').DataTable();
});
function addcategory() {
    var name = document.getElementById("newcat").value;
    $('select optgroup[label=Custom]').append('<option value="'+name+'">'+name+'</option>');
    document.getElementById("newcat").value='';
}

function quickpick() {
    $('select.qsel').each(function() {
        if ($(this).val()==0) {
            $(this).find('optgroup[label=Libraries] option:first').prop('selected',true);
        }
    });
}

function massassign() {
    var val = $('#masssel').val();
    $('#categorize-question-information-table input:checked').each(function() {
        var n = $(this).attr('id').substr(1);
        $('#'+n).val(val);
    });
}

function resetcat() {
    if (confirm("Are you SURE you want to reset all categories to Uncategorized/Default?")) {
        $('select.qsel').val(0);
    }
}

function selectCheckBox() {
    $('.categorize-question-table input[name = "categorize-question-header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#categorize-question-information-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#categorize-question-information-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}