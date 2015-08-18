$(document).ready(function () {
    initEditor();
    //$("#description").val(content);
    //tinyMCE.triggerSave();
    //tinymce.get('description').getContent();
    //editorInstance.getHTML();

});
function toggleGBdetail(v) {
    document.getElementById("gbdetail").style.display = v?"block":"none";
}
function toggleGBdetail1(v) {
    document.getElementById("datediv").style.display = v?"block":"none";
}