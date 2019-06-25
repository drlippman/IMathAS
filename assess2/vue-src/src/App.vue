<template>
  <div id="app" role="main" aria-live="polite">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>

    <router-view v-if="assessInfoLoaded"/>

    <error-dialog
      v-if="hasError"
      :errormsg="errorMsg"
      @clearerror="clearError"
    />
  </div>
</template>

<script>
import { store, actions } from './basicstore';
import ErrorDialog from '@/components/ErrorDialog.vue';
import './assess2.css';

export default {
  components: {
    ErrorDialog
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
    assessName () {
      return store.assessInfo.name;
    }
  },
  methods: {
    beforeUnload (evt) {
      if (store.autosaveQueue.length > 0) {
        actions.submitAutosave(false);
      }
      var unanswered = true;
      if (store.assessInfo.hasOwnProperty('questions')) {
        let qAnswered = 0;
        let nQuestions = store.assessInfo.questions.length;
        for (let i in store.assessInfo.questions) {
          if (store.assessInfo.questions[i].try > 0) {
            qAnswered++;
          }
        }
        if (qAnswered === nQuestions) {
          unanswered = false;
        }
      }
      if (Object.keys(actions.getChangedQuestions()).length > 0) {
        evt.preventDefault();
        return this.$t('unload.unsubmitted_questions');
      } else if (store.assessInfo.submitby === 'by_assessment' && !unanswered) {
        evt.preventDefault();
        return this.$t('unload.unsubmitted_assessment');
      }
    },
    clearError () {
      store.errorMsg = null;
    }
  },
  created () {
    window.$(window).on('beforeunload', this.beforeUnload);
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
