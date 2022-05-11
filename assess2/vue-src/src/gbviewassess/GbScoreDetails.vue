<template>
  <div class="scoredetails">
    <menu-button
      v-if="canedit"
      class = "floatright"
      :options = "moreOptions"
      position = "right"
      nobutton = "true"
      noarrow = "true"
      :id = "'qmore' + qn"
    >
      <template v-slot:button>
        <icons name="more" size="medium"/>
      </template>
    </menu-button>
    <div v-if="canedit || (qdata.hasOwnProperty('score') && qdata.score !== 'N/A')">
      {{ $t('gradebook.score') }}:
      <span
        v-for="(poss,i) in partPoss"
        :key="i"
      >
        <input
          v-if="canedit && !isPractice"
          type="text"
          size="4"
          :id="'scorebox' + qn + (partPoss.length > 1 ? '-' + i : '')"
          pattern="N\/A|\d*\.?\d*"
          v-model="curScores[i]"
          @input="updateScore(i, $event)"
          @keyup.enter="$emit('submitform')"
        /><span v-else>{{ curScores[i] }}</span>/{{ poss }}
        <button
          v-if="canedit && !isPractice && qdata.rubric > 0"
          class="plain nopad"
          @click="showRubric(i)"
        >
          <icons name="clipboard" alt="icons.rubric" size="small" />
        </button>
        <button
          v-if="canedit && !isPractice && qdata.rubric > 0"
          style="display:none"
          class="plain nopad rubriclink"
          @click="showRubric(i)"
          :id="'rublink-scorebox' + qn + (partPoss.length > 1 ? '-' + i : '')"
        >
          <icons name="clipboard" alt="icons.rubric" size="small" />
        </button>
      </span>
      <button
        v-if="canedit && !isPractice"
        type="button"
        @click="allFull"
        class="slim"
      >
        {{ fullCreditLabel }}
      </button>
      <button
        v-if="canedit && hasManual && !isPractice"
        type="button"
        @click="manualFull"
        class="slim"
      >
        {{ $t('gradebook.full_manual_parts') }}
      </button>
      <button
        v-if="canedit && !isPractice && showfeedback === false"
        type="button"
        class="slim"
        @click="revealFeedback"
      >
        {{ $t('gradebook.add_feedback') }}
      </button>
    </div>
    <gb-feedback
      :show="showfeedback"
      :canedit = "canedit"
      :useeditor = "useEditor"
      ref = "fbbox"
      :qn = "qn"
      :value = "qdata.feedback"
      @update = "updateFeedback"
    />

    <div v-if="showfull">
      <span v-if="qdata.timeactive.total > 0">
        {{ $t('gradebook.time_on_version') }}:
        {{ timeSpent }}.
      </span>
      <span v-if="qdata.lastchange">
        {{ $t('gradebook.lastchange') }}
        {{ qdata.lastchange }}.
      </span>
      <button
        v-if="maxTry > 1"
        type="button"
        class="slim"
        @click="showAllTries = !showAllTries"
      >
        {{ $t('gradebook.show_tries') }}
      </button>
      <button
        v-if="hasPenalties"
        type="button"
        class="slim"
        @click="showPenalties = !showPenalties"
      >
        {{ $t('gradebook.show_penalties') }}
      </button>
      <button
        v-if="hasAutoSaves"
        type="button"
        class="slim"
        @click="showAutosaves = !showAutosaves"
      >
        {{ $t('gradebook.show_autosaves') }}
      </button>
    </div>
    <gb-all-tries
      v-if="showAllTries"
      :tries="qdata.other_tries"
      type="tries"
      :qn="qn"
    />
    <gb-penalties
      v-if="showPenalties"
      :parts="qdata.parts"
      :submitby="submitby"
    />
    <gb-all-tries
      v-if="showAutosaves"
      :tries="qdata.autosaves"
      type="autosave"
      :submitby="submitby"
      :qn="qn"
    />
    <div v-if="showfull && qHelps.length > 0">
      {{ $t('gradebook.had_help') }}:
      <a v-for="(help,idx) in qHelps"
        :key="idx"
        :href="help.url"
        target="_blank"
      >{{ help.title }}</a>
    </div>
    <div v-if="qdata.category">
      {{ $t('qdetails.category') }}:
      {{ qdata.category }}
    </div>
    <div>
      <a :href="messageHref" target="help" v-if="showMessage">
        <icons name="message" />
        {{ $t('helps.message_instructor') }}
      </a>
      <a :href="forumHref" target="help" v-if="postToForum > 0">
        <icons name="forum" />
        {{ $t('helps.post_to_forum') }}
      </a>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbAllTries from '@/gbviewassess/GbAllTries';
import GbPenalties from '@/gbviewassess/GbPenalties';
import Icons from '@/components/widgets/Icons';
import MenuButton from '@/components/widgets/MenuButton';
import GbFeedback from '@/gbviewassess/GbFeedback';

