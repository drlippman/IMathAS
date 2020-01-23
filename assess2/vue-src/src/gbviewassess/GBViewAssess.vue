<template>
  <div id="app" role="main">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>
    <div v-else class="gbmainview">
      <h1>{{ $t('gradebook.detail_title')}}</h1>
      <h2>{{ aData.userfullname }}</h2>
      <h3>{{ aData.name }}</h3>

      <div>
        {{ $t('gradebook.started') }}: {{ startedString }}<br/>
        {{ $t('gradebook.lastchange') }}: {{ lastchangeString }}
        <span v-if="aData.timeontask > 0">
          <br/>
          {{ $t('gradebook.time_onscreen') }}: {{ totalTimeOnTask }}
        </span>
      </div>

      <div>
        {{ $t('gradebook.due')}}: {{ aData.enddate_disp }}
          <button
            v-if = "canEdit"
            type="button"
            class="slim"
            @click = "makeException"
          >
            {{ exceptionActionLabel }}
          </button>
        <span v-if="aData.hasOwnProperty('original_enddate')">
          <br/>
          {{ $t('gradebook.originally_due') }}:
            {{ aData.original_enddate_disp }}.
          {{ extensionString }}
        </span>
      </div>

      <div v-if="aData.latepass_blocked_by_practice">
        {{ $t('gradebook.latepass_blocked_practice') }}
        <button
          type="button"
          @click="clearLPblock"
        >
          {{ $t('gradebook.clear_latepass_block' )}}
        </button>
      </div>
      <div>
        <strong>
          {{ $t('gradebook.gb_score') }}:
          <span v-if="aData.hasOwnProperty('scoreoverride') && canEdit">
            <input id="assessoverride" size=4
              :value = "aData.scoreoverride"
              @input = "setScoreOverride"
            />/{{ aData.points_possible }}
          </span>
          <span v-else>
            <span v-if="!canEdit && aData.gbscore === 'N/A'">
              {{ $t('gradebook.avail_' + aData.scoresingb) }}
            </span>
            <span v-else>
              {{ aData.gbscore }}/{{ aData.points_possible }}
            </span>
          </span>
        </strong>
        <span v-if="aData.hasOwnProperty('scoreoverride')">
          ({{ $t('gradebook.overridden') }})
        </span>
        <span v-else-if="canEdit">
          <button
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
        </span>
        <button
          v-if="canEdit"
          type="button"
          class="slim"
          @click="clearAttempts('all')"
        >
          {{ $t('gradebook.clear_all') }}
        </button>
      </div>

      <div v-if="canEdit && aData.has_active_attempt">
        <a :href="viewAsStuUrl">
          {{ $t('gradebook.view_as_stu') }}
        </a> |
        <a :href="viewAsStuUrl + '#/print'">
          {{ $t('gradebook.print') }}
        </a>
      </div>

      <div v-if="aData.assess_versions.length == 0">
        {{ $t('gradebook.no_versions') }}
      </div>
      <div v-else class="gbmainview">
        <div>
          {{ scoreCalc }}
          <gb-assess-select
            :versions = "aData.assess_versions"
            :submitby = "aData.submitby"
            :haspractice = "aData.has_practice"
            :selected = "curAver"
            @setversion = "changeAssessVersion"
          />
          <div v-if="isUnsubmitted">
            {{ $t('gradebook.unsubmitted') }}.
            <button
              type="button"
              @click="submitVersion"
            >
              {{ $t('closed.submit_now') }}
            </button>
            <button
              v-if="!canEdit && aData.can_use_latepass"
              type = "button"
              @click = "redeemLatePass"
            >
              {{ $t('lti.use_latepass') }}
            </button>
          </div>
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
          <button
            type="button"
            @click = "showAllAns"
          >
            {{ $t('gradebook.show_all_ans') }}
          </button>
          <button
            v-if = "!isByQuestion"
            type="button"
            @click="clearAttempts('attempt')"
          >
            {{ $t('gradebook.clear_attempt') }}
          </button>
        </div>

        <div>
          <div
            v-for = "(qdata,qn) in curQuestions"
            :key = "qn"
            class = "bigquestionwrap"
            :id = "'qwrap' + (qn+1)"
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
                class = "med-left"
              />
              <span v-else-if = "qdata[curQver[qn]].hasOwnProperty('gbscore') && qdata[curQver[qn]].gbscore !== 'N/A'">
                {{ $t('gradebook.score') }}:
                <strong>
                  {{ qdata[curQver[qn]].gbscore }}/{{ qdata[curQver[qn]].points_possible }}
                </strong>
              </span>

            </div>
            <div class="scrollpane">
              <gb-question
                class = "questionpane"
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
            />
          </div>
        </div>
        <gb-feedback
          qn="gen"
          :show="true"
          :canedit = "canEdit"
          :useeditor = "useEditor"
          :value = "assessFeedback"
          @update = "updateFeedback"
        />
        <div>
          <button
            v-if = "canEdit"
            type = "button"
            :disabled = "!canSubmit"
            class = "primary"
            @click = "submitChanges(true)"
          >
            {{ $t('gradebook.save') }}
          </button>
          <span v-if="savedMsg !== ''" class="noticetext">
            {{ savedMsg }}
          </span>
          <button
            type = "button"
            class = "secondary"
            :disabled = "!canSubmit"
            @click = "exit"
          >
            {{ $t('gradebook.return') }}
          </button>
        </div>
        <div class="floatrightbutton">
          <div v-if="savedMsg !== ''" class="noticetext">
            {{ savedMsg }}
          </div>
          <button
            v-if = "canEdit"
            type = "button"
            :disabled = "!canSubmit"
            class = "primary"
            @click = "submitChanges"
          >
            {{ $t('gradebook.save') }}
          </button>
        </div>
        <summary-categories
          v-if = "showCategories"
          :data = "curQuestionVers"
        />
        <gb-clear-attempts />
        <div style="margin-bottom:100px"></div>
      </div>
    </div>
    <error-dialog
      v-if="hasError"
      :errormsg="errorMsg"
      @clearerror="clearError"
    />
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbQuestion from '@/gbviewassess/GbQuestion.vue';
import GbAssessSelect from '@/gbviewassess/GbAssessSelect.vue';
import GbQuestionSelect from '@/gbviewassess/GbQuestionSelect.vue';
import GbScoreDetails from '@/gbviewassess/GbScoreDetails.vue';
import GbClearAttempts from '@/gbviewassess/GbClearAttempts.vue';
import SummaryCategories from '@/components/summary/SummaryCategories.vue';
import ErrorDialog from '@/components/ErrorDialog.vue';
import GbFeedback from '@/gbviewassess/GbFeedback.vue';
import '../assess2.css';

