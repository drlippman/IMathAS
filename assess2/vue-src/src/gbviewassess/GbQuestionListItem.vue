<template>
  <span>
    {{ attemptNum }}.
    <span v-if="option.hasOwnProperty('score') && option.score !== 'N/A'">
      {{$t('gradebook.score')}}: <strong>{{ score }}</strong>
    </span>
  </span>
</template>

<script>

export default {
  name: 'GbQuestionListItem',
  props: ['option', 'total'],
  computed: {
    attemptNum () {
      return this.$tc('gradebook.version_n', this.option.ver + 1) +
        (this.option.scored ? '*' : '') + '/' + this.total;
    },
    score () {
      return this.option.score + '/' + this.option.ptsposs;
    }
  }
};
</script>
