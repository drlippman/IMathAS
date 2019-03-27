<template>
  <ul class="helplist">
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
import Icons from '@/components/Icons.vue';
import { store } from '../basicstore';

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
        add: "new",
        quoteq: this.quoteQ,
        to: "instr"
      });
      return href;
    },
    forumHref () {
      let href = window.imasroot + '/forums/thread.php?';
      href += window.$.param({
        cid: store.cid,
        forum: store.assessInfo.help_features.forum,
        modify: "new",
        quoteq: this.quoteQ
      });
      return href;
    }
  }
}
</script>

<style>
ul.helplist {
  margin-left: 0;
  padding-left: 0;
}
ul.helplist li {
  list-style-type: none;
  margin-left: 0;
}
</style>
