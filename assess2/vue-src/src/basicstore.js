import Vue from 'vue';
import Router from './router';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  queryString: '',
  errorMsg: null,
  lastLoaded: [],
  inProgress: false,
  assessFormIsDirty: [],
  autosaveQueue: {},
  autosaveTimer: null,
  timelimit_timer: null,
  timelimit_expired: false
});

export const actions = {
  loadAssessData (callback) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'loadassess.php' + store.queryString,
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
        // reset store
        store.inProgress = false;
        store.timelimit_expired = false;
        clearTimeout(store.timelimit_timer);
        // parse response
        response = this.processSettings(response);
        store.assessInfo = response;
        if (typeof callback !== 'undefined') {
          callback();
        }
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  startAssess (dopractice, password, newGroupMembers) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'startassess.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        practice: dopractice,
        password: password,
        new_group_members: newGroupMembers.join(','),
        cur_group: store.assessInfo.stugroupid
      },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
      // overwrite properties with those from response
        response = this.processSettings(response);
        store.assessInfo = Object.assign({}, store.assessInfo, response);

        // route to correct display
        if (response.error) {
          store.errorMsg = response.error;
        } else if (store.assessInfo.has_active_attempt) {
          store.inProgress = true;
          if (store.assessInfo.displaymethod === 'skip') {
            if (store.assessInfo.intro != '') {
              Router.push('/skip/0' + store.queryString);
            } else {
              Router.push('/skip/1' + store.queryString);
            }
          } else if (store.assessInfo.displaymethod === 'full') {
            Router.push('/full' + store.queryString);
          }
        }
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadQuestion (qn, regen) {
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'loadquestion.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        qn: qn,
        practice: store.assessInfo.in_practice,
        regen: regen?1:0
      },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  submitQuestion (qns, endattempt, timeactive, partnum) {
    if (typeof qns !== 'object') {
      qns = [qns];
    }
    this.clearAutosave(qns);
    // don't store time active when full-test
    if (store.assessInfo.displaymethod === 'full') {
      timeactive = [];
    } else if (typeof timeactive !== 'object') {
      timeactive = [timeactive];
    }
    if (typeof window.tinyMCE != "undefined") {window.tinyMCE.triggerSave();}
    store.inTransit = true;

    // figure out non-blank questions to submit
    let lastLoaded = [];
    let nonBlank = {};
    let data = new FormData();
    for (let k=0; k<qns.length; k++) {
      let qn = qns[k];
      nonBlank[qn] = [];
      var regex = new RegExp("^(qn|tc|qs)("+qn+"\\b|"+(qn+1)+"\\d{3})");
      window.$("#questionwrap" + qn).find("input,select,textarea").each(function(i,el) {
        if (el.name.match(regex)) {
          let fieldBlank = true;
          if ((el.type!=='radio' && el.type!=='checkbox') || el.checked) {
            if (el.type==='file') {
              data.append(el.name, el.files[0]);
              fieldBlank = (el.files.length === 0);
            } else {
              data.append(el.name, el.value);
              fieldBlank = (el.value === '');
            }
            // add to non-blank question/part list
            if (!fieldBlank) {
              if (el.name.length < 6) {
                nonBlank[qn].push(0);
              } else {
                let pn = parseInt(el.name.substring(el.name.length-3));
                if (nonBlank[qn].indexOf(pn) === -1) {
                  nonBlank[qn].push(pn);
                }
              }
            }
          }
        }
      });
      lastLoaded[k] = store.lastLoaded[qn].getTime();
    };
    data.append('toscoreqn', qns.join(','));
    data.append('nonblank', JSON.stringify(nonBlank));
    data.append('timeactive', timeactive.join(','));
    data.append('lastloaded', lastLoaded.join(','));
    if (endattempt) {
      data.append('endattempt', endattempt);
    }
    if (store.assessInfo.in_practice) {
      data.append('practice', true);
    }
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
          store.errorMsg = response.error;
          return;
        }

        response = this.processSettings(response);
        // un-dirty submitted questions
        var loc;
        for (let k=0; k<qns.length; k++) {
          let qn = qns[k]*1;
          if ((loc = store.assessFormIsDirty.indexOf(qn)) !== -1) {
		    	    store.assessFormIsDirty.splice(loc,1);
		    	}
        }
        this.copySettings(response);
        if (endattempt) {
          store.inProgress = false;
          Router.push('/summary' + store.queryString);
        }

      })
      .always(response => {
        store.inTransit = false;
      });
  },
  doAutosave (qn, partnum) {
    window.clearTimeout(store.autosaveTimer);
    if (!store.autosaveQueue.hasOwnProperty(qn)) {
      store.autosaveQueue[qn] = [];
    }
    if (store.autosaveQueue[qn].indexOf(partnum) === -1) {
      store.autosaveQueue[qn].push(partnum);
    }
    store.autosaveTimer = window.setTimeout(() => {this.submitAutosave(true);}, 2000)
  },
  clearAutosave(qns) {
    for (let i in qns) {
      if (store.autosaveQueue.hasOwnProperty(qns[i])) {
        delete store.autosaveQueue[qns[i]];
      }
    }
    if (Object.keys(store.autosaveQueue).length === 0) {
      window.clearTimeout(store.autosaveTimer);
    }
  },
  clearAutosaveTimer() {
    window.clearTimeout(store.autosaveTimer);
  },
  submitAutosave (async) {
    window.clearTimeout(store.autosaveTimer);
    if (Object.keys(store.autosaveQueue).length === 0) {
      return;
    }
    store.inTransit = true;
    let lastLoaded = {};
    if (typeof window.tinyMCE != "undefined") {window.tinyMCE.triggerSave();}
    let data = new FormData();
    for (let qn in store.autosaveQueue) {
      // build up regex to match the inputs for all the parts we want to save
      let regexpts = [];
      for (let k in store.autosaveQueue[qn]) {
        let pn = store.autosaveQueue[qn][k];
        if (pn === 0) {
          regexpts.push(qn);
        }
        regexpts.push((qn*1+1)*1000 + pn*1);
      }
      let regex = new RegExp('^(qn|tc|qs)(' + regexpts.join('\\b|') + '\\b)');
      window.$("#questionwrap" + qn).find("input,select,textarea").each(function(i,el) {
        if (el.name.match(regex)) {
          if ((el.type!=='radio' && el.type!=='checkbox') || el.checked) {
            if (el.type==='file') {
              data.append(el.name, el.files[0]);
            } else {
              data.append(el.name, el.value);
            }
          }
        }
      });
      lastLoaded[qn] = store.lastLoaded[qn].getTime();
    };
    data.append('toscoreqn', JSON.stringify(store.autosaveQueue));
    data.append('lastloaded', JSON.stringify(lastLoaded));
    if (store.assessInfo.in_practice) {
      data.append('practice', true);
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
          store.errorMsg = response.error;
          return;
        }
        // clear autosave queue
        store.autosaveQueue = {};
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  handleTimelimitUp () {
    if (store.assessInfo.has_active_attempt) {
      // submit dirty questions and end attempt
      let tosub = (store.assessFormIsDirty.length > 0) ? store.assessFormIsDirty : -1;
      this.submitQuestion(tosub, true);
    }
    //store.timelimit_expired = true;
  },
  endAssess () {
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
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getScores () {
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
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  copySettings(response) {
    // overwrite existing questions with new data
    if (response.hasOwnProperty('questions')) {
      if (!store.assessInfo.hasOwnProperty('questions')) {
        store.assessInfo.questions = [];
      }
      for (let i in response.questions) {
        store.assessInfo.questions[parseInt(i)] = response.questions[i];
      }
      delete response.questions;
    }
    // copy other settings from response to store
    store.assessInfo = Object.assign({}, store.assessInfo, response);
  },
  processSettings (data) {
    // hack job temporary fix until we can do something better
    let svgchk = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="green" stroke-width="3" fill="none"><title>correct</title>';
    svgchk += '<polyline points="20 6 9 17 4 12"></polyline></svg>';
    let svgx = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="red" stroke-width="3" fill="none"><title>correct</title>';
    svgx += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
    if (data.hasOwnProperty('questions')) {
      for (let i in data.questions) {
        let thisq = data.questions[i];

        data.questions[i].canretry = (thisq.try < thisq.tries_max);
        data.questions[i].tries_remaining = thisq.tries_max - thisq.try;
        if (thisq.hasOwnProperty('parts')) {
          let trymin = 1e10;
          let trymax = 0;
          for (let pn in thisq.parts) {
            let remaining = thisq.tries_max - thisq.parts[pn].try;
            if (remaining < trymin) {
              trymin = remaining;
            }
            if (remaining > trymax) {
              trymax = remaining;
            }
          }
          if (trymin !== trymax) {
            data.questions[i].tries_remaining_range = [trymin, trymax];
          }
        }
        if (thisq.hasOwnProperty('regens_max') !== 'undefined' && thisq.regen < thisq.regens_max) {
          data.questions[i].canregen = true;
          data.questions[i].regens_remaining = thisq.regens_max - thisq.regen;
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
        // TODO: remove this hack
        if (data.questions[i].html !== null) {
          data.questions[i].html = data.questions[i].html
            .replace(/<img[^>]*gchk.gif[^>]*>/g, svgchk)
            .replace(/<img[^>]*redx.gif[^>]*>/g, svgx);
        }
        store.lastLoaded[i] = new Date();
      }
    }
    if (data.hasOwnProperty('showscores')) {
      data['show_scores_during'] = (data.showscores === 'during');
    }
    if (data.hasOwnProperty('regen')) {
      data['regens_remaining'] = (data.regens_max - data.regen);
    }
    if (data.hasOwnProperty('timelimit_expires')) {
      clearTimeout(store.timelimit_timer);
      let now = new Date();
      let expires = new Date(data.timelimit_expires * 1000);
      if (expires > now) {
        store.timelimit_timer = setTimeout(()=>{this.handleTimelimitUp();}, expires - now);
        store.timelimit_expired = false;
      } else {
        store.timelimit_expired = true;
      }
    }
    if (data.hasOwnProperty('interquestion_text')) {
      // ensure proper data type on these
      for (let i in data.interquestion_text) {
        data.interquestion_text[i].displayBefore = parseInt(data.interquestion_text[i].displayBefore);
        data.interquestion_text[i].displayUntil = parseInt(data.interquestion_text[i].displayUntil);
        data.interquestion_text[i].forntype = !!data.interquestion_text[i].forntype;
        data.interquestion_text[i].ispage = !!data.interquestion_text[i].ispage;
      }
    }
    return data;
  }
};
