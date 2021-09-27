<template>
  <div id="skip-question-header">
    <div class="flexrow wrap" style="flex-grow: 1">
      <div id="skip-question-select"
        role="navigation" :aria-label="$t('regions.qnav')"
      >
          <menu-button id="qnav"
            :options = "navOptions"
            :selected = "curOption"
            @change = "changeQuestion"
            searchby = "dispqn"
          >
            <template v-slot="{ option, selected }">
              <skip-question-list-item
                :showretry="anyHaveRetry"
                :showretake="anyHaveRetake"
                :option="option"
                :selected="selected"
              />
            </template>
          </menu-button>

          <router-link
            :to="'/skip/'+ (dispqn-1)"
            tag="button"
            :disabled="qn < (this.hasIntro ? 0 : 1)"
            class="secondarybtn"
            id="qprev"
            :aria-label="$t('previous')"
            :title="$t('previous')"
            v-if = "showNextPrev"
          >
            <icons name="left"/>
          </router-link>
          <router-link
            :to="'/skip/' + (dispqn+1)"
            tag="button"
            :disabled="qn>=ainfo.questions.length-1"
            class="secondarybtn"
            id="qnext"
            :aria-label="$t('next')"
            :title="$t('next')"
            v-if = "showNextPrev"
          >
            <icons name="right" />
          </router-link>
        </div>
    </div>
    <question-header-icons
      :showscore = "showScore"
      :curQData = "curQData"
      :qn = "qn"
      :showretry = "true"
    />
  </div>
</template>

<script>
import QuestionHeaderIcons from '@/components/QuestionHeaderIcons.vue';
import MenuButton from '@/components/widgets/MenuButton.vue';
import SkipQuestionListItem from '@/components/SkipQuestionListItem.vue';
import Icons from '@/components/widgets/Icons.vue';
import { attemptedMixin } from '@/mixins/attemptedMixin';
import { store } from '../basicstore';

export default {
  name: 'SkipQuestionHeader',
  props: ['qn'],
  components: {
    QuestionHeaderIcons,
    Icons,
    MenuButton,
    SkipQuestionListItem
  },
  mixins: [attemptedMixin],
  data: function () {
    return {

    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    curQData () {
      return store.assessInfo.questions[this.qn];
    },
    dispqn () {
      return parseInt(this.qn) + 1;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0);
    },
    navOptions () {
      var out = [];
      if (this.hasIntro) {
        out.push({
          internallink: '/skip/0',
          dispqn: 0,
          withdrawn: 0
        });
      }
      for (const qn in store.assessInfo.questions) {
        const dispqn = parseInt(qn) + 1;
        const thisoption = {
          internallink: '/skip/' + dispqn,
          dispqn: dispqn
        };
        for (const i in store.assessInfo.questions[qn]) {
          thisoption[i] = store.assessInfo.questions[qn][i];
        }
        if (thisoption.status === 'unattempted') {
          if (this.qsAttempted[qn] === 1) {
            thisoption.status = 'attempted';
          } else if (this.qsAttempted[qn] > 0) {
            thisoption.status = 'partattempted';
          }
        }
        out.push(thisoption);
      }
      return out;
    },
    showScore () {
      if (this.qn > -1) {
        return store.assessInfo.questions[this.qn].hasOwnProperty('gbscore');
      } else {
        return false;
      }
    },
    anyHaveRetry () {
      for (const qn in store.assessInfo.questions) {
        if (store.assessInfo.questions[qn].canretry) {
          return true;
        }
      }
      return false;
    },
    anyHaveRetake () {
      for (const qn in store.assessInfo.questions) {
        if (store.assessInfo.questions[qn].regens_remaining) {
          return true;
        }
      }
      return false;
    },
    curOption () {
      if (this.hasIntro) {
        return this.dispqn;
      } else {
        return this.dispqn - 1;
      }
    },
    showNextPrev () {
      return (Object.keys(this.navOptions).length > 1);
    }
  },
  methods: {
    changeQuestion (newqn) {
      // this.$router.push({ path: '/skip/' + newqn});
    }
  }
};
</script>

<style>
#skip-question-header {
  display: flex;
  flex-flow: row wrap;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ccc;
  padding: 0;
}
#skip-question-header > * {
  margin: 4px 0;
}
#skip-question-select {
  display: flex;
  flex-flow: row nowrap;
  align-items: stretch;
}
.bigicon {
  font-size: 130%;
}

#skip-question-select #qprev, #skip-question-select #qnext {
  margin: 0;
}
#qprev, #qnext {
  padding: 0px 8px;
}
#qprev, #skip-question-select #qprev {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  margin-left: 8px;
}
#qnext {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
#skip-question-select #qnext {
  margin-right: 12px;
}
.headericons > * {
  margin-left: 8px;
}

</style>
