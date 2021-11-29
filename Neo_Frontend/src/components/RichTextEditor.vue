<template>
  <div :class="stateClass">
    <quill-editor
      :options="editorOption"
      :value="value"
      :disabled="disabled"
      :class="[stateClass, collapseClass, codeviewClass]"
      @change="onEditorChange($event)"
    />
    <textarea
      class="form-control code-editor"
      v-model="htmlValue"
      @change="onHtmlChange($event)"
      :class="[codeviewClass, stateClass]"
      :disabled="disabled"
    />
  </div>
</template>

<script>
  export default {
    name: 'RichTextEditor',
    data() {
      return {
        collapse: false,
        codeview: false,
        editorOption: {
          placeholder: this.placeholder,
          theme: 'snow',
          modules: {
            toolbar: {
              container: [
                ['codeview', 'copy', { list: 'ordered' }, { list: 'bullet' }, 'bold', 'italic', 'underline', 'collapse'],
                [{ color: [] }, { background: [] }],
              ],
              handlers: {
                codeview: () => {
                  this.codeview = !this.codeview;
                },
                copy: () => {
                  document.execCommand('copy');
                },
                collapse: () => {
                  this.collapse = !this.collapse;
                },
              },
            },
          },
        },
        htmlValue: '',
        textValue: '',
        state: '',
      };
    },
    props: {
      rules: Object,
      name: {
        type: String,
        default: '',
      },
      placeholder: {
        type: String,
        default: '',
      },
      value: {
        type: String,
        default: '',
      },
      disabled: {
        type: Boolean,
        default: false,
      },
    },
    mounted() {
      this.htmlValue = this.value;
    },
    methods: {
      onEditorChange({ html, text }) {
        this.$emit('input', html);
        this.htmlValue = html;
        this.textValue = text;
        if (this.rules.max) {
          this.state = text.length - 1 <= this.rules.max;
        } else if (this.rules.required) {
          this.state = text.length > 1;
        }
      },
      onHtmlChange($event) {
        this.$emit('input', $event.target.value);
      },
    },
    computed: {
      stateClass() {
        switch (this.state) {
          case true:
            return 'is-valid';
          case false:
            return 'is-invalid';
          default:
            return '';
        }
      },
      collapseClass() {
        return this.collapse ? 'is-open' : '';
      },
      codeviewClass() {
        return this.codeview ? 'is-codeview' : '';
      },
    },
  };
</script>
