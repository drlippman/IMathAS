$(function() {
    $("#search").on('keydown', function(e) {
        if (e.key == 'Enter') {
            doQuestionSearch();
        }
    }).on('input', function (e) {
        $("#searchwrap").toggleClass("hastext", e.currentTarget.value.trim() !== '');
    });
    $("#addbar button").on('focus', function(e) {
        if ($(this).closest("#addbar").hasClass("sr-only")) {
            $(this).closest("#addbar").removeClass("sr-only").removeClass("footerbar");
        }
    });
});

$(function() {
    $("#searchbtngrp").on('show.bs.dropdown', function () {
        parseAdvSearch();
    }).on('shown.bs.dropdown', function () {
        var advform = document.getElementById("advsearchform");
        var rect = advform.getBoundingClientRect();
        if (rect.bottom > window.innerHeight) {
            advform.scrollIntoView(false);
        }
    }).on('hide.bs.dropdown', function () {
        if (datePickerDivID) {
            $("#"+datePickerDivID).css('visibility','hidden').css('display','none');
        }
    });
});
function parseAdvSearch() {
    var search = document.getElementById("search").value;
    var matches;
    if (matches = search.match(/(author|type|id|regex|used|avgtime|mine|unused|private|res|order|lastmod|avgscore):("[^"]+?"|\w+)/g)) {
        var pts;
        for (var i=0;i<matches.length;i++) {
            pts = matches[i].split(/:/);
            pts[1] = pts[1].replace(/"/g,'');
            if (pts[0] == 'author') {
                $("#search-author").val(pts[1]);
            } else if (pts[0] == 'type') {
                $("#search-type").val(pts[1]);
            } else if (pts[0] == 'id') {
                $("#search-id").val(pts[1]);
            } else if (pts[0] == 'avgtime') {
                var avgt = pts[1].split(/,/);
                $("#search-avgtime-min").val(avgt[0]);
                $("#search-avgtime-max").val(avgt[1]);
            } else if (pts[0] == 'avgscore') {
                var avgs = pts[1].split(/,/);
                $("#search-avgscore-min").val(avgs[0]);
                $("#search-avgscore-max").val(avgs[1]);
            } else if (pts[0] == 'lastmod') {
                var avgt = pts[1].split(/,/);
                $("#search-lastmod-min").val(avgt[0]);
                $("#search-lastmod-max").val(avgt[1]);
            } else if (pts[0] == 'mine') {
                $("#search-mine").prop('checked', pts[1] == 1)
            } else if (pts[0] == 'unused') {
                $("#search-unused").prop('checked', pts[1] == 1)
            } else if (pts[0] == 'res') {
                var helps = pts[1].split(/,/);
                for (var j=0; j<helps.length;j++) {
                    $("#search-res-"+helps[j]).prop('checked', true);
                }
            } else if (pts[0] == 'order') {
                $("#search-newest").prop('checked', pts[1] == 'newest');
            }
        }
    }
    search = search.replace(/(author|type|id|regex|used|avgtime|mine|unused|private|res|order|lastmod|avgscore):("[^"]+?"|\w+)/g, '');
    var words = search.split(/\s+/);
    var haswords = [];
    var excwords = [];
    for (var i=0;i<words.length;i++) {
        if (words[i].charAt(0)=='!') {
            excwords.push(words[i].substring(1));
        } else {
            haswords.push(words[i]);
        }
    }
    $("#search-words").val(haswords.join(' '));
    $("#search-exclude").val(excwords.join(' '));
}


function doAdvSearch() {
    var outstr = '';
    outstr += $("#search-words").val() + ' ';
    var exclude = $("#search-exclude").val().trim();
    if (exclude != '') {
        outstr += '!' + exclude.split(/\s+/).join(' !');
    }
    var author = $("#search-author").val().trim()
    if (author != '') {
        outstr += 'author:"' + author + '" ';
    }
    var qid = $("#search-id").val().trim()
    if (qid != '') {
        if (qid.match(/^[\d\,\s]+$/)) {
            outstr += 'id:"' + qid + '" ';
        } else if (qid.match(/^[\d]+$/)) {
            outstr += 'id:' + qid + ' ';
        }
    }
    var type = $("#search-type").val();
    if (type != '') {
        outstr += 'type:' + type + ' ';
    }
    var avgtmin = $("#search-avgtime-min").val();
    var avgtmax = $("#search-avgtime-max").val();
    if (avgtmin != '' || avgtmax != '') {
        outstr += 'avgtime:"' + avgtmin + ',' + avgtmax + '" ';
    }
    var avgsmin = $("#search-avgscore-min").val();
    var avgsmax = $("#search-avgscore-max").val();
    if (avgsmin != '' || avgsmax != '') {
        outstr += 'avgscore:"' + avgsmin + ',' + avgsmax + '" ';
    }
    var lastmodmin = $("#search-lastmod-min").val();
    var lastmodmax = $("#search-lastmod-max").val();
    if (lastmodmin != '' || lastmodmax != '') {
        outstr += 'lastmod:"' + lastmodmin + ',' + lastmodmax + '" ';
    }
    if ($("#search-mine").is(':checked')) {
        outstr += 'mine:1 ';
    }
    if ($("#search-unused").is(':checked')) {
        outstr += 'unused:1 ';
    }
    if ($("#search-newest").is(':checked')) {
        outstr += 'order:newest ';
    }
    var helps = [];
    $("input[id^=search-res-]:checked").each(function(i,el) {
        helps.push(el.value);
    });
    if (helps.length>1) {
        outstr += 'res:"'+helps.join(',')+'" ';
    } else if (helps.length==1) {
        outstr += 'res:'+helps.join(',')+' ';
    }

    $("#search").val(outstr.trim());
    $("#advsearchbtn").dropdown('toggle');
    doQuestionSearch();
}
function startQuestionSearch(offset) {
    if ($("#advsearchbtn").attr("aria-expanded") === 'true') {
        doAdvSearch();
    } else {
        doQuestionSearch(offset);
    }
}
var qsearchintransit = false;
function doQuestionSearch(offset) {
    if (qsearchintransit) { return; }
    offset = offset || 0;
    $("#searcherror").hide();
    var search = document.getElementById("search").value;
    if (cursearchtype == 'all' && search.trim()=='') {
        $("#searcherror").html(_('You must provide a search term when searching All Libraries')).show();
        $("#search").focus();
        return;
    }
    $("#searchspinner").show();
    qsearchintransit = true;
    $.ajax({
        url: qsearchaddr,
        method: 'POST',
        data: {
            libs: curlibs,
            search: search,
            searchtype: cursearchtype,
            offset: offset
        },
        dataType: 'json'
    }).done(function(msg) {
        displayQuestionList(msg);
        document.getElementById("myTable").focus();
        document.getElementById("fullqsearchwrap").scrollIntoView();
        $("#searchspinner").hide();
        qsearchintransit = false;
    }).fail(function() {
        $("#searcherror").show();
        $("#searchspinner").hide();
        qsearchintransit = false;
    });
}

function clearSearch() {
    $("#search").val("");
    $("#searchwrap").removeClass("hastext");
    if (cursearchtype !== 'all') {
        doQuestionSearch();
    }
}

function getExistingQuestions(qlist,flattened) {
    for (var i in qlist) {
        if (qlist[i][0]=="text") { continue; }
        if (typeof qlist[i][2] == 'object') {
            getExistingQuestions(qlist[i][2], flattened);
        } else {
            flattened.push(qlist[i][1]);
        }
    }
}
var wronglibicon = '<span class="wronglibicon" title="' + _('Marked as in wrong library') + '" ' + 
    'aria-label="' + _('Marked as in wrong library') + '">' + 
    '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#f66" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M18.1 12.1C19.7 9.1 19 5.3 16.4 3.2 13.8 1 10 1 7.5 3.2 4.9 5.4 4.2 9.1 5.7 12.1l6.2 10.6z M9.5 11.5 14.5 6.5 M9.5 6.5 14.5 11.5"></path></svg>' +
    '</span> ';
var wrongLibState = {};
function displayQuestionList(results) {
    var searchtype = 'libs';
    var colcnt = 9;
    var thead = '<thead><tr>'
        + '<th><span class="sr-only">'+_('Select')+'</span></th>'
        + '<th>'+_('Description')+'</th>'
        + '<th>'+_('Actions')+'</th>'
        + '<th>'+_('Info')+'</th>'
        + '<th>'+_('ID')+'</th>'
        + '<th>'+_('Type')+'</th>'
        + '<th>'+_('Times Used')+'</th>'
        + '<th>'+_('Avg Time')+'</th>'
        + '</tr></thead>';
    var tbody = '<tbody>';
    var i,q,row,features,descrclass,descricon;
    var lastlib = -1;
    var existingq = [];
    wrongLibState = {};
    if (typeof itemarray !== 'undefined') {
        getExistingQuestions(itemarray, existingq);
    }
    for (var i in results['qs']) {
        // show lib/assess titles
        q = results['qs'][i];
        wrongLibState[i] = [q['junkflag'], q['libitemid']];
        if (results.type=='libs' && q['libid'] != lastlib) {
            tbody += '<tr><td colspan="'+colcnt+'"><b>' + results.names[q['libid']] + '</b></td></tr>';
            lastlib = q['libid'];
        } else if (results.type=='assess' && q['grp'] != lastlib) {
            tbody += '<tr><td colspan="'+colcnt+'"><b>' + results.names[q['grp']] + '</b></td></tr>';
            lastlib = q['grp'];
        }
        // build feature icons
        features = '';
        if ((q['extrefval']&1)==1) {
            if ((q['extrefval']&3)==3) {
                features += '<div class="ccvid inlinediv"';
                var altbase = _("Captioned video");
            } else {
                features += '<div class="inlinediv"';
                var altbase = _("Video");
            } 
            features += 'title="'+altbase+'">';
            features +=
                '<img src="' +
                staticroot +
                '/img/video_tiny.png" alt="' +
                altbase +
                '"/>' +
                '</div>';
        }
        if ((q['extrefval'] & 4) == 4) {
            features +=
                '<img src="' +
                staticroot +
                '/img/html_tiny.png" alt="'+_('Help Resource')+'" ' +
                'title="'+_('Help Resource')+'" />';
        }
        if ((q['extrefval'] & 8) == 8) {
            features +=
                '<img src="' +
                staticroot +
                '/img/assess_tiny.png" alt="'+_('Written example')+'" ' +  
                'title="'+_('Written example')+'" />';
        }
        if (q['mine'] == 1) {
            features += '<span title="' + _('My Question') + '" aria-label="' + _('My Question') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>' +
                '</span>';
        }
        if (q['userights'] == 0) {
            features += '<span title="' + _('Private') + '" aria-label="' + _('Private') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>' +
                '</span>';
        }
        descrclass = '';
        descricon = '';
        if (q['broken'] == 1) {
            descrclass = ' class="qbroken"';
            descricon = '<span title="' + _('Marked as broken') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#f66" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M19.7 1.3 19.6 9 16.2 6.3 13.8 11.3 10.5 8.3 7 11.7 3.6 9.2l0-7.9z" class="a"></path><path d="m19.7 22.9 0-7.8-2-1.4-3.1 4-3.3-3-3.8 3.8-4-3.9v8.4z" class="a"></path></svg>' + 
                '</span> ';
        } else if (q['junkflag'] == 1 && results.type=='libs') {
            descrclass = ' class="qwronglib"';
            descricon = wronglibicon;
        } else if (existingq.indexOf(parseInt(q['id'])) !== -1) {
            descrclass = ' class="qinassess"';
        }
        // build action dropdown

        var addqaddr = 'modquestion' +
            (assessver > 1 ? "2" : "") +
            ".php?qsetid=" + q['id'] +
            "&aid=" + curaid +
            "&cid=" + curcid +
            '&from=addq2';
        var editqaddr = 'moddataset.php?id=' + q['id'] +
            "&aid=" + curaid +
            "&cid=" + curcid +
            '&from=addq2&frompot=1';

        var actions2 = '<button role="button" class="dropdown-toggle arrow-down secondary" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' + 
            '<span class="sr-only">More</span></button><ul role="menu" class="dropdown-menu dropdown-menu-right">' + 
            '<li><a href="' + addqaddr + '">' + _('Add') + '</a></li>' +
            '<li><a href="' + editqaddr + '">' + (q['mine']==1 ? _('Edit') : _('View Code')) + '</a></li>' + 
            '<li><a href="' + editqaddr + '&template=true">' + _('Template') + '</a></li>';
        if (results.type=='libs') {
            actions2 += '<li><a href="#" onclick="toggleWrongLibFlag('+i+'); return false;" class="wronglibtoggle">' + 
                ((q['junkflag'] == 1) ? _('Un-mark as in wrong library') : _('Mark as in wrong library')) +
                '</a></li>';
        }
        actions2 += '</ul>';

        // build row
        tbody += '<tr>'
            + '<td><input type=checkbox name="nchecked[]" id="qo'+i+'" value="'+q['id']+'"></td>'
            + '<td' + descrclass + '>' + descricon + q['description'] + '</td>'
            + '<td><div class="dropdown splitbtn nowrap"><button type="button" class="secondary" onclick="previewq(\'selq\',\'qo'+i+'\','+q['id']+',true,false)">'
            + _('Preview') + '</button>'
            + actions2
            + '</div></td>'
            + '<td class="nowrap">' + features + '</td>'
            + '<td>' + q['id'] + '</td>'
            + '<td>' + q['qtype'] + '</td>'
            + '<td class="c">' + q['times'] + '</td>'
            + '<td class="c">' + (q['meantimen'] > 3 ? 
                ('<span onmouseenter="tipshow(this,\''+_('Avg score on first try: ')+q['meanscore']+'%'
                + '<br/>'+_('Avg time on first try: ') + q['meantime'] + _(' min') + 
                '<br/>N='+q['meantimen']+'\')" onmouseleave="tipout()">' + q['meantime'] + '</span>') :
                '') + '</td>'
            + '</tr>';
    }
    tbody += '</tbody>';
    document.getElementById("myTable").innerHTML = thead + tbody;
    rendermathnode(document.getElementById("myTable"));

    initSortTable('myTable',[false,'S',false,'S','N','S','N','N']);
    if (window.top == window.self && document.getElementById("addbar")) {
         $("#selq input[type=checkbox]").on("change", function () {
             $("#addbar.footerbar").toggleClass("sr-only", $("#selq input[type=checkbox]:checked").length == 0);
         });
    }
    if (results.hasOwnProperty('next') || results.hasOwnProperty('prev')) {
        $("#searchnums").show();
        $("#searchnumvals").text((results.offset+1) + '-' + (results.offset + results.qs.length));
    } else {
        $("#searchnums").hide();
    }
    if (results.hasOwnProperty('next')) {
        var resnext = results.next;
        $("#searchnext").show().off('click').on('click', function (e) {
            e.preventDefault();
            doQuestionSearch(resnext);
        });
    } else {
        $("#searchnext").hide();
    }
    if (results.hasOwnProperty('prev')) {
        var resprev = results.prev;
        $("#searchprev").show().off('click').on('click', function (e) {
            e.preventDefault();
            doQuestionSearch(resprev);
        });
    } else {
        $("#searchprev").hide();
    }
}
function updateInAssessMarkers() {
    var existingq = [];
    if (itemarray) {
        getExistingQuestions(itemarray, existingq);

    }
    $("#selq tbody tr").each(function(i,el) {
        if (el.childNodes.length == 1) { return; }
        $(el.childNodes[1]).toggleClass('qinassess', 
            existingq.indexOf(parseInt(el.childNodes[0].firstChild.value)) !== -1);
    });
}

function toggleWrongLibFlag(row) {
    saveWrongLibFlag([row]);
}
function toggleWrongLibFlags(newstate) {
    var checked = [];
    $("#selq input[type=checkbox]:checked").each(function() {
        var row = this.id.substring(2);
        if (newstate != wrongLibState[row][0]) { 
            // change it
            checked.push(row);
        }
    });
    if (checked.length == 0) { return; }
    saveWrongLibFlag(checked);
}

function saveWrongLibFlag(rows) {
    var rownums = rows;
    var newstates = [];
    var libitemids = [];
    for (var i=0; i<rownums.length; i++) {
        newstates.push(1 - wrongLibState[rownums[i]][0]);
        libitemids.push(wrongLibState[rownums[i]][1]);
    }
    $.ajax({
        url: JunkFlagsaveurl,
        method: 'POST',
        data: {
            flags: newstates.join(','),
            libitemids: libitemids.join(',')
        },
        dataType: 'text'
    }).done(function(msg) {
        var ischanged = msg.split(/,/);
        for (var i=0; i<rownums.length; i++) {
            if (ischanged[i] == 'OK') {
                wrongLibState[rownums[i]][0] = newstates[i];
                var row = $("#qo"+rownums[i]).parent().parent();
                var descr = row.find("td:nth-child(2)");
                var wronglibtoggle = row.find(".wronglibtoggle");
                if (newstates[i] == 1) { // mark as wrong lib
                    descr.prepend(wronglibicon).addClass("qwronglib");
                    wronglibtoggle.text(_("Un-mark as in wrong library"));
                } else {
                    descr.find(".wronglibicon").remove();
                    descr.removeClass("qwronglib");
                    wronglibtoggle.text(_("Mark as in wrong library"));
                }
            }
        }
    });
}

function previewq(formn,loc,qn,docheck,onlychk) {
    var addr = previewqaddr+'&qsetid='+qn;
    if (formn!=null) {
         addr +='&formn='+formn;
    }
    if (loc!=null) {
        addr +='&loc='+loc;
    }
    if (docheck) {
       addr += '&checked=1';
    }
    if (onlychk) {
       addr += '&onlychk=1';
    }
 
    previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
    previewpop.focus();
 }
 function sethighlightrow(loc) {
     $("tr.highlight").removeClass("highlight");
     $("#"+loc).closest("tr").addClass("highlight");
 }
 function previewsel(formn) {
     var form = document.getElementById(formn);
     for (var e = 0; e < form.elements.length; e++) {
         var el = form.elements[e];
         if (el.type == 'checkbox' && el.name=='nchecked[]' && el.checked) {
             previewq(formn,el.id,el.value,true,true);
             return false;
         }
     }
     alert("No questions selected");
 }
 function getnextprev(formn,loc,onlychk) {
     var onlychk = (onlychk == null) ? false : true;
     var form = document.getElementById(formn);
     if (form==null) {
         return null;
     }
     var prevl = 0; var nextl = 0; var found=false;
     var prevq = 0; var nextq = 0;
     var cntchecked = 0;  var remaining = 0;
     var looking = true;
     for (var e = 0; e < form.elements.length; e++) {
         var el = form.elements[e];
         if (typeof el.type == "undefined" || el.value.match(/text/)) {
             continue;
         }
         if (((el.type == 'checkbox' && el.name=='nchecked[]') || ((el.type=='checkbox' || el.type=='hidden') && el.name=='checked[]')) && (!onlychk || el.checked)) {
             if (el.checked) {
                 cntchecked++;
             }
             if (looking) {
                 if (found) {
                     nextq = el.value;
                     nextl = el.id;
                     remaining++;
                     looking=false;//break;
                 } else if (el.id==loc) {
                     found = true;
                 } else {
                     prevq = el.value;
                     prevl = el.id;
                 }
             } else {
                 remaining++;
             }
         }
     }
     if (formn=='curqform') {
         if (prevl!=0) {
             prevq = document.getElementById('o'+prevl).value;
         }
         if (nextl!=0) {
             nextq = document.getElementById('o'+nextl).value;
         }
     }
     return ([[prevl,prevq],[nextl,nextq],cntchecked,remaining]);
 }
 
 function chkAll(frm, arr, mark) {
   for (i = 0; i <= frm.elements.length; i++) {
    try{
      if(frm.elements[i].name == arr) {
        frm.elements[i].checked = mark;
      }
    } catch(er) {}
   }
 }
 
 function alllibs() {
     cursearchtype = 'all';
     curlibs = '';
     $("#cursearchtype").text(_('All Libraries'));
     $("#libnames").parent().hide();
     var cursearch = document.getElementById("search").value;
     if (cursearch.trim() != '') {
         doQuestionSearch();
     } else {
         document.getElementById("search").focus();
     }
 }
 
 function libselect() {
     var listlibs = '';
     if (cursearchtype == 'libs') {
         listlibs = curlibs;
     }
     GB_show('Library Select','libtree2.php?libtree=popup&libs='+listlibs,500,500);
 }

 function setlib(libs) {
     //document.getElementById("libs").value = libs;
     curlibs = libs;
     cursearchtype = 'libs';
     $("#cursearchtype").text(_('In Libraries'));
     doQuestionSearch();
 }
 function setlibnames(libn) {
     document.getElementById("libnames").innerHTML = libn.replace(/\s*<span.*?<\/span.*?>/g,'').replace(/\s+/g,' ').trim();
     $("#libnames").parent().show();

    // this gets called after setlib, so we'll check for and update history here
    setlibhistory();
 }

 var recentlibs = {'ids':[], 'names':[]};
 var cookierecentlibs = readCookie("recentlibs");
 if (cookierecentlibs !== null) {
     recentlibs = JSON.parse(decodeURIComponent(cookierecentlibs));
 }

 function setlibhistory() {
    var curloc = recentlibs.ids.indexOf(curlibs);
    if (curloc != -1) { // remove if already in list
        recentlibs.ids.splice(curloc,1);
        recentlibs.names.splice(curloc,1);
    }
    recentlibs.ids.unshift(curlibs);
    recentlibs.names.unshift(document.getElementById("libnames").innerHTML);
    if (recentlibs.ids.length > 6) {
        recentlibs.ids.pop();
        recentlibs.names.pop();
    }
    document.cookie = "recentlibs=" + encodeURIComponent(JSON.stringify(recentlibs));
    if (recentlibs.ids.length > 1) {
        $('#searchtypemenu').children(":nth-child(n+4)").remove();
        $('#searchtypemenu').append($("<li>", {
            text: _("Recent Libraries"),
            class: "dropdown-header"
        }));
        for (var i=1; i<recentlibs.ids.length; i++) {
            const curi = i;
            let libname = recentlibs.names[curi].replace(/&\w+;/g,'');
            libname = libname.length > 50 ? libname.substring(0,49) + "..." : libname;
        
            $('#searchtypemenu').append($("<li>").append($("<a>", {
                click: function (e) {
                    setlib(recentlibs.ids[curi]);
                    setlibnames(recentlibs.names[curi]);
                    $(document).trigger("click"); // for some reason not happening automatically
                    return false;
                },
                href: "#",
                role: "button",
                text: libname
            })));
        }
    }
 }
 function assessselect() {
     var lista = '';
     if (cursearchtype == 'assess') {
         lista = curlibs;
     }
     GB_show('Assessment Select',aselectaddr+'&curassess='+lista,900,500);
 }
 function setassess(aids) {
     curlibs = aids;
     cursearchtype = 'assess';
     $("#cursearchtype").text(_('In Assessments'));
     doQuestionSearch();
 }
 function setassessnames(aidn) {
     document.getElementById("libnames").innerHTML = aidn.replace(/<span.*?<\/span.*?>/g,'');
     $("#libnames").parent().show();
 }
 
 function prePageChange() {
     if ($("#selq input[type=checkbox]:checked").length > 0) {
         return confirm(_('You have questions selected which will get lost if you continue.  Continue anyway?'));
     } else {
         return true;
     }
 }
