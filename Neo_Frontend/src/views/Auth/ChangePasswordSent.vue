<template>
  <fragment>
    <div class="panel panel-status">
      <div class="panel-body">
        <img class="panel-image" src="/assets/images/envelop.svg" alt="Envelop image"/>
        <h1>{{ $t('change-pwd.sent-heading') }}</h1>
        <p>{{ $t('change-pwd.sent-to') }}</p>
        <h3 class="mb-0">{{ email }}</h3>
      </div>
      <div class="panel-footer">
        <p class="m-0">{{ $t('change-pwd.sent-tip') }}</p>
      </div>
    </div>
  </fragment>
</template>

<script>
  export default {
    name: 'ChangePasswordSent',
    data() {
      return {
        email: this.$route.query.email,
      };
    },
  };
</script>
