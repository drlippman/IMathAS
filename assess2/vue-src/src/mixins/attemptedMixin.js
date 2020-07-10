import { store } from '../basicstore';

export const attemptedMixin = {
  computed: {
    qsAttempted () {
      const qAttempted = [];
      for (const i in store.assessInfo.questions) {
        qAttempted[i] = 0;
        if (store.assessInfo.submitby === 'by_assessment' &&
          store.assessInfo.questions[i].tries_max === 1 &&
          store.assessInfo.questions[i].hasOwnProperty('parts_entered') &&
          store.assessInfo.questions[i].hasOwnProperty('answeights')
        ) {
          if (store.assessInfo.questions[i].parts_entered.length >= store.assessInfo.questions[i].answeights.length) {
            qAttempted[i] = 1;
          } else if (store.assessInfo.questions[i].parts_entered.length > 0) {
            qAttempted[i] = 0.5;
          }
        } else if (store.assessInfo.questions[i].try > 0) {
          qAttempted[i] = 1;
        }
      }
      return qAttempted;
    }
  }
};
