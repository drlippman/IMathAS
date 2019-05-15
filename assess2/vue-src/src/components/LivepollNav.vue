<template>
  <div class="subheader">
    <menu-button id="qnav"
      :options = "navOptions"
      :selected = "dispqn"
      searchby = "dispqn"
    >
      <template v-slot="{ option }">
        {{ option.title }}
      </template>
    </menu-button>
    <button
      @click="$emit('selectq', dispqn-1)"
      :disabled="dispqn <= 0"
      class="secondarybtn"
      id="qprev"
      :aria-label="$t('previous')"
      v-if = "showNextPrev"
    >
      <icons name="left"/>
    </button>
    <button
      @click="$emit('selectq', dispqn+1)"
      :disabled="curOption>=navOptions.length-1"
      class="secondarybtn"
      id="qnext"
      :aria-label="$t('next')"
      v-if = "showNextPrev"
    >
      <icons name="right" />
    </button>
    <div>
      {{ studentCount }}
    </div>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import Icons from '@/components/widgets/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'LivepollNav',
  props: ['qn'],
  components: {
    MenuButton,
    Icons
  },
  computed: {
    navOptions () {
      var out = [];
      out.push({
        onclick: () => this.$emit('selectq', 0),
        title: this.$t('livepoll.settings'),
        dispqn: 0
      });
      for (let qn in store.assessInfo.questions) {
        let dispqn = parseInt(qn) + 1;
        out.push({
          onclick: () => this.$emit('selectq', dispqn),
          title: this.$t('question_n', { n: dispqn }),
          dispqn: dispqn
        });
      }
      return out;
    },
    showNextPrev () {
      return (Object.keys(this.navOptions).length > 1);
    },
    dispqn () {
      return parseInt(this.qn) + 1;
    },
    studentCount () {
      return this.$tc('livepoll.stucnt', store.livepollStuCnt);
    }
  }
};

</script>

<style>
.subheader {
  display: flex;
  flex-flow: row wrap;
  border-bottom: 1px solid #ccc;
}
.subheader > * {
  margin: 4px 0;
}
</style>
