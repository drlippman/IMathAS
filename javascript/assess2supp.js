/*
Assess2 standalone support
 */
var allJsParams = {};

function showandinit(qn, data) {
    $('#questionwrap'+qn).html(data.html);
    showerrors(data.errors);
    initq(qn, data.jsparams);
}

function inIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

function showerrors(errors) {
    var err = $('#errorslist');
    err.empty();
    if (errors.length > 0) {
        for (var i=0; i<errors.length; i++) {
            err.append($("<li>", {text: errors[i]}));
        }
        err.show();
    } else {
        err.hide();
    }
}

function submitq(qn) {
    $("#results"+qn).html(_('Submitting...'));
    var data = dopresubmit(qn, true);
    data.append('state', document.getElementById('state').value);
    $.ajax({
        url: window.location.pathname,
        type: 'POST',
        dataType: 'json',
        data: data,
        processData: false,
        contentType: false
      }).done(function(msg) {
        var data = parseJwt(msg.jwt);
        $("#state").val(data.state);
        showerrors(data.errors);
        if (msg.disp) {
            $("#results"+qn).html(_("Score: ")+data.score);
            showandinit(qn, msg.disp);
        } else {
            $("#results"+qn).html(_('Question Submitted'));
            $("#questionwrap"+qn).empty();
        }
        sendupscores(msg.jwt);
      }).always(function(msg) {
        $("#toscoreqn").val('');
      });
}

function sendupscores(msg) {
    if(inIframe()) {
        var returnobj = {
            subject: "lti.ext.imathas.result",
            jwt: msg,
            frame_id: frame_id
        };
        window.parent.postMessage(JSON.stringify(returnobj), '*');
    }
}

function regenq(qn) {
    $("#results"+qn).empty();
    $.ajax({
        url: window.location.pathname,
        type: 'POST',
        dataType: 'json',
        data: {
            state: document.getElementById('state').value,
            regen: qn,
            ajax: 1
        }
      }).done(function(data) {
        $("#state").val(data.state);
        showerrors(data.disp.errors);
        showandinit(qn, data.disp);
      }).always(function(msg) {
        $("#toscoreqn").val('');
      });
}

function loadquestionById(qn, qsid) {
    $("#results"+qn).empty();
    $("#questionwrap"+qn).empty();
    var url = window.location.href.replace(/id=\d+/,'id='+qsid);
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: {
            ajax: true
        }
      }).done(function(msg) {
        $("#state").val(msg.state);
        showandinit(qn, msg.disp);
      }).always(function(msg) {
        $("#toscoreqn").val('');
      });
}
function loadquestionByJwt(qn, jwt) {
    $("#results"+qn).empty();
    $("#questionwrap"+qn).empty();
    $.ajax({
        url: window.location.pathname,
        type: 'POST',
        dataType: 'json',
        data: {
            jwt: jwt,
            ajax: true
        }
      }).done(function(msg) {
        $("#state").val(msg.state);
        showandinit(qn, msg.disp);
      }).always(function(msg) {
        $("#toscoreqn").val('');
      });
}

$(function() {
    $(window).on('message', function(e) {
        var msg = e.originalEvent.data;
        if (typeof msg != 'string') { return; }
        if (msg == 'submit') {
            submitq(thisqn);
        } else if (msg.match(/imathas\.show/)) {
            var data = JSON.parse(msg);
            if (data.jwt) {
                loadquestionByJwt(thisqn, data.jwt);
            } else if (data.id) {
                loadquestionById(thisqn, data.id);
            }
        }
    });
});

