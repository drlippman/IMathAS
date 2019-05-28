<template>
  <div id="app">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>

    <router-view v-if="assessInfoLoaded"/>

    <error-dialog v-if="hasError" />
  </div>
</template>

<script>
import { store, actions } from './basicstore';
import ErrorDialog from '@/components/ErrorDialog.vue';

export default {
  components: {
    ErrorDialog
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    hasError () {
      return (store.errorMsg !== null);
    },
    assessName () {
      return store.assessInfo.name;
    }
  },
  methods: {
    beforeUnload () {
      if (store.autosaveQueue.length > 0) {
        actions.submitAutosave(false);
      }
      var unanswered = true;
      if (store.assessInfo.hasOwnProperty('questions')) {
        let qAnswered = 0;
        let nQuestions = store.assessInfo.questions.length;
        for (let i in store.assessInfo.questions) {
          if (store.assessInfo.questions[i].try > 0) {
            qAnswered++;
          }
        }
        if (qAnswered === nQuestions) {
          unanswered = false;
        }
      }
      if (Object.keys(actions.getChangedQuestions()).length > 0) {
        return this.$t('unload.unsubmitted_questions');
      } else if (store.assessInfo.submitby === 'by_assessment' && !unanswered) {
        return this.$t('unload.unsubmitted_assessment');
      }
    }
  },
  created () {
    window.$(window).on('beforeunload', this.beforeUnload);
  }
};
</script>

<style>
.scrollpane {
  background-image: linear-gradient(to right, white, white), linear-gradient(to right, white, white), linear-gradient(to right, rgba(0,0,0,.25), rgba(255,255,255,0)), linear-gradient(to left, rgba(0,0,0,.25), rgba(255,255,255,0));
  background-position: left center, right center, left center, right center;
  background-repeat: no-repeat;
  background-color: inherit;
  background-size: 20px 100%, 20px 100%, 10px 100%, 10px 100%;
  background-attachment: local, local, scroll, scroll;
}
input + svg {
  vertical-align: middle;
}
a[target=_blank].noextlink::after {
  display: none;
}
input[type=submit],input[type=button], button, a.abutton {
  padding: 3px 12px;
  height: auto;
}
input[type=submit]:disabled,input[type=button]:disabled, button:disabled {
  opacity: .5;
}
input, .mq-editable-field {
  border: 1px solid #999;
  padding: 4px 6px;
  border-radius: 4px;
  margin: 1px 0;
}
button.slim {
  padding: 0px 12px;
}
button.nopad {
  padding: 0;
}
button.plain {
  border: 0;
  background-color: #fff;
}
button.plain:hover, button.plain:active, button.plain.active {
  background-color: #EDF4FC;
}
input[type=submit].primary,input[type=button].primary, button.primary, a.abutton.primary {
  color: #fff;
  background-color: #1E74D1;
}
input[type=submit].primary:enabled:hover, button.primary:enabled:hover,input[type=button].primary:enabled:hover, a.abutton.primary:hover {
  background-color: #175aa2;
}
input[type=submit].primary:focus, button.primary:focus,input[type=button].primary:focus, a.abutton.primary:focus {
  background-color: #175aa2;
}
input[type=submit].secondarybtn,input[type=button].secondarybtn, button.secondarybtn {
  color: #000;
  background-color: #eee;
}
input[type=submit].secondarybtn:enabled:hover,input[type=button].secondarybtn:enabled:hover, button.secondarybtn:enabled:hover {
  background-color: #ddd;
}
input[type=submit].secondarybtn:focus,input[type=button].secondarybtn:focus, button.secondarybtn:focus {
  background-color: #ddd;
}
.dropdown-menu a {
  text-decoration: none;
}
.subdued {
  color: #aaa;
}
.flexrow {
  display: flex;
  flex-flow: row nowrap;
}
.headerpane {
  border-bottom: 1px solid #ccc;
  padding-bottom: 10px;
}
.no-margin-top {
  margin-top: 0;
}
.ind1 {
  margin-left: 20px;
}
.med-below {
  margin-bottom: 16px;
}
.med-left {
  margin-left: 16px;
}
.fade-enter-active, .fade-leave-active {
  transition: opacity .5s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
}
.hidden {
  display: none;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition-duration: 0.5s;
  transition-property: height, opacity, transform;
  transition-timing-function: cubic-bezier(0.55, 0, 0.1, 1);
  overflow: hidden;
}

.slide-left-enter,
.slide-right-leave-active {
  opacity: 0;
  transform: translate(2em, 0);
}

.slide-left-leave-active,
.slide-right-enter {
  opacity: 0;
  transform: translate(-2em, 0);
}

