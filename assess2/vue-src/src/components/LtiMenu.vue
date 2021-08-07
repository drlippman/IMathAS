<template>
  <menu-button
    :options = "LtiOptions"
    position = "right"
    nobutton = "true"
    noarrow = "true"
    searchby = "label"
    id="ltimenubutton"
    :header = "$t('lti.more')"
  >
    <template v-slot:button>
      <icons name="more" size="medium"/>
    </template>
  </menu-button>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import MenuButton from '@/components/widgets/MenuButton.vue';
import { store } from '../basicstore';

/*
 Dropdown menu for LTI users
 Needs to show:
  x Link to set userprefs
    GB_show('"._('User Preferences')."','$imasroot/admin/ltiuserprefs.php?cid=$cid&greybox=true',800,'auto')
    Need to figure a better way to enact changes
  x Link to messages, when settings allow
    x Need to generate 'lti_showmsg' in loadassess
      $coursemsgset < 4 && msgtoinstr enabled
    x Need to genereate 'lti_msgcnt' in loadassess, maybe on load/score question too?
  Link to view scored when in review mode
    When in practice and ['prev_attempts'].length > 0
  Link to redeem latepass
    ref ainfo['can_use_latepass']
 */
export default {
  name: 'LtiMenu',
  components: {
    MenuButton,
    Icons
  },
  computed: {
    LtiOptions () {
      const out = [];
      out.push({
        label: this.$t('lti.userprefs'),
        onclick: () => {
          window.GB_show(
            this.$t('lti.userprefs'),
            store.APIbase + '../admin/ltiuserprefs.php?cid=' + store.cid + '&greybox=true',
            800, 'auto', true, 0, 0,
            { label: 'Update Info', func: 'doSubmit' }
          );
        }
      });
      /*
      Moved to separate msgs icon
      if (store.assessInfo['lti_showmsg']) {
        out.push({
          label: this.$tc('lti.msgs', store.assessInfo['lti_msgcnt']),
          link: store.APIbase + '../msgs/msglist.php?cid=' + store.cid,
          target: '_self'
        });
      } */
      // view scored assessment link
      if (store.assessInfo.in_practice &&
          store.assessInfo.prev_attempts.length > 0
      ) {
        out.push({
          label: this.$t('closed.view_scored'),
          link: store.APIbase + 'gbviewassess.php?cid=' + store.cid + '&aid=' + store.aid + '&uid=' + store.uid,
          target: '_self'
        });
      }
      if (store.assessInfo.can_use_latepass) {
        out.push({
          label: this.$t('lti.use_latepass'),
          link: store.APIbase + '../course/redeemlatepass.php?cid=' + store.cid + '&aid=' + store.aid,
          target: '_self'
        });
      }
      return out;
    }
  }
};
</script>
