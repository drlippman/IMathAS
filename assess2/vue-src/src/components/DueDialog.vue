<template>
  <div class="fullwrap" ref="wrap">
    <div class="dialog-overlay" tabindex="-1">
      <div
        class="dialog"
        ref="dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="duedialog_hdr"
        aria-describedby="duedialog_body"
        tabindex="-1"
        @click.stop
      >
        <div class="pane-header flexrow" id="duedialog_hdr">
          <div style="flex-grow: 1">
            <icons name="alert" />
            {{ $t('duedialog.due') }}
          </div>
        </div>
        <div class="pane-body" id="duedialog_body">
          <p>
            {{ $t('duedialog.nowdue') }}
          </p>
          <p v-if="settings.can_use_latepass > 0">
            {{ $tc('closed.latepassn', settings.latepasses_avail) }}
            <br/>
            {{ latepassExtendMsg }}
            <br/>
            <button @click="useLatepass" class="primary">
              {{ $tc('closed.use_latepass', this.settings.can_use_latepass) }}
            </button>
          </p>
          <p v-if="hasUnsubmitted">
            {{ unsubmittedMessage }}
            <br/>
            <button @click="submitNow" class="primary">
              {{ $t('duedialog.submitnow') }}
            </button>
          </p>
          <p>
            <button
              :class="{primary: exitPrimary, secondary: !exitPrimary}"
              @click="exit"
            >
              {{ $t('closed.exit') }}
            </button>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import A11yDialog from './a11y-dialog';
import { store, actions } from '../basicstore';

export default {
  name: 'DueDialog',
  data: function () {
    return {
      dialog: null
    };
  },
  components: {
    Icons
  },
  computed: {
    settings () {
      return store.assessInfo;
    },
    latepassExtendMsg () {
      return this.$tc('closed.latepass_needed', this.settings.can_use_latepass, {
        n: this.settings.can_use_latepass,
        date: this.settings.latepass_extendto_disp
      });
    },
    hasUnsubmitted () {
      return (this.settings.submitby === 'by_assessment' ||
        Object.keys(actions.getChangedQuestions()).length > 0);
    },
    unsubmittedMessage () {
      if (this.settings.submitby === 'by_question') {
        return this.$t('duedialog.byq_unsubmitted');
      } else {
        return this.$t('duedialog.bya_unsubmitted');
      }
    },
    exitPrimary () {
      return (!this.hasUnsubmitted && !this.canUseLatePass);
    }
  },
  methods: {
    closeDialog () {
      store.show_enddate_dialog = false;
    },
    submitNow () {
      actions.endAssess(() => {
        this.exit();
      });
    },
    useLatepass () {
      actions.redeemLatePass(() => {
        store.show_enddate_dialog = false;
      });
    },
    exit () {
      this.closeDialog();
      if (window.exiturl && window.exiturl !== '') {
        store.noUnload = true;
        window.location = window.exiturl;
      } else {
        actions.routeToStart();
      }
    }
  },
  mounted () {
    const lastHeight = store.lastPos;
    window.$(document).on('keyup.dialog', (event) => {
      if (event.key === 'Escape') {
        this.closeDialog();
      }
    });
    this.dialog = new A11yDialog(this.$refs.wrap);
    this.dialog.show();
    if (window.innerHeight > 2000 && lastHeight !== null) {
      this.$refs.dialog.style.top = Math.max(20, lastHeight - this.$refs.dialog.offsetHeight) + 'px';
    }
  },
  beforeDestroy () {
    window.$(document).off('keyup.dialog');
    this.dialog.destroy();
  }
};
</script>

<style>

</style>
