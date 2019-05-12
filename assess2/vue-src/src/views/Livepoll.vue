<template>
  <div class="home">
    <assess-header></assess-header>
    <livepoll-nav
      v-if = "isTeacher"
      :qn="curqn"
      @selectq="selectQuestion"
    />
    <div
      v-if = "isTeacher && curstate > 0 && curqn > -1"
    >
      <button
        v-if = "curstate === 2"
        @click = "closeInput"
      >
        {{ $t('livepoll.close_input') }}
      </button>
      <button
        v-else
        @click = "openInput"
      >
        {{ $t('livepoll.open_input') }}
      </button>
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
    <div
      v-if = "!isTeacher && curstate>0"
    >
      <strong>
        {{ $t('question_n', { n: qn+1 }) }}
      </strong>
    </div>
    <div class="scrollpane">
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
        v-if = "(isTeacher && curstate>0) || (!isTeacher && curstate>1)"
        v-show = "showQuestion"
        :qn = "curqn"
        :active = "true"
        :state = "curstate"
      />

      <livepoll-results
        v-if = "isTeacher && showResults && curstate > 2"
      />

    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import LivepollNav from '@/components/LivepollNav.vue';
import LivepollSettings from '@/components/LivepollSettings.vue';
import Question from '@/components/question/Question.vue';
import { store } from '../basicstore';

export default {
  name: 'livepoll',
  components: {
    LivepollNav,
    Question,
    LivepollSettings,
    AssessHeader
  },
  data: function () {
    return {
      showQuestion: true,
      showResults: true,
      showAnswers: true,
      socket: null
    }
  },
  computed: {
    isTeacher () {
      return store.assessInfo.is_teacher;
    },
    curqn () {
      return store.assessInfo.livepoll_status.curquestion;
    },
    curstate () {
      return store.assessInfo.livepoll_status.curstate;
    },
    starttime () {
      return store.assessInfo.livepoll_status.startt;
    },
    showAnswersLabel () {
      if (this.curstate < 3) {
        return this.$t('livepoll.show_answers_on_close');
      } else {
        return this.$t('livepoll.show_answers');
      }
    }
  },
  methods: {
    updateUsercount(data) {
      // update store.livepollStuCnt
      //receive usercount data
      store.livepollStuCnt = data.cnt;
  		if (data.teachcnt==0) {
        //TODO : update if needed
  			showHandler({action: 0, qn: -1});
  		}
    },
    addResult(data) {
      //add question result data
  		if (!store.livepollResults.hasOwnProperty(this.curqn)) {
  			this.$set(store.livepollResults, this.curqn, []);
  		}
      this.$set(store.livepollResults[this.curqn], data.user, data);
      //TODO: update results. Hopefully will happen automatically
    },
    showHandler(data) {

    },
    selectQuestion(dispqn) {
      // called by teacher when they select a question
      // If 0, show the settings
      let qn = parseInt(dispqn)-1;
      if (qn !== this.qn) {
        // replace settings with defaults
        this.showQuestion = store.livepollSettings.showQuestionDefault;
        this.showResults = store.livepollSettings.showResultsLiveDefault;
        this.showAnswers = store.livepollSettings.showAnswersAfter;
        actions.setLivepollStatus({
          newquestion: qn,
          newstate: 1
        });
      }
    },
    openInput() {
      actions.setLivepollStatus({
        newquestion: qn,
        newstate: 2
      });
    },
    closeInput() {
      let nextState = this.showAnswers ? 4 : 3;
      actions.setLivepollStatus({
        newquestion: qn,
        newstate: nextState;
      });
    },
    updateShowAnswers () {
      // if already showing results, need to call the server with new state
      if (this.curstate > 2) {
        let nextState = this.showAnswers ? 4 : 3;
        actions.setLivepollStatus({
          newquestion: qn,
          newstate: nextState;
        });
      }
    }
  },
  mounted () {
    // connect to livepoll server
    let server = store.assessInfo.livepoll_server;
    let LPdata = store.assessInfo.livepoll_data;

    let querystr = 'room='+LPdata.room+'&now='+LPdata.now;
    if (LPdata.sig) {
      querystr += '&sig='+encodeURIComponent(LPdata.sig);
    }
		this.socket = io('https://'+server+':3000', {query: querystr});
		this.socket.on('livepoll usercount', (data) => this.updateUsercount(data));

		if (store.assessInfo.is_teacher) {
			this.socket.on('livepoll qans', (data) => this.addResult(data));
		} else {
			this.socket.on('livepoll show', (data) => this.showHandler(data));
		}
  }
};
</script>

<style>
.inactive {
  visibility: hidden;
  position: absolute;
}
.questionpane {
  margin: 15px 15px;
  max-width: 700px;
  overflow: visible;
}
.scrollpane {
  width: 100%;
  overflow-x: auto;
}
</style>
