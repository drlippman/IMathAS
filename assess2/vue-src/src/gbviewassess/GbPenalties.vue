<template>
  <table class="qdetails">
    <caption class="sr-only">Penalties Applied</caption>
    <thead>
      <tr>
        <th>{{ $t('qdetails.part') }}</th>
        <th>{{ $t('penalties.applied') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="(part,index) in parts"
        :key = "index"
      >
        <td>{{ index + 1 }}</td>
        <td>
          <penalties-applied
            v-if="partHasPenalties.indexOf(index) != -1"
            class = "med-left"
            :part="part"
            :submitby="submitby"
          />
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
import PenaltiesApplied from '@/components/PenaltiesApplied.vue';

export default {
  name: 'GbPenalties',
  props: ['parts', 'submitby'],
  components: {
    PenaltiesApplied
  },
  computed: {
    partHasPenalties () {
      const out = [];
      for (let pn = 0; pn < this.parts.length; pn++) {
        if (this.parts[pn].hasOwnProperty('penalties') &&
          this.parts[pn].penalties.length > 0
        ) {
          out.push(pn);
        }
      }
      return out;
    }
  }
};
</script>
