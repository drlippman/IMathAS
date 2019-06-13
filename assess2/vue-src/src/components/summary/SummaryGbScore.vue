<template>
  <div v-if="showGBScore" class="pane-header">
    <strong>{{ $t('summary.recordedscore') }}: {{ gbScore }}%</strong>
    <br/>
    <span class="small subdued">
      {{ scoreUsed }}
    </span>
  </div>
</template>

<script>
import { store } from '../../basicstore';

export default {
  name: 'SummaryGbScore',
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    scoreUsed () {
      if (this.ainfo.scored_attempt.kept === 'override') {
        return this.$t('summary.use_override');
      } else if (this.ainfo.keepscore === 'best' &&
          this.ainfo.submitby === 'by_assessment'
      ) {
        return this.$t('setlist.keep_highest');
      } else if (this.ainfo.keepscore === 'best' &&
          this.ainfo.submitby === 'by_question'
      ) {
        return this.$t('setlist.keep_highest_q');
      } else if (this.ainfo.keepscore === 'average') {
        return this.$t('setlist.keep_average');
      } else if (this.ainfo.keepscore === 'last') {
        return this.$t('setlist.keep_last');
      } else {
        return '';
      }
    },
    showGBScore () {
      return (this.ainfo.hasOwnProperty('scored_attempt') &&
        this.ainfo.scored_attempt.hasOwnProperty('score') &&
        this.ainfo.showscores !== 'none'
      );
    },
    gbScore () {
      return Math.round(1000 * this.ainfo.scored_attempt.score / this.ainfo.points_possible) / 10;
    }
  }
};
</script>
