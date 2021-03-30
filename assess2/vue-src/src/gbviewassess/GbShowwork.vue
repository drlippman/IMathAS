<template>
  <div class = "questionpane viewworkwrap" v-if="!!work">
    <div>
      <button type="button" class="slim"
        @click = "show = !show"
      >
        {{ btnLabel }}
      </button>
      <span class="small" v-if="show && worktime !== '0'">
        {{ $t('gradebook.lastchange')}} {{ worktime }}
      </span>
    </div>
    <transition name="fade">
      <div class="introtext" ref="workbox" v-show="show" v-html="work" />
    </transition>
  </div>
</template>

<script>
export default {
  name: 'GbShowwork',
  props: ['work', 'worktime', 'showall'],
  data: function () {
    return {
      show: false,
      rendered: false
    };
  },
  computed: {
    btnLabel () {
      return this.$t(this.show ? 'gradebook.hidework' : 'gradebook.showwork');
    }
  },
  methods: {
    renderInit () {
      if (this.rendered || !this.work) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.workbox);
      window.initlinkmarkup(this.$refs.workbox);
      window.$(this.$refs.workbox).find('img').on('click', window.rotateimg);
      this.rendered = true;
    }
  },
  mounted () {
    this.renderInit();
  },
  watch: {
    work: function (newVal, oldVal) {
      if (newVal !== null) {
        this.rendered = false;
        this.$nextTick(this.renderInit);
      }
    },
    showall: function (newVal, oldVal) {
      this.show = newVal;
    }
  }
};
</script>
