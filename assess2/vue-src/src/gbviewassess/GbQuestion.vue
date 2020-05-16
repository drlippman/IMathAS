<template>
  <div
    v-html="qdata.html"
    class = "questionwrap"
    :id="'questionwrap' + qn"
    ref = "thisqwrap"
  />
</template>

<script>
export default {
  name: 'GbQuestion',
  props: ['qdata', 'qn'],
  data: function () {
    return {
      rendered: false
    };
  },
  methods: {
    renderInit () {
      if (this.rendered) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.thisqwrap);
      window.initSageCell(this.$refs.thisqwrap);
      window.initlinkmarkup(this.$refs.thisqwrap);
      window.imathasAssess.init(this.qdata.jsparams, true, this.$refs.thisqwrap);
      this.rendered = true;
    }
  },
  mounted () {
    this.renderInit();
  },
  watch: {
    qdata: function (newVal, oldVal) {
      if (newVal !== null) {
        this.rendered = false;
        this.$nextTick(this.renderInit);
      }
    }
  }
};
</script>
