<template>
  <span class="flex-nowrap-center">
    <icons :name="statusIcon" class="qstatusicon" v-if="option.dispqn > 0" />
    <span class="qname-wrap">
      <span
        :class="{greystrike: option.withdrawn > 0}"
        :title = "nameHover"
      >
        {{ nameDisp }}
        <em v-if="option.extracredit" class="small subdued">
          {{ $t('extracredit') }}
        </em>
      </span>
    </span>
    <span v-if="scoreDisplay !== '' && !selected" class="subdued">
      {{ scoreDisplay }}
    </span>
    <span class="redoicon" v-if="showretry && !selected">
      <icons name="retry" v-if="option.canretry" />
    </span>
    <span class="redoicon" v-if="showretake && !selected">
      <icons name="retake" v-if="option.regens_remaining" />
    </span>
  </span>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'SkipQuestionListItem',
  props: ['option', 'showretry', 'showretake', 'selected'],
  components: {
    Icons
  },
  computed: {
    statusIcon () {
      if (this.option.dispqn === 0) {
        return 'none';
      } else {
        return this.option.status;
      }
    },
    nameDisp () {
      if (this.option.dispqn === 0) {
        return this.$t('intro');
      } else {
        return this.$t('question_n', { n: this.option.dispqn });
      }
    },
    nameHover () {
      if (this.option.withdrawn !== 0) {
        return this.$t('header.withdrawn');
      } else {
        return '';
      }
    },
    scoreDisplay () {
      if (this.option.dispqn === 0) {
        return '';
      } else if (this.option.hasOwnProperty('gbscore') && this.option.tries_max > 1) {
        return this.option.gbscore + '/' + this.$tc('header.pts', this.option.points_possible);
      } else {
        return '(' + this.$tc('header.pts', this.option.points_possible) + ')';
      }
      /* else if (this.option.hasOwnProperty('gbscore')) {
        let str = this.option.canretry ? '(' : '[';
        str += this.option.gbscore + '/' + this.option.points_possible;
        str += this.option.canretry ? ')' : ']';
        return str;
      } */
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
.questionnav {
  display: inline-block;
  border: 1px solid #ccc;
  padding: 5px 10px;
}
.greentext {
  color: #090;
}
.orangetext {
  color: #AA5D00;
}
.redtext {
  color: #900;
}
.bluetext {
  color: #009;
}
.qname-wrap {
  display: inline-block;
  flex-grow: 1;
  margin-right:24px;
  min-width: 8em;
}

.qstatusicon {
  margin-right: 4px;
}
.redoicon {
  display: inline-block;
  width: 24px;
  text-align: right;
}

</style>
