<template>
  <div>
    <div class="pane-header nowrap">
      {{ $t('qdetails.question_details') }}
    </div>
    <div class="pane-body">

      <p v-if="showGBScore">
        {{ gbScoreString }}:
        <span class="nowrap">
          {{ $tc('scoreresult.scorepts', qinfo.points_possible,
            {pts: qinfo.gbscore, poss: qinfo.points_possible}) }}
        </span>
      </p>
      <p v-if="showScore">
        {{ $t('qdetails.lastscore') }}:
        <span class="nowrap">
          {{ $tc('scoreresult.scorepts', qinfo.points_possible,
            {pts: qinfo.score, poss: qinfo.points_possible}) }}
        </span>
      </p>

      <question-details-table
        v-if="hasParts"
        :caption = "$t('qdetails.lasttry')"
        :qinfo="qinfo"
      />

      <div v-if = "hasOnePartPenalties">
        <p>{{ $t('penalties.applied') }}:</p>
        <penalties-applied
          class = "med-left"
          :part="qinfo.parts[0]"
        />
      </div>

      <p v-if="hasCategory">
        <strong>{{ $t('qdetails.category') }}:</strong>
        {{ qinfo.category }}
      </p>
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';
import QuestionDetailsTable from '@/components/QuestionDetailsTable.vue';
import PenaltiesApplied from '@/components/PenaltiesApplied.vue';

export default {
  name: 'QuestionDetailPane',
  props: ['qn', 'className', 'id'],
  components: {
    QuestionDetailsTable,
    PenaltiesApplied
  },
  computed: {
    qinfo () {
      return store.assessInfo.questions[this.qn];
    },
    hasParts () {
      return (this.qinfo.hasOwnProperty('parts') &&
        this.qinfo.parts.length > 1 &&
        this.qinfo.withdrawn === 0 &&
        this.qinfo.parts[0].hasOwnProperty('points_possible')
      );
    },
    hasOnePartPenalties () {
      return (this.qinfo.hasOwnProperty('parts') &&
        this.qinfo.parts.length == 1 &&
        this.qinfo.parts[0].hasOwnProperty('penalties') &&
        this.qinfo.parts[0].penalties.length > 0
      );
    },
    showScore () {
      return this.qinfo.hasOwnProperty('score');
    },
    showGBScore () {
      return this.qinfo.hasOwnProperty('gbscore');
    },
    gbScoreString () {
      if (store.assessInfo.in_practice) {
        return this.$t('qdetails.bestpractice');
      } else {
        return this.$t('qdetails.gbscore');
      }
    },
    hasCategory () {
      return (this.qinfo.hasOwnProperty('category') &&
        this.qinfo.category !== '' &&
        this.qinfo.category !== null
      );
    }
  }
};
</script>
<style>
.pane-header {
  border-bottom: 1px solid #ccc;
  padding: 16px 20px;
  font-weight: bold;
}
.pane-body {
  padding: 16px 20px;
}
table.qdetails {
  border-collapse: collapse;
  margin-bottom: 20px;
}
table.qdetails tr {
  border-bottom: 1px solid #ccc;
}
table.qdetails td, table.qdetails th {
  padding: 4px 8px;
}
</style>
