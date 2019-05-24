<template>
  <div id="app">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>
    <div v-else>
      <h1>{{ $t('gradebook.detail_title')}}</h1>
      <h2>{{ aData.userfullname }}</h2>
      <h3>{{ aData.name }}</h3>

      <p>
        {{ $t('gradebook.started') }}: {{ startedString }}<br/>
        {{ $t('gradebook.lastchange') }}: {{ lastchangeString }}<br/>
        {{ $t('gradebook.time_onscreen') }}: {{ totalTimeOnTask }}
      </p>

      <p>
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
      </p>

      <p>
        Override score on assessment
      </p>

      <p>
        Clear all attempts | View as student | Print version
      </p>

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

      <p>
        Show/Hide controls
      </p>

      <div class="scrollpane">
        <div
          v-for = "(qdata,qn) in curQuestions"
          :key = "qn"
          class = "med-pad-below"
        >
          <div>
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
              :qdata = "qdata[curQver[qn]]"
              :qn = "qn"
            />
            <div>
              Scoreboxes will go here
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import GbQuestion from '@/gbviewassess/GbQuestion.vue';
import GbAssessSelect from '@/gbviewassess/GbAssessSelect.vue';
import GbQuestionSelect from '@/gbviewassess/GbQuestionSelect.vue';
//import ErrorDialog from '@/components/ErrorDialog.vue';

export default {
  components: {
    GbQuestion,
    GbAssessSelect,
    GbQuestionSelect
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    aData () {
      return store.assessInfo;
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
    }
  },
  created () {
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
</style>
