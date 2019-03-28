<template>
  <div id="skip-question-header">
    <div style="flex-grow: 1" id="skip-question-select">

        <menu-button id="qnav"
          :options = "navOptions"
          :selected = "dispqn"
          @change = "changeQuestion"
          searchby = "dispqn"
        >
          <template v-slot="{ option }">
            <question-list-item :option="option" />
          </template>
        </menu-button>

        <router-link
          :to="'/skip/'+ (dispqn-1) + queryString"
          tag="button"
          :disabled="qn < (this.hasIntro ? 0 : 1)"
          class="secondarybtn"
          id="qprev"
          :aria-label="$t('previous')"
          v-if = "showNextPrev"
        >
          <icons name="left"/>
        </router-link>
        <router-link
          :to="'/skip/' + (dispqn+1) + queryString"
          tag="button"
          :disabled="qn>=ainfo.questions.length-1"
          class="secondarybtn"
          id="qnext"
          :aria-label="$t('next')"
          v-if = "showNextPrev"
        >
          <icons name="right" />
        </router-link>
    </div>
    <div class="headericons">
      <span
        v-if="qn >= 0 && curQData.canretry"
        :title="retryInfo.msg">
        <icons name="retry"/>
        {{ retryInfo.cnt }}
      </span>
      <span
        v-if="qn >= 0 && curQData.canregen"
        :title="$tc('qinfo.regens_remaining', curQData.regens_remaining)">
        <icons name="retake"/>
        {{ curQData.regens_remaining }}
      </span>
      <dropdown id="question-details" position="right" v-if="showDetails">
        <template v-slot:button>
          <icons name="info" size="medium"/>
          {{ $t('header.details') }}
        </template>
        <question-details-pane :qn="qn" />
      </dropdown>

    </div>
  </div>
</template>

<script>
import QuestionDetailsPane from '@/components/QuestionDetailsPane.vue';
import MenuButton from '@/components/MenuButton.vue';
import Dropdown from '@/components/Dropdown.vue';
import QuestionListItem from '@/components/QuestionListItem.vue';
import Icons from '@/components/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'SkipQuestionHeader',
  props: ['qn'],
  components: {
    QuestionDetailsPane,
    Dropdown,
    Icons,
    MenuButton,
    QuestionListItem
  },
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
    queryString () {
      return store.queryString;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '');
    },
    navOptions () {
      var out = {};
      if (this.hasIntro) {
        out[0] = {
          internallink: '/skip/0' + this.queryString,
          dispqn: 0
        };
      }
      for (let qn in store.assessInfo.questions) {
        let dispqn = parseInt(qn) + 1;
        out[dispqn] = store.assessInfo.questions[qn];
        out[dispqn].internallink = '/skip/' + dispqn + this.queryString;
        out[dispqn].dispqn = dispqn;
      }
      return out;
    },
    showNextPrev () {
      return (Object.keys(this.navOptions).length > 1);
    },
    showDetails () {
      if (this.qn < 0) {
        return false;
      }
      let hasCategory = this.curQData.hasOwnProperty('category') && this.curQData.category !== '';
      return (this.curQData.has_details ||
        hasCategory ||
        this.curQData.hasOwnProperty('gbscore')
      );
    },
    retryInfo () {
      if (this.qn < 0) {
        return {};
      }
      let trymsg;
      let trycnt;
      if (this.curQData.hasOwnProperty('tries_remaining_range')) {
        let range = this.curQData.tries_remaining_range;
        trymsg = this.$t('qinfo.tries_remaining_range', {
          min: range[0],
          max: range[1]
        });
        trycnt = range[0] + '-' + range[1];
      } else {
        trymsg = this.$tc('qinfo.tries_remaining', this.curQData.tries_remaining);
        trycnt = this.curQData.tries_remaining;
      }
      return {
        msg: trymsg,
        cnt: trycnt
      }
    }
  },
  methods: {
    changeQuestion (newqn) {
      // this.$router.push({ path: '/skip/' + newqn + store.queryString});
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
#qprev {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  margin-left: 8px;
}
#qnext {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
.headericons > * {
  margin-left: 8px;
}

</style>
