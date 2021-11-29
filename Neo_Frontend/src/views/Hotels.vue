<template>
  <div class="page-mealplans">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.hotels.title') }}</p>
    </div>
    <hotels />
  </div>
</template>

<script>
  import Hotels from '@/components/Pages/Hotels.vue';

  export default {
    name: 'PageHotels',
    components: { Hotels },
  };
</script>
