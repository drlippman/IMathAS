/*
Assess2 standalone support
 */
var allJsParams = {};

 function initq(qn, jsparams) {
   var qwrap = document.getElementById('questionwrap'+qn);

   setTimeout(window.drawPics, 100);
   window.rendermathnode(qwrap);
   window.initSageCell(qwrap);
   window.initlinkmarkup(qwrap);
   window.setInitValues(qwrap);

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
       out += '<li><a target="qhelp" href="'+ help['url'] +'">';
       out += title + '</a></li>';
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
     const data = new FormData();
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
   }
   var changed = getChangedQuestions(qns);

   if (forbackground) {
     data.append('toscoreqn', JSON.stringify(changed));
     return data;
   } else {
     console.log(JSON.stringify(changed));
     $("input[name=toscoreqn]").val(JSON.stringify(changed));
   }
   return true;
  }
