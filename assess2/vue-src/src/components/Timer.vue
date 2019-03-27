<template>
  <div id="timerbox" @click="toggleShow" tabindex=0>
    <i class="far fa-clock"></i>
    <span
      v-if="open"
      :class = "{noticetext: hours===0 && 60*minutes+seconds<warningTime}"
    >
      {{ timeString }}
    </span>
    <span v-if="open">&times;</span>
  </div>
</template>

<script>
export default {
  name: 'Timer',
  props: ['end', 'total'],
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
  computed: {
    warningTime () {
      return Math.max(60, Math.min(.05*this.total, 300));
    }
  },
  methods: {
    updateTimer: function () {
      let now = new Date().getTime();
      let remaining = Math.max(0, this.end*1000 - now);
      if (!this.gaveWarning && remaining < this.warningTime*1000) {
        this.open = true;
        this.gaveWarning = true;
      }
      this.hours = Math.floor(remaining / (1000 * 60 * 60));
      this.minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
      this.seconds = Math.floor((remaining % (1000 * 60)) / (1000));
      if (this.hours == 0 && this.minutes < 5) {
        this.timeString = this.hours > 0 ? this.hours + ':' : '';
        this.timeString += (this.minutes < 10 ? '0' : '') + this.minutes + ':';
        this.timeString += (this.seconds < 10 ? '0' : '') + this.seconds;
      } else {
        this.timeString = this.hours > 0 ? this.hours + this.$tc('timer.hrs', this.hours) : '';
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
</style>
