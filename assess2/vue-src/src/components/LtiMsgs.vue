<template>
  <a
    :href="msglink"
    class = "noextlink badgewrap"
    target = "_blank"
    :aria-label = "$tc('lti.msgs', msgcnt)"
  >
    <icons name="message" size="medium"/>
    <div class="badgeicon" v-if="msgcnt > 0">
      {{ msgcnt }}
    </div>
  </a>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'LtiMsgs',
  components: {
    Icons
  },
  computed: {
    msglink () {
      return store.APIbase + '../msgs/msglist.php?cid=' + store.cid;
    },
    msgcnt () {
      return store.assessInfo.lti_msgcnt;
    }
  }
};
</script>

<style>
.badgewrap {
  display: inline-block;
  position: relative;
}
.badgeicon {
  position: absolute;
  right: -2px;
  bottom: 0px;
  background-color: #900;
  color: #fff;
  font-weight: bold;
  font-size: 9px;
  height: 12px;
  width: 12px;
  border-radius: 6px;
  text-align: center;
}
</style>
