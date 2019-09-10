<template>
  <div>
    <div v-for="(question,curqn) in questions" :key="curqn">
      <full-question-header
        :qn = "curqn"
        :showretry="false"
      />
      <question
        :qn = "curqn"
        :key="'sq'+curqn"
        :active = "active"
        :disabled = "true"
      />
    </div>
  </div>
</template>

<script>
import { store } from '../../basicstore';
import Question from '@/components/question/Question.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';

export default {
  name: 'SummaryReshowQuestions',
  props: ['active'],
  components: {
    Question,
    FullQuestionHeader
  },
  computed: {
    questions () {
      return store.assessInfo.questions;
    }
  }
};
</script>
