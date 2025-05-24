<template>
  <div>
    <ul class="vuetablist" :id="id">
      <li
        v-for="(tab,index) in tabs"
        :key = "index"
        :class = "{active: tab.hash === activeTabHash}"
        :aria-selected = "tab.hash === activeTabHash"
        ref = "tab"
        tabindex = "0"
        @click = "setActive(index)"
        @keydown = "handleKey($event, index)"
        :aria-controls = "tab.hash"
        :id = "tab.hash + '_tab'"
      >
        {{ tab.name }}
      </li>
    </ul>
    <slot ref="slot"></slot>
  </div>
</template>

<script>
// This uses the a11y recommendations from
// https://simplyaccessible.com/article/danger-aria-tabs/
// along with the design patterns recommended at
// https://www.w3.org/TR/wai-aria-practices/examples/tabs/tabs-2/tabs.html
// vue 3 changes from https://zerotomastery.io/blog/tab-component-design-with-vue/
import { computed } from 'vue';

export default {
  name: 'VueTabs',
  props: ['id'],
  data: function () {
    return {
      activeTabHash: '',
      tabs: []
    };
  },
  provide () {
    return {
      addTab: (tab) => {
        const count = this.tabs.push(tab);

        if (count === 1) {
          this.activeTabHash = tab.hash;
        }
      },
      activeTabHash: computed(() => this.activeTabHash)
    };
  },
  methods: {
    setActive (index) {
      this.activeTabHash = this.tabs[index].hash;
      this.$nextTick(() => {
        document.getElementById(this.tabs[index].hash).focus();
      });
    },
    setFocus (index) {
      this.$refs.tab[index].focus();
    },
    handleKey (event, index) {
      const cnt = this.tabs.length;
      const key = event.key.toLowerCase();
      let used = false;
      if (key === 'enter' || key === ' ') {
        this.setActive(index);
        used = true;
      } else if (key === 'arrowleft') {
        this.setFocus((index - 1 + cnt) % cnt);
        used = true;
      } else if (key === 'arrowright') {
        this.setFocus((index + 1) % cnt);
        used = true;
      } else if (key === 'home') {
        this.setFocus(0);
        used = true;
      } else if (key === 'end') {
        this.setFocus(cnt - 1);
        used = true;
      }
      if (used) {
        event.preventDefault();
        event.stopPropagation();
      }
    }
  }
};
</script>

<style>
ul.vuetablist {
  border-bottom: 1px solid #ccc;
  padding-left: 16px;
}
ul.vuetablist li {
  display: inline-block;
  margin-bottom: -1px;
  background-color: #ddd;
  color: #666;
  padding: 4px 8px;
  border: 1px solid #ccc;
  cursor: default;
}
ul.vuetablist li.active {
  border-bottom: 1px solid #fff;
  background-color: #fff;
  color: #000;
}
</style>
