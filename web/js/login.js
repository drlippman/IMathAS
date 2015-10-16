var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;
var thedate = new Date();
document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
var tz = jstz.determine();
document.getElementById("tzname").value = tz.name();

function updateloginarea() {
    setnode = document.getElementById("settings");
    var html = "";
    html += '<div class="form-group"><label class="col-md-4 col-sm-4 control-label">Accessibility</label> <div class="col-md-8 col-sm-8"><select name="access" class="form-control"><option value="0">Use defaults</option>';
    html += '<option value="3">Force image-based display</option>';
    html += '<option value="1">Use text-based display</option></select> </div>';
//    html += "<div class='help-link select-text-margin'><a href='#' onClick=\"window.open('helper-guide?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left=150')\">Help</a> </div></div>";

    if (!MathJaxCompatible) {
        html += '<input type=hidden name="mathdisp" value="0">';
    } else {
        html += '<input type=hidden name="mathdisp" value="1">';
    }
    if (ASnoSVG) {
        html += '<input type=hidden name="graphdisp" value="2">';
    } else {
        html += '<input type=hidden name="graphdisp" value="1">';
    }
    if (MathJaxCompatible && !ASnoSVG) {
        html += '<input type=hidden name="isok" value=1>';
    }
    setnode.innerHTML = html;
}
var existingonload = window.onload;
if (existingonload) {
    window.onload = function() {existingonload(); updateloginarea();}
} else {
    window.onload = updateloginarea;
}