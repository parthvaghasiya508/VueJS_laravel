<template>
  <div class="page-facilities">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.facilities.title') }}</p>
    </div>
    <facilities/>
  </div>
</template>

<script>
  import Facilities from '@/components/Pages/Facilities.vue';

  export default {
    name: 'PageFacilities',
    components: { Facilities },
  };
</script>
