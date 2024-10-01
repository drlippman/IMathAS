import { nextTick } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import { store, actions } from './basicstore';
import Launch from './views/Launch.vue';
import Closed from './views/Closed.vue';
import Summary from './views/Summary.vue';
import ShowWork from './views/ShowWork.vue';
import Skip from './views/Skip.vue';
import Full from './views/Full.vue';
import Print from './views/Print.vue';
import FullPaged from './views/FullPaged.vue';
// import Videocued from './views/Videocued.vue';
// import Livepoll from './views/Livepoll.vue';
// const Skip = () => import(/* webpackChunkName: "skip" */ './views/Skip.vue');
// const Full = () => import(/* webpackChunkName: "full" */ './views/Full.vue');
// const Print = () => import(/* webpackChunkName: "print" */ './views/Print.vue');
// const FullPaged = () => import(/* webpackChunkName: "fullpaged" */ './views/FullPaged.vue');
const Videocued = () => import(/* webpackChunkName: "special" */ './views/Videocued.vue');
const Livepoll = () => import(/* webpackChunkName: "special" */ './views/Livepoll.vue');

const router = createRouter({
  history: createWebHashHistory(
    process.env.NODE_ENV === 'production' ? window.imasroot + '/assess2/' : '/' // base
  ),
  routes: [
    {
      path: '/',
      name: 'launch',
      component: Launch,
      beforeEnter: (to, from, next) => {
        // if not open, route to closed
        if ((store.assessInfo.available === 'yes' ||
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice)) &&
            (store.assessInfo.has_active_attempt || store.assessInfo.can_retake)
        ) {
          next();
        } else {
          next({ path: '/closed', replace: true });
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
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice)) &&
            (store.assessInfo.has_active_attempt || store.assessInfo.can_retake)
        ) {
          next({ path: '/', replace: true });
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
          next({ path: '/', replace: true });
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
          next({ path: '/', replace: true });
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
          next({ path: '/', replace: true });
        }
      }
    },
    {
      path: '/videocued',
      component: Videocued,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (store.inProgress) {
          next();
        } else {
          next({ path: '/', replace: true });
        }
      }
    },
    {
      path: '/livepoll',
      component: Livepoll,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (store.inProgress) {
          next();
        } else {
          next({ path: '/', replace: true });
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
          (store.assessInfo.available === 'practice' && store.assessInfo.in_practice)) &&
          (!store.assessInfo.has_active_attempt || store.assessInfo.submitby === 'by_question')
        ) {
          next();
        } else {
          next({ path: '/', replace: true });
        }
      }
    },
    {
      path: '/showwork',
      name: 'showwork',
      component: ShowWork,
      beforeEnter: (to, from, next) => {
        // if active attempt or not avail, route to Launch
        if (!store.assessInfo.in_practice &&
          (!store.assessInfo.has_active_attempt ||
            store.assessInfo.submitby === 'by_question'
          ) &&
          !store.showwork_expired
        ) {
          next();
        } else {
          next({ path: '/', replace: true });
        }
      }
    },
    {
      path: '/print',
      name: 'print',
      component: Print,
      beforeEnter: (to, from, next) => {
        // if no active attempt, route to launch
        if (((store.assessInfo.available === 'yes' && store.assessInfo.has_active_attempt) ||
          store.assessInfo.available === 'practice') ||
          store.assessInfo.can_view_all
        ) {
          store.inPrintView = true;
          if (store.assessInfo.hasOwnProperty('questions')) {
            next();
          } else {
            const dopractice = (store.assessInfo.available === 'practice');
            actions.startAssess(dopractice, '', [], () => next());
          }
        } else {
          next({ path: '/', replace: true });
        }
      }
    }
  ],
  scrollBehavior (to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      // return { x: 0, y: 0 };
      return new Promise((resolve, reject) => {
        setTimeout(() => {
          resolve({ left: 0, top: 0 });
        }, 50);
      });
    }
  }
});

// This checks before every route to make sure the
// base assessInfo is loaded, and updates query string
router.beforeEach((to, from, next) => {
  if (typeof window.APIbase !== 'undefined') {
    store.APIbase = window.APIbase;
  } else {
    store.APIbase = process.env.BASE_URL;
  }
  // if no assessinfo, or if cid/aid has changed, load data
  const querycid = window.location.search.replace(/^.*cid=(\d+).*$/, '$1');
  const queryaid = window.location.search.replace(/^.*aid=(\d+).*$/, '$1');
  let queryuid = 0;
  if (window.location.search.match(/uid=/)) {
    queryuid = window.location.search.replace(/^.*uid=(\d+).*$/, '$1');
  }
  if (store.assessInfo === null ||
    store.cid !== querycid ||
    store.aid !== queryaid ||
    store.uid !== queryuid
  ) {
    store.cid = querycid;
    window.cid = querycid; // some other functions need this in global scope
    store.aid = queryaid;
    store.uid = queryuid;
    store.queryString = '?cid=' + store.cid + '&aid=' + store.aid;
    if (store.uid !== 0) {
      store.queryString += '&uid=' + store.uid;
    }
    actions.loadAssessData(() => next());
  } else if (store.inPrintView && to.name !== '/print') {
    next({ path: '/print', replace: true });
  } else {
    next();
  }
});
router.afterEach((to, from) => {
  nextTick(window.sendLTIresizemsg);
});
export default router;
