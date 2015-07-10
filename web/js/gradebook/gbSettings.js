$(document).ready(function () {
});

function toggleadv(el) {
    if ($("#viewfield").is(":hidden")) {
        $(el).html("Hide view settings");
        $("#viewfield").slideDown();
    } else {
        $(el).html("Edit view settings");
        $("#viewfield").slideUp();
    }
}
function prepforsubmit() {
    if ($("#viewfield").is(":hidden")) {
        $("#viewfield").css("visibility","hidden").css("position","absolute").show();
    }
    $("select:disabled").prop("disabled",false);
}
