<template>
  <div>
    <span :id="'qhelp' + qn">
      {{ $t('helps.help') }}<span class="sr-only">
      {{ $t('question_n', {n: qn+1}) }}</span>:
    </span>
    <ul class="helplist" :aria-labelledby="'qhelp' + qn">
      <li v-for="(qHelp,idx) in qHelps" :key="idx">
        <tooltip-span :tip="qHelp.descr">
          <a href="#" @click.prevent="loadHelp(qHelp)">
            <icons :name="qHelp.icon" alt=""/>
            {{ qHelp.title }}
            <span :class="{'sr-only': !showCnts}">
              {{ qHelp.cnt }}
            </span>
            <span v-if="qHelp.descr" class="sr-only">
              {{qHelp.descr}}
            </span>
          </a>
        </tooltip-span>
      </li>
      <li v-if="showMessage">
        <a :href="messageHref" target="help">
          <icons name="message" />
          {{ $t('helps.message_instructor') }}
        </a>
      </li>
      <li v-if="postToForum > 0">
        <a :href="forumHref" target="help">
          <icons name="forum" />
          {{ $t('helps.post_to_forum') }}
        </a>
      </li>
    </ul>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';
import { store } from '../../basicstore';

export default {
  name: 'QuestionHelps',
  props: ['qn'],
  components: {
    Icons,
    TooltipSpan
  },
  computed: {
    showMessage () {
      return (store.assessInfo.hasOwnProperty('help_features') &&
        store.assessInfo.help_features.message === true
      );
    },
    postToForum () {
      return (store.assessInfo.hasOwnProperty('help_features') &&
        store.assessInfo.help_features.forum
      );
    },
    qHelps () {
      const labelcnt = {};
      if (store.assessInfo.questions[this.qn].jsparams) {
        const helps = store.assessInfo.questions[this.qn].jsparams.helps;
        for (const i in helps) {
          if (!labelcnt.hasOwnProperty(helps[i].label)) {
            labelcnt[helps[i].label] = 1;
          } else {
            labelcnt[helps[i].label]++;
          }
          if (helps[i].label === 'video') {
            helps[i].icon = 'video';
            helps[i].title = this.$t('helps.video');
          } else if (helps[i].label === 'read') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.read');
          } else if (helps[i].label === 'ex') {
            helps[i].icon = 'file';
            helps[i].title = this.$t('helps.written_example');
          } else {
            helps[i].icon = 'file';
            helps[i].title = helps[i].label;
          }
          helps[i].cnt = labelcnt[helps[i].label];
        }
        return helps;
      } else {
        return [];
      }
    },
    showCnts () {
      return (this.qHelps.filter(a => a.cnt > 1).length > 0);
    },
    quoteQ () {
      const qsid = store.assessInfo.questions[this.qn].questionsetid;
      const seed = store.assessInfo.questions[this.qn].seed;
      const ver = 2; // TODO: send from backend
      return this.qn + '-' + qsid + '-' + seed + '-' + store.aid + '-' + ver;
    },
    messageHref () {
      let href = window.imasroot + '/msgs/msglist.php?';
      href += window.$.param({
        cid: store.cid,
        add: 'new',
        quoteq: this.quoteQ,
        to: 'instr'
      });
      return href;
    },
    forumHref () {
      let href = window.imasroot + '/forums/thread.php?';
      href += window.$.param({
        cid: store.cid,
        forum: store.assessInfo.help_features.forum,
        modify: 'new',
        quoteq: this.quoteQ
      });
      return href;
    }
  },
  methods: {
    loadHelp (help) {
      // record click if ref is provided
      if (help.ref) {
        const refpts = help.ref.split(/-/);
        const prefix = 'Q' + refpts[1] + ': ';
        if (help.url.match(/watchvid\.php/)) {
          const cp = help.url.split(/url=/);
          window.recclick('extref', help.ref, prefix + decodeURIComponent(cp[1]));
        } else {
          window.recclick('extref', help.ref, prefix + help.url);
        }
      }
      window.popupwindow('help', help.url, help.w, help.h, true);
    }
  }
};
</script>

<style>
ul.helplist {
  margin-left: 8px;
  padding-left: 0;
  display: inline;
}
ul.helplist li {
  opacity: .8;
  list-style-type: none;
  margin-left: 0;
  display: inline;
  margin-right: 10px;
}

</style>
