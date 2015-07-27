$(document).ready(function () {
    $('#checkNone').click(function() {
        $('#list input[type="checkbox"]').prop('checked', false);
    });
    $('#checkAll').click(function() {
        $('#list input[type="checkbox"]').prop('checked', true);
    });
});