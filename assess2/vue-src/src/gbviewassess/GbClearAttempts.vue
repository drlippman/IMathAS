<template>
  <div
    v-if="showModal"
    class="modalwrap"
    @keydown.esc = "close"
  >
    <div class="modal" role="alertdialog" aria-modal="true">
      <div v-if="showType === 'all'">
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
      <div v-else-if="showType === 'attempt'">
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
      <div v-else-if="showType === 'qver'">
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
        <p>
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

// TODO: Need to trap tab focus inside dialog
// TODO: Need to set focus in modal on opening

export default {
  name: 'GbClearAttempts',
  data: function () {
    return {
      type: 0
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
      let avercnt = store.assessInfo.assess_versions.length;
      if (store.assessInfo.has_practice) {
        avercnt--;
      }
      return (store.curAver === avercnt);
    }
  },
  methods: {
    close () {
      store.clearAttempts.show = false;
    },
    doAction () {
      actions.clearAttempt(this.type);
    }
  },
  updated () {
    if (store.clearAttempts.show) {
      window.$('.modal').find('input').focus();
    }
  }
};
</script>

<style>
.modalwrap {
  position: fixed;
  display: flex;
  align-items: center;
  z-index: 9998;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, .5);
  transition: opacity .3s ease;
}
.modal {
  width: 300px;
  margin: auto;
  background-color: #fff;
  padding: 12px;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
}
</style>
