var curlibs = '0';
function setlib(libs) {
    document.getElementById("parent").value = libs;
    curlibs = libs;
}
function setlibnames(libn) {
    document.getElementById("libnames").innerHTML = libn;
}

function toggle(id) {
    node = document.getElementById(id);
    button = document.getElementById('b' + id);
    if (node.className == "show") {
        node.className = "hide";
        button.innerHTML = "+";
    } else {
        node.className = "show";
        button.innerHTML = "-";
    }
}