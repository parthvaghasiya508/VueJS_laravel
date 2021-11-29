<template>
  <div class="panel panel-form">
    <div class="panel-body mx-auto">
      <h1>{{ $t('signup.perms.title') }}</h1>
      <p>{{ $t('signup.perms.hint') }}</p>
    </div>
    <div class="panel-footer mt-xl">
      <p class="mb-0">
        <b-link href="/">{{ $t('signup.perms.button') }}</b-link>
      </p>
    </div>
  </div>
</template>

<script>
  export default {
    name: 'NoPermissions',
  };
</script>
