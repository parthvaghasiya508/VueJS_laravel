<template>
  <div class="widget waterfall-chart">
    <div v-if="title === bookingValue" class="chart-container" id="chartContainer"></div>
    <div v-else id="chartContainerSold" class="chart-container"></div>
  </div>
</template>

<script>
  let CanvasJS = require('@/plugins/canvasjs.min');

  export default {
    name: 'Waterfall',
    props: {
      title: {
        type: String,
        default: null,
        required: true,
      },
      dataPoints: {
        type: Array,
        default: null,
        required: true,
      },
    },
    data() {
      return {
        chartOptions: {
          theme: 'light2',
          animationEnabled: false,
          indexLabelPlacement: 'inside',
          title: {
            text: this.title,
            fontSize: 20,
            margin: 20,
          },
          axisY: {
            interval: 100,
          },
          dataPointWidth: 100,
          data: [{
            type: 'waterfall',
            indexLabel: '{displayValue}',
            indexLabelFontColor: '#EEEEEE',
            indexLabelFontSize: 25,
            indexLabelPlacement: 'inside',
            yValueFormatString: '#,##0',
            dataPoints: this.dataPoints,
          }],
          toolTip: {
            contentFormatter(e) {
              return `${e.entries[0].dataPoint.label} : ${e.entries[0].dataPoint.displayValue}`;
            },
          },
          pointHoverBackgroundColor: 'red',
        },
        chart: null,
        bookingValue: 'Booking Value',
      };
    },
    methods: {
      getInterval() {
        return (parseInt(this.dataPoints[0].y / 10, 10) * 10) / 5;
      },
    },
    mounted() {
      CanvasJS = CanvasJS.Chart ? CanvasJS : window.CanvasJS;

      if (this.title === this.bookingValue) {
        this.chartOptions.data[0].dataPoints[0].color = 'rgba(74, 144, 226, 0.8)';
        this.chartOptions.data[0].dataPoints[1].color = 'rgba(74, 144, 226, 0.6)';
        this.chartOptions.data[0].dataPoints[2].color = 'rgba(74, 144, 226, 0.4)';
      } else {
        this.chartOptions.data[0].dataPoints[0].color = 'rgba(74, 144, 226, 0.7)';
        this.chartOptions.data[0].dataPoints[1].color = 'rgba(39, 174, 96, 0.7)';
        this.chartOptions.data[0].dataPoints[2].color = 'rgba(247, 152, 28, 0.7)';
      }

      this.chartOptions.axisY.interval = this.getInterval();

      if (this.title === this.bookingValue) this.chart = new CanvasJS.Chart('chartContainer', this.chartOptions);
      else this.chart = new CanvasJS.Chart('chartContainerSold', this.chartOptions);
      this.chart.render();
    },
  };
</script>
