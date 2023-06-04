<template>
  <div class="settings-list">
    <div class="flexrow" v-for="(row,index) in settingRows" :key="index">
      <div>
        <icons :name="row.icon" size="small"/>
      </div>
      <div>
        <div :class="{'strikethrough':!!row.altstr}">
          {{ row.str }}
        </div>
        <div class="noticetext" v-if="!!row.altstr">
          {{ row.altstr }}
        </div>
        <div class="small subdued" v-if="!!row.sub">
          {{ row.sub }}
        </div>
        <div class="small subdued" v-if="!!row.alert">
          <icons name="alert" size="micro" />
          {{ row.alert }}
        </div>
        <div v-if="!!row.latepass" class="small subdued">
          {{ row.latepass }}
          <button @click="redeemLatePass" class="slim secondary">
            {{ row.latepass_label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import { store, actions } from '../../basicstore';

export default {
  name: 'SettingsList',
  components: {
    Icons
  },
  data: function () {
    return {
      now: new Date()
    };
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
      if (store.assessInfo.hasOwnProperty('excused')) {
        pointsobj.alert = this.$t('setlist.excused');
      }
      return pointsobj;
    },
    getDateObj () {
      var settings = store.assessInfo;
      var duedate = settings.enddate_disp;
      var dateobj = {
        icon: 'calendar',
        str: this.$t('setlist.due_at', { date: duedate })
      };
      if (settings.hasOwnProperty('original_enddate')) {
        var origduedate = settings.original_enddate_disp;
        dateobj.sub = this.$t('setlist.originally_due', { date: origduedate });
        if (settings.extended_with.type === 'latepass') {
          dateobj.sub += ' ' + this.$tc('setlist.latepass_used', settings.extended_with.n);
        } else {
          dateobj.sub += ' ' + this.$t('setlist.extension');
        }
        if (settings.exceptionpenalty > 0) {
          dateobj.alert = this.$t('setlist.penalty', { p: settings.exceptionpenalty });
        }
      }
      if (settings.can_use_latepass > 0) {
        dateobj.latepass = this.$tc('setlist.latepass_needed', settings.can_use_latepass, {
          n: settings.can_use_latepass,
          date: settings.latepass_extendto_disp
        });
        dateobj.latepass_label = this.$tc('closed.use_latepass', settings.can_use_latepass);
      }
      return dateobj;
    },
    getAttemptsObj () {
      var settings = store.assessInfo;
      var mainstr, attemptsLeftStr, substr, alertstr;

      var attemptsLeft = settings.allowed_attempts - settings.prev_attempts.length;
      let altstr = '';
      if (settings.hasOwnProperty('attemptext')) {
        if (settings.prev_attempts.length === 0) {
          altstr = this.$tc('setlist.take', attemptsLeft);
        } else {
          altstr = this.$tc('setlist.take_more', attemptsLeft);
        }
        attemptsLeft = Math.max(0, attemptsLeft - settings.attemptext);
      }

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
        substr += this.$t('setlist.keep_average');
      } else if (settings.keepscore === 'last') {
        substr += this.$t('setlist.keep_last');
      }

      const nextAttempt = settings.prev_attempts.length + 1;
      if (nextAttempt > settings.retake_penalty.n && settings.retake_penalty.penalty > 0) {
        const penalty = settings.retake_penalty.penalty * (nextAttempt - settings.retake_penalty.n);
        alertstr = this.$t('setlist.retake_penalty', { p: penalty });
      }

      return {
        icon: 'retake',
        str: mainstr,
        altstr: altstr,
        sub: substr,
        alert: alertstr
      };
    },
    getTimelimitObj () {
      var settings = store.assessInfo;
      var timeobj = {
        icon: 'timer'
      };
      if (settings.has_active_attempt && settings.timelimit_ext && settings.timelimit_ext < 0) {
        timeobj.str = this.$t('setlist.timelimit_ext_used', {
          n: Math.abs(settings.timelimit_ext)
        });
      } else {
        var mytime = settings.timelimit * settings.timelimit_multiplier;
        if (settings.overtime_grace > 0) {
          timeobj.str = this.$t(
            settings.overtime_penalty > 0 ? 'setlist.timelimit_wgrace_penalty' : 'setlist.timelimit_wgrace',
            {
              time: this.formatTimeLimit(mytime),
              grace: this.formatTimeLimit(settings.overtime_grace * settings.timelimit_multiplier),
              penalty: settings.overtime_penalty
            }
          );
        } else {
          timeobj.str = this.$t('setlist.timelimit', { time: this.formatTimeLimit(mytime) });
        }
        if (store.timelimit_restricted === 1) { // time limit restricted
          timeobj.altstr = this.$t('setlist.timelimit_restricted', {
            due: settings.enddate_disp
          });
        } else if (store.timelimit_restricted === 2) { // grace restricted
          timeobj.altstr = this.$t(
            settings.overtime_penalty > 0 ? 'setlist.timelimit_wgrace_restricted_penalty' : 'setlist.timelimit_wgrace_restricted',
            {
              time: this.formatTimeLimit(mytime),
              due: settings.enddate_disp,
              penalty: settings.overtime_penalty
            }
          );
        }
        if (settings.timelimit_multiplier > 1) {
          timeobj.sub = this.$t('setlist.timelimit_extend', {
            time: this.formatTimeLimit(settings.timelimit)
          });
        }
      }
      if (settings.has_active_attempt) {
        if (!store.timelimit_expired) {
          const expires = settings.timelimit_expires_disp;
          if (settings.overtime_grace > 0 &&
              settings.timelimit_grace > settings.timelimit_expires
          ) {
            timeobj.alert = this.$t('setlist.time_expires_wgrace', {
              date: expires,
              grace: settings.timelimit_grace_disp
            });
          } else {
            timeobj.alert = this.$t('setlist.time_expires', { date: expires });
          }
        } else if (!store.timelimit_grace_expired) {
          timeobj.alert = this.$t('setlist.time_grace_expires', {
            date: settings.timelimit_expires_disp,
            grace: settings.timelimit_grace_disp
          });
        } else if (settings.timelimit_ext && settings.timelimit_ext > 0) {
          timeobj.str = this.$t('setlist.timelimit_ext', { n: settings.timelimit_ext });
        }
      } else if (settings.timelimit_ext && settings.timelimit_ext > 0) {
        timeobj.alert = this.$t('setlist.timelimit_ext', { n: settings.timelimit_ext });
      }
      return timeobj;
    },
    formatTimeLimit (time) {
      const hrs = Math.floor(time / 3600);
      const min = Math.floor(time / 60) - hrs * 60;
      const sec = time - 60 * min - 3600 * hrs;
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
    },
    redeemLatePass () {
      var settings = store.assessInfo;
      store.confirmObj = {
        body: this.$tc('closed.latepassn', settings.latepasses_avail) + ' ' +
          this.$tc('setlist.latepass_needed', settings.can_use_latepass, {
            n: settings.can_use_latepass,
            date: settings.latepass_extendto_disp
          }),
        ok: this.$tc('closed.use_latepass', settings.can_use_latepass),
        action: () => actions.redeemLatePass()
      };
    }
  }
};
</script>

<style>
.settings-list .flexrow {
  margin-bottom: 16px;
}
.settings-list .flexrow > div:first-child svg {
  padding: 0 15px;
}
.settings-list svg {
  padding: 0 10px 0 0;
}
.strikethrough {
  text-decoration: line-through;
}
</style>
