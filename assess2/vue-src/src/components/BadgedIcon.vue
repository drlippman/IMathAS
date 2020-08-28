<template>
  <tooltip-span
    :tip="$tc(label, cnt)"
    style="display: inline-block"
  >
    <a
      :href="link"
      class = "noextlink badgewrap"
      target = "_blank"
      :aria-label = "$tc(label, cnt)"
    >
      <icons :name="icon" size="medium"/>
      <div class="badgeicon" v-if="cnt > 0">
        {{ cnt }}
      </div>
    </a>
  </tooltip-span>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';
import { store } from '../basicstore';

export default {
  name: 'BadgedIcon',
  props: ['link', 'icon', 'label', 'cnt'],
  components: {
    Icons,
    TooltipSpan
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
