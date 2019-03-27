<template>
  <div class="home flexpanes">
    <div style="flex-grow: 1">
      <h1>{{ settings.name }}</h1>

      <p>{{ closedMessage }}</p>

      <p v-if = "hasActive">
        {{ hasActiveMsg }}
        <br/>
        <button
          type="button"
          class="primary"
          @click="endAssess"
        >
          {{ $t('closed.submit_now') }}
        </button>
      </p>

      <p v-if="settings.can_use_latepass > 0">
        {{ $tc('closed.latepassn', settings.latepasses_avail) }}
        <br/>
        {{ latepassExtendMsg }}
      </p>

      <p v-if="settings.available === 'practice' && settings.can_use_latepass === 0">
        {{ $t('closed.practice_no_latepass') }}
      </p>
      <p v-else-if="settings.available === 'practice' && settings.can_use_latepass > 0">
        {{ $t('closed.practice_w_latepass') }}
        <br/>
        <icons name="alert" size="micro" />
        {{ $t('closed.will_block_latepass') }}
      </p>

      <p v-if="settings.is_lti && settings.viewingb != 'never'">
        {{ $t('closed.can_view_scored') }}
        <span v-if="settings.can_use_latepass > 0">
          <br/>
          <icons name="alert" size="micro" />
          {{ $t('closed.will_block_latepass') }}
        </span>
      </p>

      <p>
        <button
          v-if = "primaryButton != ''"
          class = "primary"
          @click = "handlePrimary"
        >
          {{ primaryButton }}
        </button>
        <button
          v-if = "secondaryButton != ''"
          class = "secondarybtn"
          @click = "handleSecondary"
        >
          {{ secondaryButton }}
        </button>
      </p>

    </div>
    <div v-if="settings.hasOwnProperty('prev_attempts') && settings.prev_attempts.length > 0" >
      <summary-gb-score />
      <previous-attempts :caption = "$t('prev.scored_attempts')" />
    </div>
  </div>
</template>

<script>

import Icons from '@/components/Icons.vue';
import PreviousAttempts from '@/components/PreviousAttempts.vue';
import SummaryGbScore from '@/components/summary/SummaryGbScore.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'Closed',
  components: {
    Icons,
    PreviousAttempts,
    SummaryGbScore
  },
  computed: {
    settings () {
      return store.assessInfo;
    },
    closedMessage () {
      if (this.settings.available === 'hidden') {
        // hard hidden
        return this.$t('closed.hidden');
      } else if (this.settings.available === 'notyet') {
        // not yet available
        return this.$t('closed.notyet', {
          sd: this.$d(new Date(this.settings.startdate * 1000), 'long'),
          ed: this.$d(new Date(this.settings.enddate * 1000), 'long')
        });
      } else if (this.settings.available === 'practice' || this.settings.available === 'pastdue') {
        // past due
        return this.$t('closed.pastdue', { ed: this.$d(new Date(this.settings.enddate * 1000), 'long') });
      } else if (this.settings.available === 'needprereq') {
        return this.$t('closed.needprereq');
      } else if (this.settings.has_active_attempt === false && this.settings.can_retake === false) {
        return this.$t('closed.no_attempts');
      }
      return '';
    },
    hasActive () {
      return (this.settings.submitby === 'by_assessment' &&
        ((this.settings.available === 'yes' && this.settings.has_active_attempt) ||
        (this.settings.available !== 'yes' && this.settings.has_unsubmitted_scored))
      );
    },
    hasActiveMsg () {
      if (this.settings.hasOwnProperty('timelimit_expires')) {
        let expires = new Date(this.settings.timelimit_expires * 1000);
        let now = new Date();
        if (expires < now) {
          return this.$t('closed.unsubmitted_overtime');
        }
      } else {
        return this.$t('closed.unsubmitted_pastdue');
      }
    },
    latepassExtendMsg () {
      return this.$tc('closed.latepass_needed', this.settings.can_use_latepass, {
        n: this.settings.can_use_latepass,
        date: this.$d(new Date(this.settings.latepass_extendto * 1000), 'long')
      });
    },
    primaryButton () {
      if (this.settings.can_use_latepass > 0) {
        return this.$tc('closed.use_latepass', this.settings.can_use_latepass);
      } else if (this.settings.available === 'practice') {
        return this.$t('closed.do_practice');
      } else if (this.canViewScored) {
        return this.$t('closed.view_scored');
      } else {
        return this.$t('closed.exit');
      }
    },
    secondaryButton () {
      // Practice is secondary if we can use latepass
      if (this.settings.can_use_latepass > 0 && this.settings.available === 'practice') {
        return this.$t('closed.do_practice');
      } else {
        return '';
      }
    },
    canViewScored () {
      return (this.settings.is_lti &&
        this.settings.viewingb !== 'never' &&
        (this.settings.available === 'practice' || this.settings.available === 'pastdue')
      );
    }
  },
  methods: {
    handlePrimary () {
      if (this.settings.can_use_latepass > 0) {
        // redeem latepass
      } else if (this.settings.available === 'practice') {
        // start practice mode
        actions.startAssess(true, '', []);
      } else if (this.canViewScored) {

      } else {
        // exit assessment
      }
    },
    handleSecondary () {
      if (this.settings.can_use_latepass === 0 ||
        window.confirm(this.$t('closed.confirm'))
      ) {
        if (this.settings.available === 'practice') {
          // start practice mode
        }
      }
    },
    endAssess () {
      actions.endAssess();
    }
  }
};
</script>
