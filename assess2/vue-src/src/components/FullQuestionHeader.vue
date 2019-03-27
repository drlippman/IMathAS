<template>
  <div class="full-question-header">
    <div style="flex-grow: 1">
      <icons :name="statusIcon" class="qstatusicon" />
      <strong
        :class="{greystrike: curQData.withdrawn !==0}"
      >
        {{ $t('question_n', { n: dispqn }) }}
      </strong>
    </div>
    <div>
      <span>
        <icons name="square-check" />
        {{ scoreDisplay }}
      </span>
      <span
        v-if="qn >= 0 && curQData.canretry && showretry !== false"
        :title="$tc('qinfo.tries_remaining', curQData.tries_remaining)">
        <icons name="retry"/>
        {{ curQData.tries_remaining }}
      </span>
      <span
        v-if="qn >= 0 && curQData.canregen && showretry !== false"
        :title="$tc('qinfo.regens_remaining', curQData.regens_remaining)">
        <icons name="retake"/>
        {{ curQData.regens_remaining }}
      </span>
    </div>

    <dropdown id="question-details" position="right" v-if="showDetails">
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
import Dropdown from '@/components/Dropdown.vue';
import Icons from '@/components/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'SkipQuestionHeader',
  props: ['qn', 'showretry'],
  components: {
    QuestionDetailsPane,
    Dropdown,
    Icons
  },
  data: function () {
    return {

    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    curQData () {
      return store.assessInfo.questions[this.qn];
    },
    dispqn () {
      return parseInt(this.qn) + 1;
    },
    queryString () {
      return store.queryString;
    },
    statusIcon () {
      if (this.dispqn === 0) {
        return 'none';
      } else {
        return this.curQData.status;
      }
    },
    scoreDisplay () {
      if (this.dispqn === 0) {
        return '';
      } else if (this.curQData.hasOwnProperty('gbscore') && this.curQData.tries_max > 1) {
        return this.curQData.gbscore + '/' + this.$tc('header.pts', this.curQData.points_possible);
      } else {
        return this.$tc('header.pts', this.curQData.points_possible);
      }
    },
    nameHover () {
        if (this.curQData.withdrawn !== 0) {
          return this.$t('header.withdrawn');
        }
    },
    showDetails () {
      if (this.qn < 0) {
        return false;
      }
      let curQData = store.assessInfo.questions[this.qn];
      let hasCategory = curQData.hasOwnProperty('category') && curQData.category !== '';
      return (curQData.has_details ||
        hasCategory ||
        curQData.hasOwnProperty('gbscore')
      );
    }
  }
};
</script>

<style>
.full-question-header {
  display: flex;
  flex-flow: row wrap;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ccc;
  padding: 8px 0;
  margin-top: 16px;
}
.full-question-header > * {
  margin-right: 10px;
}
.bigicon {
  font-size: 130%;
}

</style>
