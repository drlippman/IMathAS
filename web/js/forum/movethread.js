
$(document).ready(function() {
    $('#move-forum').show();
    $('#move-thread').hide();

    $('#myForm input').on('change', function() {
        var v=$('input[name="movetype"]:checked', '#myForm').val();

        if (v==0) {
            $('#move-forum').show();
            $('#move-thread').hide();
        }
        if (v==1) {
            $('#move-forum').hide();
            $('#move-thread').show();
        }


        $("#move-button").click(function () {
            var forum_id =  $('input[name="forum-name"]:checked', '#myForm').val();

        });
    });
});
