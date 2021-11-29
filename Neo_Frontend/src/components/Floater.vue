<template>
  <div class="floater" :class="{ open, shown }">
    <span v-if="!noContent" class="floater-toggle" @click="open=!open">
      <icon type="toggle-down" width="36" height="36" />
    </span>
    <div class="floater-content">
      <slot name="content"/>
    </div>
    <div class="floater-footer">
      <slot name="footer"/>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'Floater',
    data: () => ({
      open: false,
    }),
    props: {
      shown: {
        type: Boolean,
        default: false,
      },
      noContent: {
        type: Boolean,
        default: false,
      },
    },
    watch: {
      shown(val) {
        if (!val) this.open = false;
      },
    },
  };
</script>
