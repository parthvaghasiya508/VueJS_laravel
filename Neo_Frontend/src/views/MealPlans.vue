<template>
  <div class="page-mealplans">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.mealplans.title') }}</p>
    </div>
    <meal-plans/>
  </div>
</template>

<script>
  import MealPlans from '@/components/Pages/MealPlans.vue';

  export default {
    name: 'PageMealPlans',
    components: { MealPlans },
  };
</script>
