<template>
  <span>
    {{ attemptNum }}<span v-if="option.scored">*</span>.
    <span v-if="option.score">
      {{ score }}
    </span>
  </span>
</template>

<script>


export default {
  name: 'GbQuestionListItem',
  props: ['option'],
  computed: {
    attemptNum() {
      return this.$tc('gradebook.version_n', this.option.ver + 1);
    },
    score() {
      return this.$t('gradebook.score')+": "+this.option.score;
    }
  }
};
</script>