.ehdd {
  position: absolute;
  background: #669;
  display:none;
  color: #fff;
  font-size: 75%;
  padding: 0px 4px;
  opacity: 0.9;
}
.eh {
  position: absolute;
  background: #669;
  color: #fff;
  display: none;
  padding: 0px 4px;
  font-size: 75%;
  opacity: 0.9;
}

.drawtools {
  margin-left: 8px;
}
.drawtools img {
  vertical-align: middle;
}
.drawtools [data-drawaction] {
  border: 1px solid #ccc;
  padding: 2px;
  cursor: pointer;
}
.drawtools span[data-drawaction] {
  padding: 2px 8px;
}
.drawtools [data-drawaction]:hover {
  background-color: #EDF4FC;
}
.drawtools [data-drawaction].sel {
  background-color: #E6F0F9;
}

/* TODO separate out */

div.choice {
	padding: 5px;
	float: left;
	text-align: center;
}
div.float {
	float:left;
}

div.match {
	float:left;
	padding: 5px;
	padding-right: 30px;
}
div.spacer {
	clear: both;
	height: 1px;
	padding: 0px;
}
p.centered {
	text-align: center;
}

table.p3longdiv {
  border-spacing: 0px;
  border-collapse: collapse;
}
table.p3longdiv td.right {
  text-align: right;
  padding: 0px;
}
table.p3longdiv td.bottomborder {
  border-bottom:1px solid black;
  text-align: right;
  padding: 0px;
}
table.p3longdiv td.topborder {
  border-top:1px solid black;
  text-align: right;
  padding: 0px;
}
table.p3longdiv td.topleftborder {
  border-left: 1px solid black;
  border-top: 1px solid black;
  text-align: right;
  padding: 0px;
}
table.stats {
  margin-left: 20px;
  border-collapse: collapse;
  background: #eef;
}
table.stats tbody tr td {
  border: 1px solid #000;
  padding: 1px 10px;
}
table.stats tbody tr td {
  text-align: right;
}
table td.left {
  text-align: left;
}
table.stats thead tr th {
  border: 1px solid #000;
  padding: 1px 10px;
  text-align: center;
  background: #eff;
  border-bottom: 3px solid #000;
}
table.stats tbody tr th {
  border: 1px solid #000;
  padding: 1px 10px;
  text-align: center;
  background: #eff;
  border-right: 3px solid #000;
}
table.scores td {
  padding-right: 20px;
}
span.spanbutton {
  padding: 1px 3px;
  border: 2px outset #f9f;
  background: #f9f;
  cursor: default;
}
span.spanbutton:active {
  border: 2px inset #f9f;
  background: #fcf;
  cursor: default;
}
span.spanbutton:hover {
  background: #fcf;
}
table.longdiv {
  border-collapse: collapse;
  border: 0px;
}
table.longdiv td {
  padding: 3px;
}
td.barslefttop {
  border-left: 1px solid #000;
  border-top: 1px solid #000;
}
td.matrixleft {
  border: 1px solid #000;
  border-width: 1px 0px 1px 1px;
  margin: 0px;
  padding: 0px;
}
td.matrixright {
  border: 1px solid #000;
  border-width: 1px 1px 1px 0px;
  margin: 0px;
  padding: 0px;
}
table.paddedtable tr.onepixel { line-height: 1px;}
table.paddedtable td.matrixtopborder {border-top:1px solid black;padding:0;}
table.paddedtable td.matrixtopleftborder { border-left:1px solid black;border-top:1px solid black;padding:0;width:5px; }
table.paddedtable td.matrixtoprightborder { border-right:1px solid black;border-top:1px solid black;padding:0;width:5px; }
table.paddedtable td.matrixleftborder { border-left:1px solid black;padding:0;width:5px; }
table.paddedtable td.matrixrightborder { border-right:1px solid black;padding:0;width:5px; }
table.paddedtable td.matrixbottomleftborder { border-left:1px solid black;border-bottom:1px solid black;padding:0;width:5px; }
table.paddedtable td.matrixbottomrightborder { border-right:1px solid black;border-bottom:1px solid black;padding:0;width:5px; }
table.paddedtable td.nopad { padding:0;  width: 5px;}

table.paddedtable td, table.paddedtable th {
  padding: 2px 5px;
}
td.c, table.stats tbody td.c {
  text-align: center;
}
td.r, table.stats tbody td.r {
  text-align: right;
}
td.l, table.stats tbody td.l {
  text-align: left;
}

ul.nomark {
	padding-left: 40px;
}
div.toppad {
	padding-top: 5px;
}
ul.likelines {
	padding: 0;
	margin: 0;
	list-style-type: none;
}
ul.likelines li {
	margin-bottom: .4em;
}

ul.nomark {list-style-type: none;}
ul.nomark li { margin-top: .7em;}
ol.lalpha {list-style-type: lower-alpha;}
ol.lalpha li { margin-top: .7em;}

.spaced li { margin-bottom: .3em;}

</style>
