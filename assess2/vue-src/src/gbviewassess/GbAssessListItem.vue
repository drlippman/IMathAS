<template>
  <span>
    <strong>{{ attemptNum }}.</strong>
    {{ score }}.
    {{ verStatus }}
  </span>
</template>

<script>


export default {
  name: 'GbAssessListItem',
  props: ['option', 'submitby'],
  computed: {
    attemptNum() {
      if (this.option.status === 3) {
        return $t('gradebook.practice_version');
      } else {
        return this.$tc('gradebook.attempt_n', this.option.ver + 1);
      }
    },
    verStatus () {
      if (this.option.status == -1) {
        return this.$t('gradebook.not_started');
      } else if (this.option.status == 0) {
        return $t('gradebook.not_submitted');
      } else if (this.option.status == 1 || this.option.status == 2) {
        let out = '';
        if (this.submitby === 'by_question') {
          out += this.$t('gradebook.lastchange');
        } else {
          out += this.$t('gradebook.submitted');
        }
        out += ' ' +
          this.$d(new Date(this.option.lastchange * 1000), 'long');
        return out;
      } else {
        return '';
      }
    },
    score() {
      return this.$t('gradebook.score')+": "+this.option.score;
    }
  }
};
</script>
