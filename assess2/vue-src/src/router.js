import Vue from 'vue';
import Router from 'vue-router';
import Launch from './views/Launch.vue';
import Closed from './views/Closed.vue';
import Skip from './views/Skip.vue';
import Full from './views/Full.vue';
import Summary from './views/Summary.vue';
import { store, actions } from './basicstore';

Vue.use(Router);

const router = new Router({
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
           next({path: '/closed' + store.queryString, replace: true});
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
           next({path: '/' + store.queryString, replace: true});
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
          next({path: '/' + store.queryString, replace: true});
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
          next({path: '/' + store.queryString, replace: true});
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
           next({path: '/' + store.queryString, replace: true});
         }
      }
    }
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
  if (store.assessInfo === null ||
    store.cid !== to.query.cid ||
    store.aid !== to.query.aid
  ) {
    store.cid = to.query.cid;
    store.aid = to.query.aid;
    store.queryString = '?cid=' + store.cid + '&aid=' + store.aid;
    actions.loadAssessData(() => next());
  } else {
    next();
  }
});

export default router;
