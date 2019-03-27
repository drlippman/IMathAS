<template>
  <table class="qdetails">
    <caption v-if="caption">
      {{ caption }}
    </caption>
    <thead>
      <tr>
        <th>{{ $t('qdetails.part') }}</th>
        <th v-if="showScore">{{ $t('qdetails.score') }}</th>
        <th v-if="doShowTries">{{ $t('qdetails.try') }}</th>
        <th v-if="hasPenalty">{{ $t('qdetails.penalties') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(part,index) in parts" :key="index">
        <td>
          <icons :name="partIcons[index]" />
          {{ index + 1 }}
        </td>
        <td v-if="showScore">{{ part.score }}/{{ part.points_possible }}</td>
        <td v-if="doShowTries">{{ qinfo.tries_max - part.try }}</td>
        <td v-if="hasPenalty">
          <penalties-applied
            :part="part"
            v-if="partHasPenalties.indexOf(index) != -1"
          />
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
import { store } from '../basicstore';
import Icons from '@/components/Icons.vue';
import PenaltiesApplied from '@/components/PenaltiesApplied.vue';

export default {
  name: 'QuestionDetailsTable',
  props: ['qinfo','caption','showtries'],
  components: {
    Icons,
    PenaltiesApplied
  },
  computed: {
    parts () {
      return this.qinfo.parts;
    },
    doShowTries () {
      return !(this.showtries === false);
    },
    partHasPenalties () {
      let out = [];
      for (let pn=0; pn<this.parts.length; pn++) {
        if (this.parts[pn].hasOwnProperty('penalties') &&
          this.parts[pn].penalties.length > 0
        ) {
          out.push(pn);
        }
      }
      return out;
    },
    hasPenalty () {
      return this.partHasPenalties.length > 0;
    },
    showScore () {
      return this.qinfo.hasOwnProperty('score');
    },
    partIcons () {
      let out = [];

      for (let i=0; i < this.parts.length; i++) {
        if (this.parts[i].try == 0) {
          out[i] = 'unattempted';
        } else if (!this.parts[i].hasOwnProperty('rawscore')) {
          out[i] = 'attempted';
        } else if (this.parts[i].rawscore > .99) {
          out[i] = 'correct';
        } else if (this.parts[i].rawscore < .01) {
          out[i] = 'incorrect';
        } else {
          out[i] = 'partial';
        }
      }
      return out;
    }
  }
}
</script>

<style>
.qdetails caption {
  text-align: left;
}
</style>
