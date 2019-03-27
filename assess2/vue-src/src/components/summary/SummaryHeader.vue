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
        v-if="!ainfo.is_lti"
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
    }
  },
  methods: {
    exit () {

    },
    retake () {
      store.assessInfo = null;
      this.$router.push('/' + store.queryString);
    }
  }
}
</script>
