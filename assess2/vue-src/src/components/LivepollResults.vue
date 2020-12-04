<template>
  <div v-if="qinfo && qinfo.answeights">
    <p>{{ $tc('livepoll.numresults', numResults) }}</p>
    <div v-show="showresults && numResults > 0">
      <div v-for="(resdata,pn) in results" :key="qn + '-' + pn" class="med-below">
        <livepoll-results-choices
          v-if="resdata.hasOwnProperty('choices')"
          :results = "resdata"
          :showans = "showans"
        />
        <livepoll-results-general
          v-else
          :results = "resdata"
          :showans = "showans"
          :itemid = "qn + '-' + pn"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';
import LivepollResultsChoices from '@/components/LivepollResultsChoices.vue';
import LivepollResultsGeneral from '@/components/LivepollResultsGeneral.vue';

export default {
  name: 'LivepollResults',
  props: ['qn', 'showresults', 'showans'],
  components: {
    LivepollResultsChoices,
    LivepollResultsGeneral
  },
  computed: {
    qinfo () {
      return store.assessInfo.questions[this.qn];
    },
    numResults () {
      if (store.livepollResults.hasOwnProperty(this.qn)) {
        return Object.keys(store.livepollResults[this.qn]).length;
      } else {
        return 0;
      }
    },
    params () {
      // reformat jsparams to be indexed by part number
      const out = [];
      for (let pn = 0; pn < this.qinfo.answeights.length; pn++) {
        if (pn === 0 && this.qinfo.jsparams.hasOwnProperty(this.qn)) {
          out[pn] = this.qinfo.jsparams[this.qn];
        } else {
          out[pn] = this.qinfo.jsparams[(this.qn + 1) * 1000 + pn];
        }
      }
      return out;
    },
    results () {
      const out = [];
      for (let pn = 0; pn < this.qinfo.answeights.length; pn++) {
        const datatots = {};
        const scoredata = {};
        // if has choices, initialize totals to 0
        if (this.params[pn].hasOwnProperty('livepoll_choices')) {
          for (let i = 0; i < this.params[pn].livepoll_choices.length; i++) {
            datatots[i] = 0;
            scoredata[i] = 0;
          }
        }
        const parttype = this.params[pn].qtype;
        const ischoices = (parttype === 'choices' || parttype === 'multans');
        // mark the correct answers for choices and multans
        if (ischoices) {
          let anss;
          if (parttype === 'choices') {
            anss = this.params[pn].livepoll_ans.toString().split(/\s+or\s+/);
          } else {
            anss = this.params[pn].livepoll_ans.toString().split(/\s*,\s*/);
          }
          for (let i = 0; i < anss.length; i++) {
            scoredata[anss[i]] = 1;
          }
        }
        const condenseddrawarr = [];
        let condenseddraw;
        for (const uid in store.livepollResults[this.qn]) {
          let stuans = store.livepollResults[this.qn][uid].ans[pn];
          if (ischoices) {
            stuans = stuans.toString().split('|');
          } else if (parttype.match(/calc/) || parttype === 'numfunc') {
            stuans = ['`' + stuans + '`'];
          } else {
            stuans = [stuans];
          }
          // condense drawing answer for comparison
          if (parttype === 'draw') {
            condenseddraw = this.condenseDraw(stuans[0]);
            if (!condenseddrawarr.hasOwnProperty(condenseddraw)) {
              condenseddrawarr[condenseddraw] = stuans[0];
            }
          }
          // add student's answers to tallys
          for (let pa = 0; pa < stuans.length; pa++) {
            if (parttype === 'draw' && datatots.hasOwnProperty(condenseddrawarr[condenseddraw])) {
              // if this drawing is the same condensed as another
              datatots[condenseddrawarr[condenseddraw]] += 1;
            } else if (datatots.hasOwnProperty(stuans[pa])) {
              datatots[stuans[pa]] += 1;
            } else {
              // unseen answer - add to list
              datatots[stuans[pa]] = 1;
              scoredata[stuans[pa]] = store.livepollResults[this.qn][uid].score[pn];
            }
          }
        } // end loop over results

        let maxfreq = 1;
        for (const i in datatots) {
          if (datatots[i] > maxfreq) {
            maxfreq = datatots[i];
          }
        }
        out[pn] = {
          datatots: datatots,
          scoredata: scoredata,
          maxfreq: maxfreq,
          qtype: parttype
        };
        if (ischoices) {
          out[pn].choices = this.params[pn].livepoll_choices;
        }
        if (parttype === 'draw') {
          const initpts = this.params[pn].livepoll_drawinit;
          for (let i = 1; i < Math.min(11, initpts.length); i++) {
            initpts[i] = Number(initpts[i]);
          }
          out[pn].initpts = initpts;
        }
      } // end loop over parts
      return out;
    }
  },
  methods: {
    condenseDraw (str) {
      if (str === '') { return str; }
      var la = str.replace(/\(/g, '[').replace(/\)/g, ']');
      la = la.split(';;');
      if (la[0] !== '') {
        la[0] = '[' + la[0].replace(/;/g, '],[') + ']';
      }
      la = '[[' + la.join('],[') + ']]';
      var drawarr = JSON.parse(la);
      if (drawarr[0].length > 0) { // has freehand lines
        for (let i = 0; i < drawarr[0].length; i++) {
          if (drawarr[0][i].length === 2) { // if line has two points, sort them
            drawarr[0][i].sort(function (a, b) {
              if (a[0] === b[0]) {
                return (a[1] - b[1]);
              } else {
                return (a[0] - b[0]);
              }
            });
          }
        }
      } else if (drawarr.length > 4 && drawarr[4].length > 0) { // has ineq graphs
        return str;
      }
      if (drawarr[1].length > 0) { // has dots
        drawarr[1].sort(function (a, b) {
          if (a[0] === b[0]) {
            return (a[1] - b[1]);
          } else {
            return (a[0] - b[0]);
          }
        });
      }
      if (drawarr[2].length > 0) { // has opendots
        drawarr[2].sort(function (a, b) {
          if (a[0] === b[0]) {
            return (a[1] - b[1]);
          } else {
            return (a[0] - b[0]);
          }
        });
      }
      var cc, newcc, m, b;
      if (drawarr.length > 3 && drawarr[3].length > 0) { // handle twopoint curves
        // type, x1, y1, x2, y2
        //  0    1    2   3  4
        for (let i = 0; i < drawarr[3].length; i++) {
          cc = drawarr[3][i];
          if (cc[0] === 5) { // standard line
            if (cc[1] === cc[3]) {
              newcc = [5, 'x', cc[1]];
            } else {
              m = (cc[4] - cc[2]) / (cc[3] - cc[1]);
              b = cc[2] - m * cc[1];
              newcc = [5, m.toFixed(4), b.toFixed(2)];
            }
            drawarr[3][i] = newcc;
          } else if (cc[0] === 5.2) { // ray
            if (cc[1] === cc[3]) {
              newcc = [5.2, 'x', cc[1], cc[2]];
            } else {
              m = (cc[4] - cc[2]) / (cc[3] - cc[1]);
              newcc = [5.2, m.toFixed(4), cc[1], cc[2]];
            }
            drawarr[3][i] = newcc;
          } else if (cc[0] === 5.3) { // line seg
            if (cc[1] < cc[3] || (cc[1] === cc[3] && cc[2] < cc[4])) {
              newcc = [5.3, cc[1], cc[2], cc[3], cc[4]];
            } else {
              newcc = [5.3, cc[3], cc[4], cc[1], cc[2]];
            }
            drawarr[3][i] = newcc;
          } else if (cc[0] === 6) { // parab
            if (cc[1] === cc[3]) {
              newcc = [6, 'x', cc[1], cc[2]];
            } else {
              m = (cc[4] - cc[2]) / ((cc[3] - cc[1]) * (cc[3] - cc[1]));
              newcc = [6, m.toFixed(4), cc[1], cc[2]];
            }
            drawarr[3][i] = newcc;
          } else if (cc[0] === 6.5) { // sqrt
            if (cc[1] === cc[3]) {
              newcc = [6.5, 'x', cc[1], cc[2]];
            } else {
              b = (cc[3] > cc[1]) ? 1 : -1;
              m = (cc[4] - cc[2]) / Math.sqrt(Math.abs(cc[3] - cc[1]));
              newcc = [6.5, m.toFixed(4), b, cc[1], cc[2]];
            }
            drawarr[3][i] = newcc;
          } else if (cc[0] === 8) { // abs
            if (cc[1] === cc[3]) {
              newcc = [8, 'x', cc[1], cc[2]];
            } else {
              m = (cc[4] - cc[2]) / Math.abs(cc[3] - cc[1]);
              newcc = [8, m.toFixed(4), cc[1], cc[2]];
            }
            drawarr[3][i] = newcc;
          }
        }
      }
      return JSON.stringify(drawarr);
    }
  }
};
</script>

<style>
  .LPres td, .LPres th {padding: 8px; border: 1px solid #999;}
  .LPres th {background-color: #eee;}
  .LPres {border-collapse: collapse; border: 1px solid #999;}
  .LPres tr td:first-child {padding-left: 30px;}
  .LPshowcorrect td {background-color:#CCFFCC;}
  .LPshowcorrect td:first-child {background:#CCFFCC  no-repeat 8px center;}
  .LPshowwrong td {background-color:#FFCCCC;}
  .LPshowwrong td:first-child {background:#FFCCCC  no-repeat 8px center;}
  .LPshowpartial td {background-color:#FFEEAA;}
  .LPshowpartial td:first-child {background: #FFEEAA  no-repeat 8px center;}
  .LPresval {}
  .LPresbarwrap {display:inline-block; width:100%;}
  .LPresbar {display:inline-block; background-color: #CCCCCC; text-align:center; overflow:show; padding:5px 0px;}
  .LPshowcorrect .LPresbar, .LPshowwrong  .LPresbar {background-color: #FFFFFF;}
  .LPdrawgrid > div { margin: 8px; border: 1px solid #999;}
</style>
