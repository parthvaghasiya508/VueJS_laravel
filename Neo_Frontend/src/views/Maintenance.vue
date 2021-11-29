<template>
  <fragment>
    <div class="panel panel-status">
      <div class="panel-body">
        <div class="d-none d-md-block">
          <img class="panel-image w-75" src="/assets/images/maintenance.svg" alt="Maintenance image"/>
          <h2 class="font-weight-bold">{{ $t('maintenance.title') }} <br> {{ $t('maintenance.subtitle') }} </h2>
        </div>
        <div class="d-md-none">
          <img class="w-100" src="/assets/images/maintenance.svg" alt="Maintenance image"/>
          <h4 class="font-weight-bold">{{ $t('maintenance.title') }} <br><br>
            {{ $t('maintenance.subtitle') }} </h4>
        </div>
      </div>
    </div>
  </fragment>
</template>

<script>
  export default {
    name: 'Maintenance',
  };
</script>
