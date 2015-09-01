$(document).ready(function () {
    initEditor();
    tinyMCE.triggerSave();

});
function linktypeupdate(el) {
    var tochg = ["text", "web", "file", "tool"];
    for (var i = 0; i < 4; i++) {
        if (tochg[i] == el.value) {
            disp = "";
        } else {
            disp = "none";
        }
        document.getElementById(tochg[i] +"input").style.display = disp;
    }
}

function toggleGBdetail(v) {
    document.getElementById("gbdetail").style.display = v ? "block" : "none";
}