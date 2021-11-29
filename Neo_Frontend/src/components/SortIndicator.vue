<template>
  <div class="sort-indicator">
    <button class="btn-icon icon-asc" :disabled="disabled" :class="{ active: isAsc }" @click="setAsc">
      <icon width="10" height="6" sw="2" type="arrow-up"/>
    </button>
    <button class="btn-icon icon-desc" :disabled="disabled" :class="{ active: isDesc }" @click="setDesc">
      <icon width="10" height="6" sw="2" type="arrow-down"/>
    </button>
  </div>
</template>

<script>
  export default {
    name: 'SortIndicator',
    props: {
      value: {
        required: true,
        validator: (prop) => ['object', 'string', 'undefined'].includes(typeof prop),
      },
      disabled: {
        type: Boolean,
        default: false,
      },
      field: {
        required: true,
        type: String,
      },
    },
    computed: {
      isActive() {
        const field = this.value.substr(1) === 'periodFrom' ? 'period_from' : this.value.substr(1);
        return field === this.field;
      },
      isAsc() {
        return this.isActive && this.value.charAt(0) === '+';
      },
      isDesc() {
        return this.isActive && this.value.charAt(0) === '-';
      },
    },
    methods: {
      setAsc() {
        this.$emit('input', `+${this.field}`);
      },
      setDesc() {
        this.$emit('input', `-${this.field}`);
      },
    },
  };
</script>