function disableInputs(qn, disabled) {
  var regex, pn;
  for (var i=0;i<disabled.length;i++) {
    pn = disabled[i];
    // out of tries - disable inputs
    if (pn === 'all') {
      regex = new RegExp('^(qn|tc|qs)(' + (qn) + '\\b|' + (qn + 1) + '\\d{3}\\b)');
    } else if (pn === 0) {
      regex = new RegExp('^(qn|tc|qs)(' + (qn) + '\\b|' + ((qn + 1) * 1000 + pn * 1) + '\\b)');
    } else {
      regex = new RegExp('^(qn|tc|qs)' + ((qn + 1) * 1000 + pn * 1) + '\\b');
    }
    $('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
      if (el.name.match(regex)) {
        el.disabled = true;
      }
    });
  }
}

 function initq(qn, jsparams) {
   var qwrap = document.getElementById('questionwrap'+qn);

   setTimeout(window.drawPics, 100);
   window.rendermathnode(qwrap);
   window.initSageCell(qwrap);
   window.initlinkmarkup(qwrap);
   window.setInitValues(qwrap);

   if (jsparams.disabled) {
     disableInputs(qn, jsparams.disabled);
   }

   window.imathasAssess.init(jsparams, true, qwrap);

   if (jsparams.helps && jsparams.helps.length > 0) {
     addHelps(qwrap, jsparams.helps);
   }
   
   allJsParams[qn] = jsparams;
 }

 function addHelps(qwrap, helps) {
   if ($(qwrap).find(".qhelps").length == 0) {
     var out = '<ul class="helplist">';
     out += '<li>'+_('Question Help')+':</li>';
     for (let help of helps) {
       let title = help.label;
       if (help.label == 'video') {
         title = _('Video');
       } else if (help.label == 'read') {
         title = _('Read');
       } else if (help.label == 'ex') {
         title = _('Written Example');
       }
       out += '<li><a target="qhelp" href="'+ help['url'] +'"';
       if (help['descr'] != '') {
        help['descr'] = help['descr'].replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        out += ' title="'+help['descr']+'"';
        out += ' aria-label="'+title+' '+help['descr']+'"';
     }
       out += '>' + title + '</a></li>';
     }
     out += '</ul>';
     $(qwrap).append($("<div>", {class: "qhelps"}).html(out));
   }
 }

 function setInitValues(qwrap) {
   var regex = new RegExp('^(qn|tc|qs)\\d');
   window.$(qwrap).find('input,select,textarea').each(function (index, el) {
     if (el.name.match(regex)) {
       if (el.type === 'radio' || el.type === 'checkbox') {
         if (el.checked) {
           el.setAttribute('data-initval', el.value);
         }
       } else {
         el.setAttribute('data-initval', el.value);
       }
     }
   });
 }

 function getChangedQuestions(qns) {
   if (typeof qns !== 'object') {
     qns = [qns];
   }
   const changed = {};
   for (let k = 0; k < qns.length; k++) {
     const qn = qns[k];
     var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn * 1 + 1) + '\\d{3})');
     window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
       if ((m = el.name.match(regex)) !== null) {
         let thisChanged = false;
         if (el.type === 'radio' || el.type === 'checkbox') {
           if (el.checked && el.value !== el.getAttribute('data-initval')) {
             thisChanged = true;
           } else if (!el.checked && el.value === el.getAttribute('data-initval')) {
             thisChanged = true;
           }
         } else {
           if (el.value.trim() !== el.getAttribute('data-initval') && el.value.trim() !== '') {
             thisChanged = true;
           }
         }
         if (thisChanged) {
           if (!changed.hasOwnProperty(qn)) {
             changed[qn] = [];
           }
           let pn = 0;
           const qidnum = parseInt(m[2]);
           if (qidnum > 1000) {
             pn = qidnum % 1000;
           }
           if (changed[qn].indexOf(pn) === -1) {
             changed[qn].push(pn);
           }
         }
       }
     });
     const curqparams = allJsParams[qn];
     for (const qref in curqparams) {
       if (curqparams.submitall ||
         (qref.match(/\d/) && curqparams[qref].hasOwnProperty('submitblank'))
       ) {
         let pn = 0;
         if (qref > 1000) {
           pn = qref % 1000;
         }
         if (!changed.hasOwnProperty(qn)) {
           changed[qn] = [];
         }
         if (changed[qn].indexOf(pn) === -1) {
           changed[qn].push(pn);
         }
       }
     }
   }
   return changed;
 }

 function dopresubmit(qns, forbackground) {
   if (typeof qns !== 'object') {
     qns = [qns];
   }
   if (forbackground) {
     var data = new FormData();
   }
   for (let k in window.callbackstack) {
     k = parseInt(k);
     if (qns.indexOf(k < 1000 ? k : (Math.floor(k / 1000) - 1)) > -1) {
       window.callbackstack[k](k);
     }
   }
   if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }
   window.MQeditor.resetEditor();
   window.imathasAssess.clearTips();

   for (let k = 0; k < qns.length; k++) {
     const qn = qns[k];

     var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn + 1) + '\\d{3})');
     window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
       if (el.name.match(regex)) {
         valstr = window.imathasAssess.preSubmit(el.name.substr(2));
         if (valstr !== false) {
           if (forbackground) {
             data.append(el.name + '-val', valstr);
           } else {
             $('#questionwrap' + qn).append($('<input>', {
               type: 'hidden',
               name: el.name + '-val',
               value: valstr
             }));
           }
         }
         if ((el.type !== 'radio' && el.type !== 'checkbox') || el.checked) {
           if (el.type === 'file' && el.files.length > 0) {
             if (forbackground) {
               data.append(el.name, el.files[0]);
             }
           } else {
             if (forbackground) {
               data.append(el.name, window.imathasAssess.preSubmitString(el.name, el.value));
             } else {
               el.value = window.imathasAssess.preSubmitString(el.name, el.value);
             }
           }
         }
       }
     });
     const curqparams = allJsParams[qn];
     for (const qref in curqparams) {
         if (typeof curqparams[qref] == 'object' && curqparams[qref].choicemap) {
             if (forbackground) {
                data.append("qn"+qref+'-choicemap', curqparams[qref].choicemap);
             } else {
                $('#questionwrap' + qn).append($('<input>', {
                    type: 'hidden',
                    name: "qn"+qref+'-choicemap',
                    value: curqparams[qref].choicemap
                }));
   }
        }
     }
   }
   var changed = getChangedQuestions(qns);

   if (forbackground) {
     data.append('toscoreqn', JSON.stringify(changed));
     return data;
   } else {
     $("input[name=toscoreqn]").val(JSON.stringify(changed));
   }
   return true;
  }

  function parseJwt (token) {
    var base64Url = token.split('.')[1];
    var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));

    return JSON.parse(jsonPayload);
 };
