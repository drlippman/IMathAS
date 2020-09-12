<template>
  <div class="menubutton"
    @keyup.esc="toggleOpen(false)"
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
      :class = "{'nobutton': !!nobutton, 'flex-nowrap-center': true}"
      aria-haspopup = "true"
      :aria-controls = "id + '_wrap'"
      :aria-expanded = "open?'true':'false'"
      @click = "toggleOpen"
      @keydown.space.prevent = "toggleOpen"
    >
      <slot v-if="!hasButton" :option="options[selected]" :selected="true"></slot>
      <tooltip-span :show="!open" :tip="header">
        <slot v-if="hasButton" name=button></slot>
      </tooltip-span>
      <icons class="mb_downarrow" v-if="!noarrow" name="downarrow" size="micro"/>
    </button>
    <transition name="fade">
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
        <li v-for="(option,index) in filteredOptions" :key="index"
          :class="{'listsubitem': option.subitem}"
        >
          <router-link
            v-if = "option.internallink"
            :to = "option.internallink"
            @click.native = "toggleOpen"
            @mouseover.native = "curSelected = index"
            :id = "id + '_' + index"
            :class="{'menubutton-focus': index==curSelected}"
            role = "menuitem"
            tabindex = "-1"
          >
            <slot v-if="hasSlot" :option="option" :selected="false"></slot>
            <template v-else>
              {{option.label}}
            </template>
          </router-link>
          <component
            v-else
            v-bind = "getLinkProps(option,index)"
            @click = "linkClick($event,option)"
            @keydown.enter = "linkClick($event,option)"
            @mouseover = "curSelected = index"
            :id = "id + '_' + index"
            :class="{'menubutton-focus': index==curSelected}"
            :role = "option.onclick ? 'menuitem button' : 'menuitem'"
            tabindex = "-1"
          >
            <slot v-if="hasSlot" :option="option" :selected="false"></slot>
            <template v-else>
              {{option.label}}
            </template>
          </component>
        </li>
      </ul>
    </transition>
  </div>
</template>

<script>
// This menu button follows the patterns recommended at
// https://www.w3.org/TR/wai-aria-practices/examples/menu-button/menu-button-actions-active-descendant.html

import Icons from '@/components/widgets/Icons.vue';
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';

export default {
  name: 'MenuButton',
  model: {
    prop: 'selected',
    event: 'change'
  },
  props: ['options', 'selected', 'id', 'header', 'nobutton', 'noarrow', 'position', 'searchby'],
  components: {
    Icons,
    TooltipSpan
  },
  data: function () {
    return {
      open: false,
      curSelected: 0,
      keybuffer: '',
      closetimer: null,
      screenwidth: 1200
    };
  },
  computed: {
    hasButton () {
      return !!this.$scopedSlots.button;
    },
    hasSlot () {
      return !!this.$scopedSlots.default;
    },
    filteredOptions () {
      return this.options.filter(a => (a !== null));
    }
  },
  methods: {
    getLinkProps (option, index) {
      if (option.internallink) {
        return {
          is: 'router-link',
          to: option.internallink
        };
      } else if (option.link) {
        return {
          is: 'a',
          href: option.link,
          target: option.target || '_blank'
        };
      } else {
        return {
          is: 'span'
        };
      }
    },
    linkClick (event, option) {
      if (option.link && option.popup) {
        event.preventDefault();
        window.GB_show(option.label, option.link, 400, 400, false);
        this.toggleOpen(false, true);
      }
      if (option.onclick) {
        event.preventDefault();
        option.onclick();
        this.toggleOpen(false, true);
      }
    },
    toggleOpen (val, nofocus) {
      if (typeof val === 'boolean') {
        this.open = val;
      } else {
        this.open = !this.open;
      }
      if (this.open) { // now open
        this.screenwidth = document.documentElement.offsetWidth;
        this.curSelected = this.selected ? this.selected : 0;
        this.$nextTick(this.setMenuHeight);
        this.$nextTick(this.scrollToCurrent);
        this.$nextTick(() => { document.getElementById(this.id + '_' + this.curSelected).focus(); });
      } else if (!nofocus) {
        this.$nextTick(() => { document.getElementById(this.id).focus(); });
      }
    },
    setMenuHeight () {
      const wrapper = document.getElementById(this.id + '_wrap');
      const bndbox = wrapper.getBoundingClientRect();
      const wrapperHeight = wrapper.clientHeight;
      const wrapperTop = bndbox.top;
      const windowHeight = window.innerHeight;
      if (wrapperTop + wrapperHeight > windowHeight - 30) {
        wrapper.style.height = (windowHeight - wrapperTop - 30) + 'px';
      } else {
        wrapper.style.height = 'auto';
      }
      wrapper.style.left = '';
      wrapper.style.right = '';
      if (bndbox.left < 0) {
        wrapper.style.left = '0px';
        wrapper.style.right = 'auto';
      }
    },
    scrollToCurrent () {
      const selectedEl = document.getElementById(this.id + '_' + this.curSelected);
      const selectedPos = selectedEl.offsetTop;
      const selectedHeight = selectedEl.clientHeight;
      const wrapper = document.getElementById(this.id + '_wrap');
      const wrapperHeight = wrapper.clientHeight;
      const offset = selectedPos - (wrapperHeight / 2 - selectedHeight / 2);
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
        const regex = new RegExp('^' + this.keybuffer, 'i');
        for (const i in this.options) {
          const val = this.options[i][this.searchby].toString();
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
        const key = event.key.toLowerCase();
        if (key === 'home') {
          this.curSelected = 0;
        } else if (key === 'end') {
          this.curSelected = this.options.length - 1;
        } else if (!!this.searchby &&
          this.options[Object.keys(this.options)[0]].hasOwnProperty(this.searchby) &&
          ((key >= '0' && key <= '9') || (key >= 'a' && key <= 'z'))
        ) {
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

<style>
.menubutton {
  position: relative;
  display: inline-block;
}
.menubutton-right {
  right: 0px;
}
.menubutton button {
  margin: 0;
  padding: 8px 12px;
  background-color: #fff;
  color: #000;
  text-align: left;
  height: auto;
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
  border: 1px solid #ccc;
  box-shadow: 1px 3px 8px 0 rgba(0,0,0,0.25);
}
.menubutton li {
  margin: 0;
  padding: 0;
  border-bottom: 1px solid #ddd;
}
.menubutton li a, .menubutton li > span {
  padding: 12px 20px;
  display: block;
  white-space: nowrap;
  cursor: pointer;
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
  margin-left: 8px;
  margin-right: -5px;
}

.menubutton ul::-webkit-scrollbar-track {
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
}

.menubutton ul::-webkit-scrollbar-thumb {
    border-radius: 10px;
    -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.5);
}
/*.menubutton .listsubitem {
  border-left: 8px solid #dfe3e8;
}*/
.menubutton .listsubitem > span > span{
  margin-left: 16px;
}
</style>
