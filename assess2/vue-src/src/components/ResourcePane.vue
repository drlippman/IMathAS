<template>
  <div id="resource-pane">
    <div class="pane-header">
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
          v-if = "docEl.offsetWidth > 1000"
          :title = "$t('resource.sidebar')"
          :aria-label = "$t('resource.sidebar') + ' ' + curResource.label"
        >
          <icons
            name="sidebar"
          />
        </button>

        <a
          class="abutton"
          :href="curResource.link"
          :target="'res' + index"
          :title = "$t('resource.newtab')"
          :aria-label = "$t('resource.newtab') + ' ' + curResource.label"
        >
          <icons
            name="extlink"
          />
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
        ['skip-question-header', 'resource-dropdown']
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
</style>
