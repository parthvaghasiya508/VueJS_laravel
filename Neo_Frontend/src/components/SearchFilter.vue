<template>
  <div class="search-filter" :class="{ noborder }">
    <b-input :placeholder="placeholder"
             autocomplete="off"
             v-focus:[autofocus]
             :value="value"
             @input="$emit('input', $event)"
             @keydown.esc.prevent.stop
             @keypress.esc.prevent.stop
             @keyup.esc.prevent.stop="$emit('input', '')"
             :disabled="disabled"
    />
    <button class="btn-icon search-icon" v-show="!value">
      <icon width="20" height="20" type="search"/>
    </button>
    <button class="btn-icon reset-icon" v-show="!!value" @click="$emit('input', '')">
      <icon width="20" height="20" type="close"/>
    </button>
  </div>
</template>

<script>
  export default {
    name: 'SearchFilter',
    props: {
      value: {
        required: true,
        validator: (prop) => ['object', 'number', 'string', 'undefined'].includes(typeof prop),
      },
      placeholder: {
        type: String,
        default: '',
      },
      autofocus: {
        type: Boolean,
        default: false,
      },
      disabled: {
        type: Boolean,
        default: false,
      },
      noborder: {
        type: Boolean,
        default: false,
      },
    },
  };
</script>
