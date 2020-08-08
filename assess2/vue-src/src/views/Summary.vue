<template>
  <div class="home">
    <summary-header class="headerpane" />
    <div class="flexpanes">
      <div style="flex-grow: 1">
        <summary-diag-info />

        <summary-score-total />

        <div
          v-if="ainfo.hasOwnProperty('endmsg') && ainfo.endmsg != ''"
          v-html = "ainfo.endmsg"
          class = "introtext"
        />

        <div
          v-if="ainfo.hasOwnProperty('newexcused') && Object.keys(ainfo.newexcused).length > 0"
        >
          <p>{{ $t('summary.new_excused') }}</p>
          <ul id="excusedlist">
            <li v-for="(name,index) in ainfo.newexcused" :key="index">
              {{ name }}
            </li>
          </ul>
        </div>

        <div v-if = "showScores">
          <vue-tabs id="scoretabs">
            <vue-tab :name="$t('summary.scorelist')">
              <summary-score-list />
              <p>&nbsp;</p>
              <summary-categories
                v-if="hasCategories"
                :data = "ainfo.questions"
              />
            </vue-tab>
            <vue-tab
              :name="$t('summary.reshowquestions')"
              v-if="showReviewQ"
            >
              <template v-slot = "{ active }">
                <summary-reshow-questions :active="active"/>
              </template>
            </vue-tab>
          </vue-tabs>

        </div>
      </div>
      <div v-if="ainfo.hasOwnProperty('prev_attempts') && ainfo.prev_attempts.length > 0">
        <summary-gb-score />
        <previous-attempts :caption = "$t('prev.all_attempts')" />
      </div>
    </div>
  </div>
</template>

<script>
import { store, actions } from '../basicstore';
import SummaryHeader from '@/components/summary/SummaryHeader.vue';
import SummaryGbScore from '@/components/summary/SummaryGbScore.vue';
import SummaryScoreTotal from '@/components/summary/SummaryScoreTotal.vue';
import SummaryScoreList from '@/components/summary/SummaryScoreList.vue';
import SummaryDiagInfo from '@/components/summary/SummaryDiagInfo.vue';
import SummaryReshowQuestions from '@/components/summary/SummaryReshowQuestions.vue';
import SummaryCategories from '@/components/summary/SummaryCategories.vue';
import PreviousAttempts from '@/components/PreviousAttempts.vue';
import VueTabs from '@/components/widgets/VueTabs.vue';
import VueTab from '@/components/widgets/VueTab.vue';

export default {
  name: 'Summary',
  components: {
    SummaryHeader,
    SummaryScoreTotal,
    SummaryGbScore,
    SummaryScoreList,
    SummaryCategories,
    SummaryReshowQuestions,
    SummaryDiagInfo,
    PreviousAttempts,
    VueTabs,
    VueTab
  },
  data: function () {
    return {
      activeTab: 0
    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    hasScore () {
      return store.assessInfo.hasOwnProperty('score');
    },
    showTotal () {
      return (this.ainfo.showscores !== 'none');
    },
    showScores () {
      return (this.ainfo.showscores === 'during' || this.ainfo.showscores === 'at_end');
    },
    showReviewQ () {
      return (this.ainfo.questions && this.ainfo.questions[0].html !== null);
    },
    hasCategories () {
      let hascat = false;
      if (!this.showScores) {
        return false;
      }
      for (const i in this.ainfo.questions) {
        if (this.ainfo.questions[i].hasOwnProperty('category') &&
          this.ainfo.questions[i].category !== '' &&
          this.ainfo.questions[i].category !== null
        ) {
          hascat = true;
          break;
        }
      }
      return hascat;
    }
  },
  methods: {
    loadScoresIfNeeded () {
      if (!this.hasScore) {
        actions.getScores();
      }
    }
  },
  created () {
    this.loadScoresIfNeeded();
  },
  updated () {
    this.loadScoresIfNeeded();
  }
};
</script>

<style>
#excusedlist {
  margin-bottom: 2em;
}
</style>
