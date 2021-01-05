<template>
  <div class="home">
    <assess-header></assess-header>
    <livepoll-nav
      v-if = "isTeacher"
      :qn="curqn"
      @selectq="selectQuestion"
      @openq="openInput"
      @closeq="closeInput"
      @newversion="newVersion"
    />
    <div
      class = "subheader"
      v-if = "isTeacher && curstate > 0 && curqn > -1"
    >
      <div id="livepoll_qsettings" style="flex-grow:1">
        <label>
          <input type="checkbox" v-model="showQuestion" />
          {{ $t('livepoll.show_question') }}
        </label>
        <label>
          <input type="checkbox" v-model="showResults" />
          {{ $t('livepoll.show_results') }}
        </label>
        <label>
          <input type="checkbox" v-model="showAnswers" @change="updateShowAnswers"/>
          {{ showAnswersLabel }}
        </label>
      </div>
      <timer
        v-if = "timelimit > 0 && starttime > 0"
        :end = "1000*(starttime + timelimit)"
        :total = "timelimit"
      />
    </div>
    <div
      v-if = "!isTeacher && curstate>0"
    >
      <h2>
        {{ $t('question_n', { n: curqn+1 }) }}
      </h2>
    </div>
    <div class="scrollpane" :aria-label="$t('regions.questions')">
      <livepoll-settings
        class = "questionpane"
        v-if = "isTeacher && (curstate === 0 || curqn === -1)"
      />
      <div
        class = "questionpane"
        v-if = "!isTeacher && curstate < 2"
      >
        {{ $t('livepoll.waiting') }}
      </div>
      <question
        v-if = "curqn >= 0 && ((isTeacher && curstate>0) || (!isTeacher && curstate>1))"
        v-show = "showQuestion"
        :qn = "curqn"
        :active = "true"
        :state = "curstate"
        :seed="curseed"
      />

      <livepoll-results
        v-if = "isTeacher"
        :showresults = "showResults"
        :showans = "curstate === 4"
        :qn = "curqn"
        :key = "curqn + '-' + curseed"
      />

    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import LivepollNav from '@/components/LivepollNav.vue';
