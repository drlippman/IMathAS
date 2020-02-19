<template>
  <ul class="helplist">
    <li>
      {{ $t('helps.help') }}:
    </li>
    <li v-for="(qHelp,idx) in qHelps" :key="idx">
      <a href="#" @click.prevent="loadHelp(qHelp)">
        <icons :name="qHelp.icon" />
        {{ qHelp.title }}
      </a>
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
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import { store } from '../../basicstore';

export default {
  name: 'QuestionHelps',
  props: ['qn'],
  components: {
    Icons
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
      if (store.assessInfo.questions[this.qn].jsparams) {
        let helps = store.assessInfo.questions[this.qn].jsparams.helps;
        for (let i in helps) {
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
        }
        return helps;
      } else {
        return [];
      }
    },
    quoteQ () {
      let qsid = store.assessInfo.questions[this.qn].questionsetid;
      let seed = store.assessInfo.questions[this.qn].seed;
      let ver = 2; // TODO: send from backend
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
        let refpts = help.ref.split(/-/);
        let prefix = 'Q' + refpts[1] + ': ';
        if (help.url.match(/watchvid\.php/)) {
          let cp = help.url.split(/url=/);
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
  margin-left: 0;
  padding-left: 0;
}
ul.helplist li {
  opacity: .8;
  list-style-type: none;
  margin-left: 0;
  display: inline;
  margin-right: 12px;
}

</style>
