<template>
  <span>
    <span class="qname-wrap">
      <icons :name="statusIcon" class="qstatusicon" />
      <span
        :class="{greystrike: option.withdrawn !==0}"
        :title = "nameHover"
      >
        {{ nameDisp }}
      </span>
      {{ scoreDisplay }}
    </span>
    <span class="redoicon">
      <icons name="retry" v-if="option.canretry" />
    </span>
  </span>
</template>

<script>
import Icons from '@/components/Icons.vue';

export default {
  name: 'QuestionListItem',
  props: ['option'],
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
        }
    },
    scoreDisplay () {
      if (this.option.dispqn === 0) {
        return '';
      } else if (this.option.hasOwnProperty('gbscore')) {
        let str = this.option.canretry ? '(' : '[';
        str += this.option.gbscore + '/' + this.option.points_possible;
        str += this.option.canretry ? ')' : ']';
        return str;
      } else {
        return this.$tc('header.pts', this.option.points_possible);
      }
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
  min-width: 12em;
}
.qstatusicon {
  margin-right: 4px;
}
.redoicon {
  display: inline-block;
  width: 40px;
  text-align: right;
}
</style>
