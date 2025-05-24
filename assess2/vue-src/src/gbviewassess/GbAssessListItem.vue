<template>
  <span>
    <strong>{{ attemptNum }}.</strong>
    <span v-if="option.hasOwnProperty('score') && option.score !== 'N/A'">
      {{ $t('gradebook.score') }}:
      <strong>{{ score }}</strong>. </span>
    <span v-if="option.hasOwnProperty('status')">
      {{ verStatus }}
    </span>
  </span>
</template>

<script>
import { store } from './gbstore';

export default {
  name: 'GbAssessListItem',
  props: ['option', 'submitby'],
  computed: {
    attemptNum () {
      if (this.option.status === 3) {
        return this.$t('gradebook.practice_attempt');
      } else if (this.submitby === 'by_question') {
        return this.$t('gradebook.scored_attempt');
      } else {
        return this.$tc('gradebook.attempt_n', this.option.ver + 1);
      }
    },
    verStatus () {
      if (this.option.status === -1) {
        return this.$t('gradebook.not_started');
      } else if (this.option.status === 0 && this.submitby === 'by_assessment') {
        return this.$t('gradebook.not_submitted');
      } else if (this.option.status === 1 || this.option.status === 2) {
        let out = '';
        if (this.submitby === 'by_question') {
          out += this.$t('gradebook.lastchange');
        } else {
          out += this.$t('gradebook.submitted');
        }
        out += ' ' + this.option.lastchange_disp;
        return out;
      } else {
        return '';
      }
    },
    score () {
      return this.option.score + '/' + store.assessInfo.points_possible;
    }
  }
};
</script>
