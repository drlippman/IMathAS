<template>
  <div>
    <ul class="vuetablist" :id="id">
      <li
        v-for="(tab,index) in tabs"
        :class = "{active: index === activeTab}"
        :aria-selected = "index === activeTab"
        ref = "tab"
        tabindex = "0"
        @click = "setActive(index)"
        @keydown = "handleKey($event, index)"
        :aria-controls = "tab.id"
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

export default {
  name: 'VueTabs',
  props: ['id'],
  data: function () {
    return {
      activeTab: 0,
      tabs: []
    }
  },
  methods: {
    setActive(index) {
      for (let i in this.tabs) {
        this.tabs[i].active = (i*1 === index*1);
      }
      this.activeTab = index;
      this.$nextTick(() => {
        document.getElementById(this.tabs[index].id).focus();
      });
    },
    setFocus(index) {
      this.$refs.tab[index].focus();
    },
    handleKey(event, index) {
      let cnt = this.tabs.length;
      let key = event.key.toLowerCase();
      if (key === 'enter' || key === ' ') {
        this.setActive(index);
      } else if (key === 'arrowleft') {
        this.setFocus((index-1+cnt)%cnt);
      } else if (key === 'arrowright') {
        this.setFocus((index+1)%cnt);
      } else if (key === 'home') {
        this.setFocus(0);
      } else if (key === 'end') {
        this.setFocus(cnt-1);
      }
    }
  },
  mounted() {
    this.tabs = this.$children;
    for (let i in this.tabs) {
      this.tabs[i].control = this.id + "_" + i;
      this.tabs[i].id = this.id + "_" + i + "_pane";
    }
    this.tabs[0].active = true;
  }

}
</script>

<style>
ul.vuetablist {
  border-bottom: 1px solid #ccc;
  padding-left: 12px;
}
ul.vuetablist li {
  display: inline-block;
  margin-bottom: -1px;
  background-color: #ddd;
  color: #666;
  padding: 4px 8px;
  border: 1px solid #ccc;
}
ul.vuetablist li.active {
  border-bottom: 1px solid #fff;
  background-color: #fff;
  color: #000;
}
</style>
