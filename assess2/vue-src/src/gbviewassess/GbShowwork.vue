<template>
  <div class = "questionpane" v-if="!!work">
    <div>
      <button type="button" class="slim"
        @click = "show = !show"
      >
        {{ btnLabel }}
      </button>
    </div>
    <transition name="fade">
      <div class="introtext" ref="workbox" v-show="show" v-html="work" />
    </transition>
  </div>
</template>

<script>
export default {
  name: 'GbShowwork',
  props: ['work'],
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
      if (this.rendered) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.workbox);
      window.initlinkmarkup(this.$refs.workbox);
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
    }
  }
};
</script>
