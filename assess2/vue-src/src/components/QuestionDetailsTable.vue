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
        <td v-if="doShowTries">{{ triesRemaining[index] }}</td>
        <td v-if="hasPenalty">
          <penalties-applied
            :part="part"
            :submitby="submitby"
            v-if="partHasPenalties.indexOf(index) != -1"
          />
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import PenaltiesApplied from '@/components/PenaltiesApplied.vue';

export default {
  name: 'QuestionDetailsTable',
  props: ['qinfo', 'caption', 'showtries', 'submitby'],
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
      const out = [];
      for (let pn = 0; pn < this.parts.length; pn++) {
        if (this.parts[pn].hasOwnProperty('penalties') &&
          this.parts[pn].penalties.length > 0
        ) {
          out.push(pn);
        }
      }
      return out;
    },
    triesRemaining () {
      const out = [];
      for (let pn = 0; pn < this.parts.length; pn++) {
        if (this.parts[pn].points_possible === 0) {
          out[pn] = '';
        } else if (this.qinfo.hasOwnProperty('did_jump_to_ans')) {
          out[pn] = 0;
        } else {
          out[pn] = this.qinfo.tries_max - this.parts[pn].try;
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
      const out = [];

      for (let i = 0; i < this.parts.length; i++) {
        if (this.parts[i].try === 0) {
          out[i] = 'unattempted';
        } else if (!this.parts[i].hasOwnProperty('rawscore')) {
          out[i] = 'attempted';
        } else if (this.parts[i].rawscore > 0.99) {
          out[i] = 'correct';
        } else if (this.parts[i].rawscore < 0.01) {
          out[i] = 'incorrect';
        } else {
          out[i] = 'partial';
        }
      }
      return out;
    }
  }
};
</script>

<style>
table.qdetails {
  border-collapse: collapse;
  margin-top: 0.5em;
  margin-bottom: 20px;
}
table.qdetails tr {
  border-bottom: 1px solid #ccc;
}
table.qdetails td, table.qdetails th {
  padding: 4px 8px;
}
.qdetails caption {
  text-align: left;
}
</style>
