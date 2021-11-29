<template>
  <div class="radio-wrapper custom-control" :class="{ stateClass, 'reversed': reversed }">
    <small v-if="!empty && reversed">
      <label :for="guid" class="mb-0">
        <slot/>
      </label>
    </small>
    <input
      type="radio"
      :id="guid"
      :value="val"
      :name="name"
      v-model="checked"
      @change="onChange"
      :disabled="disabled"
    />
    <label class="radio-label" :for="guid">
      <span class="radio-button"></span>
    </label>
    <small v-if="!empty && !reversed">
      <label :for="guid" class="mb-0">
        <slot/>
      </label>
    </small>
  </div>
</template>

<script>
  export default {
    name: 'Radio',
    props: {
      value: {
        required: true,
      },
      val: {
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
      empty: {
        type: Boolean,
        default: false,
      },
      reversed: {
        type: Boolean,
        default: false,
      },
    },
    data: () => ({ guid: null, checkedProxy: false }),
    created() {
      this.guid = this.$uid;
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
      checked: {
        get() { return this.value; },
        set(val) { this.checkedProxy = val; },
      },
    },
    methods: {
      onChange() {
        this.$emit('input', this.checkedProxy);
      },
    },
  };
</script>
