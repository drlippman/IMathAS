<template>
  <div
    v-if = "showNav"
    class="video-result-nav"
  >
    <router-link
      v-if = "hasNextVid"
      tag = "button"
      :to = "nextVidLink"
      :class="{'primary': status !== 'correct' || !showSkip}"
    >
      {{ $t('videocued.continue', {'title': nextVidTitle}) }}
    </router-link>

    <router-link
      v-if = "showSkip"
      tag = "button"
      :to = "skipLink"
      class="primary"
    >
      {{ $t('videocued.skipto', {'title': skipTitle}) }}
    </router-link>
  </div>
</template>

<script>
import { store, actions } from '../basicstore';
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'VideocuedResultNav',
  props: ['qn', 'cue'],
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
      for (let i=0; i < this.qdata.parts.length; i++) {
          if (this.qdata.parts[i].try === 0 ||
            this.qdata.parts[i].rawscore < .98
          ) {
            return 'neutral';
          }
      }
      return 'correct';
    },
    nextVidType () {
      return store.assessInfo.videocues[this.cue].hasOwnProperty('followuptitle') ?
        'followup' :
        'nextseg';
    },
    hasNextVid () {
      return (this.nextVidType === 'followup' ||
        store.assessInfo.videocues.hasOwnProperty(this.cue+1)
      );
    },
    nextVidLink () {
      if (this.nextVidType === 'followup') {
        return '/videocued/' + (this.cue+1) + '/f';
      } else {
        return '/videocued/' + (this.cue+2) + '/v';
      }
    },
    nextVidTitle () {
      if (this.nextVidType === 'followup') {
        return store.assessInfo.videocues[this.cue].followuptitle;
      } else {
        return store.assessInfo.videocues[this.cue+1].title;
      }
    },
    skipLink () {
      return '/videocued/' + (this.cue+2) + '/v';
    },
    showSkip () {
      return (this.status === 'correct' &&
        this.nextVidType === 'followup' &&
        store.assessInfo.videocues.hasOwnProperty(this.cue+1)
      );
    },
    skipTitle () {
      return store.assessInfo.videocues[this.cue+1].title;
    }
  },
}
</script>

<style>
.video-result-nav {
  margin: 8px 0;
}
</style>
