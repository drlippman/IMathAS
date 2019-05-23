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

      <p>
        Version selector here
      </p>

      <p>
        Show/Hide controls
      </p>

      <div class="scrollpane">
        <div
          v-for = "(qdata,qn) in curQuestions"
          :key = "qn"
          class = "questionpane"
        >
          <div>
            {{ $tc('question_n', qn+1) }}.  Version selector here
          </div>
          <div
            v-html="qdata[curQver[qn]].html"
            class = "question"
            :id="'questionwrap' + qn"
          />
          <div>
            Scoreboxes will go here
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
//import ErrorDialog from '@/components/ErrorDialog.vue';

export default {
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
      return this.$tc('gradebook.minutes', Math.round(this.aData.timeontask/60));
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
</style>
