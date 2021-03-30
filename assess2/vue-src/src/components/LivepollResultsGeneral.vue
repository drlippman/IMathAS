<template>
  <div
    v-if="results.qtype === 'draw' && results.initpts[11] === 0"
    class="LPdrawgrid"
    ref="main"
  >
    <div
      v-for = "(ans,i) in sortedKeys"
      :key = "ans"
      :class = "[showans ? (results.scoredata[ans] > 0 ? (
          results.scoredata[ans] < .99 ? 'LPshowpartial' : 'LPshowcorrect'
        ) : 'LPshowwrong') : '']"
    >
      <canvas
        class="drawcanvas"
        :id="'canvasLP' + itemid + '-' + i"
        :width = "results.initpts[6]"
        :height = "results.initpts[7]"
      ></canvas>
      <input type="hidden" :id="'qnLP' + itemid + '-' + i" />
    </div>
  </div>
  <table v-else class="LPres" ref="main">
    <thead>
      <tr>
        <th>{{ $t('livepoll.answer') }}</th>
        <th style="min-width:10em">{{ $t('livepoll.frequency') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for = "(ans,i) in sortedKeys"
        :key = "ans"
        :class = "[showans ? (results.scoredata[ans] > 0 ? (
            results.scoredata[ans] < .99 ? 'LPshowpartial' : 'LPshowcorrect'
          ) : 'LPshowwrong') : '']"
      >
        <td v-if="results.qtype === 'draw'">
          <canvas
            class="drawcanvas"
            :id="'canvasLP' + itemid + '-' + i"
            :width = "results.initpts[6]"
            :height = "results.initpts[7]"
          ></canvas>
          <input type="hidden" :id="'qnLP' + itemid + '-' + i" />
        </td>
        <td v-else>
          {{ ans }}
        </td>
        <td>
          <span class="LPresbarwrap">
            <span class="LPresbar" :style="{width: Math.round(100*results.datatots[ans]/results.maxfreq) +'%'}">
              <span class="LPresval">{{ results.datatots[ans] }}</span>
            </span>
          </span>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'LivepollResultsGeneral',
  props: ['results', 'showans', 'itemid'],
  computed: {
    sortedKeys () {
      const tots = this.results.datatots;
      const keys = Object.keys(tots);
      return keys.sort(function (a, b) { return tots[b] - tots[a]; });
    }
  },
  methods: {
    onUpdate () {
      if (this.results.qtype === 'draw') {
        for (let i = 0; i < this.sortedKeys.length; i++) {
          let la = this.sortedKeys[i].replace(/\(/g, '[').replace(/\)/g, ']');
          la = la.split(';;');
          if (la[0] !== '') {
            la[0] = '[' + la[0].replace(/;/g, '],[') + ']';
          }
          la = '[[' + la.join('],[') + ']]';
          const uniqid = 'LP' + this.itemid + '-' + i;
          window.canvases[uniqid] = this.results.initpts.slice();
          window.canvases[uniqid].unshift(uniqid);
          window.drawla[uniqid] = JSON.parse(la);
        }
        this.$nextTick(() => {
          for (let i = 0; i < this.sortedKeys.length; i++) {
            window.imathasDraw.initCanvases('LP' + this.itemid + '-' + i);
          }
        });
      }
      this.$nextTick(() => {
        setTimeout(window.drawPics, 100);
        window.rendermathnode(this.$refs.main);
      });
    }
  },
  mounted () {
    this.onUpdate();
  },
  watch: {
    results: function (newVal, oldVal) {
      this.onUpdate();
    }
  }
};
</script>
