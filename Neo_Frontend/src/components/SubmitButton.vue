<template>
  <button :type="type"
          class="btn btn-secondary"
          :class="{ 'btn-loading': loading, 'btn-lg': !inline, 'w-100': !inline }"
          :disabled="disabled||loading"
          v-on="$listeners"
  >
    <slot/>
  </button>
</template>

<script>
  export default {
    name: 'SubmitButton',
    props: {
      loading: Boolean,
      disabled: Boolean,
      inline: Boolean,
      type: {
        type: String,
        default: 'submit',
      },
    },
  };
</script>
