<template>
  <div id="app">
    <div v-if="!assessInfoLoaded">
      {{ $t('loading') }}
    </div>

    <router-view v-if="assessInfoLoaded"/>

    <error-dialog v-if="hasError" />
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

.fade-enter-active, .fade-leave-active {
  transition: opacity .5s;
}
.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */ {
  opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  transition-duration: 0.5s;
  transition-property: height, opacity, transform;
  transition-timing-function: cubic-bezier(0.55, 0, 0.1, 1);
  overflow: hidden;
}

.slide-left-enter,
.slide-right-leave-active {
  opacity: 0;
  transform: translate(2em, 0);
}

.slide-left-leave-active,
.slide-right-enter {
  opacity: 0;
  transform: translate(-2em, 0);
}
</style>
