<template>
  <div
    v-html="qdata.html"
    class = "questionwrap"
    :id="'questionwrap' + qn"
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
      window.rendermathnode(document.getElementById('questionwrap' + this.qn));
      window.imathasAssess.init(this.qdata.jsparams, true);
      let svgchk = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="green" stroke-width="3" fill="none" role="img" aria-label="' + this.$t('icons.correct') + '">';
      svgchk += '<polyline points="20 6 9 17 4 12"></polyline></svg>';
      let svgychk = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="rgb(255,187,0)" stroke-width="3" fill="none" role="img" aria-label="' + this.$t('icons.partial') + '">';
      svgchk += '<path d="M 5.3,10.6 9,14.2 18.5,4.6 21.4,7.4 9,19.8 2.7,13.5 z" /></svg>';
      let svgx = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="rgb(153,0,0)" stroke-width="3" fill="none" role="img" aria-label="' + this.$t('icons.incorrect') + '">';
      svgx += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
      window.$('#questionwrap' + this.qn).find('select.ansgrn').after(svgchk);
      window.$('#questionwrap' + this.qn).find('select.ansyel').after(svgychk);
      window.$('#questionwrap' + this.qn).find('select.ansred').after(svgx);
      window.$('#questionwrap' + this.qn).find('div.ansgrn,table.ansgrn').append(svgchk);
      window.$('#questionwrap' + this.qn).find('div.ansyel,table.ansyel').append(svgychk);
      window.$('#questionwrap' + this.qn).find('div.ansred,table.ansred').append(svgx);
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
