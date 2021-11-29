<template>
  <div v-if="!isEmptyGroup()" class="layout-ibe layout-full">
    <div class="layout-ibe-title position-relative w-100 title">
      <span>{{ $t('pages.dashboard.groups.ibe.title') }}</span>
      <div class="divider" />
    </div>
    <grid-layout
      :layout.sync="updatedLayout"
      :col-num="4"
      :row-height="250"
      :is-draggable="draggable"
      :is-resizable="resizable"
      :responsive="responsive"
      :vertical-compact="true"
      :use-css-transforms="true"
      @layout-updated="layoutUpdatedEvent"
    >
      <grid-item
        v-for="item in updatedLayout"
        v-bind:key="item.i"
        :static="item.static"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        :i="item.i"
      >
        <div
          v-if="visibleWidgets !== null && visibleWidgets.length > item.i && visibleWidgets[item.i].widgetType === 1"
          class="widget-item">
          <ScoreCardImage
            :score="visibleWidgets[item.i].content.score"
            :title="visibleWidgets[item.i].content.title"
            :imgUrl="visibleWidgets[item.i].image"
            :currency="visibleWidgets[item.i].content.currency"
          >
          </ScorecardImage>
        </div>
        <div v-if="visibleWidgets !== null && visibleWidgets[item.i].widgetType === 2" class="widget-item">
          <GaugeChart
            :averageNum="visibleWidgets[item.i].content.averageNum"
            :deviationNum="visibleWidgets[item.i].content.deviationNum"
            :conversionNum="visibleWidgets[item.i].content.conversionNum"
            :title="visibleWidgets[item.i].content.title"
            :refId="`gauge_chart_${visibleWidgets[item.i].id}`"
            :doubleChart="visibleWidgets[item.i].content.doubleChart"
          >
          </GaugeChart>
        </div>
        <div v-if="visibleWidgets !== null && visibleWidgets[item.i].widgetType === 3" class="widget-item">
          <PieChart
            :title="visibleWidgets[item.i].content.title"
            :data="visibleWidgets[item.i].content.data"
          >
          </PieChart>
        </div>
      </grid-item>
    </grid-layout>
  </div>
</template>

<script>
  import { GridLayout, GridItem } from 'vue-grid-layout';
  import ScoreCardImage from '@/components/ScoreCardImage.vue';
  import PieChart from '@/components/PieChart.vue';
  import GaugeChart from '@/components/GaugeChart.vue';
  import { widgetType, sizeSplitChar } from '@/shared';

  import { mapActions } from 'vuex';

  export default {
    name: 'IBEGroup',
    components: {
      GridLayout,
      GridItem,
      ScoreCardImage,
      PieChart,
      GaugeChart,
    },
    props: {
      widgets: {
        type: Array,
        default: null,
        required: true,
      },
    },
    data: () => ({
      layout: [],
      draggable: true,
      resizable: false,
      responsive: false,
      index: 0,
      visibleWidgets: [],
      prevWidgets: [],
      updatedLayout: [],
    }),
    watch: {
      widgets: {
        deep: true,
        handler(value) {
          this.layout.length = 0;
          value.forEach((widget) => {
            const layoutItem = {
              x: widget.x,
              y: widget.y,
              w: parseInt(widget.size.split(sizeSplitChar)[0], 10),
              h: parseInt(widget.size.split(sizeSplitChar)[1], 10),
              i: widget.id.toString(),
              widgetId: widget.widgetId,
            };
            this.layout.push(layoutItem);
          });
          this.getUpdateLayout();
        },
      },
    },
    methods: {
      ...mapActions('dashboard', ['settingDataUpdate', 'settingDataUpdateFromMovedWidgets', 'updateWidgetPosition']),
      // rearrange grid layout system corresponding widget switching status
      getUpdateLayout() {
        if (!this.widgets) return;
        if (this.isVisibleAll()) {
          this.visibleWidgets = [...this.widgets];
          this.updatedLayout = [...this.layout];
        }

        const result = [];
        let layoutId = 0;
        this.visibleWidgets = [];

        this.layout.forEach((item, index) => {
          if (this.widgets[index].widgetVisible) {
            this.visibleWidgets.push(this.widgets[index]);
            result.push(
              {
                x: item.x,
                y: item.y,
                w: (
                  this.widgets[index].widgetType === widgetType.gaugeChart
                  || this.widgets[index].widgetType === widgetType.pieChart
                  || [11, 14, 15].includes(this.widgets[index].widgetId)) ? 2 : 1,
                h: (this.widgets[index].widgetType === widgetType.gaugeChart
                  || this.widgets[index].widgetType === widgetType.pieChart) ? 2 : 1,
                i: layoutId.toString(),
                widgetId: item.widgetId,
              },
            );
            layoutId += 1;
          }
        });
        this.updatedLayout = [...result];
      },
      // check if all widgets in a group are invisible
      isEmptyGroup() {
        return this.widgets.every((widget) => widget.widgetVisible === false || widget.widgetVisible === 0);
      },
      // check if all widgets in a group are visible
      isVisibleAll() {
        const result = this.widgets.every((widget) => widget.widgetVisible === true);
        return result && this.widgets.length === 8;
      },
      // check if the switching statuses of the widgets are updated
      isUpdatedStatus() {
        let bUpdated = false;
        if (this.prevWidgets.length > 0) {
          for (let i = 0; i < this.widgets.length; i += 1) {
            if (this.prevWidgets[i].widgetVisible !== this.widgets[i].widgetVisible) {
              bUpdated = true;
              break;
            }
          }
        } else bUpdated = true;

        this.prevWidgets = JSON.parse(JSON.stringify(this.widgets));
        return bUpdated;
      },
      layoutUpdatedEvent(newLayout) {
        this.updateWidgetPosition(newLayout);
      },
    },
  };
</script>
