$('.confirmation-late-pass').click(function(e){
    var linkId = $(this).attr('id');
    var latePass = $('#late-pass'+linkId).val();
    var latePassHrs = $('#late-pass-hrs'+linkId).val();
    var useLatePass = ((latePass%10) - 1);
    var html = '<div><p>You may use up to '+useLatePass+' more LatePass(es) on this assessment.</p>' +
        '<p>You have ' +latePass+'  LatePass(es) remaining.  You can redeem one LatePass for a '+latePassHrs+' hour extension on this assessment.</p> ' +
        '<p>Are you sure you want to redeem a LatePass?</p></div>';
    var cancelUrl = $(this).attr('href');
    e.preventDefault();
    $('<div  id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                window.location = cancelUrl;
                $(this).dialog("close");
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
});
