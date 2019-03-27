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
    },
    queryString () {
      return '?cid=' + store.cid + '&aid=' + store.aid;
    }
  },
  methods: {
    beforeUnload () {
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
      if (store.assessFormIsDirty.length > 0) {
        return this.$t('unload.unsubmitted_questions');
      } else if (store.assessInfo.submitby === 'by_assessment' && !unanswered) {
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
input[type=submit],input[type=button], button, a.abutton {
  padding: 3px 12px;
  height: auto;
}
input {
  border: 1px solid #999;
  padding: 4px 6px;
  border-radius: 4px;
  margin: 1px 0;
}
button.slim {
  padding: 0px 12px;
}
button.nopad {
  padding: 0;
}
button.plain {
  border: 0;
  background-color: #fff;
}
button.plain:hover, button.plain:active {
  background-color: #EDF4FC;
}
input[type=submit].primary,input[type=button].primary, button.primary, a.abutton.primary {
  color: #fff;
  background-color: #1E74D1;
}
input[type=submit].primary:hover, button.primary:hover,input[type=button].primary:hover, a.abutton.primary:hover {
  background-color: #175aa2;
}
input[type=submit].primary:focus, button.primary:focus,input[type=button].primary:focus, a.abutton.primary:focus {
  background-color: #175aa2;
}
input[type=submit].secondarybtn,input[type=button].secondarybtn, button.secondarybtn {
  color: #000;
  background-color: #eee;
}
input[type=submit].secondarybtn:hover,input[type=button].secondarybtn:hover, button.secondarybtn:hover {
  background-color: #ddd;
}
input[type=submit].secondarybtn:focus,input[type=button].secondarybtn:focus, button.secondarybtn:focus {
  background-color: #ddd;
}
.dropdown-menu a {
  text-decoration: none;
}
.subdued {
  color: #aaa;
}
.flexrow {
  display: flex;
  flex-flow: row nowrap;
}
.headerpane {
  border-bottom: 1px solid #ccc;
  padding-bottom: 10px;
}
.no-margin-top {
  margin-top: 0;
}
.ind1 {
  margin-left: 20px;
}
.med-below {
  margin-bottom: 16px;
}
.med-left {
  margin-left: 16px;
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
