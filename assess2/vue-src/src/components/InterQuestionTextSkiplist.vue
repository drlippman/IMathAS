<template>
  <div v-if="texts.length > 0">
    <div v-for="(textitem,index) in texts" :key = "index">
      <inter-question-text
        class = "questionpane introtext"
        v-show = "showtext[index]"
        :textobj = "textitem"
        :active = "showtext[index]"
      />
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';
import InterQuestionText from '@/components/InterQuestionText.vue';

export default {
  name: 'InterQuestionTextSkiplist',
  props: ['qn', 'pos'],
  components: {
    InterQuestionText
  },
  computed: {
    lastQuestion () {
      return store.assessInfo.questions.length - 1;
    },
    showtext () {
      const out = [];
      for (const i in this.texts) {
        const textObj = this.texts[i];
        out[i] = ((this.pos === 'before' &&
          (this.qn >= textObj.displayBefore && this.qn <= textObj.displayUntil)) ||
          (this.pos === 'after' && this.qn === this.lastQuestion)
        );
      }
      return out;
    },
    texts () {
      if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
        return [];
      }
      const out = [];
      for (const i in store.assessInfo.interquestion_text) {
        const textObj = store.assessInfo.interquestion_text[i];
        if (((this.pos === 'before' && textObj.displayBefore <= this.lastQuestion) ||
          (this.pos === 'after' && textObj.displayBefore > this.lastQuestion)) &&
          textObj.text !== ''
        ) {
          out.push({
            html: textObj.text,
            expanded: true,
            displayBefore: textObj.displayBefore,
            displayUntil: textObj.displayUntil
          });
        }
      }
      return out;
    }
  }
};
</script>
