$( document ).ready(function() {
    $(".transfer").click(function () {
        var transferTo = $("#seluid option:selected").val();
        var courseId = $("#courseId").val();
        var ownerId = $("#userId").val();

        var transferData = {newOwner: transferTo,cid: courseId,oldOwner: ownerId};
        jQuerySubmit('update-owner', transferData, 'updateSuccess');

    });
});

function updateSuccess(response) {
    var data = JSON.parse(response);
    if (data.status) {
        $("#flash-message").html('<div class="alert alert-success">Ownership transferred successfully.</div>');
    }
}
