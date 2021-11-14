<template>
  <div class="home flexpanes">
    <div style="flex-grow: 1">
      <h1>{{ settings.name }}</h1>

      <p>{{ closedMessage }}</p>

      <p v-if="showTutorLinks">
        {{ $t('launch.gblinks') }}:
        <a :href="settings.tutor_gblinks[0]" target="_blank">{{ $t('launch.scorelist') }}</a> &nbsp;
        <a :href="settings.tutor_gblinks[1]" target="_blank">{{ $t('launch.itemanalysis') }}</a>
      </p>

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
      <p v-else-if="canAddWork">
        {{ $t('work.add_prev') }}<br/>
        <button
          type="button"
          class="secondary"
          @click="$router.push('/showwork')"
        >
          {{ $t('work.add') }}
        </button>
      </p>

      <p v-if="settings.can_use_latepass > 0 && showLatePassOffer">
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
        <span v-if="settings.hasOwnProperty('excused')">
          <br />
          <icons name="alert" size="micro" />
          {{ $t('setlist.excused') }}
        </span>
      </p>

      <p v-if="canViewScored">
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
          v-if = "canViewScored"
          class = "secondarybtn"
          @click = "handleViewScored"
        >
          {{ $t('closed.view_scored') }}
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
        <button
          class = "secondary"
          @click = "teacherPreviewAll"
        >
          {{ $t('closed.teacher_previewall_button') }}
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
        return this.$t('closed.needprereq') + ' ' +
          this.$t('closed.prereqreq', {
            score: this.settings.reqscorevalue,
            name: this.settings.reqscorename
          });
      } else if (this.settings.hasOwnProperty('pasttime')) {
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
        const expires = this.settings.timelimit_expiresin * 1000;
        if (expires < 0) {
          return this.$t('closed.unsubmitted_overtime');
        } else {
          return this.$t('closed.unsubmitted_pastdue');
        }
      } else {
        return this.$t('closed.unsubmitted_pastdue');
      }
    },
    showLatePassOffer () {
      return (this.settings.available !== 'needprereq' &&
        !this.settings.hasOwnProperty('pasttime'));
    },
    latepassExtendMsg () {
      return this.$tc('closed.latepass_needed', this.settings.can_use_latepass, {
        n: this.settings.can_use_latepass,
        date: this.settings.latepass_extendto_disp
      });
    },
    primaryAction () {
      if (this.settings.can_use_latepass > 0 && this.showLatePassOffer) {
        return 'latepass';
      } else if (this.settings.available === 'practice') {
        return 'practice';
      } else if (window.exiturl && window.exiturl !== '') {
        return 'exit';
      } else {
        return '';
      }
    },
    primaryButton () {
      if (this.primaryAction === 'latepass') {
        return this.$tc('closed.use_latepass', this.settings.can_use_latepass);
      } else if (this.primaryAction === 'practice') {
        return this.$t('closed.do_practice');
      } else if (this.primaryAction === 'exit') {
        return this.$t('closed.exit');
      } else {
        return '';
      }
    },
    secondaryAction () {
      // Practice is secondary if something else is primary
      if (this.primaryAction !== 'practice' &&
        this.settings.available === 'practice'
      ) {
        return 'practice';
      } else if (window.exiturl && window.exiturl !== '' && this.primaryAction !== 'exit') {
        return 'exit';
      } else {
        return '';
      }
    },
    secondaryButton () {
      // Practice is secondary if we can use latepass
      if (this.secondaryAction === 'practice') {
        return this.$t('closed.do_practice');
      } else if (this.secondaryAction === 'exit') {
        return this.$t('closed.exit');
      } else {
        return '';
      }
    },
    canViewScored () {
      return (
        !this.canViewAll &&
        this.settings.viewingb !== 'never' &&
        this.settings.prev_attempts.length > 0 &&
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
      );
    },
    canAddWork () {
      return (store.assessInfo.showwork_after);
    },
    showTutorLinks () {
      return store.assessInfo.hasOwnProperty('tutor_gblinks');
    }
  },
  methods: {
    handleViewScored () {
      // view scored assess
      if (this.settings.can_use_latepass === 0) {
        window.location = store.APIbase + 'gbviewassess.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
      } else {
        store.confirmObj = {
          body: 'closed.confirm',
          action: () => {
            window.location = store.APIbase + 'gbviewassess.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid;
          }
        };
      }
    },
    handlePrimary () {
      if (this.primaryAction === 'latepass') {
        // redeem latepass
        actions.redeemLatePass();
      } else if (this.primaryAction === 'practice') {
        // start practice mode
        actions.startAssess(true, '', []);
      } else if (this.primaryAction === 'exit') {
        // exit assessment
        window.location = window.exiturl;
      }
    },
    handleSecondary () {
      if (this.secondaryAction === 'practice' &&
        this.settings.can_use_latepass === 0
      ) {
        // start practice mode
        actions.startAssess(true, '', []);
      } else if (this.secondaryAction === 'practice') {
        store.confirmObj = {
          body: 'closed.confirm',
          action: () => { actions.startAssess(true, '', []); }
        };
      } else if (this.secondaryAction === 'exit') {
        // exit assessment
        window.location = window.exiturl;
      }
    },
    teacherPreview () {
      actions.startAssess(false, '', []);
    },
    teacherPreviewAll () {
      actions.startAssess(false, '', [], null, true);
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
