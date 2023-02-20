<template>
  <div class="questionwrap questionpane">
    <div
      v-html="qdata.html"
      class = "question"
      :id="'questionwrap' + qn"
      ref = "thisqwrap"
    />
  </div>
</template>

<script>
export default {
  name: 'GbQuestion',
  props: ['qdata', 'qn', 'disabled'],
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
      if (this.disabled) {
        window.$(this.$refs.thisqwrap).find('input,select,textarea').each(function (i, el) {
          if (el.name.match(/^(qn|tc|qs)/)) {
            el.disabled = true;
          }
        });
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
