<template functional>
  <div class="spinner" :class="data.staticClass || ''" v-bind="data.attrs">
    <img src="/assets/images/spinner.png" alt=""
         srcset="/assets/images/spinner.png 1x, /assets/images/spinner@2x.png 2x, /assets/images/spinner@3x.png 3x"/>
  </div>
</template>

<script>
  export default {
    name: 'Spinner',
    inheritAttrs: true,
  };
</script>
