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
        <span style="flex-grow: 1">{{ curResource.label }}</span>
        <button
          class="plain"
          @click="popout(curResource)"
          v-if = "docEl.offsetWidth > 1000"
        >
          Pop
        </button>
        <a :href="curResource.link" target="_blank">
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';

export default {
  name: 'ResourcePane',
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

#resource-list {
  list-style-type: none;
  margin: 0;
  padding: 0;
}
#resource-list li {
  padding: 8px 0;
}
</style>
