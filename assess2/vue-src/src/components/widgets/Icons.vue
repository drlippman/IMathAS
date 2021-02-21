<template>
  <i>
    <svg
      xmlns = "http://www.w3.org/2000/svg"
      class = "svgicon"
      role = "img"
      aria-hidden = "true"
      viewBox="0 0 24 24"
      :width = "iconwidth"
      :height = "iconwidth"
      fill="none"
      :stroke="stroke"
      :stroke-width="strokewidth"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <template v-if="name=='retake'">
        <polyline points="17 1 21 5 17 9"></polyline>
        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
        <polyline points="7 23 3 19 7 15"></polyline>
        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
      </template>
      <template v-else-if="name=='calendar'">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="16" y1="2" x2="16" y2="6"></line>
        <line x1="8" y1="2" x2="8" y2="6"></line>
        <line x1="3" y1="10" x2="21" y2="10"></line>
      </template>
      <template v-else-if="name=='retry'">
        <polyline points="1 4 1 10 7 10"></polyline>
        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
      </template>
      <template v-else-if="name=='alert'">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12" y1="9" x2="12" y2="13"></line>
        <line x1="12" y1="17" x2="12" y2="17"></line>
      </template>
      <template v-else-if="name=='info'">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="16" x2="12" y2="12"></line>
        <line x1="12" y1="8" x2="12" y2="8"></line>
      </template>
      <template v-else-if="name=='timer'">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </template>
      <template v-else-if="name=='lock'">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
      </template>
      <template v-else-if="name=='square-check'">
        <polyline points="9 11 12 14 22 4"></polyline>
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
      </template>
      <template v-else-if="name=='group'">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
      </template>
      <template v-else-if="name=='incorrect'">
        <path :stroke="colors.wrong" d="M18 6 L6 18 M6 6 L18 18" />
      </template>
      <template v-else-if="name=='correct'">
        <polyline :stroke="colors.correct" points="20 6 9 17 4 12"></polyline>
      </template>
      <template v-else-if="name=='partial'">
        <path :stroke="colors.partial" d="M 5.3,10.6 9,14.2 18.5,4.6 21.4,7.4 9,19.8 2.7,13.5 z"/>
      </template>
      <template v-else-if="name=='dot'">
        <circle cx="12" cy="12" r="8" :fill="colors.neutral"></circle>
      </template>
      <template v-else-if="name=='unattempted'">
        <circle cx="12" cy="12" r="8" :fill="colors.neutral"></circle>
      </template>
      <template v-else-if="name=='partattempted'">
        <circle cx="12" cy="12" r="8" :stroke="colors.neutral"></circle>
      </template>
      <template v-else-if="name=='print'">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </template>
      <template v-else-if="name=='left'">
        <polyline points="15 18 9 12 15 6"></polyline>
      </template>
      <template v-else-if="name=='right'">
        <polyline points="9 18 15 12 9 6"></polyline>
      </template>
      <template v-else-if="name=='downarrow'" >
        <polygon :fill="colors.dark" stroke-linecap="square" points="6 6 18 6 12 14"/>
      </template>
      <template v-else-if="name=='file'">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <line x1="16" y1="13" x2="8" y2="13"></line>
        <line x1="16" y1="17" x2="8" y2="17"></line>
        <polyline points="10 9 9 9 8 9"></polyline>
      </template>
      <template v-else-if="name=='close'">
        <path d="M18 6 L6 18 M6 6 L18 18" />
      </template>
      <template v-else-if="name=='message'">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
        <polyline points="22,6 12,13 2,6"></polyline>
      </template>
      <template v-else-if="name=='forum'">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
      </template>
      <template v-else-if="name=='video'">
        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
        <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>
      </template>
      <template v-else-if="name=='eqned'">
        <polyline points="1 10 4 10 7 22 10 2 22 2" />
        <rect x="14" y="7" width="6" height="12" rx="2" ry="2" />
      </template>
      <template v-else-if="name=='eqnedoff'">
        <polyline points="1 10 4 10 7 22 10 2 22 2" />
        <rect x="14" y="7" width="6" height="12" rx="2" ry="2" />
        <polyline points="0 2 24 22"></polyline>
      </template>
      <template v-else-if="name=='more'">
        <circle :fill="stroke" cx="12" cy="12" r="1" />
        <circle :fill="stroke" cx="12" cy="4" r="1" />
        <circle :fill="stroke" cx="12" cy="20" r="1" />
      </template>
      <template v-else-if="name=='clipboard'">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        <path d="M 9 12 h 6 M 9 16 h6"></path>
      </template>
      <template v-else-if="name=='extlink'">
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
        <polyline points="15 3 21 3 21 9"></polyline>
        <line x1="10" y1="14" x2="21" y2="3"></line>
      </template>
      <template v-else-if="name=='sidebar'">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="15" y1="3" x2="15" y2="21"></line>
      </template>
    </svg>
    <span class="sronly" v-if="alttext.length > 0">
      {{ alttext }}
    </span>
  </i>
</template>

<script>
// Icon SVG comes from https://feathericons.com/, MIT licensed

export default {
  name: 'Icons',
  data: function () {
    return {
      id: null,
      colors: {
        dark: '#000',
        neutral: '#00a',
        wrong: '#900',
        correct: '#090',
        partial: '#fb0',
        warn: '#900',
        subdued: '#757575'
      }
    };
  },
  props: ['name', 'size', 'color', 'alt'],
  computed: {
    alttext () {
      if (this.alt || this.alt === '') {
        if (this.alt === '') {
          return '';
        } else {
          return this.$t(this.alt);
        }
      } else {
        return this.$t('icons.' + this.name);
      }
    },
    stroke () {
      if (this.color) {
        if (this.colors.hasOwnProperty(this.color)) {
          return this.colors[this.color];
        } else {
          return this.color;
        }
      } else {
        return this.colors.dark;
      }
    },
    iconwidth () {
      switch (this.size) {
        case 'micro': return 12;
        case 'small': return 16;
        case 'medium': return 20;
      }
      return 16;
    },
    strokewidth () {
      switch (this.size) {
        case 'micro': return 2.5;
        case 'small': return 2;
        case 'medium': return 2;
      }
      return 2;
    }
  }
};
</script>

<style>
svg.svgicon {
  margin-bottom: -0.15em;
}
.sronly {
  position: absolute;
  left: -10000px;
  width: 1px;
  height: 1px;
  overflow: hidden;
}
</style>
