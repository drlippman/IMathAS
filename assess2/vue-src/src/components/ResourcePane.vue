<template>
  <div id="resource-pane" v-if="assessResources.length > 0">
    <div class="pane-header">
      <icons v-if="showicon" name="file" />
      Resources
    </div>
    <div class="pane-body">
      <div v-for="(curResource,index) in assessResources"
        :key="index"
        class="flexrow"
      >
        <span style="flex-grow: 1; margin-right:12px;">{{ curResource.label }}</span>

        <button
          @click="popout(curResource)"
          class = "min1000"
          :title = "$t('resource.sidebar')"
          :aria-label = "$t('resource.sidebar') + ' ' + curResource.label"
        >
          <icons name="sidebar" alt="" />
        </button>

        <a
          class="abutton"
          :href="curResource.link"
          :target="'res' + index"
          :title = "$t('resource.newtab')"
          :aria-label = "$t('resource.newtab') + ' ' + curResource.label"
        >
          <icons name="extlink" alt="" />
        </a>

      </div>
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'ResourcePane',
  props: ['showicon'],
  components: {
    Icons
  },
  computed: {
    assessResources () {
      return store.assessInfo.resources;
    },
    docEl () {
      return window.document.documentElement;
    }
  },
  methods: {
    popout (res) {
      window.GB_show(
        res.label,
        res.link,
        760,
        'auto',
        false,
        'left',
        ['skip-question-header', 'assess-header', 'resource-dropdown']
      );
    }
  }
};
</script>

<style>
#resource-pane {
  padding: 0;
  margin: 0;
  min-width: 160px;
}
#resource-pane .flexrow {
  margin-bottom: 5px;
  align-items: center;
}
#resource-pane .pane-header, #resource-pane .pane-body {
  padding: 8px 12px;
}
#resource-list {
  list-style-type: none;
  margin: 0;
  padding: 0;
}
#resource-list li {
  padding: 8px 0;
}
@media only screen and (max-width: 1000px) {
  .min1000 {
    display: none;
  }
}
</style>