import LivepollSettings from '@/components/LivepollSettings.vue';
import LivepollResults from '@/components/LivepollResults.vue';
import Question from '@/components/question/Question.vue';
import Timer from '@/components/Timer.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'livepoll',
  components: {
    LivepollNav,
    Question,
    LivepollSettings,
    LivepollResults,
    AssessHeader,
    Timer
  },
  data: function () {
    return {
      showQuestion: true,
      showResults: true,
      showAnswers: true,
      onSettings: false,
      livepollTimer: null,
      socket: null
    };
  },
  computed: {
    isTeacher () {
      return store.assessInfo.is_teacher;
    },
    curqn () {
      // In liveoll, .curquestion is display qn; 0 is settings
      if (this.onSettings) {
        return -1;
      } else {
        return parseInt(store.assessInfo.livepoll_status.curquestion) - 1;
      }
    },
    curseed () {
      return store.assessInfo.livepoll_status.seed;
    },
    curstate () {
      return store.assessInfo.livepoll_status.curstate;
    },
    starttime () {
      return store.assessInfo.livepoll_status.startt;
    },
    timelimit () {
      if (store.livepollSettings.useTimer) {
        return parseInt(store.livepollSettings.questionTimelimit);
      } else {
        return 0;
      }
    },
    showAnswersLabel () {
      if (this.curstate < 3) {
        return this.$t('livepoll.show_answers_after');
      } else {
        return this.$t('livepoll.show_answers');
      }
    }
  },
  methods: {
    updateUsercount (data) {
      // update store.livepollStuCnt
      // receive usercount data
      store.livepollStuCnt = data.cnt;
      if (data.teachcnt === 0) {
        store.assessInfo.livepoll_status.curstate = 0;
      }
    },
    addResult (data) {
      // add question result data
      if (!store.livepollResults.hasOwnProperty(this.curqn)) {
        this.$set(store.livepollResults, this.curqn, {});
      }
      data.score = JSON.parse(data.score);
      data.ans = JSON.parse(data.ans);
      this.$set(store.livepollResults[this.curqn], data.user, data);
    },
    showHandler (data) {
      if (data.action === 'showq') {
        // On question show, server sends as data:
        //  action: "showq", qn: qn, seed: seed, startt:startt
        actions.clearInitValue(data.qn);
        this.$set(store.assessInfo, 'livepoll_status', {
          curstate: 2,
          curquestion: parseInt(data.qn) + 1,
          seed: parseInt(data.seed),
          startt: parseInt(data.startt)
        });
      } else {
        // On question stop, server sends as data:
        //  action: newstate, qn: qn
        this.$set(store.assessInfo, 'livepoll_status',
          Object.assign(store.assessInfo.livepoll_status, {
            curquestion: parseInt(data.qn) + 1,
            curstate: parseInt(data.action)
          }));
      }
    },
    selectQuestion (dispqn) {
      // called by teacher when they select a question
      // If 0, show the settings
      clearTimeout(this.livepollTimer);
      const qn = parseInt(dispqn) - 1;
      if (qn === -1) {
        this.onSettings = true;
        return;
      } else {
        this.onSettings = false;
      }
      if (qn !== this.curqn) {
        // replace settings with defaults
        this.showQuestion = store.livepollSettings.showQuestionDefault;
        this.showResults = store.livepollSettings.showResultsLiveDefault;
        this.showAnswers = store.livepollSettings.showAnswersAfter;
        let nextState = 1;
        if (store.livepollResults[qn] &&
          Object.keys(store.livepollResults[qn]).length > 0
        ) {
          nextState = this.showAnswers ? 4 : 3;
        }
        if (qn >= 0) {
          actions.setLivepollStatus({
            newquestion: dispqn,
            newstate: nextState
          });
        }
      }
    },
    openInput () {
      actions.setLivepollStatus({
        newquestion: this.curqn + 1,
        newstate: 2
      });
      if (this.timelimit > 0) {
        this.livepollTimer = window.setTimeout(
          () => this.closeInput(),
          1000 * this.timelimit);
      }
    },
    closeInput () {
      clearTimeout(this.livepollTimer);
      const nextState = this.showAnswers ? 4 : 3;
      if (store.livepollSettings.showResultsAfter) {
        this.showResults = true;
      }
      actions.setLivepollStatus({
        newquestion: this.curqn + 1,
        newstate: nextState
      });
    },
    newVersion () {
      actions.setLivepollStatus({
        newquestion: this.curqn + 1,
        newstate: 1,
        forceregen: 1
      });
      this.$set(store.livepollResults, this.curqn, {});
    },
    updateShowAnswers () {
      // if already showing results, need to call the server with new state
      if (this.curstate > 2) {
        const nextState = this.showAnswers ? 4 : 3;
        actions.setLivepollStatus({
          newquestion: this.curqn + 1,
          newstate: nextState
        });
      }
    }
  },
  mounted () {
    // connect to livepoll server
    const server = store.assessInfo.livepoll_server;
    const LPdata = store.assessInfo.livepoll_data;

    let querystr = 'room=' + LPdata.room + '&now=' + LPdata.now;
    if (LPdata.sig) {
      querystr += '&sig=' + encodeURIComponent(LPdata.sig);
    }
    this.socket = window.io('https://' + server + ':3000', { query: querystr });
    this.socket.off();
    this.socket.on('livepoll usercount', (data) => this.updateUsercount(data));
    if (store.assessInfo.is_teacher) {
      this.socket.on('livepoll qans', (data) => this.addResult(data));
    } else {
      this.socket.on('livepoll show', (data) => this.showHandler(data));
    }
  },
  created () {
    if (store.assessInfo.livepoll_status.curquestion === 0 && this.isTeacher) {
      this.onSettings = true;
    }
  }
};
</script>

<style>

#livepoll_qsettings > label {
  margin-right: 8px;
}
</style>
