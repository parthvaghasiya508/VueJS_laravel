<template>
  <div v-if="!isEmptyGroup()" class="layout-book layout-full">
    <div class="layout-book-title position-relative w-100 title">
      <span>{{ $t('pages.dashboard.groups.book.title') }}</span>
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
        v-bind:key="item.widgetId"
        :static="item.static"
        :x="item.x"
        :y="item.y"
        :w="item.w"
        :h="item.h"
        :i="item.i"
      >
        <div v-if="visibleWidgets !== null && visibleWidgets[item.i].widgetType === 4" class="widget-item">
          <WaterfallChart
            :title="visibleWidgets[item.i].content.title"
            :dataPoints="visibleWidgets[item.i].content.dataPoints"
          >
          </WaterfallChart>
        </div>
      </grid-item>
    </grid-layout>
  </div>
</template>

<script>
  import { GridLayout, GridItem } from 'vue-grid-layout';
  import { sizeSplitChar } from '@/shared';
  import { mapActions } from 'vuex';
  import WaterfallChart from '@/components/WaterfallChart.vue';

  export default {
    name: 'KPIGroup',
    components: {
      GridLayout,
      GridItem,
      WaterfallChart,
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
      ...mapActions('dashboard', ['settingDataUpdate', 'updateWidgetPosition', 'settingDataUpdateFromMovedWidgets']),

      getUpdateLayout() {
        if (!this.widgets) return;
        const result = [];
        this.visibleWidgets = [];
        let layoutId = 0;
        this.widgets.forEach((widget) => {
          if (widget.widgetVisible) {
            this.visibleWidgets.push(widget);
            result.push({
              x: widget.x,
              y: widget.y,
              w: parseInt(widget.size.split(sizeSplitChar)[0], 10),
              h: parseInt(widget.size.split(sizeSplitChar)[1], 10),
              i: layoutId.toString(),
              widgetId: widget.widgetId,
            });
            layoutId += 1;
          }
        });
        this.updatedLayout = [...result];
      },
      isEmptyGroup() {
        return this.widgets.every((widget) => widget.widgetVisible === false || widget.widgetVisible === 0);
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
