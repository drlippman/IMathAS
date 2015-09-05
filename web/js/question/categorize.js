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
    $('input:checked').each(function() {
        var n = $(this).attr('id').substr(1);
        $('#'+n).val(val);
    });
}

function resetcat() {
    if (confirm("Are you SURE you want to reset all categories to Uncategorized/Default?")) {
        $('select.qsel').val(0);
    }
}
