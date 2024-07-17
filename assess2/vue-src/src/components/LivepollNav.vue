<template>
  <div class="subheader">
    <div class="flexrow" style="flex-grow:1"
      role="navigation" :aria-label="$t('regions.qnav')"
    >
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
        @click="selectQuestion(dispqn-1)"
        :disabled="dispqn <= 0"
        class="secondarybtn"
        id="qprev"
        :aria-label="$t('previous')"
        v-if = "showNextPrev"
      >
        <icons name="left"/>
      </button>
      <button
        @click="selectQuestion(dispqn+1)"
        :disabled="dispqn >= navOptions.length-1"
        class="secondarybtn"
        id="qnext"
        :aria-label="$t('next')"
        v-if = "showNextPrev"
      >
        <icons name="right" />
      </button>
    </div>
    <div style="flex-grow:1">
      <button
        class = "primary"
        v-if = "curstate === 2 && dispqn > 0"
        @click = "closeQuestion"
      >
        {{ $t('livepoll.close_input') }}
      </button>
      <button
        class = "primary"
        v-else-if = "curstate > 0 && dispqn > 0"
        @click = "openQuestion"
      >
        {{ $t('livepoll.open_input') }}
      </button>
      <button
        class = "secondary"
        v-if = "(curstate == 1 || curstate > 2) && dispqn > 0"
        @click = "newVersion"
      >
        <icons name="retake" />
        {{ $t('livepoll.new_version') }}
      </button>
    </div>
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
      for (const qn in store.assessInfo.questions) {
        const dispqn = parseInt(qn) + 1;
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
    curstate () {
      return store.assessInfo.livepoll_status.curstate;
    },
    studentCount () {
      return this.$tc('livepoll.stucnt', store.livepollStuCnt);
    }
  },
  methods: {
    selectQuestion (n) {
      this.$emit('selectq', n);
    },
    openQuestion () {
      this.$emit('openq');
    },
    closeQuestion () {
      this.$emit('closeq');
    },
    newVersion () {
      this.$emit('newversion');
    }
  }
};

</script>

<style>
.subheader {
  display: flex;
  flex-flow: row wrap;
  border-bottom: 1px solid #ccc;
  align-items: center;
}
.subheader > *, .subheader > .flexrow > * {
  margin: 4px 0;
}
</style>
