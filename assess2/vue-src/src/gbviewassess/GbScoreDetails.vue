<template>
  <div class="scoredetails">
    <menu-button
      class = "floatright"
      :options = "moreOptions"
      position = "right"
      nobutton = "true"
      noarrow = "true"
    >
      <template v-slot:button>
        <icons name="more" size="medium"/>
      </template>
    </menu-button>
    <div>
      {{ $t('gradebook.score') }}:
      <span
        v-for="(poss,i) in partPoss"
        :key="i"
      >
        <input
          v-if="canedit && !isPractice"
          type="text"
          size="4"
          :id="'sc'+qn+'-'+i"
          v-model="curScores[i]"
          @input="updateScore(i, $event)"
        /><span v-else>{{ curScores[i] }}</span>/{{ poss }}
        <button
          v-if="canedit && !isPractice && qdata.rubric > 0"
          class="plain nopad"
          @click="showRubric(i)"
        >
          <icons name="clipboard" alt="icons.rubric" size="small" />
        </button>
      </span>
      <button
        type="button"
        @click="allFull"
        class="slim"
      >
        {{ fullCreditLabel }}
      </button>
      <button
        v-if="canedit && !isPractice && showfeedback === false"
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
        :name="'fb'+qn"
        rows="2"
        cols="60"
        @input="updateFeedback"
      >{{ qdata.feedback }}</textarea>
      <div
        v-else-if="canedit"
        rows="2"
        :id="'fb'+qn"
        class="fbbox"
        v-html="qdata.feedback"
        @input="updateFeedback"
      />
      <div
        v-else
        v-html="qdata.feedback"
      />
    </div>

    <div v-if="showfull">
      <span v-if="qdata.timeactive.total > 0">
        {{ $t('gradebook.time_on_version') }}:
        {{ timeSpent }}
      </span>
      <button
        v-if="maxTry > 1"
        type="button"
        class="slim"
        @click="showAllTries = !showAllTries"
      >
        {{ $t('gradebook.show_tries') }}
      </button>
    </div>
    <gb-all-tries
      v-if="showAllTries"
      :tries="qdata.other_tries"
    />
    <div v-if="canedit && showfull && qHelps.length > 0">
      {{ $t('gradebook.had_help') }}:
      <a v-for="help in qHelps"
        :href="help.url"
        target="_blank"
      >{{ help.title }}</a>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbAllTries from '@/gbviewassess/GbAllTries';
import Icons from '@/components/widgets/Icons';
import MenuButton from '@/components/widgets/MenuButton';

export default {
  name: 'GbScoreDetails',
  props: ['qdata', 'qn', 'canedit', 'showfull'],
  components: {
    GbAllTries,
    MenuButton,
    Icons
  },
  data: function() {
    return {
      curScores: false,
      showfeedback: false,
      showAllTries: false
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
        if (this.qdata.scoreoverride && typeof this.qdata.scoreoverride !== 'object') {
          // handle the case of a single override
          let partscore = this.qdata.scoreoverride * this.answeights[i] * this.qdata.points_possible;
          partscore = Math.round(1000*partscore)/1000;
          out.push(partscore);
        } else if (this.qdata.scoreoverride) {
          if (this.qdata.parts[i] && this.qdata.parts[i].points_possible) {
            out.push(this.qdata.scoreoverride[i] * this.qdata.parts[i].points_possible);
          } else {
            out.push(Math.round(1000*this.qdata.scoreoverride[i] * this.answeights[i] * this.qdata.points_possible)/1000);
          }
        } else if (this.maxTry === 0) { // not attempted
          out.push('N/A');
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
    isPractice() {
      return store.ispractice;
    },
    maxTry() {
      let maxtry = 0;
      for (let i=0; i<this.qdata.parts.length; i++) {
        if (this.qdata.parts[i] && this.qdata.parts[i].try) {
          if (this.qdata.parts[i].try > maxtry) {
            maxtry = this.qdata.parts[i].try;
          }
        }
      }
      return maxtry;
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
    moreOptions () {
      return [
        {
          label: this.$t('gradebook.use_in_msg'),
          link: this.useInMsg
        },
        {
          label: this.$t('gradebook.view_edit') + ' ID '+this.qdata.qsetid + ' Seed ' + this.qdata.seed,
          link: this.questionEditUrl
        },
        {
          label: this.$t('gradebook.msg_owner'),
          link: this.questionErrorUrl
        },
        {
          label: this.$t('gradebook.clear_qwork'),
          onclick: () => this.clearWork()
        }
      ];
    }
  },
  methods: {
    updateScore(pn, evt) {
      actions.setScoreOverride(this.qn, pn, this.curScores[pn]/this.partPoss[pn]);
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
        actions.setScoreOverride(this.qn, i, this.curScores[i]/this.partPoss[i]);
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
    },
    showRubric(pn) {
      if (!window.imasrubrics) {
        window.imasrubrics = store.assessInfo['rubrics'];
      }
      this.showfeedback = true;
      imasrubric_show(
        this.qdata.rubric,
        this.qdata.points_possible,
        'sc'+this.qn+'-'+pn,
        'fb'+this.qn,
        this.qn,
        600
      );
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
  border-top: 1px solid #ccc;
  padding: 8px;
}
</style>
