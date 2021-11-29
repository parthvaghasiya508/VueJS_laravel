<template>
  <fragment>
    <div class="mobile-responsive d-lg-none">
      <ul class="list-unstyled">
        <slot :name="`mobile`"/>
      </ul>
    </div>
  </fragment>
</template>

<script>
  export default {
    name: 'MobileTable',
    props: {
      mobileView: {
        type: Boolean,
        default: false,
      },
    },
  };
</script>
