<template>
  <div>
    <p v-if="showTotal">
      <strong class="larger">
        {{ $t("summary.score") }}:
        {{ scoreTotalPercent }}%
      </strong>
      <br/>
      {{ $tc("summary.scorepts", ainfo.points_possible, {pts: scoreTotal, poss: ainfo.points_possible}) }}
      <span v-if="retakePenalty > 0">
        <br/>
        {{ $t("summary.retake_penalty", {n: retakePenalty}) }}
      </span>
      <span v-if="latePenalty > 0">
        <br/>
        {{ $t("summary.late_penalty", {n: latePenalty}) }}
      </span>
    </p>
    <p v-else>
      {{ $t("summary.no_total") }}
      {{ $t("summary.viewwork_" + viewWorkType) }}
    </p>
  </div>
</template>

<script>
import { store } from '../../basicstore';

export default {
  name: 'SummaryScoreTotal',
  components: {

  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    showTotal () {
      return (this.ainfo.showscores !== 'none');
    },
    scoreTotal () {
      if (this.ainfo.hasOwnProperty('score')) {
        return this.ainfo.score;
      } else {
        let score = 0;
        for (const i in this.ainfo.questions) {
          score += this.ainfo.questions[i].score;
        }
        return score;
      }
    },
    retakePenalty () {
      if (this.ainfo.submitby === 'by_question') {
        return 0;
      }
      const curAttempt = this.ainfo.prev_attempts.length;
      if (curAttempt > this.ainfo.retake_penalty.n) {
        return this.ainfo.retake_penalty.penalty * (curAttempt - this.ainfo.retake_penalty.n);
      }
      return 0;
    },
    latePenalty () {
      if (this.ainfo.submitby === 'by_question') {
        return 0;
      }
      let hasLate = 0;
      for (const i in this.ainfo.questions) {
        const parts = this.ainfo.questions[i].parts;
        for (let pn = 0; pn < parts.length; pn++) {
          if (parts[pn].hasOwnProperty('penalties') && parts[pn].penalties.hasOwnProperty('late')) {
            hasLate += parts[pn].penalties.late;
          } else if (hasLate > 0) {
            // if one is not late, we won't report late here
            return 0;
          }
        }
      }
      return hasLate;
    },
    scoreTotalPercent () {
      return Math.round(1000 * this.scoreTotal / this.ainfo.points_possible) / 10;
    },
    viewWorkType () {
      if (this.ainfo.viewingb === 'never') {
        return 'never';
      } else if (this.ainfo.viewingb === 'after_due') {
        if (this.ainfo.scoresingb === 'never') {
          return 'work_after';
        } else {
          return 'after_due';
        }
      } else { // after_take or immediately
        if (this.ainfo.scoresingb === 'never') {
          return 'work';
        } else if (this.ainfo.scoresingb === 'after_due') {
          return 'work_scores_after';
        } else {
          return 'immediately';
        }
      }
    }
  }
};
</script>

<style>
.larger {
  font-size: 130%;
}
</style>
