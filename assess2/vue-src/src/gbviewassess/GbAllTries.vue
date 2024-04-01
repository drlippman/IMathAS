<template>
  <div ref="trywrap">
    <p>
      <strong v-if="type === 'tries'">
        {{ $t('gradebook.all_tries') }}
      </strong>
      <strong v-else-if="type === 'autosave'">
        {{ $t('gradebook.autosaves') }}
      </strong>
    </p>
    <p v-if="type === 'autosave'" class="subdued">
      {{ $t('gradebook.autosave_info') }}
      <span v-if="submitby == 'by_assessment'">
        {{ $t('gradebook.autosave_byassess') }}
      </span>
      <span v-else>
      </span>
    </p>
    <div
      v-for="(part,index) in processedTries"
      :key = "index"
      class="med-below med-left"
    >
      <div v-if="processedTries.length > 1">
        <strong>{{ $t('gradebook.part_n', {n: index+1}) }}</strong>
      </div>
      <div v-for="(trystr, tryn) in part" :key="tryn">
        <span v-if="type =='tries'">
          {{ $t('gradebook.try_n', {n: tryn+1}) }}:
        </span>
        <span v-html="trystr"></span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GbAllTries',
  props: ['tries', 'qn', 'type', 'submitby'],
  data: function () {
    return {
      rendered: false,
      drawToRender: []
    };
  },
  computed: {
    processedTries () {
      const out = [];
      let pn, tn;
      for (pn in this.tries) {
        const partout = [];
        for (tn in this.tries[pn]) {
          if (typeof this.tries[pn][tn] === 'object' && this.tries[pn][tn][0] === 'draw') {
            // drawing
            const id = this.qn + '-' + pn + '-' + tn;
            const drawwidth = this.tries[pn][tn][2][6];
            const drawheight = this.tries[pn][tn][2][7];
            partout[tn] = '<canvas id="canvasGBR' + id + '" width=' + drawwidth + ' height=' + drawheight + '></canvas>';
            partout[tn] += '<input type="hidden" id="qnGBR' + id + '"/>';
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

      // initialize any drawing canvases
      let pn, tn;
      for (pn in this.tries) {
        for (tn in this.tries[pn]) {
          if (typeof this.tries[pn][tn] === 'object' && this.tries[pn][tn][0] === 'draw') {
            // drawing
            let la = this.tries[pn][tn][1].replace(/\(/g, '[').replace(/\)/g, ']').split(';;');
            if (la[0] !== '') {
              la[0] = '[' + la[0].replace(/;/g, '],[') + ']';
            }
            la = '[[' + la.join('],[') + ']]';
            const id = this.qn + '-' + pn + '-' + tn;
            const ref = this.tries[pn][tn][2] || [];

            window.canvases['GBR' + id] = ref.slice();
            window.canvases['GBR' + id].unshift('GBR' + id);
            window.drawla['GBR' + id] = JSON.parse(la);
            window.imathasDraw.initCanvases('GBR' + id);
          }
        }
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
