<template>
  <div class="home">
    <assess-header></assess-header>
    <skip-question-header :qn="qn"/>
    <div class="scrollpane" role="region" :aria-label="$t('regions.questions')">
      <div
        class = "questionpane introtext"
        v-show = "qn == -1"
        key = "-1"
        v-html = "intro"
        ref = "introtext"
      />
      <div
        v-for="curqn in questionArray"
        :key="curqn"
        :class="{inactive: curqn != qn}"
        :aria-hidden = "curqn != qn"
      >
        <inter-question-text-list
          pos = "before"
          :qn = "curqn"
          :active="curqn == qn"
        />
        <question
          :qn="curqn"
          :active="curqn == qn"
        />
        <inter-question-text-list
          pos = "after"
          :qn = "curqn"
          :active="curqn == qn"
        />
      </div>
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import SkipQuestionHeader from '@/components/SkipQuestionHeader.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import Question from '@/components/question/Question.vue';
import { store } from '../basicstore';

export default {
  name: 'skip',
  components: {
    SkipQuestionHeader,
    Question,
    InterQuestionTextList,
    AssessHeader
  },
  computed: {
    qn () {
      return parseInt(this.$route.params.qn) - 1;
    },
    intro () {
      return store.assessInfo.intro;
    },
    questionArray () {
      let qnArray = {};
      for (let i = 0; i < store.assessInfo.questions.length; i++) {
        qnArray[i] = i;
      }
      return qnArray;
    }
  },
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
  }
};
</script>

<style>

</style>
