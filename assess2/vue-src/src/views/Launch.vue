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

      <p v-if="showReset">
        {{ $t('launch.resetmsg') }}
        <br/>
        <button
          type="button"
          class="secondary"
          @click="doReset"
        >
          {{ $t('launch.doreset') }}
        </button>
      </p>
      <p v-if="aInfo.view_as_stu" class="noticetext">
        {{ $t('launch.view_as_stu', {name: aInfo.stu_fullname}) }}
      </p>
      <p>
        <button
          v-if="okToLaunch"
          type="button"
          class="primary"
          @click="startAssess"
        >
          {{ startLabel }}
        </button>
        <button
          v-if="hasExit"
          type="button"
          class="secondary"
          @click="exitAssess"
        >
          {{ $t('closed.exit') }}
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

import Icons from '@/components/widgets/Icons.vue';

import { store, actions } from '../basicstore';

export default {
  name: 'Launch',
  components: {
    SettingsList,
    PasswordEntry,
    GroupEntry,
    SummaryGbScore,
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
      if (store.timelimit_expired && store.timelimit_grace_expired &&
          this.aInfo.has_active_attempt
      ) {
        let expires = this.aInfo.timelimit_expires_disp;
        return this.$t('setlist.time_expired', { date: expires });
      } else {
        return '';
      }
    },
    okToLaunch () {
      if (!this.canViewAll &&
        this.aInfo.isgroup === 3 &&
        this.aInfo.group_members.length === 0
      ) {
        // If it's instructor-created groups and not in a group yet
        return false;
      }
      if (this.aInfo.timelimit > 0 &&
        this.aInfo.has_active_attempt &&
        ((store.timelimit_expired &&
        this.aInfo.timelimit_type === 'kick_out') ||
        (store.timelimit_grace_expired &&
        this.aInfo.timelimit_type === 'allow_overtime'))
      ) {
        return false;
      }
      return true;
    },
    showReset () {
      return this.aInfo.is_teacher &&
        !this.aInfo.view_as_stu &&
        (this.aInfo.has_active_attempt || this.aInfo.prev_attempts.length > 0);
    },
    hasExit () {
      return (window.exiturl && window.exiturl !== '');
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
    doReset () {
      actions.loadAssessData(null, true);
    },
    updateNewGroup (newMembers) {
      this.newGroupMembers = newMembers;
    },
    exitAssess () {
      window.location = window.exiturl;
    }
  },
  mounted () {
    if (this.aInfo.displaymethod === 'livepoll') {
      // inject socket javascript
      var script = document.createElement('script');
      script.src = 'https://' + this.aInfo.livepoll_server + ':3000/socket.io/socket.io.js';
      document.getElementsByTagName('head')[0].appendChild(script);
    }
  }
};
</script>
