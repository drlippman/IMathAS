<template>
  <span class="flex-nowrap-center">
    <span class="qname-wrap">
      <icons v-if="statusIcon !== 'none'" :name="statusIcon" class="qstatusicon" />
      {{ nameDisp }}
    </span>
    <span class="subdued nowrap">
      {{ qStatus }}
    </span>
  </span>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'FullPagedListItem',
  props: ['option', 'selected'],
  components: {
    Icons
  },
  computed: {
    statusIcon () {
      if (this.option.disppage === 0) {
        return 'none';
      } else if (this.option.numquestions === this.option.numattempted) {
        return 'attempted';
      } else {
        return 'unattempted';
      }
    },
    nameDisp () {
      if (this.option.disppage === 0) {
        return this.$t('intro');
      } else {
        return this.option.title;
      }
    },
    qStatus () {
      if (this.option.disppage === 0 || this.option.numquestions === 0) {
        return '';
      } else {
        return this.$t('header.answered', {
          n: this.option.numattempted,
          tot: this.option.numquestions
        });
      }
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>

</style>
