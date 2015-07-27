
function chgfb() {
    if (document.getElementById("deffeedback").value=="Practice" || document.getElementById("deffeedback").value=="Homework") {
    document.getElementById("showanspracspan").className = "show";
    document.getElementById("showansspan").className = "hidden";
    document.getElementById("showreattdiffver").className = "hidden";
    } else {
    document.getElementById("showanspracspan").className = "hidden";
    document.getElementById("showansspan").className = "show";
    document.getElementById("showreattdiffver").className = "show";
    }
if (document.getElementById("deffeedback").value=="Practice") {
    document.getElementById("stdcntingb").className = "hidden";
    document.getElementById("praccntingb").className = "formright";
    } else {
    document.getElementById("stdcntingb").className = "formright";
    document.getElementById("praccntingb").className = "hidden";
    }
}
function chgcopyfrom() {
    if (document.getElementById('copyfrom').value==0) {
    document.getElementById('customoptions').className="show";
    document.getElementById('copyfromoptions').className="hidden";
    } else {
    document.getElementById('customoptions').className="hidden";
    document.getElementById('copyfromoptions').className="show";
    }
}
function apwshowhide(s) {
    var el = document.getElementById("assmpassword");
    if (el.type == "password") {
    el.type = "text";
    s.innerHTML = "Hide";
    } else {
    el.type = "password";
    s.innerHTML = "Show";
    }
}
