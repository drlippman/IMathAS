<template>
  <div class="menubutton"
    @keyup.esc="toggleOpen(true)"
    @keydown.up.prevent = "handleUpDown(-1)"
    @keydown.down.prevent = "handleUpDown(1)"
    @keydown = "handleKeys"
    @focusin = "handleFocus"
    @focusout = "handleBlur"
  >
    <button
      :id = "id"
      ref = "button"
      :tabindex = "open?-1:0"
      :class = "{'nobutton': !!nobutton}"
      aria-haspopup = "true"
      :aria-controls = "id + '_wrap'"
      :aria-expanded = "open?'true':'false'"
      @click = "toggleOpen"
      @keydown.space.prevent = "toggleOpen"
    >
      <slot v-if="!hasButton" :option="options[selected]"></slot>
      <slot v-if="hasButton" name=button></slot>
      <icons class="mb_downarrow" v-if="!noarrow" name="downarrow" size="micro"/>
    </button>
    <ul
      v-if="open"
      role = "menu"
      :aria-labelledby="id"
      :aria-activedescendant="id + '_' + curSelected"
      :id = "id + '_wrap'"
      tabindex = "-1"
      :class = "{'menubutton-right': position=='right'}"
    >
      <li v-if="!!header" class="menubutton-header">
        {{ header }}
      </li>
      <li v-for="(option,index) in options" :key="index">
        <component
          v-bind = "getLinkProps(option,index)"
          @click = "toggleOpen"
          @mouseover = "curSelected = index"
          @click.native = "toggleOpen"
          @mouseover.native = "curSelected = index"
          :id = "id + '_' + index"
          :class="{'menubutton-focus': index==curSelected}"
          role = "menuitem"
          tabindex = "-1"
        >
          <slot v-if="hasSlot" :option="option"></slot>
          <template v-else>
            {{option.label}}
          </template>
        </component>
      </li>
    </ul>
  </div>
</template>

<script>
// This menu button follows the patterns recommended at
// https://www.w3.org/TR/wai-aria-practices/examples/menu-button/menu-button-actions-active-descendant.html

import Icons from '@/components/Icons.vue';

export default {
  name: 'MenuButton',
  model: {
    prop: 'selected',
    event: 'change'
  },
  props: ['options', 'selected', 'id', 'header', 'nobutton', 'noarrow', 'position', 'searchby'],
  components: {
    Icons
  },
  data: function () {
    return {
      open: false,
      curSelected: 0,
      keybuffer: '',
      closetimer: null
    };
  },
  computed: {
    hasButton () {
      return !!this.$scopedSlots['button'];
    },
    hasSlot () {
      return !!this.$scopedSlots['default'];
    }
  },
  methods: {
    getLinkProps (option, index) {
      if (option.internallink) {
        return {
          is: 'router-link',
          to: option.internallink
        };
      } else {
        return {
          is: 'a',
          href: option.link,
          target: '_blank'
        };
      }
    },
    toggleOpen (val) {
      if (typeof val === 'boolean') {
        this.open = val;
      } else {
        this.open = !this.open;
      }
      if (this.open) { // now open
        this.curSelected = this.selected ? this.selected : 0;
        this.$nextTick(this.setMenuHeight);
        this.$nextTick(this.scrollToCurrent);
        this.$nextTick(() => { document.getElementById(this.id + '_' + this.curSelected).focus(); });
      } else {
        this.$nextTick(() => { document.getElementById(this.id).focus(); });
      }
    },
    setMenuHeight () {
      let wrapper = document.getElementById(this.id + '_wrap');
      let wrapperHeight = wrapper.clientHeight;
      let wrapperTop = wrapper.getBoundingClientRect().top;
      let windowHeight = window.innerHeight;
      if (wrapperTop + wrapperHeight > windowHeight - 30) {
        wrapper.style.height = (windowHeight - wrapperTop - 30) + 'px';
      } else {
        wrapper.style.height = 'auto';
      }
    },
    scrollToCurrent () {
      let selectedEl = document.getElementById(this.id + '_' + this.curSelected);
      let selectedPos = selectedEl.offsetTop;
      let selectedHeight = selectedEl.clientHeight;
      let wrapper = document.getElementById(this.id + '_wrap');
      let wrapperHeight = wrapper.clientHeight;
      let offset = selectedPos - (wrapperHeight / 2 - selectedHeight / 2);
      wrapper.scrollTop = offset;
    },
    handleUpDown (val) {
      if (!this.open) {
        this.toggleOpen();
        if (val === 1) {
          this.curSelected = 0;
        } else if (val === -1) {
          this.curSelected = this.options.length - 1;
        }
      } else {
        this.curSelected = (this.curSelected + val + this.options.length) % this.options.length;
      }
      this.$nextTick(() => { document.getElementById(this.id + '_' + this.curSelected).focus(); });
    },
    processKeyBuffer (clear) {
      if (this.keybuffer !== '') {
        let regex = new RegExp('^' + this.keybuffer, 'i');
        for (let i in this.options) {
          let val = this.options[i][this.searchby].toString();
          if (val.match(regex)) {
            this.curSelected = i;
            this.$nextTick(this.scrollToCurrent);
            break;
          }
        }
      }
      if (clear) {
        this.keybuffer = '';
      }
    },
    handleKeys (event) {
      if (this.open) {
        let key = event.key.toLowerCase();
        if (key === 'home') {
          this.curSelected = 0;
        } else if (key === 'end') {
          this.curSelected = this.options.length - 1;
        } else if (!!this.searchby && this.options[0].hasOwnProperty(this.searchby) && ((key >= '0' && key <= '9') || (key >= 'a' && key <= 'z'))) {
          this.keybuffer += key;
          this.processKeyBuffer(false);
          setTimeout(() => this.processKeyBuffer(true), 300);
        }
      }
    },
    handleBlur () {
      this.closetimer = setTimeout(() => { this.open = false; }, 50);
    },
    handleFocus () {
      clearTimeout(this.closetimer);
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
.menubutton {
  position: relative;
}
.menubutton-right {
  right: 0px;
}
.menubutton button {
  margin: 0;
  padding: 8px 12px;
  background-color: #fff;
  color: #000;
}
.menubutton button.nobutton {
  border: 0;
  padding: 0;
}
.menubutton-focus {
  background-color: #f0f0f0;
}
.menubutton ul {
  z-index: 1000;
  background-color: #fff;
  display: block;
  position: absolute;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  box-shadow: 0 2px 32px 0 rgba(145, 158, 171, 0.3), 0 1px 3px 0 rgba(63, 63, 68, 0.15), 0 0 0 1px rgba(63, 63, 68, 0.05);
}
.menubutton li {
  margin: 0;
  padding: 0;
  border-bottom: 1px solid #ddd;
}
.menubutton li a {
  padding: 12px 20px;
  display: block;
  white-space: nowrap;
}
li.menubutton-header {
  padding: 12px 20px;
  display: block;
  font-weight: bold;
}
.menubutton a, .menubutton a:hover, .menubutton a:focus, .menubutton a:active {
  text-decoration: none;
  outline: none;
}
.menubutton a, .menubutton a:hover, .menubutton a:focus, .menubutton a:visited {
  color: #000;
}
.menubutton ul::-webkit-scrollbar {
    width: 12px;
}
.mb_downarrow {
  margin-left: 10px;
}

.menubutton ul::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
}

.menubutton ul::-webkit-scrollbar-thumb {
    border-radius: 10px;
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5);
}
</style>
