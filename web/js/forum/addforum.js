$(document).ready(function () {
    initEditor();
    tinyMCE.triggerSave();
});
function toggleGBdetail(v) {
    document.getElementById("gbdetail").style.display = v?"block":"none";
}
