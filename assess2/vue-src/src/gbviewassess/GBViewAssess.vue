<template>
  <div id="app">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>
    <div v-else class="gbmainview">
      <h1>{{ $t('gradebook.detail_title')}}</h1>
      <h2>{{ aData.userfullname }}</h2>
      <h3>{{ aData.name }}</h3>

      <div>
        {{ $t('gradebook.started') }}: {{ startedString }}<br/>
        {{ $t('gradebook.lastchange') }}: {{ lastchangeString }}<br/>
        {{ $t('gradebook.time_onscreen') }}: {{ totalTimeOnTask }}
      </div>

      <div>
        {{ $t('gradebook.due')}}:
          {{ $d(new Date(aData.enddate * 1000), 'long') }}
          <button>
            {{ $t('gradebook.make_exception') }}
          </button>
        <span v-if="aData.hasOwnProperty('original_enddate')">
          <br/>
          {{ $t('gradebook.originally_due') }}:
            {{ $d(new Date(aData.original_enddate * 1000), 'long') }}.
          {{ extensionString }}
        </span>
      </div>

      <div>
        <strong>
          {{ $t('gradebook.gb_score') }}:
          {{ aData.gbscore }}/{{ aData.points_possible }}
        </strong>
        <span v-if="aData.scoreoverride">
          {{ $t('gradebook.overridden') }}
        </span>
        <button v-if="canEdit"
          class = "slim"
          type="button"
          @click="showOverride = !showOverride"
        >
          {{ $t('gradebook.override') }}
        </button>
        <span v-if="showOverride">
          <label for="assessoverride">{{ $t('gradebook.override') }}</label>:
          <input id="assessoverride" size=4 v-model="assessOverride" />
        </span>
      </div>

      <div v-if="canEdit">
        Clear all attempts |
        <a :href="viewAsStuUrl" target="_blank">
          {{ $t('gradebook.view_as_stu') }}
        </a> |
        <a :href="viewAsStuUrl + '#/print'" target="_blank">
          {{ $t('gradebook.print') }}
        </a>
      </div>

      <div>
        {{ scoreCalc }}
        <gb-assess-select
          :versions = "aData.assess_versions"
          :submitby = "aData.submitby"
          :haspractice = "aData.has_practice"
          :selected = "curAver"
          @setversion = "changeAssessVersion"
        />
      </div>

      <div v-if="canEdit">
        <button
          type="button"
          @click = "hidePerfect = !hidePerfect"
        >
          {{ hidePerfectLabel }}
        </button>
        <button
          type="button"
          @click = "hideCorrect = !hideCorrect"
        >
          {{ hideCorrectLabel }}
        </button>
        <button
          type="button"
          @click = "hideUnanswered = !hideUnanswered"
        >
          {{ hideUnansweredLabel }}
        </button>

      </div>

      <div class="scrollpane">
        <div
          v-for = "(qdata,qn) in curQuestions"
          :key = "qn"
          class = "med-pad-below"
        >
          <div class="headerpane">
            <strong>
              {{ $tc('question_n', qn+1) }}.
            </strong>
            <gb-question-select
              v-if = "aData.submitby === 'by_question'"
              :versions="qdata"
              :selected="curQver[qn]"
              :qn="qn"
              @setversion = "changeQuestionVersion"
            />
          </div>
          <div class = "questionpane">
            <gb-question
              v-show = "showQuestion[qn]"
              :qdata = "qdata[curQver[qn]]"
              :qn = "qn"
            />
          </div>
          <gb-score-details
            :showfull = "showQuestion[qn]"
            :canedit = "canEdit"
            :qdata = "qdata[curQver[qn]]"
            :qn = "qn"
            @updatescore = "updateScore"
            @updatefeedback = "updateFeedback"
          />
        </div>
      </div>
      <div>
        {{ $t('gradebook.general_feedback') }}:
        <textarea
          v-if="canEdit && !useEditor"
          class="fbbox"
          rows="2"
          cols="60"
          ref="genfb"
        >{{  }}</textarea>
        <div
          v-else-if="canEdit"
          rows="2"
          class="fbbox"
          ref="genfb"
          v-html=""
        />
        <div
          v-else
          v-html="qdata-feedback"
        />
      </div>
      <div>
        <button
          v-if = "canEdit"
          type = "button"
          class = "primary"
          @click = "submitChanges"
        >
          {{ $t('gradebook.save') }}
        </button>
        <button
          type = "button"
          class = "secondary"
          @click = "exit"
        >
          {{ $t('gradebook.return') }}
        </button>
      </div>
      <div style="margin-bottom:100px"></div>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbQuestion from '@/gbviewassess/GbQuestion.vue';
import GbAssessSelect from '@/gbviewassess/GbAssessSelect.vue';
import GbQuestionSelect from '@/gbviewassess/GbQuestionSelect.vue';
import GbScoreDetails from '@/gbviewassess/GbScoreDetails.vue';
//import ErrorDialog from '@/components/ErrorDialog.vue';

