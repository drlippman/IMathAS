<template>
  <button type="button" class="plain" id="timerbox" @click="toggleShow">
    <icons name="timer" size="small" />
    <span
      v-if="open"
      :class = "{noticetext: hours === 0 && 60*minutes+seconds < warningTime}"
    >
      {{ timeString }}
    </span>
    <icons v-if="open" name="close" size="small" color="subdued" />
    <span v-else class="sronly">
      {{ $t('timer.show') }}
    </span>
  </button>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'Timer',
  props: ['end', 'total', 'grace'],
  components: {
    Icons
  },
  data: function () {
    return {
      hours: 0,
      minutes: 0,
      seconds: 0,
      timeString: '',
      interval: null,
      open: true,
      gaveWarning: false
    };
  },
  created () {
    this.updateTimer();
    this.interval = setInterval(this.updateTimer, 1000);
  },
  mounted () {
    var s = window.$('#timerbox');
    var pos = s.offset();
    window.$(window).scroll(function () {
      var windowpos = window.$(window).scrollTop();
      if (windowpos >= pos.top) {
        s.addClass('sticky');
      } else {
        s.removeClass('sticky');
      }
    });
  },
  beforeUnmount () {
    clearInterval(this.interval);
  },
  computed: {
    warningTime () {
      return Math.max(60, Math.min(0.05 * this.total, 300));
    }
  },
  methods: {
    updateTimer: function () {
      const now = new Date().getTime();
      this.timeString = '';
      let remaining = Math.max(0, this.end - now);
      if (remaining === 0 && this.grace > 0) {
        remaining = Math.max(0, this.grace - now);
        this.timeString += this.$t('timer.overtime') + ' ';
      }
      if (!this.gaveWarning && remaining < this.warningTime * 1000) {
        this.open = true;
        this.gaveWarning = true;
      }
      this.hours = Math.floor(remaining / (1000 * 60 * 60));
      this.minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
      this.seconds = Math.floor((remaining % (1000 * 60)) / (1000));
      if (this.hours === 0 && this.minutes < 5) {
        this.timeString += this.hours > 0 ? this.hours + ':' : '';
        this.timeString += (this.minutes < 10 ? '0' : '') + this.minutes + ':';
        this.timeString += (this.seconds < 10 ? '0' : '') + this.seconds;
      } else {
        this.timeString += this.hours > 0 ? this.hours + this.$tc('timer.hrs', this.hours) : '';
        this.timeString += this.minutes > 0 ? this.minutes + this.$tc('timer.min', this.minutes) : '';
      }
    },
    toggleShow: function () {
      this.open = !this.open;
    }
  }
};
</script>

<style>
#timerbox span {
  margin-left: 4px;
}
#timerbox {
  border: 1px solid #ccc;
  padding: 5px;
  cursor: pointer;
}
#timerbox.sticky {
  position: fixed;
  top: 0px;
  right: 0px;
  padding: 5px;
  display: block;
  background-color: #fff;
  z-index: 10;
}
</style>