export default {
  components: {
    GbQuestion,
    GbAssessSelect,
    GbQuestionSelect,
    GbScoreDetails,
    GbClearAttempts,
    SummaryCategories,
    ErrorDialog,
    GbFeedback
  },
  data: function () {
    return {
      showOverride: false,
      assessOverride: '',
      hidePerfect: false,
      hideCorrect: false,
      hideUnanswered: false
    };
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    aData () {
      return store.assessInfo;
    },
    canEdit () {
      return store.assessInfo['can_edit_scores'];
    },
    canSubmit () {
      return (!store.inTransit);
    },
    useEditor () {
      return (typeof window.tinyMCE !== 'undefined');
    },
    isByQuestion () {
      return (this.aData.submitby === 'by_question');
    },
    startedString () {
      if (this.aData.starttime === 0) {
        return this.$t('gradebook.not_started');
      } else {
        return this.aData.starttime_disp;
      }
    },
    lastchangeString () {
      if (this.aData.lastchange === 0) {
        return this.$t('gradebook.not_submitted');
      } else {
        return this.aData.lastchange_disp;
      }
    },
    totalTimeOnTask () {
      return Math.round(10 * this.aData.timeontask / 60) / 10 + ' ' + this.$t('gradebook.minutes');
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
    curQuestionVers () {
      let out = [];
      for (let qn = 0; qn < this.curQuestions.length; qn++) {
        out[qn] = this.curQuestions[qn][this.curQver[qn]];
      }
      return out;
    },
    showCategories () {
      let hascat = false;
      for (let i in this.curQuestionVers) {
        if (this.curQuestionVers[i].hasOwnProperty('category') &&
          this.curQuestionVers[i].category !== '' &&
          this.curQuestionVers[i].category !== null
        ) {
          hascat = true;
          break;
        }
      }
      let hasScores = this.curQuestionVers[0].hasOwnProperty('score') &&
        !isNaN(Number(this.curQuestionVers[0].score));
      return hascat && hasScores;
    },
    scoreCalc () {
      if (this.aData.submitby === 'by_question') {
        return this.$t('gradebook.best_on_question');
      } else if (this.aData.keepscore === 'best') {
        let out = this.$t('gradebook.keep_best');
        if (typeof this.aData.gbscore === 'number') {
          out += ' (' + this.$tc('gradebook.attempt_n', this.aData.scored_version + 1) + ')';
        }
        return out;
      } else if (this.aData.keepscore === 'average') {
        return this.$t('gradebook.keep_avg');
      } else if (this.aData.keepscore === 'last') {
        return this.$t('gradebook.keep_last');
      } else {
        return '';
      }
    },
    viewAsStuUrl () {
      return 'index.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
    },
    showQuestion () {
      // 1 to hide perfect, 2 correct, 4 unanswered
      let out = {};
      for (let i = 0; i < this.curQuestions.length; i++) {
        let qdata = this.curQuestions[i][this.curQver[i]];
        let showit = true;
        if (this.hidePerfect && Math.abs(qdata.score - qdata.points_possible) < 0.002) {
          showit = false;
        } else if (this.hideCorrect && Math.abs(qdata.rawscore - 1) < 0.002) {
          showit = false;
        } else if (this.hideUnanswered && qdata.try === 0) {
          showit = false;
        }
        out[i] = showit;
      }
      return out;
    },
    hidePerfectLabel () {
      return this.hidePerfect
        ? this.$t('gradebook.show_perfect')
        : this.$t('gradebook.hide_perfect');
    },
    hideCorrectLabel () {
      return this.hideCorrect
        ? this.$t('gradebook.show_correct')
        : this.$t('gradebook.hide_correct');
    },
    hideUnansweredLabel () {
      return this.hideUnanswered
        ? this.$t('gradebook.show_unans')
        : this.$t('gradebook.hide_unans');
    },
    exceptionActionLabel () {
      if (this.aData.hasexception) {
        return this.$t('gradebook.edit_exception');
      } else {
        return this.$t('gradebook.make_exception');
      }
    },
    assessFeedback () {
      return this.aData.assess_versions[store.curAver].feedback || '';
    },
    savedMsg () {
      if (store.saving === '') {
        return '';
      } else {
        return this.$t('gradebook.' + store.saving);
      }
    },
    isUnsubmitted () {
      return (this.aData.submitby === 'by_assessment' &&
        this.aData.assess_versions[store.curAver].status === 0);
    },
    hasError () {
      return (store.errorMsg !== null);
    },
    errorMsg () {
      return store.errorMsg;
    }
  },
  methods: {
    changeAssessVersion (val) {
      if (Object.keys(store.scoreOverrides).length > 0 ||
        Object.keys(store.feedbacks).length > 0
      ) {
        if (!confirm(this.$t('gradebook.unsaved_warn'))) {
          return;
        }
      }
      if (val !== store.curAver) {
        if (this.aData.assess_versions[val].status === 3) {
          // requesting the practice version
          actions.loadGbAssessVersion(0, true);
        } else {
          actions.loadGbAssessVersion(val, false);
        }
      }
    },
    changeQuestionVersion (qn, val) {
      let hasUnsaved = false;
      let regex = new RegExp('^' + store.curAver + '-' + qn + '-');
      for (let k in store.scoreOverrides) {
        if (regex.test(k)) {
          hasUnsaved = true;
        }
      }
      for (let k in store.feedbacks) {
        if (regex.test(k)) {
          hasUnsaved = true;
        }
      }
      if (hasUnsaved && !confirm(this.$t('gradebook.unsaved_warn'))) {
        return;
      }
      if (val !== store.curQver[qn]) {
        actions.loadGbQuestionVersion(qn, val);
      }
    },
    updateFeedback (val) {
      actions.setFeedback(null, val);
    },
    setScoreOverride (evt) {
      this.assessOverride = evt.target.value.trim();
      store.saving = '';
    },
    submitChanges (exit) {
      if (this.showOverride && this.assessOverride !== '') {
        store.scoreOverrides['gen'] = this.assessOverride;
      } else if (this.aData.hasOwnProperty('scoreoverride') &&
        this.assessOverride !== this.aData.scoreoverride
      ) {
        store.scoreOverrides['gen'] = this.assessOverride;
      } else {
        delete store.scoreOverrides['gen'];
      }
      var doexit = (exit === true);
      actions.saveChanges(doexit);
    },
    exit () {
      window.location = window.exiturl;
    },
    clearAttempts (type) {
      store.clearAttempts.type = type;
      store.clearAttempts.show = true;
    },
    clearLPblock () {
      actions.clearLPblock();
    },
    submitVersion () {
      actions.endAssess();
    },
    redeemLatePass () {
      window.location = this.APIbase + '../course/redeemlatepass.php?cid=' + store.cid + '&aid=' + store.aid;
    },
    makeException () {
      let url = store.APIbase + '../course/exception.php';
      url += '?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
      url += '&from=gb';
      window.location = url;
    },
    showAllAns () {
      window.$("span[id^='ans']").removeClass('hidden').show();
      window.$('.sabtn').replaceWith('<span>Answer: </span>');
      window.$('.keybtn').attr('aria-expanded', 'true');
    },
    beforeUnload (evt) {
      if (Object.keys(store.scoreOverrides).length > 0 ||
        Object.keys(store.feedbacks).length > 0
      ) {
        evt.preventDefault();
        evt.returnValue = 'You have unsaved changes';
        return 'You have unsaved changes';
      }
    },
    clearError () {
      store.errorMsg = null;
    }
  },
  created () {
    window.$(window).on('beforeunload', this.beforeUnload);
    if (typeof window.APIbase !== 'undefined') {
      store.APIbase = window.APIbase;
    } else {
      store.APIbase = process.env.BASE_URL;
    }
    // if no assessinfo, or if cid/aid has changed, load data
    let querycid = window.location.search.replace(/^.*cid=(\d+).*$/, '$1');
    let queryaid = window.location.search.replace(/^.*aid=(\d+).*$/, '$1');
    let queryuid = window.location.search.replace(/^.*uid=(\d+).*$/, '$1');
    let querystu = window.location.search.replace(/^.*stu=(\d+).*$/, '$1');
    if (store.assessInfo === null ||
      store.cid !== querycid ||
      store.aid !== queryaid ||
      store.uid !== queryuid
    ) {
      store.cid = querycid;
      window.cid = querycid; // some other functions need this in global scope
      store.aid = queryaid;
      store.uid = queryuid;
      store.stu = querystu;
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
.floatrightbutton {
  position: fixed;
  right: 10px;
  bottom: 10px;
  text-align: center;
}
.bigquestionwrap {
  border: 1px solid #ccc;
  margin-bottom: 16px;
  border-radius: 4px;
}
.bigquestionwrap .headerpane {
  padding: 8px;
  background-color: #eee;
}
</style>
