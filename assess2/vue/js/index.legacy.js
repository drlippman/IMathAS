(function(){"use strict";var e={176:function(e,t,n){n(752),n(3964),n(429),n(6409);var o=n(9963),r=n(6252),s=n(3577),i={id:"app",role:"main"},a={key:0};function u(e,t,n,o,u,l){var d=(0,r.up)("router-view"),c=(0,r.up)("error-dialog"),f=(0,r.up)("due-dialog"),h=(0,r.up)("confirm-dialog");return(0,r.wg)(),(0,r.iD)("div",i,[l.assessInfoLoaded?(0,r.kq)("",!0):((0,r.wg)(),(0,r.iD)("div",a,(0,s.zw)(e.$t("loading")),1)),l.assessInfoLoaded?((0,r.wg)(),(0,r.j4)(d,{key:1})):(0,r.kq)("",!0),l.hasError?((0,r.wg)(),(0,r.j4)(c,{key:2,errormsg:l.errorMsg,lastpos:l.lastPos,onClearerror:l.clearError},null,8,["errormsg","lastpos","onClearerror"])):(0,r.kq)("",!0),l.showDueDialog?((0,r.wg)(),(0,r.j4)(f,{key:3})):(0,r.kq)("",!0),null!==l.confirmObj?((0,r.wg)(),(0,r.j4)(h,{key:4,data:l.confirmObj,lastpos:l.lastPos,onClose:l.closeConfirm},null,8,["data","lastpos","onClose"])):(0,r.kq)("",!0)])}n(4284),n(9358);var l=n(7956),d=n(7911),c={class:"fullwrap",ref:"wrap"},f={class:"dialog-overlay",tabindex:"-1"},h={class:"pane-header flexrow",id:"duedialog_hdr"},p={style:{"flex-grow":"1"}},g={class:"pane-body",id:"duedialog_body"},m={key:0},b=(0,r._)("br",null,null,-1),w=(0,r._)("br",null,null,-1),v={key:1},y=(0,r._)("br",null,null,-1);function _(e,t,n,i,a,u){var l=(0,r.up)("icons");return(0,r.wg)(),(0,r.iD)("div",c,[(0,r._)("div",f,[(0,r._)("div",{class:"dialog",ref:"dialog",role:"alertdialog","aria-modal":"true","aria-labelledby":"duedialog_hdr","aria-describedby":"duedialog_body",tabindex:"-1",onClick:t[3]||(t[3]=(0,o.iM)((function(){}),["stop"]))},[(0,r._)("div",h,[(0,r._)("div",p,[(0,r.Wm)(l,{name:"alert"}),(0,r.Uk)(" "+(0,s.zw)(e.$t("duedialog.due")),1)])]),(0,r._)("div",g,[(0,r._)("p",null,(0,s.zw)(e.$t("duedialog.nowdue")),1),u.settings.can_use_latepass>0&&u.settings.latepass_after?((0,r.wg)(),(0,r.iD)("p",m,[(0,r.Uk)((0,s.zw)(e.$tc("closed.latepassn",u.settings.latepasses_avail))+" ",1),b,(0,r.Uk)(" "+(0,s.zw)(u.latepassExtendMsg)+" ",1),w,(0,r._)("button",{onClick:t[0]||(t[0]=function(){return u.useLatepass&&u.useLatepass.apply(u,arguments)}),class:"primary"},(0,s.zw)(e.$tc("closed.use_latepass",this.settings.can_use_latepass)),1)])):(0,r.kq)("",!0),u.hasUnsubmitted?((0,r.wg)(),(0,r.iD)("p",v,[(0,r.Uk)((0,s.zw)(u.unsubmittedMessage)+" ",1),y,(0,r._)("button",{onClick:t[1]||(t[1]=function(){return u.submitNow&&u.submitNow.apply(u,arguments)}),class:"primary"},(0,s.zw)(e.$t("duedialog.submitnow")),1)])):(0,r.kq)("",!0),(0,r._)("p",null,[(0,r._)("button",{class:(0,s.C_)({primary:u.exitPrimary,secondary:!u.exitPrimary}),onClick:t[2]||(t[2]=function(){return u.exit&&u.exit.apply(u,arguments)})},(0,s.zw)(e.$t("closed.exit")),3)])])],512)])],512)}var k=n(1038),O=n(5965),j=n.n(O),C={name:"DueDialog",data:function(){return{dialog:null}},components:{Icons:k.Z},computed:{settings:function(){return l.h.assessInfo},latepassExtendMsg:function(){return this.$tc("closed.latepass_needed",this.settings.can_use_latepass,{n:this.settings.can_use_latepass,date:this.settings.latepass_extendto_disp})},hasUnsubmitted:function(){return"by_assessment"===this.settings.submitby||Object.keys(l.N.getChangedQuestions()).length>0},unsubmittedMessage:function(){return"by_question"===this.settings.submitby?this.$t("duedialog.byq_unsubmitted"):this.$t("duedialog.bya_unsubmitted")},exitPrimary:function(){return!this.hasUnsubmitted&&!this.canUseLatePass}},methods:{closeDialog:function(){l.h.show_enddate_dialog=!1},submitNow:function(){var e=this;l.N.endAssess((function(){e.exit()}))},useLatepass:function(){l.N.redeemLatePass((function(){l.h.show_enddate_dialog=!1}))},exit:function(){this.closeDialog(),window.exiturl&&""!==window.exiturl?(l.h.noUnload=!0,window.location=window.exiturl):l.N.routeToStart()}},mounted:function(){var e=this,t=l.h.lastPos;window.$(document).on("keyup.dialog",(function(t){"Escape"===t.key&&e.closeDialog()})),this.dialog=new(j())(this.$refs.wrap),this.dialog.show(),window.innerHeight>2e3&&null!==t&&(this.$refs.dialog.style.top=Math.max(20,t-this.$refs.dialog.offsetHeight)+"px")},beforeUnmount:function(){window.$(document).off("keyup.dialog"),this.dialog.destroy()}},x=n(3744);const P=(0,x.Z)(C,[["render",_]]);var D=P,$=n(4643),E={components:{ErrorDialog:d.Z,DueDialog:D,ConfirmDialog:$.Z},data:function(){return{prewarned:!1}},computed:{assessInfoLoaded:function(){return null!==l.h.assessInfo},hasError:function(){return null!==l.h.errorMsg},errorMsg:function(){return l.h.errorMsg},confirmObj:function(){return l.h.confirmObj},assessName:function(){return l.h.assessInfo.name},showDueDialog:function(){return l.h.show_enddate_dialog},lastPos:function(){return l.h.lastPos}},methods:{beforeUnload:function(e){Object.keys(l.h.autosaveQueue).length>0&&l.N.submitAutosave(!1);var t=!0;if(l.h.assessInfo.hasOwnProperty("questions")){var n=0,o=l.h.assessInfo.questions.length;for(var r in l.h.assessInfo.questions)l.h.assessInfo.questions[r].try>0&&n++;n===o&&(t=!1)}if(l.h.noUnload);else{if(!l.h.inProgress&&Object.keys(l.h.work).length>0&&!this.prewarned)return e.preventDefault(),this.prewarned=!1,this.$t("unload.unsubmitted_work");if(l.h.inProgress){if(Object.keys(l.N.getChangedQuestions()).length>0&&!this.prewarned)return e.preventDefault(),this.prewarned=!1,this.$t("unload.unsubmitted_questions");if("by_assessment"===l.h.assessInfo.submitby&&l.h.assessInfo.has_active_attempt&&!this.prewarned)return e.preventDefault(),t?this.$t("unload.unsubmitted_assessment"):this.$t("unload.unsubmitted_done_assessment")}else;}this.prewarned=!1},clearError:function(){l.h.errorMsg=null},closeConfirm:function(){l.h.confirmObj=null}},created:function(){window.$(document).on("click",(function(e){l.h.lastPos=e.pageY})),window.$(document).on("focusin",(function(e){e.target&&e.target.getBoundingClientRect&&(l.h.lastPos=e.target.getBoundingClientRect().top)})),window.$(window).on("beforeunload",this.beforeUnload);var e=this;window.$("a").not('#app a, a[href="#"]').on("click",(function(t){return"by_assessment"===l.h.assessInfo.submitby&&l.h.assessInfo.has_active_attempt?(t.preventDefault(),l.h.confirmObj={body:"unload.unsubmitted_assessment",action:function(){e.prewarned=!0,window.location=t.currentTarget.href}},!1):!l.h.inProgress&&Object.keys(l.h.work).length>0?(t.preventDefault(),l.h.confirmObj={body:"unload.unsubmitted_work",action:function(){e.prewarned=!0,window.location=t.currentTarget.href}},!1):void 0}))}};const N=(0,x.Z)(E,[["render",u]]);var q=N,I=n(9400),M=n(3568);n.p=window.imasroot+"/assess2/vue/",(0,o.ri)(q).use(I.Z).use(M.a).mount("#app")}},t={};function n(o){var r=t[o];if(void 0!==r)return r.exports;var s=t[o]={exports:{}};return e[o].call(s.exports,s,s.exports,n),s.exports}n.m=e,function(){var e=[];n.O=function(t,o,r,s){if(!o){var i=1/0;for(d=0;d<e.length;d++){o=e[d][0],r=e[d][1],s=e[d][2];for(var a=!0,u=0;u<o.length;u++)(!1&s||i>=s)&&Object.keys(n.O).every((function(e){return n.O[e](o[u])}))?o.splice(u--,1):(a=!1,s<i&&(i=s));if(a){e.splice(d--,1);var l=r();void 0!==l&&(t=l)}}return t}s=s||0;for(var d=e.length;d>0&&e[d-1][2]>s;d--)e[d]=e[d-1];e[d]=[o,r,s]}}(),function(){n.n=function(e){var t=e&&e.__esModule?function(){return e["default"]}:function(){return e};return n.d(t,{a:t}),t}}(),function(){var e,t=Object.getPrototypeOf?function(e){return Object.getPrototypeOf(e)}:function(e){return e.__proto__};n.t=function(o,r){if(1&r&&(o=this(o)),8&r)return o;if("object"===typeof o&&o){if(4&r&&o.__esModule)return o;if(16&r&&"function"===typeof o.then)return o}var s=Object.create(null);n.r(s);var i={};e=e||[null,t({}),t([]),t(t)];for(var a=2&r&&o;"object"==typeof a&&!~e.indexOf(a);a=t(a))Object.getOwnPropertyNames(a).forEach((function(e){i[e]=function(){return o[e]}}));return i["default"]=function(){return o},n.d(s,i),s}}(),function(){n.d=function(e,t){for(var o in t)n.o(t,o)&&!n.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})}}(),function(){n.f={},n.e=function(e){return Promise.all(Object.keys(n.f).reduce((function(t,o){return n.f[o](e,t),t}),[]))}}(),function(){n.u=function(e){return"js/"+{924:"special",987:"lang-de-json"}[e]+".legacy.js?v="+{924:"0a7307239246104c",987:"d5cc97651a98ff36"}[e]}}(),function(){n.miniCssF=function(e){return"css/special.css"}}(),function(){n.g=function(){if("object"===typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(e){if("object"===typeof window)return window}}()}(),function(){n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}}(),function(){var e={},t="imathas-assess2:";n.l=function(o,r,s,i){if(e[o])e[o].push(r);else{var a,u;if(void 0!==s)for(var l=document.getElementsByTagName("script"),d=0;d<l.length;d++){var c=l[d];if(c.getAttribute("src")==o||c.getAttribute("data-webpack")==t+s){a=c;break}}a||(u=!0,a=document.createElement("script"),a.charset="utf-8",a.timeout=120,n.nc&&a.setAttribute("nonce",n.nc),a.setAttribute("data-webpack",t+s),a.src=o),e[o]=[r];var f=function(t,n){a.onerror=a.onload=null,clearTimeout(h);var r=e[o];if(delete e[o],a.parentNode&&a.parentNode.removeChild(a),r&&r.forEach((function(e){return e(n)})),t)return t(n)},h=setTimeout(f.bind(null,void 0,{type:"timeout",target:a}),12e4);a.onerror=f.bind(null,a.onerror),a.onload=f.bind(null,a.onload),u&&document.head.appendChild(a)}}}(),function(){n.r=function(e){"undefined"!==typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})}}(),function(){n.p="vue/"}(),function(){if("undefined"!==typeof document){var e=function(e,t,n,o,r){var s=document.createElement("link");s.rel="stylesheet",s.type="text/css";var i=function(n){if(s.onerror=s.onload=null,"load"===n.type)o();else{var i=n&&("load"===n.type?"missing":n.type),a=n&&n.target&&n.target.href||t,u=new Error("Loading CSS chunk "+e+" failed.\n("+a+")");u.code="CSS_CHUNK_LOAD_FAILED",u.type=i,u.request=a,s.parentNode&&s.parentNode.removeChild(s),r(u)}};return s.onerror=s.onload=i,s.href=t,n?n.parentNode.insertBefore(s,n.nextSibling):document.head.appendChild(s),s},t=function(e,t){for(var n=document.getElementsByTagName("link"),o=0;o<n.length;o++){var r=n[o],s=r.getAttribute("data-href")||r.getAttribute("href");if("stylesheet"===r.rel&&(s===e||s===t))return r}var i=document.getElementsByTagName("style");for(o=0;o<i.length;o++){r=i[o],s=r.getAttribute("data-href");if(s===e||s===t)return r}},o=function(o){return new Promise((function(r,s){var i=n.miniCssF(o),a=n.p+i;if(t(i,a))return r();e(o,a,null,r,s)}))},r={826:0};n.f.miniCss=function(e,t){var n={924:1};r[e]?t.push(r[e]):0!==r[e]&&n[e]&&t.push(r[e]=o(e).then((function(){r[e]=0}),(function(t){throw delete r[e],t})))}}}(),function(){var e={826:0};n.f.j=function(t,o){var r=n.o(e,t)?e[t]:void 0;if(0!==r)if(r)o.push(r[2]);else{var s=new Promise((function(n,o){r=e[t]=[n,o]}));o.push(r[2]=s);var i=n.p+n.u(t),a=new Error,u=function(o){if(n.o(e,t)&&(r=e[t],0!==r&&(e[t]=void 0),r)){var s=o&&("load"===o.type?"missing":o.type),i=o&&o.target&&o.target.src;a.message="Loading chunk "+t+" failed.\n("+s+": "+i+")",a.name="ChunkLoadError",a.type=s,a.request=i,r[1](a)}};n.l(i,u,"chunk-"+t,t)}},n.O.j=function(t){return 0===e[t]};var t=function(t,o){var r,s,i=o[0],a=o[1],u=o[2],l=0;if(i.some((function(t){return 0!==e[t]}))){for(r in a)n.o(a,r)&&(n.m[r]=a[r]);if(u)var d=u(n)}for(t&&t(o);l<i.length;l++)s=i[l],n.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return n.O(d)},o=self["webpackChunkimathas_assess2"]=self["webpackChunkimathas_assess2"]||[];o.forEach(t.bind(null,0)),o.push=t.bind(null,o.push.bind(o))}();var o=n.O(void 0,[998,64],(function(){return n(176)}));o=n.O(o)})();
//# sourceMappingURL=index.legacy.js.map