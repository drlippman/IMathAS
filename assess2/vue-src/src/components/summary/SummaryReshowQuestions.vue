<template>
  <div>
    <div v-for="(question,curqn) in questions">
      <full-question-header
        :qn = "curqn"
        :showretry="false"
      />
      <question
        :qn = "curqn"
        :key="'q'+curqn"
        active = "true"
      />
    </div>
  </div>
</template>

<script>
import { store } from '../../basicstore';
import Question from '@/components/Question.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';

export default {
  name: 'SummaryReshowQuestions',
  components: {
    Question,
    FullQuestionHeader
  },
  computed: {
    questions () {
      return store.assessInfo.questions;
    }
  }
}
</script>
