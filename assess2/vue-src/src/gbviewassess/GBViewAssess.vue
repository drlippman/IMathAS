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
          {{ $tc('gradebook.time_onscreen', attemptCount) }}:
          {{ totalTimeOnTask }}
        </span>
        <span v-if="aData.hasOwnProperty('timelimit_ext')">
          <br/>
          {{ $t('gradebook.'+ (aData.timelimit_ext > 0 ? 'has_timeext' : 'used_timeext'),
            {n: Math.abs(aData.timelimit_ext)}) }}
        </span>
      </div>

      <div>
        {{ $t('gradebook.due')}}: {{ aData.enddate_disp }}
          <button
            v-if = "canEdit && aData.can_make_exception"
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
        <span v-if="aData.hasOwnProperty('attemptext')">
          <br/>
          {{ $t('gradebook.attemptext', {n: aData.attemptext}) }}
        </span>
      </div>

      <div v-if="canEdit && aData.latepass_status > 1">
        {{ latepassBlockMsg }}
        <button
          v-if="aData.latepass_status > 6"
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
        <button
          v-if="aData.hasOwnProperty('excused')"
          type="button"
          class="slim"
          @click="showExcused = !showExcused"
        >
          {{ $t('gradebook.' + (showExcused ? 'hide' : 'show') + '_excused') }}
        </button>
      </div>

      <div v-if="showExcused" class="introtext">
        {{ $t('gradebook.excused_list') }}
        <ul>
          <li v-for="name in aData.excused" :key="name">
            {{ name }}
          </li>
        </ul>
      </div>

      <div v-if="canEdit">
        <a v-if="showViewAsStu" :href="viewAsStuUrl">
          {{ $t('gradebook.view_as_stu') }}
        </a>
        <span v-if="showViewAsStu">|</span>
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
          <div>
            <gb-assess-select
              style = "display: inline-block"
              v-if = "viewFull || aData.submitby === 'by_assessment'"
              :versions = "aData.assess_versions"
              :submitby = "aData.submitby"
              :haspractice = "aData.has_practice"
              :selected = "curAver"
              @setversion = "changeAssessVersion"
            />
            <button
              v-if = "!isByQuestion && canEdit && aData.assess_versions[curAver].status != -1"
              type="button"
              @click="clearAttempts('attempt')"
            >
              {{ $t('gradebook.clear_attempt') }}
            </button>
          </div>
          <div v-if="isUnsubmitted">
            {{ $t('gradebook.unsubmitted') }}
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

        <div v-if = "curEndmsg !== ''">
          <button
            v-if = "viewFull"
            type="button"
            @click = "showEndmsg = !showEndmsg"
          >
            {{ $t('gradebook.' + (showEndmsg ? 'hide' : 'show') + '_endmsg') }}
          </button>
          <div
            class="introtext"
            v-if="showEndmsg || !viewFull"
            v-html="curEndmsg"
          />
        </div>

        <div v-if="canEdit && viewFull">
          <button @click = "showFilters = !showFilters">
            {{ $t('gradebook.filters') }}
          </button>
          <div v-if = "showFilters" class="tabpanel">
            <p>{{ $t('gradebook.hide') }}:</p>
            <ul style="list-style-type: none; margin:0; padding-left: 15px;">
              <li>
                <label>
                  <input type=checkbox v-model="hideUnanswered">
                  {{ $t('gradebook.hide_unans') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hideZero">
                  {{ $t('gradebook.hide_zero') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hideNonzero">
                  {{ $t('gradebook.hide_nonzero') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hidePerfect">
                  {{ $t('gradebook.hide_perfect') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hide100">
                  {{ $t('gradebook.hide_100') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hideFeedback">
                  {{ $t('gradebook.hide_fb') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hideNowork">
                  {{ $t('gradebook.hide_nowork') }}
                </label>
              </li>
              <li>
                <label>
                  <input type=checkbox v-model="hidetexts" @change="loadTexts">
                  {{ $t('gradebook.introtexts') }}
                </label>
              </li>
            </ul>
            <p>
              <button
                type="button"
                @click = "showAllAns"
              >
                {{ $t('gradebook.show_all_ans') }}
              </button>
              <button
                type="button"
                @click = "showAllWork = !showAllWork"
              >
                {{ $t('gradebook.show_all_work') }}
              </button>
              <button
                @click = "previewFiles"
              >
                {{ $t('gradebook.preview_files') }}
              </button>
            </p>
          </div>
        </div>

        <div v-if="viewFull">
          <inter-question-text
            v-if = "aData.hasOwnProperty('intro') && aData.intro !== ''"
            v-show = "!hidetexts"
            :active = "!hidetexts"
            :textobj = "{html: aData.intro}"
            class = "questionpane introtext"
          />
          <div
            v-for = "(qdata,qn) in curQuestions"
            :key = "qn"
            :id = "'qwrap' + (qn+1)"
          >
            <inter-question-text-list
              pos="beforeexact"
              :qn="qn"
              :key="'iqt'+qn"
              v-show = "!hidetexts"
              :active = "!hidetexts"
              :lastq = "lastQ"
              :textlist = "textList"
            />
            <div class = "bigquestionwrap">
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
                  :class = "{'inactive':!showQuestion[qn]}"
                  :qdata = "qdata[curQver[qn]]"
                  :qn = "qn"
                />
                <gb-showwork
                  :work = "qdata[curQver[qn]].work"
                  :worktime = "qdata[curQver[qn]].worktime"
                  :showall = "showAllWork"
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
          <inter-question-text-list
            pos="after"
            :qn="lastQ"
            :active = "!hidetexts"
            v-show = "!hidetexts"
            :lastq = "lastQ"
            :textlist = "textList"
          />
        </div>
        <gb-feedback
          qn="gen"
          :username="aData.userfullname"
          :show="viewFull && (canEdit || assessFeedback !== '')"
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
    <confirm-dialog
      v-if="confirmObj !== null"
      :data="confirmObj"
      @close="closeConfirm"
    />
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbQuestion from '@/gbviewassess/GbQuestion.vue';
import GbShowwork from '@/gbviewassess/GbShowwork.vue';
import GbAssessSelect from '@/gbviewassess/GbAssessSelect.vue';
import GbQuestionSelect from '@/gbviewassess/GbQuestionSelect.vue';
import GbScoreDetails from '@/gbviewassess/GbScoreDetails.vue';
import GbClearAttempts from '@/gbviewassess/GbClearAttempts.vue';
import SummaryCategories from '@/components/summary/SummaryCategories.vue';
import ErrorDialog from '@/components/ErrorDialog.vue';
import GbFeedback from '@/gbviewassess/GbFeedback.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import InterQuestionText from '@/components/InterQuestionText.vue';

import '../assess2.css';

export default {
  components: {
    GbQuestion,
    GbShowwork,
    GbAssessSelect,
    GbQuestionSelect,
    GbScoreDetails,
    GbClearAttempts,
    SummaryCategories,
    ErrorDialog,
    GbFeedback,
    ConfirmDialog,
    InterQuestionTextList,
    InterQuestionText
  },
  data: function () {
    return {
      showOverride: false,
      assessOverride: '',
      hide100: false,
      hidePerfect: false,
      hideNonzero: false,
      hideZero: false,
      hideUnanswered: false,
      hideFeedback: false,
      hideNowork: false,
      showFilters: false,
      showEndmsg: false,
      showExcused: false,
      showAllWork: false,
      hidetexts: true
    };
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    aData () {
      return store.assessInfo;
    },
    viewFull () {
      return this.aData.viewfull;
    },
    canEdit () {
      return store.assessInfo.can_edit_scores && this.viewFull;
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
    attemptCount () {
      let cnt = 0;
      for (let i = 0; i < this.aData.assess_versions.length; i++) {
        if (this.aData.assess_versions[i].status < 3) {
          cnt++;
        }
      }
      return cnt;
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
      const out = [];
      for (let qn = 0; qn < this.curQuestions.length; qn++) {
        out[qn] = this.curQuestions[qn][this.curQver[qn]];
      }
      return out;
    },
    curEndmsg () {
      return this.aData.assess_versions[store.curAver].endmsg || '';
    },
    showCategories () {
      let hascat = false;
      for (const i in this.curQuestionVers) {
        if (this.curQuestionVers[i].hasOwnProperty('category') &&
          this.curQuestionVers[i].category !== '' &&
          this.curQuestionVers[i].category !== null
        ) {
          hascat = true;
          break;
        }
      }
      const hasScores = this.curQuestionVers[0].hasOwnProperty('score') &&
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
    showViewAsStu () {
      // show if there's an active attempt, or if there's only an instructor-generated
      // non-started assessment version
      return (this.aData.has_active_attempt ||
        (this.aData.scored_version === 0 && this.aData.assess_versions[0].status === -1)
      );
    },
    viewAsStuUrl () {
      return 'index.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
    },
    showQuestion () {
      const out = {};
      for (let i = 0; i < this.curQuestions.length; i++) {
        const qdata = this.curQuestions[i][this.curQver[i]];
        let showit = true;
        if (this.hide100 && Math.abs(qdata.score - qdata.points_possible) < 0.002) {
          showit = false;
        } else if (this.hidePerfect && Math.abs(qdata.rawscore - 1) < 0.002) {
          showit = false;
        } else if (this.hideUnanswered && qdata.parts.reduce((a, c) => Math.max(a, c.try), 0) === 0) {
          showit = false;
        } else if (this.hideZero && Math.abs(qdata.rawscore) < 0.002) {
          showit = false;
        } else if (this.hideNonzero && Math.abs(qdata.rawscore) > 0.002 && Math.abs(qdata.rawscore) < 0.998) {
          showit = false;
        } else if (this.hideFeedback && qdata.feedback !== null && qdata.feedback !== '') {
          showit = false;
        } else if (this.hideNowork && (!qdata.hasOwnProperty('work') || qdata.work === null || qdata.work === '')) {
          showit = false;
        }
        out[i] = showit;
      }
      return out;
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
    latepassBlockMsg () {
      var m;
      switch (this.aData.latepass_status) {
        case 7: m = 'practice'; break;
        case 8: m = 'gb'; break;
        case 2: m = 'lpcutoff'; break;
        case 3: m = 'courseend'; break;
        case 4: m = 'pastdue'; break;
        case 5: m = 'toolate'; break;
        case 6: m = 'toofew'; break;
      }
      return this.$t('gradebook.latepass_blocked_' + m);
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
    },
    confirmObj () {
      return store.confirmObj;
    },
    lastQ () {
      return this.aData.assess_versions[store.curAver].questions.length - 1;
    },
    textList () {
      if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
        return [];
      } else {
        return store.assessInfo.interquestion_text;
      }
    }
  },
  methods: {
    changeAssessVersion (val) {
      if (val === store.curAver) {
        return; // not a change - abort
      }
      if (Object.keys(store.scoreOverrides).length > 0 ||
        Object.keys(store.feedbacks).length > 0
      ) {
        store.confirmObj = {
          body: 'gradebook.unsaved_warn',
          action: () => this.doChangeAssessVersion(val)
        };
      } else {
        this.doChangeAssessVersion(val);
      }
    },
    doChangeAssessVersion (val) {
      if (val !== store.curAver) {
        this.hidetexts = true;
        if (this.aData.assess_versions[val].status === 3) {
          // requesting the practice version
          actions.loadGbAssessVersion(0, true);
        } else {
          actions.loadGbAssessVersion(val, false);
        }
      }
    },
    changeQuestionVersion (qn, val) {
      if (val === store.curQver[qn]) {
        return; // same value - abort
      }
      let hasUnsaved = false;
      const regex = new RegExp('^' + store.curAver + '-' + qn + '-');
      for (const k in store.scoreOverrides) {
        if (regex.test(k)) {
          hasUnsaved = true;
        }
      }
      for (const k in store.feedbacks) {
        if (regex.test(k)) {
          hasUnsaved = true;
        }
      }
      if (hasUnsaved) {
        store.confirmObj = {
          body: 'gradebook.unsaved_warn',
          action: () => actions.loadGbQuestionVersion(qn, val)
        };
      } else {
        actions.loadGbQuestionVersion(qn, val);
      }
    },
    updateFeedback (val) {
      actions.setFeedback(null, val);
    },
    setScoreOverride (evt) {
      const val = evt.target.value.trim();
      if (val !== this.aData.scoreoverride) {
        store.scoreOverrides.gen = val;
        this.assessOverride = '';
      }
      store.saving = '';
    },
    submitChanges (exit) {
      if (!this.aData.hasOwnProperty('scoreoverride') && this.showOverride) {
        if (this.assessOverride !== '') {
          store.scoreOverrides.gen = this.assessOverride;
        }
        this.showOverride = false;
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
      window.$('span[id^=ans]').toggleClass('hidden', false).show();
      window.$('.sabtn').replaceWith('<span>Answer: </span>');
      window.$('.keybtn').attr('aria-expanded', 'true');
      window.$('div[id^=dsbox]').toggleClass('hidden', false).attr('aria-hidden', false)
        .attr('aria-expanded', true);
      window.$('input[aria-controls^=dsbox]').attr('aria-expanded', true);
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
    },
    closeConfirm () {
      store.confirmObj = null;
    },
    previewFiles () {
      window.previewallfiles();
    },
    loadTexts () {
      if (!store.assessInfo.hasOwnProperty('intro')) {
        actions.loadGbTexts();
      }
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
    const querycid = window.location.search.replace(/^.*cid=(\d+).*$/, '$1');
    const queryaid = window.location.search.replace(/^.*aid=(\d+).*$/, '$1');
    const queryuid = window.location.search.replace(/^.*uid=(\d+).*$/, '$1');
    const querystu = window.location.search.replace(/^.*stu=(\d+).*$/, '$1');
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
