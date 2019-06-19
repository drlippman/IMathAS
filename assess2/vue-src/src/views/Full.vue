<template>
  <div class="home">
    <assess-header></assess-header>
    <div class="scrollpane">
      <div
        class = "questionpane"
        v-if = "intro != ''"
        v-html = "intro"
      />

      <div
        v-for="curqn in questionArray" :key="curqn"
      >
        <inter-question-text-list
          pos="beforeexact"
          :qn="curqn"
          :key="'iqt'+curqn"
        />
        <full-question-header :qn = "curqn" />
        <question
          class="questionpane"
          :qn="curqn"
          active="true"
          :key="'q'+curqn"
        />
      </div>
      <inter-question-text-list
        class = "questionpane"
        pos="after"
        :qn="lastQ"
      />
    </div>
    <p v-if = "showSubmit">
      <button
        type = "button"
        class = "primary"
        @click = "submitAssess"
      >
        {{ $t('header.assess_submit') }}
      </button>
    </p>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'Full',
  components: {
    Question,
    AssessHeader,
    FullQuestionHeader,
    InterQuestionTextList
  },
  computed: {
    intro () {
      return store.assessInfo.intro;
    },
    questionArray () {
      let qnArray = {};
      for (let i = 0; i < store.assessInfo.questions.length; i++) {
        qnArray[i] = i;
      }
      return qnArray;
    },
    lastQ () {
      return store.assessInfo.questions.length - 1;
    },
    showSubmit () {
      return (store.assessInfo.submitby === 'by_assessment');
    }
  },
  methods: {
    submitAssess () {
      actions.submitAssessment();
    }
  }
};
</script>

<style>

</style>
