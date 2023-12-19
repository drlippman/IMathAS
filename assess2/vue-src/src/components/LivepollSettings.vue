<template>
  <div>
    <h2>{{ $t('livepoll.settings') }}</h2>
    <p>
      <label>
        <input type="checkbox" v-model="showQuestionDefault"/>
        {{ $t('livepoll.show_question_default') }}
      </label>
      <br/>
      <label>
        <input type="checkbox" v-model="showResultsLiveDefault"/>
        {{ $t('livepoll.show_results_live_default') }}
      </label>
      <br/>
      <label>
        <input type="checkbox" v-model="showResultsAfter"/>
        {{ $t('livepoll.show_results_after') }}
      </label>
      <br/>
      <label>
        <input type="checkbox" v-model="showAnswersAfter"/>
        {{ $t('livepoll.show_answers_after') }}
      </label>
      <br/>
      <label>
        <input type="checkbox" v-model="useTimer"/>
        {{ $t('livepoll.use_timer') }}
      </label>
      <span v-show="useTimer">
        <input type="text" size="3" v-model="questionTimelimit" />
        {{ $t('livepoll.seconds') }}
      </span>
    </p>
  </div>
</template>

<script>
import { store } from '../basicstore';

export default {
  name: 'LivepollSettings',
  computed: {
    showQuestionDefault: {
      set (val) {
        store.livepollSettings['showQuestionDefault'] = val;
      },
      get () {
        return store.livepollSettings.showQuestionDefault;
      }
    },
    showResultsLiveDefault: {
      set (val) {
        store.livepollSettings['showResultsLiveDefault'] = val;
      },
      get () {
        return store.livepollSettings.showResultsLiveDefault;
      }
    },
    showResultsAfter: {
      set (val) {
        store.livepollSettings['showResultsAfter'] = val;
      },
      get () {
        return store.livepollSettings.showResultsAfter;
      }
    },
    showAnswersAfter: {
      set (val) {
        store.livepollSettings['showAnswersAfter'] = val;
      },
      get () {
        return store.livepollSettings.showAnswersAfter;
      }
    },
    useTimer: {
      set (val) {
        store.livepollSettings['useTimer'] = val;
      },
      get () {
        return store.livepollSettings.useTimer;
      }
    },
    questionTimelimit: {
      set (val) {
        store.livepollSettings['questionTimelimit'] = val;
      },
      get () {
        return store.livepollSettings.questionTimelimit;
      }
    }
  }
};
</script>
