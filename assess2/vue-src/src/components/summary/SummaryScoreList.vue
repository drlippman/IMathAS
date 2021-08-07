<template>
  <table class="scorelist">
    <tr>
      <th>{{ $t('scorelist.question') }}</th>
      <th>{{ $t('scorelist.score') }}</th>
    </tr>
    <tr v-for="(question,index) in questions" :key="index">
      <td>
        <icons :name="question.status" />
        {{ $t('question_n', {n: index+1}) }}
        <em v-if="question.extracredit" class="small subdued">
          {{ $t('extracredit') }}
        </em>
      </td>
      <td v-if="noTries[index]">
        {{ $t('scorelist.unattempted') }}
      </td>
      <td v-else>
        {{ $tc('scorelist.pts', question.points_possible,
              {pts: question.gbscore, poss: question.points_possible}) }}
        &nbsp;&nbsp;
        <click-to-show
          v-if = "question.has_details"
          class="question-details"
          :id="'qd_'+index"
        >
          <template v-slot:button>
            <icons name="info" size="small"/>
            {{ $t('header.details') }}
          </template>
          <question-details-table
            :qinfo="question"
            :showtries="false"
          />
        </click-to-show>
      </td>
    </tr>
  </table>
</template>

<script>
import { store } from '../../basicstore';
import Icons from '@/components/widgets/Icons.vue';
import QuestionDetailsTable from '@/components/QuestionDetailsTable.vue';
import ClickToShow from '@/components/widgets/ClickToShow.vue';

export default {
  name: 'SummaryScoreList',
  components: {
    Icons,
    QuestionDetailsTable,
    ClickToShow
  },
  computed: {
    questions () {
      return store.assessInfo.questions;
    },
    noTries () {
      var out = {};
      for (const i in this.questions) {
        if (!this.questions[i].hasOwnProperty('parts')) {
          out[i] = true;
        } else {
          let notries = true;
          for (const p in this.questions[i].parts) {
            if (this.questions[i].parts[p].try > 0) {
              notries = false;
              break;
            }
          }
          out[i] = notries;
        }
      }
      return out;
    }
  }
};
</script>

<style>
.scorelist {
  border-collapse: collapse;
}
.scorelist td, .scorelist th {
  padding: 8px 12px;
}
.scorelist td {
  border-bottom: 1px solid #ddd;
}
.scorelist th {
  text-align: left;
  border-bottom: 2px solid #ddd;
}
</style>
