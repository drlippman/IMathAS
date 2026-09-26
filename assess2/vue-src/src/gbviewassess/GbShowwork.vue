<template>
  <div v-if="showwork > 0">
    <div v-if="!!work" class = "questionpane viewworkwrap" ref="wrap">
      <div>
        <button type="button" class="slim"
          @click = "show = !show"
        >
          {{ btnLabel }}
        </button>
        <span class="small" v-if="show && worktime !== '0'">
          {{ $t('gradebook-lastchange')}} {{ worktime }}
        </span>
      </div>
      <transition name="fade">
        <div class="introtext" ref="workbox" v-show="show" v-html="work" />
      </transition>
    </div>
    <div class = "questionpane" v-else>
      <div class="introtext">
        {{ $t('gradebook-nowork') }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GbShowwork',
  props: ['work', 'worktime', 'showwork', 'showall', 'previewfiles'],
  data: function () {
    return {
      show: false,
      rendered: false
    };
  },
  computed: {
    btnLabel () {
      return this.$t(this.show ? 'gradebook-hidework' : 'gradebook-showwork');
    }
  },
  methods: {
    renderInit () {
      if (this.rendered || !this.work || this.showwork === 0) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.workbox);
      window.initlinkmarkup(this.$refs.workbox);
      window.$(this.$refs.workbox).find('img').on('click', window.rotateimg);
      this.rendered = true;
      if (this.previewfiles) {
        window.togglepreviewallfiles(true, this.$refs.wrap);
      }
    }
  },
  mounted () {
    this.renderInit();
    this.show = this.showall;
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
