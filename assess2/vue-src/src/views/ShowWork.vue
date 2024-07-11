<template>
  <div class="home">
    <div class="assess-header headerpane">
      <div style="flex-grow: 1">
        <h1>{{ $t('work.add') }}: {{ ainfo.name }}</h1>
      </div>
      <timer v-if="ainfo.showwork_cutoff > 0"
        :total="ainfo.showwork_cutoff * 60"
        :end="ainfo.showwork_local_cutoff_expires"
        :grace="0">
      </timer>
      <div>
        <button @click = "save" class="primary">
          {{ saveLabel }}
        </button>
      </div>
    </div>
    <div v-if="readyToShow">
      <p v-if="ainfo.showwork_cutoff > 0">
        {{ $tc('work.duein', {date: ainfo.showwork_cutoff_expires_disp}) }}
      </p>
      <p v-if="questions.length === 0">
        {{ $t('work.noquestions') }}
      </p>
      <div v-for="(question,curqn) in questions" :key="curqn">
        <full-question-header
          :qn = "curqn"
          :showretry="false"
        />
        <question
          v-if = "question.html !== null"
          :qn = "curqn"
          :key="'sq'+curqn"
          :active = "true"
          :disabled = "true"
          :getwork = "2"
          @workchanged = "(val) => workChanged(curqn, val)"
        />
        <div v-else>
          <showwork-input
            :id="'sw' + curqn"
            :value = "question.work"
            rows = "3"
            @input = "(val) => workChanged(curqn, val)"
          />
        </div>
      </div>
      <div>
        <button @click = "save" class="primary">
          {{ saveLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { store, actions } from '../basicstore';
import Question from '@/components/question/Question.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import ShowworkInput from '@/components/ShowworkInput.vue';
import Timer from '@/components/Timer.vue';

export default {
  name: 'Summary',
  components: {
    Question,
    FullQuestionHeader,
    ShowworkInput,
    Timer
  },
  data: function () {
    return {
      loaded: false,
      duringAssess: false,
      work: {}
    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    mode () {
      return store.inAssess ? 'aftertake' : 'gb';
    },
    readyToShow () {
      return ((this.mode === 'gb' && store.assessInfo.hasOwnProperty('questions')) ||
        (this.mode === 'aftertake' && store.assessInfo.hasOwnProperty('score')));
    },
    hasScore () {
      return store.assessInfo.hasOwnProperty('score');
    },
    questions () {
      var out = {};
      for (var qn in store.assessInfo.questions) {
        if (store.assessInfo.questions[qn].showwork & 2) {
          out[qn] = store.assessInfo.questions[qn];
        }
      }
      return out;
    },
    saveLabel () {
      return store.inAssess ? this.$t('work.save_continue') : this.$t('work.save');
    }
  },
  methods: {
    loadScoresIfNeeded () {
      if (this.mode === 'gb' && !this.readyToShow) {
        // for when it's accessed from gradebook
        actions.getQuestions();
      } else if (this.mode === 'aftertake' && !this.readyToShow) {
        // for when it's accessed after by-assess submission
        actions.getScores();
      }
    },
    workChanged (qn, value) {
      store.work[qn] = value;
    },
    save () {
      actions.submitWork();
    }
  },
  created () {
    this.loadScoresIfNeeded();
  },
  updated () {
    this.loadScoresIfNeeded();
  }
};
</script>

<style>
</style>
