<template>
  <div class="widget pie-chart">
    <h3 class="pie-chart-title">{{title}}</h3>
    <div class="pie-chart-content">
      <div class="pie-chart-content-inner">
        <Pie :data="data"></Pie>
      </div>
      <div class="pie-chart-content-legend">
        <div class="pie-chart-content-legend-item">
          <div class="pie-chart-content-legend-item-color" />
          <span class="pie-chart-content-legend-item-title">{{ $t('pages.dashboard.charts.pie.direct') }}</span>
        </div>
        <div class="pie-chart-content-legend-item">
          <div class="pie-chart-content-legend-item-color" />
          <span class="pie-chart-content-legend-item-title">{{ $t('pages.dashboard.charts.pie.indirect') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import Pie from '@/components/Pie.vue';

  export default {
    name: 'PieChart',
    components: {
      Pie,
    },
    props: {
      title: {
        type: String,
        default: '',
        required: true,
      },
      data: {
        type: Array,
        default: null,
        required: true,
      },
    },
  };
</script>
