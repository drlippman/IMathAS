<template>
  <div class="home flexpanes">
    <div style="flex-grow: 1">
      <h1>{{ aInfo.name }}</h1>

      <div class="med-below" v-html="aInfo.summary"></div>

      <settings-list />

      <div class="settings-list">
        <div class="flexrow" v-if="aInfo.has_password">
          <div>
            <icons name="lock" size="small"/>
          </div>
          <password-entry v-model="password"/>
        </div>
        <div class="flexrow" v-if="aInfo.isgroup > 0">
          <div>
            <icons name="group" size="small" />
          </div>
          <group-entry @update-new-group="updateNewGroup" />
        </div>
      </div>

      <p
        class = "noticetext"
        v-if = "errorMsg !== null"
      >
        {{ errorMsg }}
      <p>

      <p
        v-if = "timeLimitExpired !== ''"
        class = "noticetext"
      >
        <icons name="alert" />
        {{ timeLimitExpired }}
        <br/>
        <button
          type="button"
          class="primary"
          @click="endAssess"
        >
          {{ $t('closed.submit_now') }}
        </button>
      </p>

      <p v-if="okToLaunch">
        <button
          type="button"
          class="primary"
          @click="startAssess"
        >
          {{ startLabel }}
        </button>
      </p>
    </div>
    <div v-if="aInfo.hasOwnProperty('prev_attempts') && aInfo.prev_attempts.length > 0" >
      <summary-gb-score />
      <previous-attempts :caption = "$t('prev.previous_attempts')" />
    </div>
  </div>
</template>

<script>
import SettingsList from '@/components/launch/SettingsList.vue';
import PasswordEntry from '@/components/launch/PasswordEntry.vue';
import GroupEntry from '@/components/launch/GroupEntry.vue';
import PreviousAttempts from '@/components/PreviousAttempts.vue';
import SummaryGbScore from '@/components/summary/SummaryGbScore.vue';


import Icons from '@/components/Icons.vue';

import { store, actions } from '../basicstore';

export default {
  name: 'Launch',
  components: {
    SettingsList,
    PasswordEntry,
    GroupEntry,
    PreviousAttempts,
    Icons
  },
  data: function () {
    return {
      password: '',
      newGroupMembers: []
    };
  },
  computed: {
    aInfo () {
      return store.assessInfo;
    },
    errorMsg () {
      if (store.errorMsg === null) {
        return null;
      }
      return this.$t('error.' + store.errorMsg);
    },
    startLabel () {
      if (this.aInfo.has_active_attempt) {
        return this.$t('launch.continue_assess');
      } else if (this.aInfo.submitby === 'by_assessment' &&
        this.aInfo.prev_attempts.length > 0
      ) {
        return this.$t('launch.retake_assess');
      } else {
        return this.$t('launch.start_assess');
      }
    },
    timeLimitExpired () {
      if (store.timelimit_expired) {
        let expires = new Date(this.aInfo.timelimit_expires * 1000);
        return this.$t('setlist.time_expired', {date: this.$d(expires, 'long')});
      } else {
        return '';
      }
    },
    okToLaunch () {
      if (this.aInfo.isgroup === 3 && this.aInfo.group_members.length === 0) {
        // If it's instructor-created groups and not in a group yet
        return false;
      }
      if (this.aInfo.timelimit > 0 &&
        store.timelimit_expired &&
        this.aInfo.timelimit_type == 'kick_out' &&
        this.aInfo.has_active_attempt
      ) {
        return false;
      }
      return true;
    }
  },
  methods: {
    startAssess () {
      let timelimit = this.aInfo.timelimit;
      if (timelimit === 0 || confirm(this.$t('launch.timewarning'))) {
        let pwval = this.password;
        this.password = '';
        actions.startAssess(false, pwval, this.newGroupMembers);
      }
    },
    endAssess () {
      actions.endAssess();
    },
    updateNewGroup (newMembers) {
      this.newGroupMembers = newMembers;
    }
  }
};
</script>
