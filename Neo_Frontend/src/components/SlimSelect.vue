<template>
  <select
    class="custom-control"
    :class="stateClass"
    :name="name"
    :value="transformValue"
    autocomplete="off"
    :disabled="disabled"
    @change="updateValue"
  ></select>
</template>

<script>
  import SlimSelect from 'slim-select';

  export default {
    name: 'SlimSelect',
    props: {
      value: {
        required: true,
      },
      placeholder: {
        type: String,
        default: '',
      },
      name: {
        type: String,
        required: true,
      },
      state: {
        required: false,
      },
      options: {
        required: true,
        validator: (prop) => prop === null || Array.isArray(prop),
      },
      searchable: {
        type: Boolean,
        default: false,
      },
      trackBy: {
        type: String,
        default: 'id',
      },
      labelBy: {
        type: String,
        default: 'text',
      },
      disabled: Boolean,
    },
    data() {
      return {
        sel: null,
      };
    },
    mounted() {
      this.sel = new SlimSelect({
        select: this.$el,
        showSearch: this.searchable,
        placeholder: this.placeholder,
        allowDeselect: true,
        addToBody: true,
        data: this.selectOptions,
      });
      this.sel.set(this.transformValue);
    },
    beforeDestroy() {
      if (this.sel) {
        this.sel.destroy();
        this.sel = null;
      }
    },
    computed: {
      selectOptions() {
        return [
          {
            text: '',
            value: '',
            placeholder: true,
          },
          ...(this.options || []).map((opt) => ({ text: opt[this.labelBy], value: opt[this.trackBy] })),
        ];
      },
      transformValue() {
        let val = this.value;
        if (typeof this.value === 'object' && this.value != null) {
          val = this.value[this.trackBy];
        }
        return val;
      },
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
    },
    watch: {
      value() {
        this.sel.set(this.transformValue);
      },
      options() {
        if (!this.sel) return;
        this.sel.setData(this.selectOptions);
      },
      disabled(disabled) {
        if (!this.sel) return;
        if (disabled) {
          this.sel.disable();
        } else {
          this.sel.enable();
        }
      },
    },
    methods: {
      updateValue($ev) {
        this.$emit('input', $ev.target.value);
      },
    },
  };
</script>
