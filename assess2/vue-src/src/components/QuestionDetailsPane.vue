<template>
  <div>
    <div class="pane-header nowrap">
      {{ $t('qdetails.question_details') }}
    </div>
    <div class="pane-body">
      <p v-if="qinfo.extracredit === 1">
        {{ $t('qdetails.extracredit') }}
      </p>

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
        :submitby="submitby"
      />

      <div v-if = "hasOnePartPenalties">
        <p>{{ $t('penalties.applied') }}:</p>
        <penalties-applied
          class = "med-left"
          :part="qinfo.parts[0]"
          :submitby="submitby"
        />
      </div>

      <p v-if="hasCategory">
        <strong>{{ $t('qdetails.category') }}:</strong>
        {{ qinfo.category }}
      </p>
      <p class="small subdued" style="text-align:right">
        <a target="license" :href="licenseUrl">
          {{ $t('qdetails.license') }}
        </a>
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
        this.qinfo.parts[0].hasOwnProperty('points_possible') &&
        !this.qinfo.singlescore
      );
    },
    hasOnePartPenalties () {
      return (this.qinfo.hasOwnProperty('parts') &&
        this.qinfo.parts.length === 1 &&
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
      return (store.assessInfo.showcat === 1 &&
        this.qinfo.hasOwnProperty('category') &&
        this.qinfo.category !== '' &&
        this.qinfo.category !== null
      );
    },
    submitby () {
      return store.assessInfo.submitby;
    },
    licenseUrl () {
      return window.imasroot + '/course/showlicense.php?id=' + this.qinfo.questionsetid;
    }
  }
};
</script>
<style>

</style>
