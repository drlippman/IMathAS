"use strict";(self["webpackChunkimathas_assess2"]=self["webpackChunkimathas_assess2"]||[]).push([[924],{4864:function(e,t,s){s.r(t),s.d(t,{default:function(){return me}});var n=s(6252),i=s(9963),o=s(3577),r={class:"home"},l={key:1,class:"subheader"},u={key:0,id:"livepoll_qsettings",style:{"flex-grow":"1"}},a={key:1,style:{"flex-grow":"1"}},c={key:2},h=["aria-label"],p={key:1,class:"questionpane"};function d(e,t,s,d,f,w){var v=(0,n.up)("assess-header"),m=(0,n.up)("livepoll-nav"),y=(0,n.up)("timer"),q=(0,n.up)("livepoll-settings"),g=(0,n.up)("question"),k=(0,n.up)("livepoll-results");return(0,n.wg)(),(0,n.iD)("div",r,[(0,n.Wm)(v),w.isTeacher?((0,n.wg)(),(0,n.j4)(m,{key:0,qn:w.curqn,onSelectq:w.selectQuestion,onOpenq:w.openInput,onCloseq:w.closeInput,onNewversion:w.newVersion},null,8,["qn","onSelectq","onOpenq","onCloseq","onNewversion"])):(0,n.kq)("",!0),w.curstate>0&&w.curqn>-1&&(w.isTeacher||w.timelimit>0)?((0,n.wg)(),(0,n.iD)("div",l,[w.isTeacher?((0,n.wg)(),(0,n.iD)("div",u,[(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[0]||(t[0]=function(t){return e.showQuestion=t})},null,512),[[i.e8,e.showQuestion]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_question")),1)]),(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[1]||(t[1]=function(t){return e.showResults=t})},null,512),[[i.e8,e.showResults]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_results")),1)]),(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[2]||(t[2]=function(t){return e.showAnswers=t}),onChange:t[3]||(t[3]=function(){return w.updateShowAnswers&&w.updateShowAnswers.apply(w,arguments)})},null,544),[[i.e8,e.showAnswers]]),(0,n.Uk)(" "+(0,o.zw)(w.showAnswersLabel),1)])])):((0,n.wg)(),(0,n.iD)("div",a)),w.timelimit>0&&w.starttime>0?((0,n.wg)(),(0,n.j4)(y,{key:2,end:1e3*(w.starttime+w.timelimit),total:w.timelimit},null,8,["end","total"])):(0,n.kq)("",!0)])):(0,n.kq)("",!0),!w.isTeacher&&w.curstate>0?((0,n.wg)(),(0,n.iD)("div",c,[(0,n._)("h2",null,(0,o.zw)(e.$t("question_n",{n:w.curqn+1})),1)])):(0,n.kq)("",!0),(0,n._)("div",{class:"scrollpane","aria-label":e.$t("regions.questions")},[!w.isTeacher||0!==w.curstate&&-1!==w.curqn?(0,n.kq)("",!0):((0,n.wg)(),(0,n.j4)(q,{key:0,class:"questionpane"})),!w.isTeacher&&w.curstate<2?((0,n.wg)(),(0,n.iD)("div",p,(0,o.zw)(e.$t("livepoll.waiting")),1)):(0,n.kq)("",!0),w.curqn>=0&&(w.isTeacher&&w.curstate>0||!w.isTeacher&&w.curstate>1)?(0,n.wy)(((0,n.wg)(),(0,n.j4)(g,{key:2,qn:w.curqn,active:!0,state:w.curstate,seed:w.curseed},null,8,["qn","state","seed"])),[[i.F8,e.showQuestion]]):(0,n.kq)("",!0),w.isTeacher?((0,n.wg)(),(0,n.j4)(k,{showresults:e.showResults,showans:4===w.curstate,qn:w.curqn,key:w.curqn+"-"+w.curseed},null,8,["showresults","showans","qn"])):(0,n.kq)("",!0)],8,h)])}s(9358);var f=s(1104),w={class:"subheader"},v=["aria-label"],m=["disabled","aria-label"],y=["disabled","aria-label"],q={style:{"flex-grow":"1"}};function g(e,t,s,i,r,l){var u=(0,n.up)("menu-button"),a=(0,n.up)("icons");return(0,n.wg)(),(0,n.iD)("div",w,[(0,n._)("div",{class:"flexrow",style:{"flex-grow":"1"},role:"navigation","aria-label":e.$t("regions.qnav")},[(0,n.Wm)(u,{id:"qnav",options:l.navOptions,selected:l.dispqn,searchby:"dispqn"},{default:(0,n.w5)((function(e){var t=e.option;return[(0,n.Uk)((0,o.zw)(t.title),1)]})),_:1},8,["options","selected"]),l.showNextPrev?((0,n.wg)(),(0,n.iD)("button",{key:0,onClick:t[0]||(t[0]=function(e){return l.selectQuestion(l.dispqn-1)}),disabled:l.dispqn<=0,class:"secondarybtn",id:"qprev","aria-label":e.$t("previous")},[(0,n.Wm)(a,{name:"left"})],8,m)):(0,n.kq)("",!0),l.showNextPrev?((0,n.wg)(),(0,n.iD)("button",{key:1,onClick:t[1]||(t[1]=function(e){return l.selectQuestion(l.dispqn+1)}),disabled:l.dispqn>=l.navOptions.length-1,class:"secondarybtn",id:"qnext","aria-label":e.$t("next")},[(0,n.Wm)(a,{name:"right"})],8,y)):(0,n.kq)("",!0)],8,v),(0,n._)("div",q,[2===l.curstate&&l.dispqn>0?((0,n.wg)(),(0,n.iD)("button",{key:0,class:"primary",onClick:t[2]||(t[2]=function(){return l.closeQuestion&&l.closeQuestion.apply(l,arguments)})},(0,o.zw)(e.$t("livepoll.close_input")),1)):l.curstate>0&&l.dispqn>0?((0,n.wg)(),(0,n.iD)("button",{key:1,class:"primary",onClick:t[3]||(t[3]=function(){return l.openQuestion&&l.openQuestion.apply(l,arguments)})},(0,o.zw)(e.$t("livepoll.open_input")),1)):(0,n.kq)("",!0),l.curstate>2&&l.dispqn>0?((0,n.wg)(),(0,n.iD)("button",{key:2,class:"secondary",onClick:t[4]||(t[4]=function(){return l.newVersion&&l.newVersion.apply(l,arguments)})},[(0,n.Wm)(a,{name:"retake"}),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.new_version")),1)])):(0,n.kq)("",!0)]),(0,n._)("div",null,(0,o.zw)(l.studentCount),1)])}s(560);var k=s(8584),_=s(1038),I=s(9273),b={name:"LivepollNav",props:["qn"],components:{MenuButton:k.Z,Icons:_.Z},computed:{navOptions:function(){var e=this,t=[];t.push({onclick:function(){return e.$emit("selectq",0)},title:this.$t("livepoll.settings"),dispqn:0});var s=function(){var s=parseInt(n)+1;t.push({onclick:function(){return e.$emit("selectq",s)},title:e.$t("question_n",{n:s}),dispqn:s})};for(var n in I.h.assessInfo.questions)s();return t},showNextPrev:function(){return Object.keys(this.navOptions).length>1},dispqn:function(){return parseInt(this.qn)+1},curstate:function(){return I.h.assessInfo.livepoll_status.curstate},studentCount:function(){return this.$tc("livepoll.stucnt",I.h.livepollStuCnt)}},methods:{selectQuestion:function(e){this.$emit("selectq",e)},openQuestion:function(){this.$emit("openq")},closeQuestion:function(){this.$emit("closeq")},newVersion:function(){this.$emit("newversion")}}},x=s(3744);const T=(0,x.Z)(b,[["render",g]]);var P=T,$=(0,n._)("br",null,null,-1),S=(0,n._)("br",null,null,-1),L=(0,n._)("br",null,null,-1),O=(0,n._)("br",null,null,-1);function R(e,t,s,r,l,u){return(0,n.wg)(),(0,n.iD)("div",null,[(0,n._)("h2",null,(0,o.zw)(e.$t("livepoll.settings")),1),(0,n._)("p",null,[(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[0]||(t[0]=function(e){return u.showQuestionDefault=e})},null,512),[[i.e8,u.showQuestionDefault]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_question_default")),1)]),$,(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[1]||(t[1]=function(e){return u.showResultsLiveDefault=e})},null,512),[[i.e8,u.showResultsLiveDefault]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_results_live_default")),1)]),S,(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[2]||(t[2]=function(e){return u.showResultsAfter=e})},null,512),[[i.e8,u.showResultsAfter]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_results_after")),1)]),L,(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[3]||(t[3]=function(e){return u.showAnswersAfter=e})},null,512),[[i.e8,u.showAnswersAfter]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.show_answers_after")),1)]),O,(0,n._)("label",null,[(0,n.wy)((0,n._)("input",{type:"checkbox","onUpdate:modelValue":t[4]||(t[4]=function(e){return u.useTimer=e})},null,512),[[i.e8,u.useTimer]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.use_timer")),1)]),(0,n.wy)((0,n._)("span",null,[(0,n.wy)((0,n._)("input",{type:"text",size:"3","onUpdate:modelValue":t[5]||(t[5]=function(e){return u.questionTimelimit=e})},null,512),[[i.nr,u.questionTimelimit]]),(0,n.Uk)(" "+(0,o.zw)(e.$t("livepoll.seconds")),1)],512),[[i.F8,u.useTimer]])])])}var D={name:"LivepollSettings",computed:{showQuestionDefault:{set:function(e){this.$set(I.h.livepollSettings,"showQuestionDefault",e)},get:function(){return I.h.livepollSettings.showQuestionDefault}},showResultsLiveDefault:{set:function(e){this.$set(I.h.livepollSettings,"showResultsLiveDefault",e)},get:function(){return I.h.livepollSettings.showResultsLiveDefault}},showResultsAfter:{set:function(e){this.$set(I.h.livepollSettings,"showResultsAfter",e)},get:function(){return I.h.livepollSettings.showResultsAfter}},showAnswersAfter:{set:function(e){this.$set(I.h.livepollSettings,"showAnswersAfter",e)},get:function(){return I.h.livepollSettings.showAnswersAfter}},useTimer:{set:function(e){this.$set(I.h.livepollSettings,"useTimer",e)},get:function(){return I.h.livepollSettings.useTimer}},questionTimelimit:{set:function(e){this.$set(I.h.livepollSettings,"questionTimelimit",e)},get:function(){return I.h.livepollSettings.questionTimelimit}}}};const N=(0,x.Z)(D,[["render",R]]);var A=N,C={key:0};function V(e,t,s,r,l,u){var a=(0,n.up)("livepoll-results-choices"),c=(0,n.up)("livepoll-results-general");return u.qinfo&&u.qinfo.answeights?((0,n.wg)(),(0,n.iD)("div",C,[(0,n._)("p",null,(0,o.zw)(e.$tc("livepoll.numresults",u.numResults)),1),(0,n.wy)((0,n._)("div",null,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(u.results,(function(e,t){return(0,n.wg)(),(0,n.iD)("div",{key:s.qn+"-"+t,class:"med-below"},[e.hasOwnProperty("choices")?((0,n.wg)(),(0,n.j4)(a,{key:0,results:e,showans:s.showans},null,8,["results","showans"])):((0,n.wg)(),(0,n.j4)(c,{key:1,results:e,showans:s.showans,itemid:s.qn+"-"+t},null,8,["results","showans","itemid"]))])})),128))],512),[[i.F8,s.showresults&&u.numResults>0]])])):(0,n.kq)("",!0)}s(4043),s(9873),s(228),s(2826),s(2462),s(9288),s(7267),s(6203),s(5137),s(7389),s(8324);var j={class:"LPres",ref:"main"},F=(0,n._)("caption",{class:"sr-only"},"Results",-1),z={style:{"min-width":"10em"}},E=["innerHTML"],U={class:"LPresbarwrap"},Q={class:"LPresval"};function W(e,t,s,i,r,l){return(0,n.wg)(),(0,n.iD)("table",j,[F,(0,n._)("thead",null,[(0,n._)("tr",null,[(0,n._)("th",null,(0,o.zw)(e.$t("livepoll.answer")),1),(0,n._)("th",z,(0,o.zw)(e.$t("livepoll.frequency")),1)])]),(0,n._)("tbody",null,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(s.results.choices,(function(e,t){return(0,n.wg)(),(0,n.iD)("tr",{key:t,class:(0,o.C_)([s.showans?s.results.scoredata[t]>0?"LPshowcorrect":"LPshowwrong":""])},[(0,n._)("td",{innerHTML:e},null,8,E),(0,n._)("td",null,[(0,n._)("span",U,[(0,n._)("span",{class:"LPresbar",style:(0,o.j5)({width:Math.round(100*s.results.datatots[t]/s.results.maxfreq)+"%"})},[(0,n._)("span",Q,(0,o.zw)(s.results.datatots[t]),1)],4)])])],2)})),128))])],512)}var Z={name:"LivepollResultsChoices",props:["results","showans"],methods:{onUpdate:function(){var e=this;this.$nextTick((function(){setTimeout(window.drawPics,100),window.rendermathnode(e.$refs.main)}))}},mounted:function(){this.onUpdate()},watch:{results:function(e,t){this.onUpdate()}}};const M=(0,x.Z)(Z,[["render",W]]);var H=M,Y={key:0,class:"LPdrawgrid",ref:"main"},K=["id","width","height"],J=["id"],G={key:1,class:"LPres",ref:"main"},B=(0,n._)("caption",{class:"sr-only"},"Results",-1),X={style:{"min-width":"10em"}},ee={key:0},te=["id","width","height"],se=["id"],ne={key:1},ie={class:"LPresbarwrap"},oe={class:"LPresval"};function re(e,t,s,i,r,l){return"draw"===s.results.qtype&&0===s.results.initpts[11]?((0,n.wg)(),(0,n.iD)("div",Y,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(l.sortedKeys,(function(e,t){return(0,n.wg)(),(0,n.iD)("div",{key:e,class:(0,o.C_)([s.showans?s.results.scoredata[e]>0?s.results.scoredata[e]<.99?"LPshowpartial":"LPshowcorrect":"LPshowwrong":""])},[(0,n._)("canvas",{class:"drawcanvas",id:"canvasLP"+s.itemid+"-"+t,width:s.results.initpts[6],height:s.results.initpts[7]},null,8,K),(0,n._)("input",{type:"hidden",id:"qnLP"+s.itemid+"-"+t},null,8,J)],2)})),128))],512)):((0,n.wg)(),(0,n.iD)("table",G,[B,(0,n._)("thead",null,[(0,n._)("tr",null,[(0,n._)("th",null,(0,o.zw)(e.$t("livepoll.answer")),1),(0,n._)("th",X,(0,o.zw)(e.$t("livepoll.frequency")),1)])]),(0,n._)("tbody",null,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(l.sortedKeys,(function(e,t){return(0,n.wg)(),(0,n.iD)("tr",{key:e,class:(0,o.C_)([s.showans?s.results.scoredata[e]>0?s.results.scoredata[e]<.99?"LPshowpartial":"LPshowcorrect":"LPshowwrong":""])},["draw"===s.results.qtype?((0,n.wg)(),(0,n.iD)("td",ee,[(0,n._)("canvas",{class:"drawcanvas",id:"canvasLP"+s.itemid+"-"+t,width:s.results.initpts[6],height:s.results.initpts[7]},null,8,te),(0,n._)("input",{type:"hidden",id:"qnLP"+s.itemid+"-"+t},null,8,se)])):((0,n.wg)(),(0,n.iD)("td",ne,(0,o.zw)(e),1)),(0,n._)("td",null,[(0,n._)("span",ie,[(0,n._)("span",{class:"LPresbar",style:(0,o.j5)({width:Math.round(100*s.results.datatots[e]/s.results.maxfreq)+"%"})},[(0,n._)("span",oe,(0,o.zw)(s.results.datatots[e]),1)],4)])])],2)})),128))])],512))}s(9730),s(1719);var le={name:"LivepollResultsGeneral",props:["results","showans","itemid"],computed:{sortedKeys:function(){var e=this.results.datatots,t=Object.keys(e);return t.sort((function(t,s){return e[s]-e[t]}))}},methods:{onUpdate:function(){var e=this;if("draw"===this.results.qtype){for(var t=0;t<this.sortedKeys.length;t++){var s=this.sortedKeys[t].replace(/\(/g,"[").replace(/\)/g,"]");s=s.split(";;"),""!==s[0]&&(s[0]="["+s[0].replace(/;/g,"],[")+"]"),s="[["+s.join("],[")+"]]";var n="LP"+this.itemid+"-"+t;window.canvases[n]=this.results.initpts.slice(),window.canvases[n].unshift(n),window.drawla[n]=JSON.parse(s)}this.$nextTick((function(){for(var t=0;t<e.sortedKeys.length;t++)window.imathasDraw.initCanvases("LP"+e.itemid+"-"+t)}))}this.$nextTick((function(){setTimeout(window.drawPics,100),window.rendermathnode(e.$refs.main)}))}},mounted:function(){this.onUpdate()},watch:{results:function(e,t){this.onUpdate()}}};const ue=(0,x.Z)(le,[["render",re]]);var ae=ue,ce={name:"LivepollResults",props:["qn","showresults","showans"],components:{LivepollResultsChoices:H,LivepollResultsGeneral:ae},computed:{qinfo:function(){return I.h.assessInfo.questions[this.qn]},numResults:function(){return I.h.livepollResults.hasOwnProperty(this.qn)?Object.keys(I.h.livepollResults[this.qn]).length:0},params:function(){for(var e=[],t=0;t<this.qinfo.answeights.length;t++)0===t&&this.qinfo.jsparams.hasOwnProperty(this.qn)?e[t]=this.qinfo.jsparams[this.qn]:e[t]=this.qinfo.jsparams[1e3*(this.qn+1)+t];return e},results:function(){for(var e=[],t=0;t<this.qinfo.answeights.length;t++){var s={},n={};if(this.params[t].hasOwnProperty("livepoll_choices"))for(var i=0;i<this.params[t].livepoll_choices.length;i++)s[i]=0,n[i]=0;var o=this.params[t].qtype,r="choices"===o||"multans"===o;if(r){var l=void 0;l="choices"===o?this.params[t].livepoll_ans.toString().split(/\s+or\s+/):this.params[t].livepoll_ans.toString().split(/\s*,\s*/);for(var u=0;u<l.length;u++)n[l[u]]=1}var a=[],c=void 0;for(var h in I.h.livepollResults[this.qn]){var p=I.h.livepollResults[this.qn][h].ans[t];p=r?p.toString().split("|"):o.match(/calc/)||"numfunc"===o?["`"+p+"`"]:[p],"draw"===o&&(c=this.condenseDraw(p[0]),a.hasOwnProperty(c)||(a[c]=p[0]));for(var d=0;d<p.length;d++)"draw"===o&&s.hasOwnProperty(a[c])?s[a[c]]+=1:s.hasOwnProperty(p[d])?s[p[d]]+=1:(s[p[d]]=1,n[p[d]]=I.h.livepollResults[this.qn][h].score[t])}var f=1;for(var w in s)s[w]>f&&(f=s[w]);if(e[t]={datatots:s,scoredata:n,maxfreq:f,qtype:o},r&&(e[t].choices=this.params[t].livepoll_choices),"draw"===o){for(var v=this.params[t].livepoll_drawinit,m=1;m<Math.min(11,v.length);m++)v[m]=Number(v[m]);e[t].initpts=v}}return e}},methods:{condenseDraw:function(e){if(""===e)return e;var t=e.replace(/\(/g,"[").replace(/\)/g,"]");t=t.split(";;"),""!==t[0]&&(t[0]="["+t[0].replace(/;/g,"],[")+"]"),t="[["+t.join("],[")+"]]";var s,n,i,o,r=JSON.parse(t);if(r[0].length>0)for(var l=0;l<r[0].length;l++)2===r[0][l].length&&r[0][l].sort((function(e,t){return e[0]===t[0]?e[1]-t[1]:e[0]-t[0]}));else if(r.length>4&&r[4].length>0)return e;if(r[1].length>0&&r[1].sort((function(e,t){return e[0]===t[0]?e[1]-t[1]:e[0]-t[0]})),r[2].length>0&&r[2].sort((function(e,t){return e[0]===t[0]?e[1]-t[1]:e[0]-t[0]})),r.length>3&&r[3].length>0)for(var u=0;u<r[3].length;u++)s=r[3][u],5===s[0]?(s[1]===s[3]?n=[5,"x",s[1]]:(i=(s[4]-s[2])/(s[3]-s[1]),o=s[2]-i*s[1],n=[5,i.toFixed(4),o.toFixed(2)]),r[3][u]=n):5.2===s[0]?(s[1]===s[3]?n=[5.2,"x",s[1],s[2]]:(i=(s[4]-s[2])/(s[3]-s[1]),n=[5.2,i.toFixed(4),s[1],s[2]]),r[3][u]=n):5.3===s[0]?(n=s[1]<s[3]||s[1]===s[3]&&s[2]<s[4]?[5.3,s[1],s[2],s[3],s[4]]:[5.3,s[3],s[4],s[1],s[2]],r[3][u]=n):6===s[0]?(s[1]===s[3]?n=[6,"x",s[1],s[2]]:(i=(s[4]-s[2])/((s[3]-s[1])*(s[3]-s[1])),n=[6,i.toFixed(4),s[1],s[2]]),r[3][u]=n):6.5===s[0]?(s[1]===s[3]?n=[6.5,"x",s[1],s[2]]:(o=s[3]>s[1]?1:-1,i=(s[4]-s[2])/Math.sqrt(Math.abs(s[3]-s[1])),n=[6.5,i.toFixed(4),o,s[1],s[2]]),r[3][u]=n):8===s[0]&&(s[1]===s[3]?n=[8,"x",s[1],s[2]]:(i=(s[4]-s[2])/Math.abs(s[3]-s[1]),n=[8,i.toFixed(4),s[1],s[2]]),r[3][u]=n);return JSON.stringify(r)}}};const he=(0,x.Z)(ce,[["render",V]]);var pe=he,de=s(9174),fe=s(7575),we={name:"livepoll",components:{LivepollNav:P,Question:de.Z,LivepollSettings:A,LivepollResults:pe,AssessHeader:f.Z,Timer:fe.Z},data:function(){return{showQuestion:!0,showResults:!0,showAnswers:!0,onSettings:!1,livepollTimer:null,socket:null}},computed:{isTeacher:function(){return I.h.assessInfo.is_teacher},curqn:function(){return this.onSettings?-1:parseInt(I.h.assessInfo.livepoll_status.curquestion)-1},curseed:function(){return I.h.assessInfo.livepoll_status.seed},curstate:function(){return I.h.assessInfo.livepoll_status.curstate},starttime:function(){return I.h.assessInfo.livepoll_status.startt},timelimit:function(){return I.h.livepollSettings.useTimer?parseInt(I.h.livepollSettings.questionTimelimit):I.h.assessInfo.livepoll_status.timelimit?parseInt(I.h.assessInfo.livepoll_status.timelimit):0},showAnswersLabel:function(){return this.curstate<3?this.$t("livepoll.show_answers_after"):this.$t("livepoll.show_answers")}},methods:{updateUsercount:function(e){I.h.livepollStuCnt=e.cnt,0===e.teachcnt&&(I.h.assessInfo.livepoll_status.curstate=0)},addResult:function(e){I.h.livepollResults.hasOwnProperty(this.curqn)||this.$set(I.h.livepollResults,this.curqn,{}),e.score=JSON.parse(e.score),e.ans=JSON.parse(e.ans),this.$set(I.h.livepollResults[this.curqn],e.user,e)},showHandler:function(e){if("showq"===e.action){if(I.N.clearInitValue(e.qn),-1!==e.startt.indexOf("-")){var t=e.startt.split("-");e.startt=t[0],e.timelimit=t[1]}else e.timelimit=0;this.$set(I.h.assessInfo,"livepoll_status",{curstate:2,curquestion:parseInt(e.qn)+1,seed:parseInt(e.seed),startt:parseInt(e.startt),timelimit:parseInt(e.timelimit)})}else this.$set(I.h.assessInfo,"livepoll_status",Object.assign(I.h.assessInfo.livepoll_status,{curquestion:parseInt(e.qn)+1,curstate:parseInt(e.action),timelimit:0}))},selectQuestion:function(e){clearTimeout(this.livepollTimer);var t=parseInt(e)-1;if(-1!==t){if(this.onSettings=!1,t!==this.curqn){this.showQuestion=I.h.livepollSettings.showQuestionDefault,this.showResults=I.h.livepollSettings.showResultsLiveDefault,this.showAnswers=I.h.livepollSettings.showAnswersAfter;var s=1;I.h.livepollResults[t]&&Object.keys(I.h.livepollResults[t]).length>0&&(s=this.showAnswers?4:3),t>=0&&I.N.setLivepollStatus({newquestion:e,newstate:s})}}else this.onSettings=!0},openInput:function(){var e=this;I.N.setLivepollStatus({newquestion:this.curqn+1,newstate:2,timelimit:this.timelimit}),this.timelimit>0&&(this.livepollTimer=window.setTimeout((function(){return e.closeInput()}),1e3*this.timelimit))},closeInput:function(){clearTimeout(this.livepollTimer);var e=this.showAnswers?4:3;I.h.livepollSettings.showResultsAfter&&(this.showResults=!0),I.N.setLivepollStatus({newquestion:this.curqn+1,newstate:e})},newVersion:function(){I.N.setLivepollStatus({newquestion:this.curqn+1,newstate:1,forceregen:1}),this.$set(I.h.livepollResults,this.curqn,{})},updateShowAnswers:function(){if(this.curstate>2){var e=this.showAnswers?4:3;I.N.setLivepollStatus({newquestion:this.curqn+1,newstate:e})}}},mounted:function(){var e=this,t=I.h.assessInfo.livepoll_server,s=I.h.assessInfo.livepoll_data,n="room="+s.room+"&now="+s.now;s.sig&&(n+="&sig="+encodeURIComponent(s.sig)),this.socket=window.io("https://"+t+":3000",{query:n}),this.socket.off(),this.socket.on("livepoll usercount",(function(t){return e.updateUsercount(t)})),I.h.assessInfo.is_teacher?this.socket.on("livepoll qans",(function(t){return e.addResult(t)})):this.socket.on("livepoll show",(function(t){return e.showHandler(t)}))},created:function(){0===I.h.assessInfo.livepoll_status.curquestion&&this.isTeacher&&(this.onSettings=!0)}};const ve=(0,x.Z)(we,[["render",d]]);var me=ve},3019:function(e,t,s){s.r(t),s.d(t,{default:function(){return W}});var n=s(6252),i=s(9963),o=s(3577),r={class:"home"},l=["aria-label"],u={id:"playerwrapper"},a=(0,n._)("div",{id:"player"},null,-1),c=[a],h=["aria-hidden"];function p(e,t,s,a,p,d){var f=(0,n.up)("assess-header"),w=(0,n.up)("videocued-result-nav"),v=(0,n.up)("videocued-nav"),m=(0,n.up)("intro-text"),y=(0,n.up)("inter-question-text-list"),q=(0,n.up)("full-question-header"),g=(0,n.up)("question");return(0,n.wg)(),(0,n.iD)("div",r,[(0,n._)("a",{href:"#",class:"sr-only",onClick:t[0]||(t[0]=(0,i.iM)((function(t){return e.$refs.scrollpane.focus()}),["prevent"]))},(0,o.zw)(e.$t("jumptocontent")),1),(0,n.Wm)(f),(0,n.Wm)(v,{cue:e.cue,toshow:e.toshow,onJumpto:d.jumpTo},{default:(0,n.w5)((function(){return[(0,n.Wm)(w,{class:"med-left",qn:d.qn,cue:e.cue,onJumpto:d.jumpTo},null,8,["qn","cue","onJumpto"])]})),_:1},8,["cue","toshow","onJumpto"]),(0,n._)("div",{class:"scrollpane",role:"region",ref:"scrollpane",tabindex:"-1","aria-label":e.$t("regions.q_and_vid")},[(0,n.Wm)(m,{active:-1==e.cue,html:d.intro,key:"-1"},null,8,["active","html"]),(0,n.wy)((0,n._)("div",u,[(0,n._)("div",{class:"video-wrapper-wrapper",style:(0,o.j5)({"max-width":e.videoWidth+"px"})},[(0,n._)("div",{class:"fluid-width-video-wrapper",style:(0,o.j5)({"padding-bottom":e.aspectRatioPercent+"%"})},c,4)],4)],512),[[i.F8,e.cue>-1&&-1===d.qn]]),((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(d.questionArray,(function(e){return(0,n.wg)(),(0,n.iD)("div",{key:e,"aria-hidden":e!=d.qn,class:(0,o.C_)({inactive:e!=d.qn})},[(0,n.Wm)(y,{pos:"before",qn:e,active:e==d.qn,textlist:d.textList,lastq:d.lastQ},null,8,["qn","active","textlist","lastq"]),(0,n.wy)((0,n.Wm)(q,{qn:e},null,8,["qn"]),[[i.F8,e==d.qn]]),(0,n.Wm)(g,{qn:e,active:e==d.qn,getwork:1},null,8,["qn","active"]),(0,n.Wm)(y,{pos:"after",qn:e,active:e==d.qn,textlist:d.textList,lastq:d.lastQ},null,8,["qn","active","textlist","lastq"])],10,h)})),128))],8,l)])}var d=s(1104),f=["aria-label"];function w(e,t,s,i,o,r){var l=(0,n.up)("videocued-nav-list-item"),u=(0,n.up)("menu-button");return(0,n.wg)(),(0,n.iD)("div",{class:"subheader",role:"navigation","aria-label":e.$t("regions.qvidnav")},[(0,n.Wm)(u,{id:"qnav",options:r.navOptions,selected:r.curOption,searchby:"title"},{default:(0,n.w5)((function(e){var t=e.option,s=e.selected;return[(0,n.Wm)(l,{option:t,selected:s},null,8,["option","selected"])]})),_:1},8,["options","selected"]),(0,n.WI)(e.$slots,"default")],8,f)}s(560),s(9358);var v=s(8584),m={class:"flex-nowrap-center"},y={class:"qname-wrap"},q=["title"],g={key:0,class:"redoicon"},k={key:1,class:"redoicon"};function _(e,t,s,i,r,l){var u=(0,n.up)("icons");return(0,n.wg)(),(0,n.iD)("span",m,[(0,n._)("span",y,[(0,n.Wm)(u,{name:l.statusIcon,class:"qstatusicon"},null,8,["name"]),(0,n._)("span",{class:(0,o.C_)({greystrike:""!==l.nameHover}),title:l.nameHover},(0,o.zw)(s.option.title),11,q),(0,n.Uk)(" "+(0,o.zw)(l.scoreDisplay),1)]),s.selected?(0,n.kq)("",!0):((0,n.wg)(),(0,n.iD)("span",g,[l.canRetry?((0,n.wg)(),(0,n.j4)(u,{key:0,name:"retry"})):(0,n.kq)("",!0)])),s.selected?(0,n.kq)("",!0):((0,n.wg)(),(0,n.iD)("span",k,[l.canRegen?((0,n.wg)(),(0,n.j4)(u,{key:0,name:"retake"})):(0,n.kq)("",!0)]))])}var I=s(1038),b=s(9273),x=s(4845),T={name:"VideocuedNavListItem",props:["option","selected"],components:{Icons:I.Z},mixins:[x.w],computed:{statusIcon:function(){if("v"===this.option.type||"f"===this.option.type)return"video";if("q"===this.option.type){if("unattempted"===b.h.assessInfo.questions[this.option.qn].status){if(1===this.qsAttempted[this.option.qn])return"attempted";if(this.qsAttempted[this.option.qn]>0)return"partattempted"}return b.h.assessInfo.questions[this.option.qn].status}return"none"},nameHover:function(){return"q"===this.option.type&&0!==b.h.assessInfo.questions[this.option.qn].withdrawn?this.$t("header.withdrawn"):""},scoreDisplay:function(){if("q"!==this.option.type)return"";var e=b.h.assessInfo.questions[this.option.qn];if(e.hasOwnProperty("gbscore")){var t=e.canretry?"(":"[";return t+=e.gbscore+"/"+e.points_possible,t+=e.canretry?")":"]",t}return this.$tc("header.pts",e.points_possible)},canRetry:function(){if("q"===this.option.type){var e=b.h.assessInfo.questions[this.option.qn];return e.canretry}return!1},canRegen:function(){if("q"===this.option.type){var e=b.h.assessInfo.questions[this.option.qn];return e.regens_remaining}return!1}}},P=s(3744);const $=(0,P.Z)(T,[["render",_]]);var S=$,L={name:"VideocuedNav",props:["cue","toshow"],components:{MenuButton:v.Z,VideocuedNavListItem:S},computed:{hasIntro:function(){return""!==b.h.assessInfo.intro},navOptions:function(){var e=this,t=[];this.hasIntro&&t.push({onclick:function(){return e.$emit("jumpto",-1,"i")},title:this.$t("intro"),type:"i"});for(var s=function(s){var n=b.h.assessInfo.videocues[s];t.push({onclick:function(){return e.$emit("jumpto",s,"v")},type:"v",title:n.title,cue:s}),n.hasOwnProperty("qn")&&t.push({onclick:function(){return e.$emit("jumpto",s,"q")},type:"q",title:e.$t("question_n",{n:parseInt(n.qn)+1}),qn:parseInt(n.qn),cue:s,subitem:!0}),n.hasOwnProperty("followuptime")&&t.push({onclick:function(){return e.$emit("jumpto",s,"f")},type:"f",title:n.followuptitle,cue:s,subitem:!0})},n=0;n<b.h.assessInfo.videocues.length;n++)s(n);return t},curOption:function(){var e=parseInt(this.cue);if(-1===e&&this.hasIntro)return 0;for(var t=this.hasIntro?1:0;t<this.navOptions.length;t++)if(this.navOptions[t].cue===e&&this.navOptions[t].type===this.toshow)return t;return-1},showNextPrev:function(){return Object.keys(this.navOptions).length>1},prevLink:function(){return this.curOption<=0?"":this.navOptions[this.curOption-1].internallink},nextLink:function(){return this.curOption>=this.navOptions.length-1?"":this.navOptions[this.curOption+1].internallink}}};const O=(0,P.Z)(L,[["render",w]]);var R=O,D=s(5530),N=s(5713),A={key:0};function C(e,t,s,i,r,l){return-1===s.qn||l.showNav?((0,n.wg)(),(0,n.iD)("div",A,[-1===s.qn&&-1===s.cue?((0,n.wg)(),(0,n.iD)("button",{key:0,onClick:t[0]||(t[0]=function(){return l.startVid&&l.startVid.apply(l,arguments)}),class:"primary"},(0,o.zw)(e.$t("videocued.start")),1)):(0,n.kq)("",!0),s.qn>-1&&l.hasNextVid?((0,n.wg)(),(0,n.iD)("button",{key:1,onClick:t[1]||(t[1]=function(){return l.nextVidLink&&l.nextVidLink.apply(l,arguments)}),class:(0,o.C_)({primary:"correct"!==l.status||!l.showSkip})},(0,o.zw)(e.$t("videocued.continue",{title:l.nextVidTitle})),3)):(0,n.kq)("",!0),s.qn>-1&&l.showSkip?((0,n.wg)(),(0,n.iD)("button",{key:2,onClick:t[2]||(t[2]=function(){return l.skipLink&&l.skipLink.apply(l,arguments)}),class:"primary"},(0,o.zw)(e.$t("videocued.skipto",{title:l.skipTitle})),1)):(0,n.kq)("",!0)])):(0,n.kq)("",!0)}var V={name:"VideocuedResultNav",props:["qn","cue"],computed:{qdata:function(){return b.h.assessInfo.questions[this.qn]},showNav:function(){return b.h.inProgress&&b.h.assessInfo.hasOwnProperty("questions")&&this.qdata.hasOwnProperty("score")&&(this.qdata.try>0||this.qdata.hasOwnProperty("tries_remaining_range"))&&0===this.qdata.withdrawn},showScores:function(){return"during"===b.h.assessInfo.showscores},status:function(){if(!this.showScores||!this.qdata.hasOwnProperty("parts"))return"neutral";for(var e=0;e<this.qdata.parts.length;e++)if(0===this.qdata.parts[e].try||this.qdata.parts[e].rawscore<.98)return"neutral";return"correct"},nextVidType:function(){return b.h.assessInfo.videocues[this.cue].hasOwnProperty("followuptitle")?"followup":"nextseg"},hasNextVid:function(){return"followup"===this.nextVidType||b.h.assessInfo.videocues.hasOwnProperty(this.cue+1)},nextVidTitle:function(){return"followup"===this.nextVidType?b.h.assessInfo.videocues[this.cue].followuptitle:b.h.assessInfo.videocues[this.cue+1].title},showSkip:function(){return"correct"===this.status&&"followup"===this.nextVidType&&b.h.assessInfo.videocues.hasOwnProperty(this.cue+1)},skipTitle:function(){return b.h.assessInfo.videocues[this.cue+1].title}},methods:{skipLink:function(){this.$emit("jumpto",this.cue+1,"v")},nextVidLink:function(){"followup"===this.nextVidType?this.$emit("jumpto",this.cue,"f"):this.$emit("jumpto",this.cue+1,"v")},startVid:function(){this.$emit("jumpto",0,"v")}}};const j=(0,P.Z)(V,[["render",C]]);var F=j,z=s(9174),E=s(1436),U={name:"videocued",components:{FullQuestionHeader:D.Z,VideocuedNav:R,Question:z.Z,VideocuedResultNav:F,InterQuestionTextList:N.Z,AssessHeader:d.Z,IntroText:E.Z},data:function(){return{videoWidth:600,aspectRatioPercent:56.2,ytplayer:null,timer:null,cue:0,toshow:"v"}},computed:{curCue:function(){return this.cue>-1?b.h.assessInfo.videocues[this.cue]:{}},qn:function(){return"q"===this.toshow?parseInt(this.curCue.qn):-1},timeCues:function(){var e={};for(var t in b.h.assessInfo.videocues)b.h.assessInfo.videocues[t].hasOwnProperty("qn")&&(e[b.h.assessInfo.videocues[t].time]=parseInt(t));return e},nextVidTimes:function(){for(var e={},t=0;t<b.h.assessInfo.videocues.length;t++)b.h.assessInfo.videocues[t].hasOwnProperty("followuptime")&&b.h.assessInfo.videocues.hasOwnProperty(t+1)?e[b.h.assessInfo.videocues[t].followuptime]=t:!b.h.assessInfo.videocues[t].hasOwnProperty("qn")&&b.h.assessInfo.videocues.hasOwnProperty(t+1)&&(e[b.h.assessInfo.videocues[t].time]=t);return e},intro:function(){return b.h.assessInfo.intro},questionArray:function(){for(var e={},t=0;t<b.h.assessInfo.questions.length;t++)e[t]=t;return e},lastQ:function(){return b.h.assessInfo.questions.length-1},textList:function(){return b.h.assessInfo.hasOwnProperty("interquestion_text")?b.h.assessInfo.interquestion_text:[]}},methods:{createPlayer:function(){var e=this,t=!!(document.exitFullscreen||document.mozCancelFullScreen||document.webkitExitFullscreen||document.msExitFullscreen),s={autoplay:0,wmode:"transparent",fs:t?1:0,controls:2,rel:0,modestbranding:1,showinfo:0,origin:window.location.protocol+"//"+window.location.host},n=b.h.assessInfo.videoar.split(":"),i=window.innerHeight-50;this.videoWidth=n[0]/n[1]*i,this.aspectRatioPercent=Math.round(1e3*i/this.videoWidth)/10,this.ytplayer=new window.YT.Player("player",{height:i,width:this.videoWidth,videoId:b.h.assessInfo.videoid,playerVars:s,events:{onReady:function(){return e.handlePlayerReady()},onStateChange:function(t){return e.handlePlayerStateChange(t)},onError:function(t){return e.handlePlayerError(t)}}})},exitFullscreen:function(){var e=document.fullscreenElement||document.webkitFullscreenElement||document.mozFullScreenElement||document.msFullscreenElement;e&&(document.exitFullscreen?document.exitFullscreen():document.webkitExitFullscreen?document.webkitExitFullscreen():document.mozCancelFullScreen?document.mozCancelFullScreen():document.msExitFullscreen&&document.msExitFullscreen())},checkTime:function(){var e=this,t=Math.floor(this.ytplayer.getCurrentTime());!this.timeCues.hasOwnProperty(t)||"v"===this.toshow&&this.cue===this.timeCues[t]+1||"f"===this.toshow&&this.cue===this.timeCues[t]||this.ytplayer.getPlayerState()!==window.YT.PlayerState.PLAYING?(this.nextVidTimes.hasOwnProperty(t)&&this.cue===this.nextVidTimes[t]&&(this.cue=this.cue+1,this.toshow="v"),this.timer=window.setTimeout((function(){e.checkTime()}),200)):this.jumpTo(parseInt(this.timeCues[t]),"q")},handlePlayerReady:function(){window.$("iframe#player").removeAttr("height").removeAttr("width").css("height","").css("width","")},handlePlayerStateChange:function(e){var t=this;e.data===window.YT.PlayerState.PLAYING?this.timer=window.setTimeout((function(){t.checkTime()}),200):e.data===window.YT.PlayerState.ENDED&&"v"===this.toshow&&this.curCue.hasOwnProperty("qn")&&(window.clearTimeout(this.timer),this.jumpTo(this.cue,"q"))},handlePlayerError:function(e){b.h.errorMsg=e.data},jumpTo:function(e,t){var s=this,n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:0;if(-1===e||"q"===t)this.exitFullscreen(),this.ytplayer&&this.ytplayer.pauseVideo();else{if(null===this.ytplayer||"function"!==typeof this.ytplayer.seekTo)return 0===n&&(b.h.errorMsg="ytnotready"),void window.setTimeout((function(){s.jumpTo(e,t,1)}),100);n>0&&(b.h.errorMsg=null);var i=b.h.assessInfo.videocues[e],o=0;if("v"===t){if(e>0){var r=b.h.assessInfo.videocues[e-1];o=r.hasOwnProperty("followuptime")?r.followuptime:r.time}}else"f"===t&&(o=i.time);this.ytplayer.seekTo(o,!0),this.ytplayer.playVideo()}this.cue=e,this.toshow=t}},mounted:function(){var e=this;if(window.YT)this.createPlayer();else if(window.onYouTubePlayerAPIReady=function(){e.createPlayer()},!document.getElementById("yt_player_api")){var t=document.createElement("script");t.id="yt_player_api",t.src="https://www.youtube.com/player_api",document.head.appendChild(t)}},created:function(){""!==b.h.assessInfo.intro&&(this.cue=-1,this.toshow="i")}};const Q=(0,P.Z)(U,[["render",p]]);var W=Q},534:function(e,t,s){var n=s(8700),i=s(4327),o=s(4684),r=RangeError;e.exports=function(e){var t=i(o(this)),s="",l=n(e);if(l<0||l===1/0)throw new r("Wrong number of repetitions");for(;l>0;(l>>>=1)&&(t+=t))1&l&&(s+=t);return s}},3648:function(e,t,s){var n=s(8844);e.exports=n(1..valueOf)},1719:function(e,t,s){var n=s(9989),i=s(690),o=s(6310),r=s(5649),l=s(8494),u=s(5565),a=1!==[].unshift(0),c=function(){try{Object.defineProperty([],"length",{writable:!1}).unshift()}catch(e){return e instanceof TypeError}},h=a||!c();n({target:"Array",proto:!0,arity:1,forced:h},{unshift:function(e){var t=i(this),s=o(t),n=arguments.length;if(n){u(s+n);var a=s;while(a--){var c=a+n;a in t?t[c]=t[a]:l(t,c)}for(var h=0;h<n;h++)t[h]=arguments[h]}return r(t,s+n)}})},9288:function(e,t,s){var n=s(9989),i=s(3931),o=s(7697),r=s(9037),l=s(496),u=s(8844),a=s(5266),c=s(6812),h=s(3457),p=s(3622),d=s(734),f=s(8732),w=s(3689),v=s(2741).f,m=s(2474).f,y=s(2560).f,q=s(3648),g=s(1435).trim,k="Number",_=r[k],I=l[k],b=_.prototype,x=r.TypeError,T=u("".slice),P=u("".charCodeAt),$=function(e){var t=f(e,"number");return"bigint"==typeof t?t:S(t)},S=function(e){var t,s,n,i,o,r,l,u,a=f(e,"number");if(d(a))throw new x("Cannot convert a Symbol value to a number");if("string"==typeof a&&a.length>2)if(a=g(a),t=P(a,0),43===t||45===t){if(s=P(a,2),88===s||120===s)return NaN}else if(48===t){switch(P(a,1)){case 66:case 98:n=2,i=49;break;case 79:case 111:n=8,i=55;break;default:return+a}for(o=T(a,2),r=o.length,l=0;l<r;l++)if(u=P(o,l),u<48||u>i)return NaN;return parseInt(o,n)}return+a},L=a(k,!_(" 0o1")||!_("0b1")||_("+0x1")),O=function(e){return p(b,e)&&w((function(){q(e)}))},R=function(e){var t=arguments.length<1?0:_($(e));return O(this)?h(Object(t),this,R):t};R.prototype=b,L&&!i&&(b.constructor=R),n({global:!0,constructor:!0,wrap:!0,forced:L},{Number:R});var D=function(e,t){for(var s,n=o?v(t):"MAX_VALUE,MIN_VALUE,NaN,NEGATIVE_INFINITY,POSITIVE_INFINITY,EPSILON,MAX_SAFE_INTEGER,MIN_SAFE_INTEGER,isFinite,isInteger,isNaN,isSafeInteger,parseFloat,parseInt,fromString,range".split(","),i=0;n.length>i;i++)c(t,s=n[i])&&!c(e,s)&&y(e,s,m(t,s))};i&&I&&D(l[k],I),(L||i)&&D(l[k],_)},7389:function(e,t,s){var n=s(9989),i=s(8844),o=s(8700),r=s(3648),l=s(534),u=s(3689),a=RangeError,c=String,h=Math.floor,p=i(l),d=i("".slice),f=i(1..toFixed),w=function(e,t,s){return 0===t?s:t%2===1?w(e,t-1,s*e):w(e*e,t/2,s)},v=function(e){var t=0,s=e;while(s>=4096)t+=12,s/=4096;while(s>=2)t+=1,s/=2;return t},m=function(e,t,s){var n=-1,i=s;while(++n<6)i+=t*e[n],e[n]=i%1e7,i=h(i/1e7)},y=function(e,t){var s=6,n=0;while(--s>=0)n+=e[s],e[s]=h(n/t),n=n%t*1e7},q=function(e){var t=6,s="";while(--t>=0)if(""!==s||0===t||0!==e[t]){var n=c(e[t]);s=""===s?n:s+p("0",7-n.length)+n}return s},g=u((function(){return"0.000"!==f(8e-5,3)||"1"!==f(.9,0)||"1.25"!==f(1.255,2)||"1000000000000000128"!==f(0xde0b6b3a7640080,0)}))||!u((function(){f({})}));n({target:"Number",proto:!0,forced:g},{toFixed:function(e){var t,s,n,i,l=r(this),u=o(e),h=[0,0,0,0,0,0],f="",g="0";if(u<0||u>20)throw new a("Incorrect fraction digits");if(l!==l)return"NaN";if(l<=-1e21||l>=1e21)return c(l);if(l<0&&(f="-",l=-l),l>1e-21)if(t=v(l*w(2,69,1))-69,s=t<0?l*w(2,-t,1):l/w(2,t,1),s*=4503599627370496,t=52-t,t>0){m(h,0,s),n=u;while(n>=7)m(h,1e7,0),n-=7;m(h,w(10,n,1),0),n=t-1;while(n>=23)y(h,1<<23),n-=23;y(h,1<<n),m(h,1,1),y(h,2),g=q(h)}else m(h,0,s),m(h,1<<-t,0),g=q(h)+p("0",u);return u>0?(i=g.length,g=f+(i<=u?"0."+p("0",u-i)+g:d(g,0,i-u)+"."+d(g,i-u))):g=f+g,g}})}}]);
//# sourceMappingURL=special.legacy.js.map?v=15b52ed0f4de4ad2