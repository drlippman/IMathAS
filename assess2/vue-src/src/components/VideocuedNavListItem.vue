<template>
  <span>
    <span class="qname-wrap">
      <icons :name="statusIcon" class="qstatusicon" />
      <span
        :class="{greystrike: nameHover !== ''}"
        :title = "nameHover"
      >
        {{ option.title }}
      </span>
      {{ scoreDisplay }}
    </span>
    <span class="redoicon">
      <icons name="retry" v-if="canRetry" />
      <icons name="retake" v-if="canRegen" />
    </span>
  </span>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'VideocuedNavListItem',
  props: ['option'],
  components: {
    Icons
  },
  computed: {
    statusIcon () {
      if (this.option.type === 'v' || this.option.type === 'f') {
        return 'video';
      } else if (this.option.type === 'q') {
        return store.assessInfo.questions[this.option.qn].status;
      } else {
        return 'none';
      }
    },
    nameHover () {
      if (this.option.type === 'q' &&
          store.assessInfo.questions[this.option.qn].withdrawn !== 0
      ) {
        return this.$t('header.withdrawn');
      }
      return '';
    },
    scoreDisplay () {
      if (this.option.type !== 'q') {
        return '';
      } else {
        let qdata = store.assessInfo.questions[this.option.qn];
        if (qdata.hasOwnProperty('gbscore')) {
          let str = qdata.canretry ? '(' : '[';
          str += qdata.gbscore + '/' + qdata.points_possible;
          str += qdata.canretry ? ')' : ']';
          return str;
        } else {
          return this.$tc('header.pts', qdata.points_possible);
        }
      }
    },
    canRetry () {
      if (this.option.type === 'q') {
        let qdata = store.assessInfo.questions[this.option.qn];
        return qdata.canretry;
      }
      return false;
    },
    canRegen () {
      if (this.option.type === 'q') {
        let qdata = store.assessInfo.questions[this.option.qn];
        return qdata.regens_remaining;
      }
      return false;
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
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
</style>
