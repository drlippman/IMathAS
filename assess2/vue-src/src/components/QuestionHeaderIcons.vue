<template>
  <div class="headericons">
    <tooltip-span
      v-if="scoreDisplay !== ''"
      :tip="scoreTip"
    >
      <icons name="square-check" />
      {{ scoreDisplay }}
    </tooltip-span>
    <tooltip-span
      v-if="qn >= 0 && curQData.canretry && showretry !== false"
      :tip="retryInfo.msg">
      <icons name="retry"/>
      {{ retryInfo.cnt }}
    </tooltip-span>
    <tooltip-span
      v-if="qn >= 0 && curQData.canregen && showretry !== false"
      :tip="$tc('qinfo.regens_remaining', curQData.regens_remaining)">
      <icons name="retake"/>
      {{ curQData.regens_remaining }}
    </tooltip-span>
    <dropdown
      :id="'qd-dd-'+qn"
      class="question-details"
      v-if="showDetails"
      :tip = "$t('qdetails.question_details')"
    >
      <template v-slot:button>
        <icons name="info" size="medium"/>
        {{ $t('header.details') }}
      </template>
      <question-details-pane :qn="qn" />
    </dropdown>
  </div>
</template>

<script>
import QuestionDetailsPane from '@/components/QuestionDetailsPane.vue';
import Dropdown from '@/components/widgets/Dropdown.vue';
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'QuestionHeaderIcons',
  props: ['showscore', 'curQData', 'qn', 'showretry'],
  components: {
    Dropdown,
    Icons,
    QuestionDetailsPane,
    TooltipSpan
  },
  computed: {
    dispqn () {
      return parseInt(this.qn) + 1;
    },
    scoreDisplay () {
      if (this.dispqn === 0) {
        return '';
      } else if (this.showscore && this.curQData.hasOwnProperty('gbscore') && this.curQData.tries_max > 1) {
        return this.curQData.gbscore + '/' + this.$tc('header.pts', this.curQData.points_possible);
      } else {
        return this.$tc('header.pts', this.curQData.points_possible);
      }
    },
    scoreTip () {
      if (this.showscore && this.curQData.hasOwnProperty('gbscore') && this.curQData.tries_max > 1) {
        return this.$t('qdetails.gbscore');
      } else {
        return this.$tc('header.possible', this.curQData.points_possible);
      }
    },
    retryInfo () {
      if (this.qn < 0) {
        return {};
      }
      let trymsg;
      let trycnt;
      if (this.curQData.hasOwnProperty('tries_remaining_range')) {
        const range = this.curQData.tries_remaining_range;
        trymsg = this.$t('qinfo.tries_remaining_range', {
          min: range[0],
          max: range[1]
        });
        trycnt = range[0] + '-' + range[1];
      } else {
        trymsg = this.$tc('qinfo.tries_remaining', this.curQData.tries_remaining);
        trycnt = this.curQData.tries_remaining;
      }
      return {
        msg: trymsg,
        cnt: trycnt
      };
    },
    showDetails () {
      if (this.qn < 0) {
        return false;
      }
      const hasCategory = this.curQData.hasOwnProperty('category') &&
        this.curQData.category !== '';
      return (this.curQData.has_details ||
        hasCategory ||
        this.curQData.hasOwnProperty('gbscore')
      );
    }
  }
};
</script>
