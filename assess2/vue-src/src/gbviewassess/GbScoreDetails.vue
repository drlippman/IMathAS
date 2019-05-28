<template>
  <div class="scoredetails">
    <div>
      {{ $t('gradebook.score') }}:
      <span
        v-for="(poss,i) in partPoss"
        :key="i"
      >
        <input
          v-if="canedit"
          type="text"
          size="4"
          v-model="curScores[i]"
          @input="updateScore(i, $event)"
        /><span v-else>{{ curScores[i] }}</span>/{{ poss }}
      </span>
      <button
        v-if="canedit && showfeedback === false"
        type="button"
        class="slim"
        @click="showfeedback = true"
      >
        {{ $t('gradebook.add_feedback') }}
      </button>
    </div>
    <div
      v-show="showfeedback"
    >
      {{ $t('gradebook.feedback') }}:<br/>
      <textarea
        v-if="canedit && !useEditor"
        class="fbbox"
        rows="2"
        cols="60"
        @input="updateFeedback"
      >{{ qdata.feedback }}</textarea>
      <div
        v-else-if="canedit"
        rows="2"
        class="fbbox"
        v-html="qdata.feedback"
        @input="updateFeedback"
      />
      <div
        v-else
        v-html="qdata.feedback"
      />
    </div>
    <div v-if="canedit && showfull">
      {{ $t('gradebook.quick_grade') }}:
      <button
        type="button"
        @click="allFull"
        class="slim"
      >
        {{ fullCreditLabel }}
      </button>
    </div>
    <div v-if="qdata.timeactive.total > 0 && showfull">
      {{ $t('gradebook.time_on_version') }}:
      {{ timeSpent }}
    </div>
    <div v-if="canedit && showfull">
      <a :href="useInMsg" target="_blank">
        {{ $t('gradebook.use_in_msg') }}
      </a>
      <button
        type="button"
        class="slim"
        @click="clearWork"
      >
        {{ $t('gradebook.clear_qwork') }}
      </button>
    </div>
    <div v-if="canedit && showfull">
      {{ $t('gradebook.question_id') }}:
        <a
          target="_blank"
          :href="questionEditUrl"
        >{{ qdata.qsetid }}</a>.
      {{ $t('gradebook.seed') }}:
        {{ qdata.seed }}.
      <a
        v-if="questionErrorUrl != ''"
        target="_blank"
        :href="questionErrorUrl"
      >{{ $t('gradebook.msg_owner') }}.</a>
      <span v-if="qHelps.length > 0">
        {{ $t('gradebook.had_help') }}:
        <a v-for="help in qHelps"
          :href="help.url"
          target="_blank"
        >{{ help.title }}</a>
      </span>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';

