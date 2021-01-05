<template>
  <div id="app" role="main">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>

    <router-view v-if="assessInfoLoaded"/>

    <error-dialog
      v-if="hasError"
      :errormsg="errorMsg"
      :lastpos="lastPos"
      @clearerror="clearError"
    />
    <due-dialog v-if="showDueDialog"/>
    <confirm-dialog
      v-if="confirmObj !== null"
      :data="confirmObj"
      :lastpos="lastPos"
      @close="closeConfirm"
    />
  </div>
</template>

<script>
import { store, actions } from './basicstore';
import ErrorDialog from '@/components/ErrorDialog.vue';
import DueDialog from '@/components/DueDialog.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import './assess2.css';

export default {
  components: {
    ErrorDialog,
    DueDialog,
    ConfirmDialog
  },
  data: function () {
    return {
      prewarned: false
    };
  },
  computed: {
    assessInfoLoaded () {
      return (store.assessInfo !== null);
    },
    hasError () {
      return (store.errorMsg !== null);
    },
    errorMsg () {
      return store.errorMsg;
    },
    confirmObj () {
      return store.confirmObj;
    },
    assessName () {
      return store.assessInfo.name;
    },
    showDueDialog () {
      return store.show_enddate_dialog;
    },
    lastPos () {
      return store.lastPos;
    }
  },
  methods: {
    beforeUnload (evt) {
      if (Object.keys(store.autosaveQueue).length > 0) {
        actions.submitAutosave(false);
      }
      var unanswered = true;
      if (store.assessInfo.hasOwnProperty('questions')) {
        let qAnswered = 0;
        const nQuestions = store.assessInfo.questions.length;
        for (const i in store.assessInfo.questions) {
          if (store.assessInfo.questions[i].try > 0) {
            qAnswered++;
          }
        }
        if (qAnswered === nQuestions) {
          unanswered = false;
        }
      }
      if (store.noUnload) {

      } else if (!store.inProgress && Object.keys(store.work).length > 0 && !this.prewarned) {
        evt.preventDefault();
        this.prewarned = false;
        return this.$t('unload.unsubmitted_work');
      } else if (!store.inProgress) {

      } else if (Object.keys(actions.getChangedQuestions()).length > 0 &&
        !this.prewarned
      ) {
        evt.preventDefault();
        this.prewarned = false;
        return this.$t('unload.unsubmitted_questions');
      } else if (store.assessInfo.submitby === 'by_assessment' &&
        store.assessInfo.has_active_attempt &&
        !this.prewarned
      ) {
        evt.preventDefault();
        if (!unanswered) {
          return this.$t('unload.unsubmitted_done_assessment');
        } else {
          return this.$t('unload.unsubmitted_assessment');
        }
      }
      this.prewarned = false;
    },
    clearError () {
      store.errorMsg = null;
    },
    closeConfirm () {
      store.confirmObj = null;
    }
  },
  created () {
    window.$(document).on('click', function (e) {
      store.lastPos = e.pageY;
    });
    window.$(document).on('focusin', function (e) {
      if (e.target && e.target.getBoundingClientRect) {
        store.lastPos = e.target.getBoundingClientRect().top;
      }
    });
    window.$(window).on('beforeunload', this.beforeUnload);
    // Give a warning if the assessment is quiz-style and not submitted
    // We're attaching this to breadcrumbs and nav buttons to avoid the default
    // beforeunload
    var self = this;
    window.$('a').not('#app a, a[href="#"]').on('click', function (e) {
      if (store.assessInfo.submitby === 'by_assessment' && store.assessInfo.has_active_attempt) {
        e.preventDefault();
        store.confirmObj = {
          body: 'unload.unsubmitted_assessment',
          action: () => {
            self.prewarned = true;
            window.location = e.currentTarget.href;
          }
        };
        return false;
      } else if (!store.inProgress && Object.keys(store.work).length > 0) {
        e.preventDefault();
        store.confirmObj = {
          body: 'unload.unsubmitted_work',
          action: () => {
            self.prewarned = true;
            window.location = e.currentTarget.href;
          }
        };
        return false;
      }
    });
  }
};
</script>

<style>
input + svg {
  vertical-align: middle;
}

.dropdown-menu a {
  text-decoration: none;
}

.fade-enter-active {
  transition: opacity .15s;
}
.fade-leave-active {
  transition: opacity .1s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
}
</style>
