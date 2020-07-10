<template>
  <div class="assess-header headerpane">
    <div style="flex-grow: 1">
      <h1>{{ ainfo.name }}</h1>
    </div>
    <div>
      <button
        v-if="retakeLabel != ''"
        @click = "retake"
      >
        {{ retakeLabel }}
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
    retakeLabel () {
      if (this.ainfo.submitby === 'by_assessment' &&
        this.ainfo.prev_attempts.length < this.ainfo.allowed_attempts
      ) {
        return this.$t('launch.retake_assess');
      } else if (this.ainfo.submitby === 'by_question' &&
        store.inAssess &&
        this.ainfo.has_active_attempt
      ) {
        return this.$t('launch.continue_assess');
      } else {
        return '';
      }
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
