<template>
  <span class="flex-nowrap-center">
    <icons :name="statusIcon" class="qstatusicon" v-if="showIcon" />
    <span class="qname-wrap">
      {{ option.dispname }}
    </span>
  </span>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'DrillQuestionListItem',
  props: ['option', 'selected'],
  components: {
    Icons
  },
  computed: {
    showIcon () {
      return !!this.option && this.option.hasOwnProperty('drillstatus');
    },
    hasCorrect () {
      if (this.option.drillresults) {
        for (let j=0; j<this.option.drillresults.length; j++) {
          if (this.option.drillresults[j].correct > 0) {
            return true;
          }
        }
      }
      return false;
    },
    statusIcon () {
      // option.status reflects the current (possibly just-regenerated)
      // question version's own try, which resets on every auto-regen and
      // so doesn't represent overall drill progress - use the drill-level
      // fields instead, mapped onto the same icon set skip mode uses
      if (!this.showIcon) {
        return 'none';
      } else if (this.option.drillcomplete) {
        if (this.hasCorrect) {
          return 'correct';
        } else {
          return 'partattempted';
        }
      } else if (this.option.drillstatus === 1) {
        return 'partattempted';
      } else {
        return 'unattempted';
      }
    }
  }
};
</script>
