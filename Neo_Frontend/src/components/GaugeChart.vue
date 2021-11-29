<template>
  <div class="widget gauge-chart" :id="doubleChart ? 'gauge-chart-double' : ''">
    <h3 class="gauge-chart-title">{{title}}</h3>
    <div class="gauge-chart-item">
      <vue-speedometer
        class="big-resolutions"
        :maxSegmentLabels="maxSegmentLabels"
        :segments="segments"
        :value="conversionNum"
        :currentValueText="currentValue()"
        textColor="${textColor}"
        :paddingVertical="paddingVertical"
        :minValue="min()"
        :maxValue="conversionNum == 0 ? 1000000000 : max()"
        :width="500"
        :height="400"
      />
      <vue-speedometer
        class="small-resolutions hide"
        :maxSegmentLabels="maxSegmentLabels"
        :segments="segments"
        :value="conversionNum"
        :currentValueText="currentValue()"
        textColor="${textColor}"
        :paddingVertical="paddingVertical"
        :minValue="min()"
        :maxValue="conversionNum == 0 ? 1000000000 : max()"
        :width="300"
        :height="200"
      />
    </div>
  </div>
</template>

<script>
  import VueSpeedometer from 'vue-speedometer';

  export default {
    name: 'GaugeChart',
    components: {
      VueSpeedometer,
    },
    props: {
      averageNum: {
        required: true,
      },
      deviationNum: {
        required: true,
      },
      conversionNum: {
        required: true,
      },
      title: {
        type: String,
        default: '',
        required: true,
      },
      refId: {
        type: String,
        default: '',
        required: true,
      },
      doubleChart: {
        type: Boolean,
        default: false,
      },
    },
    data() {
      return {
        segments: 100,
        maxSegmentLabels: 0,
        paddingVertical: 10,
      };
    },
    mounted() {
    },
    methods: {
      currentValue() {
        return this.$tc('pages.dashboard.groups.ibe.conversion-rate', parseFloat(this.conversionNum).toFixed(1));
      },
      min() {
        return Number(parseFloat(Number(this.averageNum) - (2 * Number(this.deviationNum))).toFixed(1));
      },
      max() {
        let maxValue = Number(parseFloat(Number(this.averageNum) + (2 * Number(this.deviationNum))).toFixed(1));
        if (Number(this.conversionNum) >= maxValue) {
          maxValue = Number(this.conversionNum);
        }
        return maxValue;
      },
    },
  };
</script>
