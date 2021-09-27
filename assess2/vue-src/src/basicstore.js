import Vue from 'vue';
import Router from './router';
import { mapInterquestionTexts, mapInterquestionPages } from '@/mixins/maptexts';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  uid: null,
  queryString: '',
  inAssess: false,
  inTransit: false,
  autoSaving: false,
  errorMsg: null,
  confirmObj: null,
  lastLoaded: [],
  inProgress: false,
  autosaveQueue: {},
  autosaveTimeactive: {},
  timeActive: {},
  timeActivated: {},
  initValues: {},
  initTimes: {},
  work: {},
  autosaveTimer: null,
  somethingDirty: false,
  noUnload: false,
  timelimit_timer: null,
  timelimit_expired: false,
  timelimit_grace_expired: false,
  timelimit_restricted: 0,
  enddate_timer: null,
  show_enddate_dialog: false,
  inPrintView: false,
  enableMQ: true,
  lastPos: null,
  livepollServer: '',
  livepollSettings: {
    showQuestionDefault: true,
    showResultsLiveDefault: false,
    showResultsAfter: true,
    showAnswersAfter: true,
    useTimer: false,
    questionTimelimit: 60
  },
  livepollStuCnt: 0,
  livepollResults: {}
});

export const actions = {
  loadAssessData (callback, doreset) {
    let qs = store.queryString;
    if (doreset === true) {
      qs += '&reset=1';
    }
    if (store.inTransit) {
      window.setTimeout(() => this.loadAssessData(callback, doreset), 20);
      return;
    }
    store.inTransit = true;
    store.errorMsg = null;
    store.inAssess = false;
    window.$.ajax({
      url: store.APIbase + 'loadassess.php' + qs,
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        // reset store
        store.inProgress = false;
        store.timelimit_expired = false;
        clearTimeout(store.timelimit_timer);
        // parse response
        response = this.processSettings(response);
        store.assessInfo = response;
        if (typeof callback !== 'undefined' && callback !== null) {
          callback();
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  startAssess (dopractice, password, newGroupMembers, callback, previewAll) {
    if (store.inTransit) {
      window.setTimeout(() => this.startAssess(dopractice, password, newGroupMembers, callback), 20);
      return;
    }
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'startassess.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        practice: dopractice,
        preview_all: previewAll ? 1 : 0,
        password: password,
        in_print: store.inPrintView ? 1 : 0,
        new_group_members: newGroupMembers.join(','),
        cur_group: store.assessInfo.stugroupid,
        has_ltisourcedid: (store.assessInfo.is_lti && store.assessInfo.has_ltisourcedid) ? 1 : 0
      },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        // reset drawing handler
        window.imathasDraw.reset();

        // overwrite properties with those from response
        response = this.processSettings(response);
        store.assessInfo = Object.assign({}, store.assessInfo, response);

        // clear out trackers, in case we're retaking
        store.autosaveQueue = {};
        store.autosaveTimeactive = {};
        store.initValues = {};
        store.initTimes = {};
        store.timeActive = {};
        store.timeActivated = {};
        store.work = {};
        store.inAssess = true;
        // route to correct display
        if (response.error) {
          this.handleError(response.error);
        } else if (store.assessInfo.has_active_attempt) {
          store.inProgress = true;
          if (typeof callback !== 'undefined' && callback !== null) {
            callback();
            return;
          }
          if (store.assessInfo.displaymethod === 'skip') {
            if (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0) {
              Router.push('/skip/0');
            } else {
              Router.push('/skip/1');
            }
          } else if (store.assessInfo.displaymethod === 'full') {
            if (store.assessInfo.hasOwnProperty('interquestion_pages')) {
              if (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0) {
                Router.push('/full/page/0');
              } else {
                Router.push('/full/page/1');
              }
            } else {
              Router.push('/full');
            }
          } else if (store.assessInfo.displaymethod === 'video_cued') {
            Router.push('/videocued');
          } else if (store.assessInfo.displaymethod === 'livepoll') {
            Router.push('/livepoll');
          }
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadQuestion (qn, regen, jumptoans) {
    if (store.inTransit) {
      window.setTimeout(() => this.loadQuestion(qn, regen, jumptoans), 20);
      return;
    }
    store.inTransit = true;
    if (regen) {
      this.clearInitValue(qn);
      if (store.assessInfo.hasOwnProperty('scoreerrors') &&
        store.assessInfo.scoreerrors.hasOwnProperty(qn)
      ) {
        delete store.assessInfo.scoreerrors[qn];
      }
    }
    const data = new FormData();
    data.append('qn', qn);
    data.append('practice', store.assessInfo.in_practice);
    data.append('regen', regen ? 1 : 0);
    data.append('jumptoans', jumptoans ? 1 : 0);
    if (store.assessInfo.preview_all) {
      data.append('preview_all', true);
    }
    if (Object.keys(store.autosaveQueue).length > 0) {
      actions.clearAutosaveTimer();
      this.addAutosaveData(data);
    }

    window.$.ajax({
      url: store.APIbase + 'loadquestion.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
      processData: false,
      contentType: false,
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        if (response.saved_autosaves) {
          this.markAutosavesDone();
        }
        delete response.saved_autosaves;
        if (regen && store.assessInfo.questions[qn].jsparams) {
          // clear out before overwriting
          window.imathasAssess.clearparams(store.assessInfo.questions[qn].jsparams);
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  submitAssessment () {
    let warnMsg = 'header.confirm_assess_submit';
    if (store.assessInfo.submitby === 'by_assessment') {
      let qAttempted = 0;
      const changedQuestions = this.getChangedQuestions();
      for (const i in store.assessInfo.questions) {
        if (store.assessInfo.questions[i].try > 0 ||
          (store.assessInfo.questions[i].hasOwnProperty('parts_entered') &&
          store.assessInfo.questions[i].hasOwnProperty('answeights') &&
          store.assessInfo.questions[i].parts_entered.length >=
          store.assessInfo.questions[i].answeights.length
          ) ||
          changedQuestions.hasOwnProperty(i)
        ) {
          qAttempted++;
        }
      }
      const nQuestions = store.assessInfo.questions.length;
      if (qAttempted !== nQuestions) {
        warnMsg = 'header.confirm_assess_unattempted_submit';
      }
      store.confirmObj = {
        body: warnMsg,
        action: () => {
          /*
          // TODO: Check if we should always submit all

          if (store.assessInfo.showscores === 'during') {
            // check for dirty questions and submit them
            this.submitQuestion(Object.keys(changedQuestions), true);
          } else {
          */
          // submit them all
          var qns = [];
          for (let k = 0; k < store.assessInfo.questions.length; k++) {
            qns.push(k);
          }
          this.submitQuestion(qns, true);
          // }
        }
      };
    }
  },
  submitWork () {
    if (store.inTransit) {
      window.setTimeout(() => this.submitWork(), 20);
      return;
    }
    if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }
    store.inTransit = true;
    const data = {};
    // get values again, in case event trigger didn't happen
    window.$('.swbox').each(function () {
      const qn = parseInt(this.id.substr(2));
      if (!store.assessInfo.questions[qn].hasOwnProperty('work') ||
        this.value !== store.assessInfo.questions[qn].work
      ) {
        store.work[qn] = this.value;
      }
    });
    for (const qn in store.work) {
      data[qn] = store.work[qn];
    }
    if (Object.keys(data).length === 0) { // nothing to submit
      store.inTransit = false;
      if (store.inAssess) {
        Router.push('/summary');
      } else if (store.assessInfo.available === 'yes') {
        Router.push('/');
      } else {
        window.location = window.exiturl;
      }
      return;
    }
    window.$.ajax({
      url: store.APIbase + 'savework.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: { work: data },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        } else {
          store.errorMsg = null;
        }
        // copy into questions for reload later if needed
        for (const qn in store.work) {
          Vue.set(store.assessInfo.questions[parseInt(qn)], 'work', store.work[qn]);
          delete store.work[qn];
        }

        if (store.inAssess) {
          Router.push('/summary');
        } else if (store.assessInfo.available === 'yes') {
          Router.push('/');
        } else {
          window.location = window.exiturl;
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  submitQuestion (qns, endattempt) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    if (store.inTransit) {
      window.setTimeout(() => this.submitQuestion(qns, endattempt), 20);
      return;
    }
    store.inTransit = true;
    if (typeof qns !== 'object') {
      qns = [qns];
    }
    for (let k in window.callbackstack) {
      k = parseInt(k);
      if (qns.indexOf(k < 1000 ? k : (Math.floor(k / 1000) - 1)) > -1) {
        window.callbackstack[k](k);
      }
    }
    if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }

    // figure out non-blank questions to submit
    const lastLoaded = [];
    const changedQuestions = this.getChangedQuestions(qns);
    let changedWork = false;
    for (let k = 0; k < qns.length; k++) {
      const qn = parseInt(qns[k]);
      // get work again, in case triggers didn't work
      if (document.getElementById('sw' + qn)) {
        store.work[qn] = document.getElementById('sw' + qn).value;
      }
      if (store.work[qn] && store.work[qn] !== actions.getInitValue(qn, 'sw' + qn)) {
        changedWork = true;
        break;
      }
    }
    if (Object.keys(changedQuestions).length === 0 && !changedWork && !endattempt) {
      store.errorMsg = 'nochange';
      store.inTransit = false;
      return;
    }

    window.MQeditor.resetEditor();
    window.imathasAssess.clearTips();

    window.setTimeout(() => this.clearAutosave(qns), 100);

    const data = new FormData();

    let valstr;
    const timeactive = [];
    for (const qn in changedQuestions) {
      // get timeactive values
      if (store.assessInfo.displaymethod !== 'full') { // don't store time active when full-test
        if (store.timeActivated.hasOwnProperty(qn)) {
          const now = new Date();
          store.timeActive[qn] += (now - store.timeActivated[qn]);
        }
        timeactive.push(store.timeActive[qn]);
      }
      // run any pre-submit routines.  The question type wants to return a value,
      // it will get returned here.
      if (changedQuestions[qn].length === 1 && changedQuestions[qn][0] === 0) {
        // one part, might be single part
        valstr = window.imathasAssess.preSubmit(qn);
        if (valstr !== false) {
          data.append('qn' + qn + '-val', valstr);
        }
      }
      // get presubmit for multipart parts
      let subqn;
      for (let k = 0; k < changedQuestions[qn].length; k++) {
        subqn = (parseInt(qn) + 1) * 1000 + changedQuestions[qn][k];
        valstr = window.imathasAssess.preSubmit(subqn);
        if (valstr !== false) {
          data.append('qn' + subqn + '-val', valstr);
        }
      }
    }

    for (let k = 0; k < qns.length; k++) {
      const qn = parseInt(qns[k]);

      // add in regular input fields.
      var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn + 1) + '\\d{3})');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if (el.name.match(regex)) {
          if ((el.type !== 'radio' && el.type !== 'checkbox') || el.checked) {
            if (el.type === 'file' && el.files.length > 0) {
              data.append(el.name, el.files[0]);
            } else if (el.type === 'file') {
              if (document.getElementById(el.name + '-autosave')) {
                data.append(el.name, 'file-autosave');
              }
            } else {
              data.append(el.name, window.imathasAssess.preSubmitString(el.name, el.value));
            }
          }
        }
      });
      if (store.work[qn] && store.work[qn] !== actions.getInitValue(qn, 'sw' + qn)) {
        data.append('sw' + qn, store.work[qn]);
      }
      lastLoaded[k] = store.lastLoaded[qn].getTime();
    };
    data.append('toscoreqn', JSON.stringify(changedQuestions));
    data.append('timeactive', timeactive.join(','));
    data.append('lastloaded', lastLoaded.join(','));
    data.append('verification', JSON.stringify(this.getVerificationData(changedQuestions)));
    if (endattempt) {
      data.append('endattempt', endattempt);
    }
    if (store.assessInfo.in_practice) {
      data.append('practice', true);
    }
    if (store.assessInfo.preview_all) {
      data.append('preview_all', true);
    }
    this.addAutosaveData(data, Object.keys(changedQuestions));

    const hasSeqNext = (qns.length === 1 && store.assessInfo.questions[qns[0]].jsparams &&
      store.assessInfo.questions[qns[0]].jsparams.hasseqnext);

    window.$.ajax({
      url: store.APIbase + 'scorequestion.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
      processData: false,
      contentType: false,
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        } else {
          store.errorMsg = null;
        }
        if (response.saved_autosaves) {
          this.markAutosavesDone();
        }
        delete response.saved_autosaves;
        // clear out initValues for this question so they get re-set
        for (const qn in changedQuestions) {
          if (store.assessInfo.hasOwnProperty('scoreerrors') &&
            store.assessInfo.scoreerrors.hasOwnProperty(qn)
          ) {
            delete store.assessInfo.scoreerrors[qn];
          }
          if (store.initValues.hasOwnProperty(qn)) {
            delete store.initValues[qn];
          }
          if (store.work.hasOwnProperty(qn)) {
            delete store.work[qn];
          }
        }

        response = this.processSettings(response);
        this.copySettings(response);

        // update tree reader with score
        if (!store.assessInfo.in_practice && window.inTreeReader) {
          this.updateTreeReader();
        }

        let hasShowWorkAfter = false;
        for (let k = 0; k < store.assessInfo.questions.length; k++) {
          if (store.assessInfo.questions[k].showwork & 2) {
            hasShowWorkAfter = true;
            break;
          }
        }

        if (endattempt) {
          store.inProgress = false;
          if (hasShowWorkAfter && !store.assessInfo.in_practice) {
            Router.push('/showwork');
          } else {
            Router.push('/summary');
          }
        } else if (qns.length === 1) {
          store.assessInfo.questions[qns[0]].hadSeqNext = hasSeqNext;
          // scroll to score result
          Vue.nextTick(() => {
            var el;
            if (!hasSeqNext) {
              el = document.getElementById('questionwrap' + qns[0]).parentNode.parentNode;
              window.$(el).find('.scoreresult').focus();
            } else {
              el = window.$('#questionwrap' + qns[0]).find('.seqsep').last().next()[0];
              window.$('#questionwrap' + qns[0]).find('.seqsep').last().focus();
            }
            var bounding = el.getBoundingClientRect();
            if (bounding.top < 0 || bounding.bottom > document.documentElement.clientHeight) {
              el.scrollIntoView();
            }
          });
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  gotoSummary () {
    let hasShowWorkAfter = false;
    for (let k = 0; k < store.assessInfo.questions.length; k++) {
      if (store.assessInfo.questions[k].showwork & 2) {
        hasShowWorkAfter = true;
        store.assessInfo.showwork_after = true;
        break;
      }
    }

    if (store.assessInfo.submitby === 'by_question') {
      if (hasShowWorkAfter && !store.assessInfo.in_practice) {
        Router.push('/showwork');
      } else {
        Router.push('/summary');
      }
    }
  },
  doAutosave (qn, partnum, timeactive) {
    if (store.inTransit) {
      // wait until not in transit; don't want to add to autosavequeue then
      // have queue cleared when intransit returns
      window.setTimeout(() => this.doAutosave(qn, partnum, timeactive), 20);
      return;
    }
    store.somethingDirty = false;
    // this.clearAutosaveTimer()
    if (!store.autosaveQueue.hasOwnProperty(qn)) {
      Vue.set(store.autosaveQueue, qn, []);
    }
    if (store.autosaveQueue[qn].indexOf(partnum) === -1) {
      store.autosaveQueue[qn].push(partnum);
    }
    Vue.set(store.autosaveTimeactive, qn, timeactive);
    if (store.autosaveTimer === null) {
      store.autosaveTimer = window.setTimeout(() => { this.submitAutosave(true); }, 60 * 1000);
    }
  },
  clearAutosave (qns) {
    for (const i in qns) {
      if (store.autosaveQueue.hasOwnProperty(qns[i])) {
        Vue.delete(store.autosaveQueue, qns[i]);
      }
    }
    if (Object.keys(store.autosaveQueue).length === 0) {
      this.clearAutosaveTimer();
    }
  },
  clearAutosaveTimer () {
    window.clearTimeout(store.autosaveTimer);
    store.autosaveTimer = null;
  },
  addAutosaveData (data, skip) {
    skip = skip || [];
    // adds autosave data to existing FormData
    const lastLoaded = {};
    const tosaveqn = {};
    let valstr;
    for (const qn in store.autosaveQueue) {
      if (skip.indexOf(qn) !== -1) {
        continue; // skip it
      }
      tosaveqn[qn] = store.autosaveQueue[qn];
      if (store.autosaveQueue[qn].length === 1 && store.autosaveQueue[qn][0] === 0) {
        // one part, might be single part
        valstr = window.imathasAssess.preSubmit(qn);
        if (valstr !== false) {
          data.append('qn' + qn + '-val', valstr);
        }
      }
      // build up regex to match the inputs for all the parts we want to save
      const regexpts = [];
      let subqn;
      for (const k in store.autosaveQueue[qn]) {
        const pn = store.autosaveQueue[qn][k];
        if (pn === 'sw') {
          data.append('sw' + qn, store.work[qn]);
          continue;
        }
        if (pn === 0) {
          regexpts.push(qn);
        }
        subqn = (qn * 1 + 1) * 1000 + pn * 1;
        regexpts.push(subqn);
        valstr = window.imathasAssess.preSubmit(subqn);
        if (valstr !== false) {
          data.append('qn' + subqn + '-val', valstr);
        }
      }
      var regex = new RegExp('^(qn|tc|qs)(' + regexpts.join('\\b|') + '\\b)');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if (el.name.match(regex)) {
          if ((el.type !== 'radio' && el.type !== 'checkbox') || el.checked) {
            if (el.type === 'file') {
              if (el.files.length === 0) {
                data.append(el.name, '');
              } else {
                data.append(el.name, el.files[0]);
              }
            } else {
              data.append(el.name, window.imathasAssess.preSubmitString(el.name, el.value));
            }
          }
        }
      });
      lastLoaded[qn] = store.lastLoaded[qn].getTime();
    };
    data.append('autosave-tosaveqn', JSON.stringify(tosaveqn));
    data.append('autosave-lastloaded', JSON.stringify(lastLoaded));
    data.append('autosave-verification', JSON.stringify(this.getVerificationData(tosaveqn)));
    if (store.assessInfo.displaymethod === 'full') {
      data.append('autosave-timeactive', '');
    } else {
      data.append('autosave-timeactive', JSON.stringify(store.autosaveTimeactive));
    }
  },
  markAutosavesDone () {
    for (const qn in store.autosaveQueue) {
      for (const k in store.autosaveQueue[qn]) {
        if (store.assessInfo.questions[parseInt(qn)].hasOwnProperty('parts_entered')) {
          if (store.assessInfo.questions[parseInt(qn)].parts_entered.indexOf(store.autosaveQueue[qn][k]) === -1) {
            store.assessInfo.questions[parseInt(qn)].parts_entered.push(store.autosaveQueue[qn][k]);
          }
        }
      }
    }

    // clear autosave queue
    store.autosaveQueue = {};
  },
  submitAutosave (async) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    if (Object.keys(store.autosaveQueue).length === 0) {
      return;
    }
    if (store.inTransit) {
      window.setTimeout(() => this.submitAutosave(async), 20);
      return;
    }
    store.inTransit = true;
    store.autoSaving = true;

    if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }
    const data = new FormData();
    this.addAutosaveData(data);

    if (store.assessInfo.in_practice) {
      data.append('practice', true);
    }
    if (store.assessInfo.preview_all) {
      data.append('preview_all', true);
    }
    if (async === false && navigator.sendBeacon) {
      navigator.sendBeacon(
        store.APIbase + 'autosave.php' + store.queryString,
        data
      );
      store.inTransit = false;
      store.autoSaving = false;
      store.autosaveQueue = {};
      return;
    }
    window.$.ajax({
      url: store.APIbase + 'autosave.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
      async: async,
      processData: false,
      contentType: false,
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        }
        if (response.autosave === 'done') {
          this.markAutosavesDone();
          delete response.autosave;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
        store.autoSaving = false;
      });
  },
  handleTimelimitUp () {
    if (store.assessInfo.has_active_attempt) {
      // submit dirty questions and end attempt
      store.errorMsg = 'timesup_submitting';
      setTimeout(() => {
        const tosub = Object.keys(this.getChangedQuestions());
        this.submitQuestion(tosub, true);
      }, 1000);
    }
    // store.timelimit_expired = true;
  },
  handleDueDate () { // due date has hit
    actions.submitAutosave();
    store.show_enddate_dialog = true;
  },
  endAssess (callback) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    window.MQeditor.resetEditor();
    window.imathasAssess.clearTips();
    if (store.inTransit) {
      window.setTimeout(() => this.endAssess(callback), 20);
      return;
    }
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'endassess.php' + store.queryString,
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
        if (typeof callback === 'function') {
          callback();
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getScores () {
    if (store.inTransit) {
      window.setTimeout(() => this.getScores(), 20);
      return;
    }
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'getscores.php' + store.queryString,
      type: 'GET',
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getQuestions () {
    if (store.inTransit) {
      window.setTimeout(() => this.getQuestions(), 20);
      return;
    }
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'getquestions.php' + store.queryString,
      type: 'GET',
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  redeemLatePass (callback) {
    if (store.inTransit) {
      window.setTimeout(() => this.redeemLatePass(callback), 20);
      return;
    }
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'uselatepass.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
        if (typeof callback === 'function') {
          callback();
        } else {
          Router.push('/');
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  routeToStart () {
    Router.push('/');
  },
  setLivepollStatus (data) {
    if (store.inTransit) {
      window.setTimeout(() => this.setLivepollStatus(data), 20);
      return;
    }
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'livepollstatus.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          this.handleError(response.error);
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getVerificationData (qns) {
    const out = {};
    const byQuestion = (store.assessInfo.submitby === 'by_question');
    const assessRegen = store.assessInfo.prev_attempts.length;
    for (const qn in qns) {
      const parttries = [];
      const qdata = store.assessInfo.questions[qn];
      for (let pn = 0; pn < qdata.parts.length; pn++) {
        parttries[pn] = qdata.parts[pn].try;
      }
      out[qn] = {
        tries: parttries,
        regen: byQuestion ? qdata.regen : assessRegen
      };
    }
    return out;
  },
  setInitValue (qn, fieldname, val) {
    if (!store.initValues.hasOwnProperty(qn)) {
      Vue.set(store.initValues, qn, {});
    }
    // only record initvalue if we don't already have one
    let pn = 0;
    if (fieldname.match(/^sw/)) {
      pn = 'sw';
    } else {
      const m = fieldname.match(/^(qs|qn|tc)(\d+)/);
      const qref = m[2];
      if (qref > 1000) {
        pn = qref % 1000;
      }
      // for draw questions, overwrite blank to the expected blank format
      if (store.assessInfo.questions[qn].jsparams[qref].qtype === 'draw' && val === '') {
        val = ';;;;;;;;';
      }
    }
    if (store.assessInfo.questions[qn].hasOwnProperty('usedautosave') &&
      store.assessInfo.questions[qn].usedautosave.indexOf(pn) !== -1
    ) {
      // was loaded from autosave, so don't record as init initValue
      return;
    }

    if (!store.initValues[qn].hasOwnProperty(fieldname)) {
      store.initValues[qn][fieldname] = val;
    }
  },
  getInitValue (qn, fieldname) {
    if (!store.initValues.hasOwnProperty(qn)) {
      return '';
    } else if (!store.initValues[qn].hasOwnProperty(fieldname)) {
      return '';
    } else {
      return store.initValues[qn][fieldname];
    }
  },
  clearInitValue (qn) {
    store.initValues[qn] = {};
  },
  getInitTimeactive (qn) {
    if (store.assessInfo.questions[qn].hasOwnProperty('autosave_timeactive')) {
      var timeactive = store.assessInfo.questions[qn].autosave_timeactive;
      // set to 0 to indicate it's used
      store.assessInfo.questions[qn].autosave_timeactive = 0;
      return timeactive;
    }
    return 0;
  },
  setRendered (qn, value) {
    if (store.assessInfo) {
      store.assessInfo.questions[qn].rendered = value;
    }
  },
  getChangedQuestions (qns) {
    if (typeof qns !== 'object') {
      if (!store.assessInfo.hasOwnProperty('questions')) {
        return {};
      }
      qns = [];
      for (let qn = 0; qn < store.assessInfo.questions.length; qn++) {
        qns.push(qn);
      }
    }
    const changed = {};
    let m;
    for (let k = 0; k < qns.length; k++) {
      const qn = qns[k];

      if (store.assessInfo.questions[qn].showwork && store.work.hasOwnProperty(qn)) {
        if (store.work[qn] !== actions.getInitValue(qn, 'sw' + qn)) {
          if (!changed.hasOwnProperty(qn)) {
            changed[qn] = [];
          }
        }
      }
      var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn * 1 + 1) + '\\d{3})');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if ((m = el.name.match(regex)) !== null) {
          let thisChanged = false;
          if (el.type === 'radio' || el.type === 'checkbox') {
            if (el.checked && el.value !== actions.getInitValue(qn, el.name)) {
              thisChanged = true;
            } else if (!el.checked && el.value === actions.getInitValue(qn, el.name)) {
              thisChanged = true;
            }
          } else if (el.type === 'file' && document.getElementById(el.name + '-autosave') !== null) {
            thisChanged = true; // file with autosave input
          } else {
            if (el.value.trim() !== actions.getInitValue(qn, el.name) && el.value.trim() !== '') {
              thisChanged = true;
            }
          }
          if (thisChanged) {
            if (!changed.hasOwnProperty(qn)) {
              changed[qn] = [];
            }
            let pn = 0;
            const qidnum = parseInt(m[2]);
            if (qidnum > 1000) {
              pn = qidnum % 1000;
            }
            if (changed[qn].indexOf(pn) === -1) {
              changed[qn].push(pn);
            }
          }
        }
      });
      // look to see if any have submitblank set
      if (store.assessInfo.questions[qn].hasOwnProperty('jsparams')) {
        const curqparams = store.assessInfo.questions[qn].jsparams;
        for (const qref in curqparams) {
          if (curqparams.submitall ||
            (qref.match(/\d/) && curqparams[qref].hasOwnProperty('submitblank'))
          ) {
            let pn = 0;
            if (qref > 1000) {
              pn = qref % 1000;
            }
            if (!changed.hasOwnProperty(qn)) {
              changed[qn] = [];
            }
            if (changed[qn].indexOf(pn) === -1) {
              changed[qn].push(pn);
            }
          }
        }
      }
    }
    return changed;
  },
  handleError (error) {
    if (store.assessInfo.hasOwnProperty('is_lti') &&
      store.assessInfo.is_lti &&
      error === 'no_session'
    ) {
      error = 'lti_no_session';
    }
    store.errorMsg = error;
  },
  updateTreeReader () {
    let qAttempted = 0;
    for (const i in store.assessInfo.questions) {
      if (store.assessInfo.questions[i].try > 0) {
        qAttempted++;
      }
    }
    let status = 0;
    if (qAttempted === store.assessInfo.questions.length) {
      status = 2;
    } else if (qAttempted > 0) {
      status = 1;
    }
    try {
      top.updateTRunans(store.aid, status);
    } catch (e) {}
  },
  enableMQ () {
    store.enableMQ = true;
    window.imathasAssess.clearLivePreviewTimeouts();
    window.$('input[type=button][id^=pbtn],button[id^=pbtn]').hide();
    window.$('span[id^=p] span[id^=lpbuf]').empty();
    window.MQeditor.toggleMQAll('input[data-mq]', true);
  },
  disableMQ () {
    store.enableMQ = false;
    window.$('input[type=button][id^=pbtn],button[id^=pbtn]').show().trigger('click');
    window.MQeditor.toggleMQAll('input[data-mq]', false);
  },
  copySettings (response) {
    // overwrite existing questions with new data
    if (response.hasOwnProperty('questions')) {
      if (!store.assessInfo.hasOwnProperty('questions')) {
        store.assessInfo.questions = [];
      }
      for (const i in response.questions) {
        const iint = parseInt(i);
        if (response.questions[i].category === null &&
          store.assessInfo.questions.hasOwnProperty(iint) &&
          store.assessInfo.questions[iint].hasOwnProperty('category')
        ) {
          response.questions[i].category = store.assessInfo.questions[iint].category;
        }
        Vue.set(store.assessInfo.questions, iint, response.questions[i]);
      }
      delete response.questions;
    }
    // copy other settings from response to store
    store.assessInfo = Object.assign({}, store.assessInfo, response);
  },
  processSettings (data) {
    if (data.hasOwnProperty('questions')) {
      for (const i in data.questions) {
        const thisq = data.questions[i];

        data.questions[i].canretry = (thisq.try < thisq.tries_max);
        data.questions[i].canretry_primary = data.questions[i].canretry;
        data.questions[i].tries_remaining = thisq.tries_max - thisq.try;
        if (thisq.hasOwnProperty('parts')) {
          let trymin = 1e10;
          let trymax = 0;
          let canretrydet = false;
          for (const pn in thisq.parts) {
            const remaining = thisq.tries_max - thisq.parts[pn].try;
            if (remaining < trymin) {
              trymin = remaining;
            }
            if (remaining > trymax) {
              trymax = remaining;
            }
            if (remaining > 0 &&
              (!thisq.parts[pn].hasOwnProperty('rawscore') ||
                thisq.parts[pn].rawscore < 1 ||
                thisq.parts[pn].req_manual
              )
            ) {
              canretrydet = true;
            }
          }
          data.questions[i].canretry_primary = canretrydet;
          if (trymin !== trymax) {
            data.questions[i].tries_remaining_range = [trymin, trymax];
          }
        }
        if (thisq.hasOwnProperty('regens_max') !== 'undefined' && thisq.regen < thisq.regens_max - 1) {
          data.questions[i].canregen = true;
          data.questions[i].regens_remaining = thisq.regens_max - thisq.regen - 1; // -1 to adjust to current version
        } else {
          data.questions[i].canregen = false;
          data.questions[i].regens_remaining = 0;
        }
        data.questions[i].has_details = (thisq.hasOwnProperty('parts') && (
          thisq.parts.length > 1 || (
            thisq.parts[0].hasOwnProperty('penalties') &&
            thisq.parts[0].penalties.length > 0
          ))
        );
        if (data.questions[i].withdrawn !== 0) {
          data.questions[i].canretry = false;
          data.questions[i].tries_remaining = 0;
          data.questions[i].canregen = false;
          data.questions[i].regens_remaining = 0;
        }

        store.lastLoaded[i] = new Date();
      }
    }
    if (data.hasOwnProperty('showscores')) {
      data.show_scores_during = (data.showscores === 'during');
    }
    if (data.hasOwnProperty('regen')) {
      data.regens_remaining = (data.regens_max - data.regen - 1);
    }
    if (data.hasOwnProperty('enableMQ')) {
      store.enableMQ = data.enableMQ;
    }
    if (data.hasOwnProperty('enddate_in') && data.enddate_in > 0 &&
      store.timelimit_timer === null &&
      data.enddate_in < 20 * 24 * 60 * 60 // over 20 days causes int overlow
    ) {
      window.clearTimeout(store.enddate_timer);
      const now = new Date().getTime();
      const dueat = data.enddate_in * 1000;
      data.enddate_local = now + dueat;
      store.enddate_timer = setTimeout(() => { this.handleDueDate(); }, dueat);
    }
    if (data.hasOwnProperty('timelimit_expiresin')) {
      window.clearTimeout(store.timelimit_timer);
      window.clearTimeout(store.enddate_timer); // no need for it w timelimit timer
      const now = new Date().getTime();
      if (data.hasOwnProperty('timelimit_expires')) {
        if (data.timelimit_expires === data.enddate) {
          store.timelimit_restricted = 1;
        } else if (data.timelimit_grace === data.enddate) {
          store.timelimit_restricted = 2;
        }
      }
      const expires = data.timelimit_expiresin * 1000;
      const grace = data.timelimit_gracein * 1000;

      data.timelimit_local_expires = now + expires;
      if (grace > 0) {
        data.timelimit_local_grace = now + grace;
      } else {
        data.timelimit_local_grace = 0;
      }
      if (expires > 0) {
        if (data.timelimit_gracein > 0) {
          store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, grace);
        } else {
          store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, expires);
        }
        store.timelimit_expired = false;
        store.timelimit_grace_expired = false;
      } else {
        store.timelimit_expired = true;
        store.timelimit_grace_expired = true;
        if (data.timelimit_gracein > 0) {
          if (grace > 0) {
            store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, grace);
            store.timelimit_grace_expired = false;
          }
        }
      }
    } else if (data.timelimit > 0) { // haven't started timed assessment yet
      if (data.enddate_in < data.timelimit) {
        store.timelimit_restricted = 1;
      } else if (data.enddate_in < data.timelimit + data.overtime_grace) {
        store.timelimit_restricted = 2;
      } else {
        store.timelimit_restricted = 0;
      }
    }
    if (data.hasOwnProperty('questions') && data.hasOwnProperty('interquestion_text')) {
      // map and override previous interquestion_text, if map defined
      mapInterquestionTexts(data, data.questions);
    }
    if (data.hasOwnProperty('interquestion_text')) {
      mapInterquestionPages(data, data.questions);
    }
    if (data.hasOwnProperty('noprint') && data.noprint === 1) {
      // want to block printing - inject print styles
      const styleEl = document.createElement('style');
      styleEl.type = 'text/css';
      styleEl.media = 'print';
      styleEl.innerText = 'body { display: none;}';
      document.head.appendChild(styleEl);
    }
    if (data.hasOwnProperty('livepoll_server') && store.livepollServer === '') {
      // inject socket script.
      const scriptEl = document.createElement('script');
      scriptEl.src = 'https://' + data.livepoll_server + ':3000/socket.io/socket.io.js';
      document.head.appendChild(scriptEl);
      // save for later
      store.livepollServer = data.livepoll_server;
    }
    if (data.hasOwnProperty('useMQ')) {
      if (data.useMQ === true && !store.enableMQ) {
        this.enableMQ();
      } else if (data.useMQ === false && store.enableMQ) {
        this.disableMQ();
      }
    }
    return data;
  }
};
