$(document).ready(function () {
    initEditor();
    tinyMCE.triggerSave();
});

function changenparts(el) {
    var np = el.value;
    for (var i=0;i<10;i++) {
        if (i<np) {
            document.getElementById("partwrapper"+i).style.display="";
        } else {
            document.getElementById("partwrapper"+i).style.display="none";
        }
    }
    if (np==1) {
        document.getElementById("anstipsingle").style.display="";
        document.getElementById("anstipmult").style.display="none";
    } else {
        document.getElementById("anstipsingle").style.display="none";
        document.getElementById("anstipmult").style.display="";
    }
}
function changeqparts(n,el) {
    var np = el.value;
    for (var i=0;i<6;i++) {
        if (i<np) {
            document.getElementById("qc"+n+"-"+i).style.display="";
        } else {
            document.getElementById("qc"+n+"-"+i).style.display="none";
        }
    }
}
function changehparts(el) {
    var np = el.value;
    for (var i=0;i<4;i++) {
        if (i<np) {
            document.getElementById("hintwrapper"+i).style.display="";
        } else {
            document.getElementById("hintwrapper"+i).style.display="none";
        }
    }
}

function changeqtype(n,el) {
    var qt = el.value;
    document.getElementById("qti"+n+"mc").style.display="none";
    document.getElementById("qti"+n+"num").style.display="none";
    document.getElementById("qc"+n+"-def").style.display="none";
    $('#essayopts'+n).hide();
    //document.getElementById("qti"+n+"mc").style.display="";
    if (qt=='choices') {
        $('#essay'+n+'wrap').hide();
        $('.hasparts'+n).show();
        document.getElementById("qti"+n+"mc").style.display="";
        document.getElementById("choicelbl"+n).innerHTML = "Choice";
    } else if (qt=='number') {
        $('#essay'+n+'wrap').hide();
        $('.hasparts'+n).show();
        document.getElementById("qti"+n+"num").style.display="";
        document.getElementById("qc"+n+"-def").style.display="";
        document.getElementById("choicelbl"+n).innerHTML = "Answer";
    } else if (qt=='essay') {
        $('#essay'+n+'wrap').show();
        $('.hasparts'+n).hide();
        $('#essayopts'+n).show();
    }

}

function popuptxtsave() {
    var txt = tinyMCE.get('popuptxt').getContent();
    if (txt.substring(0,3)=='<p>' && txt.slice(-4)=='</p>' && txt.match(/<p>/g).length==1) {
        txt = txt.substring(3,txt.length - 4);
    }
    $('#'+popupedid).val(txt);
    GB_hide();
}
var rubricbase, lastrubricpos=null, popupedid;
function popupeditor(elid) {
    var width = 900;
    popupedid = elid;
    $('#GB_window').show();
    tinyMCE.get('popuptxt').setContent($('#'+elid).val());
    $('#GB_caption').mousedown(function(evt) {
        rubricbase = {left:evt.pageX, top: evt.pageY};
        $("body").bind('mousemove',rubricmousemove);
        $("body").mouseup(function(event) {
            var p = $('#GB_window').position();
            lastrubricpos.left = p.left;
            lastrubricpos.top = p.top;
            $("body").unbind('mousemove',rubricmousemove);
            $(this).unbind(event);
        });
    });
    var de = document.documentElement;
    var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
    var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
    document.getElementById("GB_window").style.width = width + "px";

    if ($("#GB_window").outerHeight() > h - 30) {
        document.getElementById("GB_window").style.height = (h-30) + "px";
    }
    document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
    lastrubricpos = {
        left: ($(window).width() - $("#GB_window").outerWidth())/2,
        top: $(window).scrollTop() + ((window.innerHeight ? window.innerHeight : $(window).height()) - $("#GB_window").outerHeight())/2,
        scroll: $(window).scrollTop()
    };
    document.getElementById("GB_window").style.top = lastrubricpos.top+"px";

}
function rubricmousemove(evt) {
    $('#GB_window').css('left', (evt.pageX - rubricbase.left) + lastrubricpos.left)
        .css('top', (evt.pageY - rubricbase.top) + lastrubricpos.top);
    evt.preventDefault();
    return false;
}
function rubrictouchmove(evt) {
    var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];

    $('#GB_window').css('left', (touch.pageX - rubricbase.left) + lastrubricpos.left)
        .css('top', (touch.pageY - rubricbase.top) + lastrubricpos.top);
    evt.preventDefault();

    return false;
}

function setlib(libs) {
    if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
        libs = libs.substring(2);
    }
    document.getElementById("libs").value = libs;
    curlibs = libs;
}
function setlibnames(libn) {
    if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
        libn = libn.substring(11);
    }
    document.getElementById("libnames").innerHTML = libn;
}
