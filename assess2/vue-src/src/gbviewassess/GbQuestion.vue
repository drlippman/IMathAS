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
  data: function() {
    return {
      rendered: false
    }
  },
  methods: {
    renderMath () {
      if (this.rendered) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(document.getElementById('questionwrap' + this.qn));
      //TODO: improve this, and add yellow check
      let svgchk = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="green" stroke-width="3" fill="none"><title>correct</title>';
      svgchk += '<polyline points="20 6 9 17 4 12"></polyline></svg>';
      let svgx = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="red" stroke-width="3" fill="none"><title>correct</title>';
      svgx += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
      window.$('#questionwrap' + this.qn).find('input[type=text].ansgrn,select.ansgrn').after(svgchk);
      window.$('#questionwrap' + this.qn).find('input[type=text].ansred,select.ansred').after(svgx);
      window.$('#questionwrap' + this.qn).find('div.ansgrn,table.ansgrn').append(svgchk);
      window.$('#questionwrap' + this.qn).find('div.ansred,table.ansred').append(svgx);
      this.rendered = true;
    }
  },
  mounted () {
    this.renderMath();
  },
  watch: {
    qdata: function (newVal, oldVal) {
      this.rendered = false;
      this.renderMath();
    }
  }
}
</script>
