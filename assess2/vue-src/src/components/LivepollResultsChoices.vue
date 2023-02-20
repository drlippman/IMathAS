<template>
  <table class="LPres" ref="main">
    <thead>
      <tr>
        <th>{{ $t('livepoll.answer') }}</th>
        <th style="min-width:10em">{{ $t('livepoll.frequency') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for = "(choice,i) in results.choices"
        :key = "i"
        :class = "[showans ? (results.scoredata[i] > 0 ? 'LPshowcorrect' : 'LPshowwrong') : '']"
      >
        <td v-html="choice"></td>
        <td>
          <span class="LPresbarwrap">
            <span class="LPresbar" :style="{width: Math.round(100*results.datatots[i]/results.maxfreq) +'%'}">
              <span class="LPresval">{{ results.datatots[i] }}</span>
            </span>
          </span>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  name: 'LivepollResultsChoices',
  props: ['results', 'showans'],
  methods: {
    onUpdate () {
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
