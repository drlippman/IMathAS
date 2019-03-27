<template>
  <div class="home">
    <assess-header></assess-header>
    <div class="scrollpane">
      <div
        class = "questionpane"
        v-if = "intro != ''"
        v-html = "intro"
      />
      <div class="questionpane">
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
            class = "med-left"
            :qn="curqn"
            active="true"
            :key="'q'+curqn"
          />
        </div>

        <inter-question-text-list pos="after" :qn="lastQ" />

      </div>
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import { store } from '../basicstore';

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
      return store.assessInfo.questions.length-1;
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