export default {
  components: {
    GbQuestion,
    GbAssessSelect,
    GbQuestionSelect,
    GbScoreDetails
  },
  data: function () {
    return {
      showOverride: false,
      assessOverride: '',
      hidePerfect: false,
      hideCorrect: false,
      hideUnanswered: false,
    }
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    aData () {
      return store.assessInfo;
    },
    canEdit() {
      return store.assessInfo['can_edit_scores'];
    },
    useEditor() {
      return (typeof window.tinyMCE !== 'undefined');
    },
    startedString() {
      if (this.aData.starttime === 0) {
        return this.$t('gradebook.not_started');
      } else {
        return this.$d(new Date(this.aData.starttime * 1000), 'long');
      }
    },
    lastchangeString() {
      if (this.aData.lastchange === 0) {
        return this.$t('gradebook.not_submitted');
      } else {
        return this.$d(new Date(this.aData.lastchange * 1000), 'long');
      }
    },
    totalTimeOnTask () {
      return this.$tc('minutes', Math.round(this.aData.timeontask/60));
    },
    extensionString () {
      if (this.aData.extended_with.type === 'latepass') {
        return this.$tc('setlist.latepass_used', this.aData.extended_with.n);
      } else {
        return this.$t('setlist.extension');
      }
    },
    curQuestions () {
      return this.aData.assess_versions[store.curAver].questions;
    },
    curAver () {
      return store.curAver;
    },
    curQver () {
      return store.curQver;
    },
    scoreCalc() {
      if (this.aData.submitby === 'by_question') {
        return this.$t('gradebook.best_on_question');
      } else if (this.aData.keepscore === 'best') {
        return this.$t('gradebook.keep_best') +
          ' (' + this.$tc('gradebook.attempt_n', this.aData.scored_version + 1) + ')';
      } else if (this.aData.keepscore === 'average') {
        return this.$t('gradebook.keep_avg');
      } else if (this.aData.keepscore === 'last') {
        return this.$t('gradebook.keep_last');
      }
    },
    viewAsStuUrl() {
      return 'index.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
    },
    showQuestion() {
      //1 to hide perfect, 2 correct, 4 unanswered
      let out = {};
      for (let i=0; i < this.curQuestions.length; i++) {
        let qdata = this.curQuestions[i][this.curQver[i]];
        let showit = true;
        if (this.hidePerfect && Math.abs(qdata.score - qdata.points_possible) < .002) {
          showit = false;
        } else if (this.hideCorrect && Math.abs(qdata.rawscore - 1) < .002) {
          showit = false;
        } else if (this.hideUnanswered && qdata.try === 0) {
          showit = false;
        }
        out[i] = showit;
      }
      return out;
    },
    hidePerfectLabel() {
      return this.hidePerfect ?
        this.$t('gradebook.show_perfect') :
        this.$t('gradebook.hide_perfect');
    },
    hideCorrectLabel() {
      return this.hideCorrect ?
        this.$t('gradebook.show_correct') :
        this.$t('gradebook.hide_correct');
    },
    hideUnansweredLabel() {
      return this.hideUnanswered ?
        this.$t('gradebook.show_unans') :
        this.$t('gradebook.hide_unans');
    }
  },
  methods: {
    changeAssessVersion(val) {
      if (val !== store.curAver) {
        if (val === this.aData.assess_versions.length) {
          // requesting the practice version
          actions.loadGbAssessVersion(0, true);
        } else {
          actions.loadGbAssessVersion(val, false);
        }
      }
    },
    changeQuestionVersion(qn,val) {
      if (val !== store.curQver[qn]) {
        actions.loadGbQuestionVersion(qn, val);
      }
    },
    updateScore(qn, pn, score) {
      //console.log("update for "+qn+" part "+pn+": "+score);
    },
    updateFeedback(qn, feedback) {

    },
    submitChanges() {

    },
    exit() {

    }
  },
  created () {
    // TODO: Also need to run this on updated?
    if (typeof window.APIbase !== 'undefined') {
      store.APIbase = window.APIbase;
    } else {
      store.APIbase = process.env.BASE_URL;
    }
    // if no assessinfo, or if cid/aid has changed, load data
    let querycid = window.location.search.replace(/^.*cid=(\d+).*$/, '$1');
    let queryaid = window.location.search.replace(/^.*aid=(\d+).*$/, '$1');
    let queryuid = window.location.search.replace(/^.*uid=(\d+).*$/, '$1');
    if (store.assessInfo === null ||
      store.cid !== querycid ||
      store.aid !== queryaid ||
      store.uid !== queryuid
    ) {
      store.cid = querycid;
      window.cid = querycid;  // some other functions need this in global scope
      store.aid = queryaid;
      store.uid = queryuid;
      store.queryString = '?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
      actions.loadGbAssessData();
    }
  }
};
</script>

<style>
.med-pad-below {
  padding-bottom: 16px;
}
.gbmainview > div {
  margin-bottom: 16px;
}
</style>
