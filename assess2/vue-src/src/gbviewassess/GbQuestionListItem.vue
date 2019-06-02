<template>
  <span>
    {{ attemptNum }}.
    <span v-if="option.score">
      {{ score }}
    </span>
  </span>
</template>

<script>


export default {
  name: 'GbQuestionListItem',
  props: ['option', 'total'],
  computed: {
    attemptNum() {
      return this.$tc('gradebook.version_n', this.option.ver + 1) +
        (this.option.scored?'*':'') + '/' + this.total;
    },
    score() {
      return this.$t('gradebook.score')+": "+this.option.score;
    }
  }
};
</script>