export default {
  name: 'GbScoreDetails',
  props: ['qdata', 'qn', 'canedit', 'showfull'],
  data: function() {
    return {
      curScores: false,
      showfeedback: false
    }
  },
  computed: {
    answeights() {
      if (!this.qdata.answeights) { // if answeights not generated yet
        return [1];
      } else {
        return this.qdata.answeights;
      }
    },
    partPoss() {
      var out = [];
      for (let i=0; i<this.answeights.length; i++) {
        out[i] = Math.round(1000*this.qdata.points_possible*this.answeights[i])/1000;
      }
      return out;
    },
    initScores() {
      var out = [];
      let partscore;
      for (let i=0; i<this.answeights.length; i++) {
        // handle the case of a single override
        if (this.qdata.scoreoverride && typeof this.qdata.scoreoverride !== 'object') {
          let partscore = this.qdata.scoreoverride * this.answeights[i] * this.qdata.points_possible;
          partscore = Math.round(1000*partscore)/1000;
          out.push(partscore);
        } else if (this.qdata.scoreoverride) {
          out.push(this.qdata.scoreoverride[i] * this.qdata.parts[i].points_possible);
        } else {
          out.push(this.qdata.parts[i].score);
        }
      }
      return out;
    },
    fullCreditLabel() {
      if (this.answeights.length > 1) {
        return this.$t('gradebook.full_credit_parts');
      } else {
        return this.$t('gradebook.full_credit');
      }
    },
    timeSpent() {
      let out = this.$tc('minutes', Math.round(10*this.qdata.timeactive.total/60)/10);
      // TODO: Add per-try average?
      return out;
    },
    useEditor() {
      return (typeof window.tinyMCE !== 'undefined');
    },
    questionEditUrl() {
      let qs = 'id=' + this.qdata.qsetid + '&cid=' + store.cid;
      qs += '&aid=' + store.aid + '&qid=' + this.qdata.qid;
      console.log(store.APIbase);
      return store.APIbase + '../course/moddataset.php?' + qs;
    },
    questionErrorUrl() {
      if (store.assessInfo.qerror_cid) {
        let quoteq = '0-' + this.qdata.qsetid + '-' + this.qdata.seed +
          '-reperr-' + store.assessInfo.ver;
        let qs = 'add=new&cid=' + store.assessInfo.qerror_cid +
          '&quoteq=' + quoteq
          '&to=' + this.qdata.qowner + '&title=Problem%20with%20question%20id%20'
          + this.qdata.qsetid;
        return store.APIbase + '../msgs/msglist.php?' + qs;
      } else {
        return '';
      }
    },
    useInMsg() {
      // TODO
      let quoteq = this.qn + '-' + this.qdata.qsetid + '-' + this.qdata.seed +
        '-' + store.aid + '-' + store.assessInfo.ver;
      let qs = 'add=new&cid=' + store.assessInfo.qerror_cid +
        '&quoteq=' + quoteq + '&to=' + store.uid;
      return store.APIbase + '../msgs/msglist.php?'+qs;
      //TODO: get GB to work for this.
      //window.GB_show(this.$t('gradebook.send_msg'),
      //  store.APIbase + '../msgs/msglist.php?'+qs, 800, 'auto');
    },
  },
  methods: {
    updateScore(pn, evt) {
      actions.setScoreOverride(this.qn, pn, this.curScores[pn]);
    },
    updateFeedback(evt) {
      let content;
      if (this.useEditor) {
        content = window.tinymce.activeEditor.getContent();
      } else {
        content = evt.target.value;
      }
      actions.setFeedback(this.qn, content);
    },
    allFull() {
      for (let i=0; i<this.answeights.length; i++) {
        this.$set(this.curScores, i, this.partPoss[i]);
        actions.setScoreOverride(this.qn, i, this.curScores[i]);
      }
    },
    clearWork() {
      store.clearAttempts.type = 'qver';
      store.clearAttempts.qn = this.qn;
      store.clearAttempts.show = true;
    },
    initCurScores () {
      this.$set(this, 'curScores', this.initScores);
      this.showfeedback = (this.qdata.feedback !== null && this.qdata.feedback.length > 0);
      if (this.useEditor) {
        window.initeditor("divs","fbbox",null,true);
      }
    },
    qHelps () {
      if (this.qdata.jsparams) {
        let helps = this.qdata.jsparams.helps;
        for (let i in helps) {
          if (helps[i].label == 'video') {
            helps[i].icon = 'video';
            helps[i].title = this.$t('helps.video');
          } else if (helps[i].label == 'read') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.read');
          } else if (helps[i].label == 'ex') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.written_Example');
          } else {
            helps[i].icon = 'file';
            helps[i].title = helps[i].label;
          }
        }
        return helps;
      } else {
        return [];
      }
    }
  },
  mounted () {
    this.initCurScores();
  },
  watch: {
    qdata: function (newVal, oldVal) {
      this.initCurScores();
    }
  }
}
</script>

<style>
.scoredetails {
  border: 1px solid #ccc;
  padding: 10px;
  margin-bottom:16px;
}
</style>
