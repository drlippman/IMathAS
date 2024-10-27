<template>
  <div
    v-if = "qn === -1 || showNav"
  >
    <button
      v-if="qn === -1 && !playing"
      @click = "startVid"
      class = "primary"
    >
      {{ $t('videocued.start') }}
    </button>
    <button
      v-if = "qn > -1 && hasNextVid"
      @click = "nextVidLink"
      :class="{'primary': status !== 'correct' || !showSkip}"
    >
      {{ $t('videocued.continue', {'title': nextVidTitle}) }}
    </button>

    <button
      v-if = "qn > -1 && showSkip"
      @click = "skipLink"
      class="primary"
    >
      {{ $t('videocued.skipto', {'title': skipTitle}) }}
    </button>
  </div>
</template>

<script>
import { store } from '../basicstore';

export default {
  name: 'VideocuedResultNav',
  props: ['qn', 'cue', 'playing'],
  computed: {
    qdata () {
      return store.assessInfo.questions[this.qn];
    },
    showNav () {
      return (store.inProgress &&
        store.assessInfo.hasOwnProperty('questions') &&
        this.qdata.hasOwnProperty('score') &&
        (this.qdata.try > 0 ||
          this.qdata.hasOwnProperty('tries_remaining_range')) &&
        this.qdata.withdrawn === 0
      );
    },
    showScores () {
      return (store.assessInfo.showscores === 'during');
    },
    status () {
      if (!this.showScores || !this.qdata.hasOwnProperty('parts')) {
        return 'neutral';
      }
      for (let i = 0; i < this.qdata.parts.length; i++) {
        if (this.qdata.parts[i].try === 0 ||
            this.qdata.parts[i].rawscore < 0.98
        ) {
          return 'neutral';
        }
      }
      return 'correct';
    },
    nextVidType () {
      return store.assessInfo.videocues[this.cue].hasOwnProperty('followuptitle')
        ? 'followup'
        : 'nextseg';
    },
    hasNextVid () {
      if (store.assessInfo.videocues[this.cue].hasOwnProperty('followuptitle')) {
        this.$emit('addfollowup', this.cue);
      }
      return (this.nextVidType === 'followup' ||
        store.assessInfo.videocues.hasOwnProperty(this.cue + 1)
      );
    },
    nextVidTitle () {
      if (this.nextVidType === 'followup') {
        return store.assessInfo.videocues[this.cue].followuptitle;
      } else {
        return store.assessInfo.videocues[this.cue + 1].title;
      }
    },
    showSkip () {
      return (this.status === 'correct' &&
        this.nextVidType === 'followup' &&
        store.assessInfo.videocues.hasOwnProperty(this.cue + 1)
      );
    },
    skipTitle () {
      return store.assessInfo.videocues[this.cue + 1].title;
    }
  },
  methods: {
    skipLink () {
      this.$emit('jumpto', this.cue + 1, 'v');
    },
    nextVidLink () {
      if (this.nextVidType === 'followup') {
        this.$emit('jumpto', this.cue, 'f');
      } else {
        this.$emit('jumpto', this.cue + 1, 'v');
      }
    },
    startVid () {
      this.$emit('jumpto', this.cue === -1 ? 0 : this.cue, 'rv');
    }
  }
};
</script>
