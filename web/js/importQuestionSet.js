function setlib(libs) {
    if (libs.charAt(0) == '0' && libs.indexOf(',') > -1) {
        libs = libs.substring(2);
    }
    document.getElementById("libs").value = libs;
    curlibs = libs;
}
function setlibnames(libn) {
    if (libn.indexOf('Unassigned') > -1 && libn.indexOf(',') > -1) {
        libn = libn.substring(11);
    }
    document.getElementById("libnames").innerHTML = libn;
}