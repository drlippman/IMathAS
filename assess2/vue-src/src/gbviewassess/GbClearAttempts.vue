<template>
  <div
    v-if="showModal"
    class="dialog-overlay"
    ref = "wrap"
  >
    <div
      class="dialog pane-body"
      role="alertdialog"
      aria-modal="true"
      :aria-label="$t('gradebook.clear_hdr')"
      aria-describedby="clearoptions"
      tabindex="-1"
    >
      <div v-if="showType === 'all'" class="clearoptions">
        <p>
          <label>
            <input type="radio" value="0" v-model="type">
            {{ $t('gradebook.clear_completely_msg') }}
          </label>
        </p>
        <p v-if="isByQuestion">
          <label>
            <input type="radio" value="1" v-model="type">
            {{ $t('gradebook.clear_all_work_msg') }}
          </label>
        </p>
      </div>
      <div v-else-if="showType === 'attempt'" class="clearoptions">
        <p>
          <label>
            <input type="radio" value="0" v-model="type">
            {{ $t('gradebook.clear_attempt_regen_msg') }}
          </label>
        </p>
        <p v-if="isLastAttempt">
          <label>
            <input type="radio" value="1" v-model="type">
            {{ $t('gradebook.clear_attempt_msg') }}
          </label>
        </p>
      </div>
      <div v-else-if="showType === 'qver'" class="clearoptions">
        <p v-if="isByQuestion">
          <label>
            <input type="radio" value="0" v-model="type">
            {{ $t('gradebook.clear_qver_regen_msg') }}
          </label>
        </p>
        <p v-else>
          <label>
            <input type="radio" value="0" v-model="type">
            {{ $t('gradebook.clear_qver_regen_msg2') }}
          </label>
        </p>
        <p v-if="isLastQAttempt">
          <label>
            <input type="radio" value="1" v-model="type">
            {{ $t('gradebook.clear_qver_msg') }}
          </label>
        </p>
      </div>
      <p class="noticetext">
        {{ $t('gradebook.clear_warning') }}
      </p>
      <div class="flexrow" style="justify-content: space-between;">
        <button class="primary" @click="close">
          Cancel
        </button>
        <button class="secondary" @click="doAction">
          Continue
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { store, actions } from './gbstore';
import A11yDialog from '../components/a11y-dialog';

export default {
  name: 'GbClearAttempts',
  data: function () {
    return {
      type: 0,
      dialog: null
    };
  },
  computed: {
    showModal () {
      return store.clearAttempts.show;
    },
    showType () {
      return store.clearAttempts.type;
    },
    isByQuestion () {
      return (store.assessInfo.submitby === 'by_question');
    },
    isLastAttempt () {
      let avercnt = store.assessInfo.assess_versions.length - 1;
      if (store.assessInfo.has_practice) {
        avercnt--;
      }
      return (store.curAver === avercnt);
    },
    isLastQAttempt () {
      const qvercnt = store.assessInfo.assess_versions[store.curAver].questions[store.clearAttempts.qn].length - 1;
      return (store.curQver[store.clearAttempts.qn] === qvercnt);
    }
  },
  methods: {
    close () {
      store.clearAttempts.show = false;
      window.$(document).off('keyup.dialog');
      this.dialog.destroy();
    },
    doAction () {
      actions.clearAttempt(this.type);
    }
  },
  updated () {
    if (store.clearAttempts.show) {
      window.$(document).on('keyup.dialog', (event) => {
        if (event.key === 'Escape') {
          this.close();
        }
      });
      this.dialog = new A11yDialog(this.$refs.wrap);
      this.dialog.show();
    }
  }
};
</script>

<style>

</style>
