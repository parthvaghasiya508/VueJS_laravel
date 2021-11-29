import { Line, mixins } from 'vue-chartjs';

const { reactiveProp } = mixins;

export default {
  extends: Line,
  mixins: [reactiveProp],
  data() {
    return {
      options: {
        legend: {
          display: true,
        },
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          yAxes: [
            {
              ticks: {
                fontSize: 20,
              },
            },
          ],
          xAxes: [
            {
              ticks: {
                fontSize: 20,
              },
            },
          ],
        },
      },
    };
  },
  mounted() {
    // this.chartData is created in the mixin.
    // If you want to pass options please create a local options object
    this.renderChart(this.chartData, this.options);
  },
  watch: {
    chartData() {
      // eslint-disable-next-line no-underscore-dangle
      this.$data._chart.update();
    },
  },
};
