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

      <p v-if="settings.can_use_latepass > 0 && this.settings.available !== 'needprereq'">
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

      <p v-if = "canViewAll && showReset">
        {{ $t('launch.resetmsg') }}
        <br/>
        <button
          type="button"
          class="primary"
          @click="doReset"
        >
          {{ $t('launch.doreset') }}
        </button>
      </p>
      <p v-if = "canViewAll">
        {{ $t('closed.teacher_preview') }}
        <br/>
        <button
          class = "primary"
          @click = "teacherPreview"
        >
          {{ $t('closed.teacher_preview_button') }}
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

import Icons from '@/components/widgets/Icons.vue';
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
          sd: this.settings.startdate_disp,
          ed: this.settings.enddate_disp
        });
      } else if (this.settings.available === 'practice' || this.settings.available === 'pastdue') {
        // past due
        return this.$t('closed.pastdue', { ed: this.settings.enddate_disp });
      } else if (this.settings.available === 'needprereq') {
        return this.$t('closed.needprereq');
      } else if (this.settings.available === 'pasttime') {
        return this.$t('closed.pasttime');
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
      if (this.settings.hasOwnProperty('timelimit_expiresin')) {
        let expires = this.settings.timelimit_expiresin * 1000;
        if (expires < 0) {
          return this.$t('closed.unsubmitted_overtime');
        } else {
          return this.$t('closed.unsubmitted_pastdue');
        }
      } else {
        return this.$t('closed.unsubmitted_pastdue');
      }
    },
    latepassExtendMsg () {
      return this.$tc('closed.latepass_needed', this.settings.can_use_latepass, {
        n: this.settings.can_use_latepass,
        date: this.settings.latepass_extendto_disp
      });
    },
    primaryButton () {
      if (this.settings.can_use_latepass > 0 && this.settings.available !== 'needprereq') {
        return this.$tc('closed.use_latepass', this.settings.can_use_latepass);
      } else if (this.settings.available === 'practice') {
        return this.$t('closed.do_practice');
      } else if (this.canViewScored) {
        return this.$t('closed.view_scored');
      } else if (window.exiturl && window.exiturl !== '') {
        return this.$t('closed.exit');
      } else {
        return '';
      }
    },
    primaryAction () {
      if (this.settings.can_use_latepass > 0 && this.settings.available !== 'needprereq') {
        return 'latepass';
      } else if (this.settings.available === 'practice') {
        return 'practice';
      } else if (this.canViewScored) {
        return 'view_scored';
      } else if (window.exiturl && window.exiturl !== '') {
        return 'exit';
      } else {
        return '';
      }
    },
    secondaryButton () {
      // Practice is secondary if we can use latepass
      if (this.settings.can_use_latepass > 0 && this.settings.available === 'practice') {
        return this.$t('closed.do_practice');
      } else if (window.exiturl && window.exiturl !== '' && this.primaryAction !== 'exit') {
        return this.$t('closed.exit');
      } else {
        return '';
      }
    },
    secondaryAction () {
      // Practice is secondary if we can use latepass
      if (this.settings.can_use_latepass > 0 && this.settings.available === 'practice') {
        return 'practice';
      } else if (window.exiturl && window.exiturl !== '') {
        return 'exit';
      } else {
        return '';
      }
    },
    canViewScored () {
      return (this.settings.is_lti &&
        this.settings.viewingb !== 'never' &&
        (this.settings.available === 'practice' || this.settings.available === 'pastdue')
      );
    },
    canViewAll () {
      return store.assessInfo.can_view_all;
    },
    showReset () {
      return store.assessInfo.hasOwnProperty('show_reset') ||
      (
        store.assessInfo.is_teacher &&
        !store.assessInfo.view_as_stu &&
        (store.assessInfo.has_active_attempt ||
          store.assessInfo.prev_attempts.length > 0 ||
          store.assessInfo.has_unsubmitted_scored
        )
      )
    }
  },
  methods: {
    handlePrimary () {
      if (this.primaryAction === 'latepass') {
        // redeem latepass
        actions.redeemLatePass();
      } else if (this.primaryAction === 'practice') {
        // start practice mode
        actions.startAssess(true, '', []);
      } else if (this.primaryAction === 'view_scored') {
        // view scored assess
        if (this.settings.can_use_latepass === 0 ||
          window.confirm(this.$t('closed.confirm'))
        ) {
          window.location = store.APIbase + 'gbviewassess.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
        }
      } else if (this.primaryAction === 'exit') {
        // exit assessment
        window.location = window.exiturl;
      }
    },
    handleSecondary () {
      if (this.secondaryAction === 'practice' &&
        (this.settings.can_use_latepass === 0 ||
        window.confirm(this.$t('closed.confirm')))
      ) {
        // start practice mode
        actions.startAssess(true, '', []);
      } else if (this.secondaryAction === 'exit') {
        // exit assessment
        window.location = window.exiturl;
      }
    },
    teacherPreview () {
      actions.startAssess(false, '', []);
    },
    doReset () {
      actions.loadAssessData(null, true);
    },
    endAssess () {
      actions.endAssess();
    }
  }
};
</script>
