<template>
  <div class="home">
    <div class="assess-header headerpane">
      <div style="flex-grow: 1">
        <h1>{{ $t('work.add') }}: {{ ainfo.name }}</h1>
      </div>
      <div>
        <button @click = "save" class="primary">
          {{ saveLabel }}
        </button>
      </div>
    </div>
    <div v-if="readyToShow">
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
          @workchanged = "workChanged(curqn, ...arguments)"
        />
        <div v-else>
          <showwork-input
            :id="'sw' + curqn"
            :value = "question.work"
            rows = "3"
            @input = "workChanged(curqn, ...arguments)"
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

export default {
  name: 'Summary',
  components: {
    Question,
    FullQuestionHeader,
    ShowworkInput
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
