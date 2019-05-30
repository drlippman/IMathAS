<template>
  <div class="assess-header headerpane">
    <div style="flex-grow: 1">
      <h1>{{ ainfo.name }}</h1>
    </div>
    <div>
      <button
        v-if="canRetake"
        @click = "retake"
      >
        {{ $t('launch.retake_assess') }}
      </button>
      <button
        v-if="hasExit"
        @click = "exit"
      >
        {{ $t('closed.exit') }}
      </button>
    </div>
  </div>
</template>

<script>
import { store } from '../../basicstore';

export default {
  name: 'SummaryHeader',
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    canRetake () {
      return (this.ainfo.submitby === 'by_assessment' &&
        this.ainfo.prev_attempts.length < this.ainfo.allowed_attempts);
    },
    hasExit () {
      return (window.exiturl && window.exiturl !== '' && !this.ainfo.is_lti);
    }
  },
  methods: {
    exit () {
      window.location = window.exiturl;
    },
    retake () {
      store.assessInfo = null;
      this.$router.push('/');
    }
  }
};
</script>
