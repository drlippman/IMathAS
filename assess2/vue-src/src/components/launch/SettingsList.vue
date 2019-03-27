<template>
  <div class="settings-list">
    <div class="flexrow" v-for="(row,index) in settingRows" :key="index">
      <div>
        <icons :name="row.icon" size="small"/>
      </div>
      <div>
        <div>
          {{ row.str }}
        </div>
        <div class="small subdued" v-if="!!row.sub">
          {{ row.sub }}
        </div>
        <div class="small subdued" v-if="!!row.alert">
          <icons name="alert" size="micro" />
          {{ row.alert }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/Icons.vue';
import { store } from '../../basicstore';

export default {
  name: 'SettingsList',
  components: {
    Icons
  },
  data: function () {
    return {
      now: new Date
    }
  },
  computed: {
    settingRows () {
      var out = [];
      var settings = store.assessInfo;

      if (settings.in_practice) {
        out.push({
          icon: 'alert',
          str: this.$t('setlist.practice')
        });
      } else {
        // points possible
        out.push(this.getPointsObj());

        // due date
        if (settings.enddate < 2000000000) {
          out.push(this.getDateObj());
        }

        // retakes
        if (settings.submitby === 'by_assessment' && settings.allowed_attempts > 1) {
          out.push(this.getAttemptsObj());
        }

        // time limit
        if (settings.timelimit > 0) {
          out.push(this.getTimelimitObj());
        }
      }
      return out;
    }
  },
  methods: {
    getPointsObj () {
      var settings = store.assessInfo;
      var pointsobj = {
        icon: 'square-check',
        str: this.$t('setlist.points_possible', { pts: settings.points_possible })
      };
      return pointsobj;
    },
    getDateObj () {
      var settings = store.assessInfo;
      var duedate = this.$d(new Date(settings.enddate * 1000), 'long');
      var dateobj = {
        icon: 'calendar',
        str: this.$t('setlist.due_at', { date: duedate })
      };
      if (settings.hasOwnProperty('original_enddate')) {
        var origduedate = this.$d(new Date(settings.original_enddate * 1000), 'long');
        dateobj.sub = this.$t('setlist.originally_due', { date: origduedate });
        if (settings.extended_with.type === 'latepass') {
          dateobj.sub += this.$tc('setlist.latepass_used', settings.extended_with.n);
        } else {
          dateobj.sub += this.$t('setlist.extension');
        }
        if (settings.exceptionpenalty > 0) {
          dateobj.alert = this.$t('setlist.penalty', { p: settings.exceptionpenalty });
        }
      }
      return dateobj;
    },
    getAttemptsObj () {
      var settings = store.assessInfo;
      var mainstr, attemptsLeftStr, substr, alertstr;

      var attemptsLeft = settings.allowed_attempts - settings.prev_attempts.length;
      if (settings.prev_attempts.length === 0) {
        attemptsLeftStr = this.$tc('setlist.take', attemptsLeft);
      } else {
        attemptsLeftStr = this.$tc('setlist.take_more', attemptsLeft);
      }

      if (settings.has_active_attempt) {
        mainstr = this.$t('setlist.attempt_inprogress');
        substr = this.$t('setlist.cur_attempt_n_of', {
          n: settings.prev_attempts.length + 1,
          nmax: settings.allowed_attempts
        });
      } else {
        mainstr = attemptsLeftStr;
        substr = '';
      }

      if (settings.keepscore === 'best') {
        substr += this.$t('setlist.keep_highest');
      } else if (settings.keepscore === 'average') {
        substr += this.$t('setlist.keep_highest');
      } else if (settings.keepscore === 'last') {
        substr += this.$t('setlist.keep_last');
      }

      if (settings.prev_attempts.length > 0 && settings.retake_penalty > 0) {
        let penalty = settings.prev_attempts.length * settings.retake_penalty;
        alertstr = this.$t('retake_penalty', { p: penalty });
      }

      return {
        icon: 'retake',
        str: mainstr,
        sub: substr,
        alert: alertstr
      };
    },
    getTimelimitObj () {
      var settings = store.assessInfo;
      var timeobj = {
        icon: 'timer'
      };
      var mytime = settings.timelimit * settings.timelimit_multiplier;
      timeobj.str = this.$t('setlist.timelimit', { time: this.formatTimeLimit(mytime) });
      if (settings.timelimit_multiplier > 1) {
        timeobj.sub = this.$t('setlist.timelimit_extend', { time: this.formatTimeLimit(settings.timelimit) });
      }
      if (settings.has_active_attempt) {
        if (!store.timelimit_expired) {
          let expires = new Date(settings.timelimit_expires * 1000);
          timeobj.alert = this.$t('setlist.time_expires', {date: this.$d(expires, 'long')});
        }
      }
      return timeobj;
    },
    formatTimeLimit (time) {
      let hrs = Math.floor(time / 3600);
      let min = Math.floor(time / 60) - hrs * 60;
      let sec = time - 60 * min - 3600 * hrs;
      let out = '';
      if (hrs > 0) {
        out += this.$tc('hours', hrs);
      }
      if (min > 0) {
        if (out !== '') { out += ' '; }
        out += this.$tc('minutes', min);
      }
      if (sec > 0) {
        if (out !== '') { out += ' '; }
        out += this.$tc('seconds', sec);
      }
      return out;
    }
  }
};
</script>

<style>
.settings-list .flexrow {
  margin-bottom: 16px;
}
.settings-list svg {
  padding: 0 15px;
}
.settings-list .small svg {
  padding: 0 10px 0 0;
}
.subdued {
  color: #637381;
}
</style>
