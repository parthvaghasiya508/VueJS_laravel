<script>
  import { Pie } from 'vue-chartjs';
  import 'chartjs-plugin-labels';

  export default {
    extends: Pie,
    name: 'Pie',
    props: ['data'],
    mounted() {
      this.renderPie();
    },
    watch: {
      data: {
        handler() {
          this.renderPie();
        },
      },
    },
    methods: {
      renderPie() {
        this.renderChart(
          {
            labels: [this.$t('pages.dashboard.charts.pie.direct'), this.$t('pages.dashboard.charts.pie.indirect')],
            datasets: [
              {
                backgroundColor: ['rgb(74, 144, 226)', 'rgba(74, 144, 226, 0.7)'],
                data: this.data,
              },
            ],
          },
          {
            legend: {
              display: false,
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              labels: [
                {
                  render: 'percentage',
                  fontStyle: 'bold',
                  fontColor: '#ffffff',
                  fontSize: 18,
                  precision: 1,
                },
              ],
            },
          },
        );
      },
    },
  };
</script>
