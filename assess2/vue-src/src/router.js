import Vue from 'vue';
import Router from 'vue-router';
import Launch from './views/Launch.vue';
import Closed from './views/Closed.vue';
import Skip from './views/Skip.vue';
import Full from './views/Full.vue';
import Print from './views/Print.vue';
import FullPaged from './views/FullPaged.vue';
import Summary from './views/Summary.vue';
import { store, actions } from './basicstore';

Vue.use(Router);

const router = new Router({
  base: process.env.NODE_ENV === 'production' ? window.imasroot + '/assess2/' : '/',
  //mode: 'history',
  routes: [
    {
      path: '/',
      name: 'launch',
      component: Launch,
      beforeEnter: (to, from, next) => {
        // if not open, route to closed
        if ((store.assessInfo.available === 'yes' ||
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice))
            && (store.assessInfo.has_active_attempt || store.assessInfo.can_retake)
         ) {
           next();
         } else {
           next({path: '/closed', replace: true});
         }
      }
    },
    {
      path: '/closed',
      name: 'closed',
      component: Closed,
      beforeEnter: (to, from, next) => {
        // if open, route to launch instead
        if ((store.assessInfo.available === 'yes' ||
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice))
            && (store.assessInfo.has_active_attempt || store.assessInfo.can_retake)
         ) {
           next({path: '/', replace: true});
         } else {
           next();
         }
      }
    },
    {
      path: '/skip/:qn',
      name: 'skip',
      component: Skip,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (store.inProgress) {
          next();
        } else {
          next({path: '/', replace: true});
        }
      }
    },
    {
      path: '/full',
      name: 'full',
      component: Full,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (store.inProgress) {
          next();
        } else {
          next({path: '/', replace: true});
        }
      }
    },
    {
      path: '/full/page/:page',
      name: 'fullpaged',
      component: FullPaged,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (store.inProgress) {
          next();
        } else {
          next({path: '/', replace: true});
        }
      }
    },
    {
      path: '/summary',
      name: 'summary',
      component: Summary,
      beforeEnter: (to, from, next) => {
        // if active attempt or not avail, route to Launch
        if ((store.assessInfo.available === 'yes' ||
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice))
          && (!store.assessInfo.has_active_attempt)
         ) {
           next();
         } else {
           next({path: '/', replace: true});
         }
      }
    },
    {
      path: '/print',
      name: 'print',
      component: Print,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if ((store.assessInfo.available === 'yes' ||
          (store.assessInfo.available === 'practice'))
          && (store.assessInfo.has_active_attempt)
        ) {
          store.inPrintView = true;
          if (store.assessInfo.hasOwnProperty('questions')) {
            next();
          } else {
            let dopractice = (store.assessInfo.available === 'practice');
            actions.startAssess(dopractice, '', [], () => next());
          }
        } else {
          next({path: '/', replace: true});
        }
      }
    },
  ]
});

// This checks before every route to make sure the
// base assessInfo is loaded, and updates query string
router.beforeEach((to,from,next) => {
  if (typeof window.APIbase !== 'undefined') {
    store.APIbase = window.APIbase;
  } else {
    store.APIbase = process.env.BASE_URL;
  }
  // if no assessinfo, or if cid/aid has changed, load data
  console.log(window.location.search);
  let querycid = window.location.search.replace(/^.*cid=(\d+).*$/, '$1');
  let queryaid = window.location.search.replace(/^.*aid=(\d+).*$/, '$1');
  if (store.assessInfo === null ||
    store.cid !== querycid ||
    store.aid !== queryaid
  ) {
    store.cid = querycid;
    store.aid = queryaid;
    store.queryString = '?cid=' + store.cid + '&aid=' + store.aid;
    actions.loadAssessData(() => next());
  } else {
    next();
  }
});

export default router;
