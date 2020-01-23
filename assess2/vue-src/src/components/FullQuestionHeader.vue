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
    <question-header-icons
      :showscore = "true"
      :curQData = "curQData"
      :qn = "qn"
      :showretry = "showretry"
    />
  </div>
</template>

<script>
import QuestionHeaderIcons from '@/components/QuestionHeaderIcons.vue';
import Icons from '@/components/widgets/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'SkipQuestionHeader',
  props: ['qn', 'showretry'],
  components: {
    QuestionHeaderIcons,
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
    statusIcon () {
      if (this.dispqn === 0) {
        return 'none';
      } else {
        return this.curQData.status;
      }
    },
    nameHover () {
      if (this.curQData.withdrawn !== 0) {
        return this.$t('header.withdrawn');
      } else {
        return '';
      }
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
  border-top: 1px solid #ccc;
  padding: 8px 0;
}
.fulldisp > div > div:first-of-type {
  margin-top: 32px;
}
.fulldisp > div:first-of-type > div:first-of-type {
  margin-top: 16px;
}
.fulldisp > div:first-of-type > div:first-of-type.full-question-header {
  border-top: 0;
}
.fullpaged > div:first-of-type {
  margin-top: 32px;
}
.fullpaged:first-of-type > div:first-of-type {
  margin-top: 15px;
}
.full-question-header > * {
  margin-right: 10px;
}
.bigicon {
  font-size: 130%;
}
.headericons > * {
  margin-left: 8px;
}
</style>
