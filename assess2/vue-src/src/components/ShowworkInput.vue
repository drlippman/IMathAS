<template>
  <div class="showworkwrap">
    <div v-if="worktype === 0">
      {{ $t("question.showwork") }}
      <textarea
        :id="computedId"
        ref="inbox"
        class="fbbox swbox"
        :rows="rows"
      ></textarea>
    </div>
    <div v-else class="feedbackwrap">
      {{ $t("question.uploadwork") }}
      <ul class="nomark">
        <li v-for="(file,index) in filelist" :key="index">
          <a :href="file" class="attach" target="_blank">{{ file.split('/').pop() }}</a>
          <button @click="removeFile(index)">
            {{ $t('group.remove') }}
          </button>
        </li>
        <li>
          <input type="file" ref="fileinput" @change="uploadFile" />
          <span class="noticetext" v-if="uploading">
            {{ $t('question.uploading') }}
          </span>
        </li>
      </ul>
      <div>

      </div>
    </div>
  </div>
</template>

<style>
.showworkwrap {
  max-width: 700px;
}
</style>
<script>
import { store, actions } from '../basicstore';

export default {
  name: 'ShowworkInput',
  props: {
    id: { default: null },
    value: { default: '' },
    rows: { default: 2 },
    active: { default: true }
  },
  data: function () {
    return {
      objTinymce: null,
      filelist: [],
      uploading: false
    };
  },
  computed: {
    computedId: function () {
      if (this.id === 'editor' || this.id === '' || this.id === null) {
        return 'editor-' + this.guidGenerator(); // put default value on computedId
      } else {
        return this.id;
      }
    },
    worktype: function () {
      return (store.assessInfo.showworktype === 4) ? 1 : 0;
    }
  },
  mounted: function () {
    if (this.worktype === 0) {
      this.$refs.inbox.innerHTML = this.value;
    }
    if (this.active) {
      this.initEditor();
    }
  },
  updated: function () {
    if (this.active && this.worktype === 0) {
      this.initEditor();
    }
  },
  methods: {
    guidGenerator: function () {
      function s4 () {
        return Math.random().toString(36).substr(2, 9);
      }
      return 'ed-' + s4() + '-' + s4();
    },
    initEditor () {
      var component = this;
      if (this.worktype === 1) {
        window.$(this.$refs.fileinput).on('focus', function (e) {
          component.$emit('focus', true);
        });
        this.updateFilelist(this.value);
        this.objTinymce = { value: this.value };
      } else if (window.tinymce) {
        window.initeditor('exact', this.computedId, null, false, function (ed) {
          ed.on('input change keyup undo redo', function (e) {
            component.updateValue(ed.getContent());
          }).on('blur', function (e) {
            component.$emit('blur', true);
          }).on('focus', function (e) {
            component.$emit('focus', true);
          });
          component.objTinymce = ed;
        });
      } else {
        window.$(this.$refs.inbox).on('focus', function (e) {
          component.$emit('focus', true);
        }).on('blur', function (e) {
          component.$emit('blur', true);
        }).on('input change keyup undo redo', function (e) {
          component.updateValue(e.target.value);
        });
        component.objTinymce = this.$refs.inbox;
      }
    },
    updateFilelist: function (value) {
      const out = [];
      const m = value.match(/<(a[^>]*href|img[^>]*src)="(.*?)"/g);

      if (m !== null) {
        for (let i = 0; i < m.length; i++) {
          out.push(m[i].replace(/^.*?(href|src)="(.*?)"/, '$2'));
        }
      }
      this.filelist = out;
    },
    updateValue: function (value) {
      this.$emit('input', value);
    },
    focus: function () {
      this.objTinymce.focus();
    },
    uploadFile: function () {
      const data = new FormData();
      data.append('type', 'attach');
      data.append('file', this.$refs.fileinput.files[0]);
      this.uploading = true;
      window.$.ajax({
        url: store.APIbase + '../tinymce4/upload_handler.php',
        type: 'POST',
        dataType: 'json',
        data: data,
        processData: false,
        contentType: false,
        xhrFields: {
          withCredentials: true
        },
        crossDomain: true
      })
        .done(response => {
          if (response.location) {
            this.filelist.push(response.location);
            this.updateValueFromFilelist();
          } else {
            actions.handleError('file_upload_error');
          }
        })
        .fail(response => {
          actions.handleError('file_upload_error');
        })
        .always(() => {
          this.$refs.fileinput.value = null;
          this.uploading = false;
        });
    },
    removeFile: function (index) {
      store.confirmObj = {
        body: 'work.remove',
        action: () => {
          const todel = this.filelist[index];
          this.filelist.splice(index, 1);
          this.updateValueFromFilelist();
          // actually delete file, if possible
          window.$.ajax({
            url: store.APIbase + '../tinymce4/upload_handler.php',
            type: 'POST',
            dataType: 'json',
            data: { remove: todel },
            xhrFields: {
              withCredentials: true
            },
            crossDomain: true
          });
        }
      };
    },
    updateValueFromFilelist: function () {
      let out = '';
      if (this.filelist.length > 0) {
        out += '<ul class="nomark">';
        for (let i = 0; i < this.filelist.length; i++) {
          out += '<li><a href="' + this.filelist[i] + '" class="attach" target="_blank">' + (this.filelist[i].split('/').pop()) + '</a></li>';
        }
        out += '</ul>';
      }
      this.objTinymce.value = out;
      this.$emit('input', out);
      this.$emit('blur', out); // this triggers autosave
    }
  },
  watch: {
    active: function (newValue, oldValue) {
      if (newValue === true && this.objTinymce === null) {
        this.initEditor();
      }
    },
    value: function (newValue, oldValue) {
      if (typeof newValue !== 'string') {
        // handle null and undefined
        newValue = '';
      }
      // if v-model content change programmability
      if (this.worktype === 1) {
        if (newValue !== this.objTinymce.value) {
          this.updateFilelist(newValue);
        }
      } else if (window.tinymce) {
        if (newValue !== this.objTinymce.getContent()) {
          this.objTinymce.setContent(newValue);
        }
      } else {
        if (newValue !== this.objTinymce.value) {
          this.objTinymce.value = newValue;
        }
      }
    }
  }
};
</script>
