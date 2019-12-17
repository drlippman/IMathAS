<template>
  <div ref="trywrap">
    <p><strong>{{ $t('gradebook.all_tries') }}</strong></p>
    <div
      v-for="(part,index) in processedTries"
      :key = "index"
      class="med-below med-left"
    >
      <div v-if="tries.length > 1">
        <strong>{{ $t('gradebook.part_n', {n: index+1}) }}</strong>
      </div>
      <div v-for="(trystr, tryn) in part" :key="tryn">
        {{ $t('gradebook.try_n', {n: tryn+1}) }}:
        <span v-html="trystr"></span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GbAllTries',
  props: ['tries','qn'],
  data: function () {
    return {
      rendered: false,
      drawToRender: []
    };
  },
  computed: {
    processedTries () {
      let out = [];
      let pn, tn;
      for (pn in this.tries) {
        let partout = [];
        for (tn in this.tries[pn]) {
          if (typeof this.tries[pn][tn] == 'object' && this.tries[pn][tn][0] == 'draw') {
            // drawing
            let la = this.tries[pn][tn][1].replace(/\(/g,"[").replace(/\)/g,"]").split(";;");
          	if (la[0]!='') {
          		la[0] = '['+la[0].replace(/;/g,"],[")+"]";
          	}
          	la = '[['+la.join('],[')+']]';
            let id = this.qn + '-' + pn + '-' + tn;
            window.canvases["GBR"+id] = this.tries[pn][tn][2].slice();
            let drawwidth = canvases["GBR"+id][6];
          	let drawheight = canvases["GBR"+id][7];
          	window.canvases["GBR"+id].unshift("GBR"+id);
          	window.drawla["GBR"+id] = JSON.parse(la);
            this.drawToRender.push("GBR"+id);
            partout[tn] = '<canvas class="drawcanvas" id="canvasGBR'+id+'" width='+drawwidth+' height='+drawheight+'></canvas>';
          	partout[tn] += '<input type="hidden" id="qnGBR'+id+'"/>'
          } else {
            partout[tn] = this.tries[pn][tn];
          }
        }
        out[pn] = partout;
      }
      return out;
    }
  },
  methods: {
    renderInit () {
      if (this.rendered) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.trywrap);
      for (let i=0; i < this.drawToRender.length; i++) {
        imathasDraw.initCanvases(this.drawToRender[i]);
      }
    }
  },
  mounted () {
    this.renderInit();
  },
  watch: {
    tries: function (newVal, oldVal) {
      if (newVal !== null) {
        this.rendered = false;
        this.$nextTick(this.renderInit);
      }
    }
  }
};
</script>