export default {
  name: 'GbScoreDetails',
  props: ['qdata', 'qn', 'canedit', 'showfull'],
  components: {
    GbAllTries,
    GbPenalties,
    MenuButton,
    Icons,
    GbFeedback
  },
  data: function () {
    return {
      curScores: false,
      showfeedback: false,
      showAllTries: false,
      showPenalties: false,
      showAutosaves: false
    };
  },
  computed: {
    answeights () {
      if (!this.qdata.answeights || this.qdata.singlescore) { // if answeights not generated yet
        return [1];
      } else {
        const answeights = this.qdata.answeights.map(x => parseFloat(x));
        const answeightTot = answeights.reduce((a, c) => a + c);
        return answeights.map(x => x / answeightTot);
      }
    },
    partPoss () {
      var out = [];
      for (let i = 0; i < this.answeights.length; i++) {
        out[i] = Math.round(1000 * this.qdata.points_possible * this.answeights[i]) / 1000;
      }
      return out;
    },
    initScores () {
      var out = [];
      for (let i = 0; i < this.answeights.length; i++) {
        if (this.qdata.singlescore) {
          out.push(this.qdata.score);
        } else if (this.qdata.scoreoverride && typeof this.qdata.scoreoverride !== 'object') {
          // handle the case of a single override
          let partscore = this.qdata.scoreoverride * this.answeights[i] * this.qdata.points_possible;
          partscore = Math.round(1000 * partscore) / 1000;
          out.push(partscore);
        } else if (this.qdata.scoreoverride && this.qdata.scoreoverride.hasOwnProperty(i)) {
          if (this.qdata.parts[i] && this.qdata.parts[i].points_possible) {
            out.push(Math.round(1000 * this.qdata.scoreoverride[i] * this.qdata.parts[i].points_possible) / 1000);
          } else {
            out.push(Math.round(1000 * this.qdata.scoreoverride[i] * this.answeights[i] * this.qdata.points_possible) / 1000);
          }
        } else if (this.maxTry === 0 || !this.qdata.parts[i].hasOwnProperty('score')) { // not attempted or not showing
          out.push('N/A');
        } else {
          out.push(this.qdata.parts[i].score);
        }
      }
      return out;
    },
    fullCreditLabel () {
      if (this.answeights.length > 1) {
        return this.$t('gradebook.full_credit_parts');
      } else {
        return this.$t('gradebook.full_credit');
      }
    },
    timeSpent () {
      const out = Math.round(10 * this.qdata.timeactive.total / 60) / 10 + ' ' + this.$t('gradebook.minutes');
      // TODO: Add per-try average?
      return out;
    },
    useEditor () {
      return (typeof window.tinyMCE !== 'undefined');
    },
    isPractice () {
      return store.ispractice;
    },
    isLastVersion () {
      let avercnt = store.assessInfo.assess_versions.length - 1;
      if (store.assessInfo.has_practice) {
        avercnt--;
      }
      return (store.curAver === avercnt);
    },
    maxTry () {
      let maxtry = 0;
      for (let i = 0; i < this.qdata.parts.length; i++) {
        if (this.qdata.parts[i] && this.qdata.parts[i].try) {
          if (this.qdata.parts[i].try > maxtry) {
            maxtry = this.qdata.parts[i].try;
          }
        }
      }
      return maxtry;
    },
    questionEditUrl () {
      let qs = 'id=' + this.qdata.qsetid + '&cid=' + store.cid;
      qs += '&aid=' + store.aid + '&qid=' + this.qdata.qid;
      return store.APIbase + '../course/moddataset.php?' + qs;
    },
    questionErrorUrl () {
      if (store.assessInfo.qerror_cid) {
        const quoteq = '0-' + this.qdata.qsetid + '-' + this.qdata.seed +
          '-reperr-' + store.assessInfo.ver;
        const qs = 'add=new&cid=' + store.assessInfo.qerror_cid +
          '&quoteq=' + quoteq + '&to=' + this.qdata.qowner +
          '&title=Problem%20with%20question%20id%20' +
          this.qdata.qsetid;
        return store.APIbase + '../msgs/msglist.php?' + qs;
      } else {
        return '';
      }
    },
    useInMsg () {
      // TODO
      const quoteq = this.qn + '-' + this.qdata.qsetid + '-' + this.qdata.seed +
        '-' + store.aid + '-' + store.assessInfo.ver;
      const qs = 'add=new&cid=' + store.cid +
        '&quoteq=' + quoteq + '&to=' + store.uid;
      return store.APIbase + '../msgs/msglist.php?' + qs;
      // TODO: get GB to work for this.
      // window.GB_show(this.$t('gradebook.send_msg'),
      //  store.APIbase + '../msgs/msglist.php?'+qs, 800, 'auto');
    },
    moreOptions () {
      const out = [
        {
          label: this.$t('gradebook.use_in_msg'),
          link: this.useInMsg
        },
        {
          label: this.$t('gradebook.view_edit') + ' ID ' + this.qdata.qsetid + ' Seed ' + this.qdata.seed,
          link: this.questionEditUrl
        },
        {
          label: (store.assessInfo.hasOwnProperty('qerrortitle')
            ? store.assessInfo.qerrortitle : this.$t('gradebook.msg_owner')),
          link: this.questionErrorUrl
        }
      ];
      if (!this.isPractice && this.isLastVersion) {
        out.push({
          label: this.$t('gradebook.clear_qwork'),
          onclick: () => this.clearWork()
        });
      }
      return out;
    },
    hasPenalties () {
      for (let pn = 0; pn < this.qdata.parts.length; pn++) {
        if (this.qdata.parts[pn].hasOwnProperty('penalties') &&
          this.qdata.parts[pn].penalties.length > 0
        ) {
          return true;
        }
      }
      return false;
    },
    hasManual () {
      if (this.qdata.parts.length === 1) {
        return false;
      }
      for (let pn = 0; pn < this.qdata.parts.length; pn++) {
        if (this.qdata.parts[pn].hasOwnProperty('req_manual') &&
          this.qdata.parts[pn].req_manual === true
        ) {
          return true;
        }
      }
      return false;
    },
    hasAutoSaves () {
      return this.qdata.hasOwnProperty('autosaves');
    },
    submitby () {
      return store.assessInfo.submitby;
    },
    qHelps () {
      if (this.qdata.jsparams) {
        const helps = this.qdata.jsparams.helps;
        for (const i in helps) {
          if (helps[i].label === 'video') {
            helps[i].icon = 'video';
            helps[i].title = this.$t('helps.video');
          } else if (helps[i].label === 'read') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.read');
          } else if (helps[i].label === 'ex') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.written_example');
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
    showMessage () {
      return (store.assessInfo.hasOwnProperty('help_features') &&
        store.assessInfo.help_features.message === true &&
        !store.assessInfo.can_edit_scores
      );
    },
    postToForum () {
      return (store.assessInfo.hasOwnProperty('help_features') &&
        store.assessInfo.help_features.forum
      );
    },
    quoteQ () {
      const qsid = this.qdata.questionsetid;
      const seed = this.qdata.seed;
      const ver = 2; // TODO: send from backend
      return this.qn + '-' + qsid + '-' + seed + '-' + store.aid + '-' + ver;
    },
    messageHref () {
      let href = window.imasroot + '/msgs/msglist.php?';
      href += window.$.param({
        cid: store.cid,
        add: 'new',
        quoteq: this.quoteQ,
        to: 'instr'
      });
      return href;
    },
    forumHref () {
      let href = window.imasroot + '/forums/thread.php?';
      href += window.$.param({
        cid: store.cid,
        forum: store.assessInfo.help_features.forum,
        modify: 'new',
        quoteq: this.quoteQ
      });
      return href;
    }
  },
  methods: {
    updateScore (pn, evt) {
      if (this.curScores[pn].trim() === '') {
        actions.setScoreOverride(this.qn, pn, '');
      } else {
        const partposs = this.qdata.points_possible * this.answeights[pn];
        actions.setScoreOverride(this.qn, pn, this.curScores[pn] / partposs);
      }
    },
    revealFeedback () {
      this.showfeedback = true;
      this.$nextTick(() => this.$refs.fbbox.focus());
    },
    updateFeedback (val) {
      actions.setFeedback(this.qn, val);
    },
    allFull () {
      for (let i = 0; i < this.answeights.length; i++) {
        this.$set(this.curScores, i, this.partPoss[i]);
        actions.setScoreOverride(this.qn, i, this.curScores[i] / this.partPoss[i]);
      }
    },
    manualFull () {
      for (let i = 0; i < this.answeights.length; i++) {
        if (this.qdata.parts[i] && this.qdata.parts[i].hasOwnProperty('req_manual') &&
          this.qdata.parts[i].req_manual === true
        ) {
          this.$set(this.curScores, i, this.partPoss[i]);
          actions.setScoreOverride(this.qn, i, this.curScores[i] / this.partPoss[i]);
        }
      }
    },
    clearWork () {
      store.clearAttempts.type = 'qver';
      store.clearAttempts.qn = this.qn;
      store.clearAttempts.show = true;
    },
    initCurScores () {
      this.$set(this, 'curScores', this.initScores);
      this.showfeedback = (this.qdata.feedback !== null && this.qdata.feedback.length > 0);
    },
    showRubric (pn) {
      if (!window.imasrubrics) {
        window.imasrubrics = store.assessInfo.rubrics;
      }
      this.showfeedback = true;
      window.imasrubric_show(
        this.qdata.rubric,
        this.partPoss[pn],
        'scorebox' + this.qn + (this.partPoss.length > 1 ? '-' + pn : ''),
        'fb' + this.qn,
        (this.qn + 1) + (this.partPoss.length > 1 ? ' part ' + (pn + 1) : ''),
        600
      );
    }
  },
  mounted () {
    this.initCurScores();
  },
  watch: {
    qdata: {
      handler: function (newVal, oldVal) {
        this.initCurScores();
      },
      deep: true
    }
  }
};
</script>

<style>
.scoredetails {
  border-top: 1px solid #ccc;
  padding: 8px;
}
</style>
