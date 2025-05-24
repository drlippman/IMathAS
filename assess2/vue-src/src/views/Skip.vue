<template>
  <div class="home">
    <a href="#" class="sr-only" id="skipnav" @click.prevent="$refs.scrollpane.focus()">
      {{ $t('jumptocontent') }}
    </a>
    <assess-header></assess-header>
    <skip-question-header :qn="qn"/>
    <div
      class="scrollpane"
      role="region"
      ref="scrollpane"
      tabindex="-1"
      :aria-label="$t('regions.questions')"
    >
      <intro-text
        :active = "qn == -1"
        :html = "intro"
        key = "-1"
      />
      <router-link
          v-if = "qn == -1"
          :to="'/skip/1'"
          custom
          v-slot="{ navigate }"
      >
        <button
          type="button"
          @click="navigate"
          @keypress.enter="navigate"
          role="link"
        >
          <icons name="right" alt=""/>
          {{ $t('question.firstq') }}
        </button>
      </router-link>
      <inter-question-text-skiplist
        pos = "before"
        :qn = "qn"
      />
      <div
        v-for="curqn in questionArray"
        :key="curqn"
        :class="{inactive: curqn != qn}"
        :aria-hidden = "curqn != qn"
      >
        <h2 class="sr-only">
          {{ $t('question_n', { n: curqn + 1 }) }}
        </h2>
        <question
          :qn="curqn"
          :active="curqn == qn"
          :getwork="1"
        />
      </div>
      <inter-question-text-skiplist
        pos = "after"
        :qn = "qn"
      />
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import SkipQuestionHeader from '@/components/SkipQuestionHeader.vue';
import InterQuestionTextSkiplist from '@/components/InterQuestionTextSkiplist.vue';
import Question from '@/components/question/Question.vue';
import IntroText from '@/components/IntroText.vue';
import Icons from '@/components/widgets/Icons.vue';

import { store } from '../basicstore';

export default {
  name: 'skip',
  components: {
    SkipQuestionHeader,
    Question,
    InterQuestionTextSkiplist,
    AssessHeader,
    IntroText,
    Icons
  },
  computed: {
    qn () {
      return parseInt(this.$route.params.qn) - 1;
    },
    intro () {
      return store.assessInfo.intro;
    },
    questionArray () {
      const qnArray = {};
      for (let i = 0; i < store.assessInfo.questions.length; i++) {
        qnArray[i] = i;
      }
      return qnArray;
    }
  }
};
</script>

<style>

</style>
