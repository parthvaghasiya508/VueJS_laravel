<template>
  <div class="switcher-wrapper" :class="stateClass">
    <input
      type="checkbox"
      :id="guid"
      :value="value"
      v-model="checkedProxy"
      @change="onChange"
      @click="attemptChange"
      :disabled="disabled"
    />
    <label class="switcher-label" :for="guid" :class="{ colored, labels, small, medium }">
      <span class="switcher-text" v-if="onLabel" v-text="onLabel"/>
      <span class="switcher-button"></span>
      <span class="switcher-text" v-if="offLabel" v-text="offLabel"/>
    </label>
  </div>
</template>

<script>
  export default {
    name: 'Switcher',
    model: {
      prop: 'checked',
      event: 'change',
    },
    props: {
      checked: {
        default: false,
        requred: true,
      },
      value: {
        required: false,
      },
      name: {
        type: String,
        required: false,
      },
      state: {
        required: false,
      },
      disabled: Boolean,
      colored: {
        default: false,
        type: Boolean,
      },
      onLabel: {
        default: null,
        type: String,
      },
      offLabel: {
        default: null,
        type: String,
      },
      lazy: Boolean,
      small: {
        type: Boolean,
        default: false,
      },
      medium: {
        type: Boolean,
        default: false,
      },
    },
    data: () => ({ guid: null, proxy: false }),
    created() {
      this.guid = this.name || this.$uid;
    },
    computed: {
      labels() {
        return this.onLabel != null && this.offLabel != null;
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
      checkedProxy: {
        get() { return this.checked; },
        set(val) { this.proxy = val; },
      },
    },
    methods: {
      onChange() {
        this.$emit('change', this.proxy);
      },
      attemptChange(ev) {
        if (!this.lazy) return;
        ev.stopPropagation();
        ev.preventDefault();
        this.$emit('willChange', ev.target.checked);
      },
    },
  };
</script>
