<template>
  <div v-if="!isEmptyGroup()" class="layout-channel layout-full">
    <div class="layout-channel-title position-relative w-100 title">
      <span>{{ $t('pages.dashboard.groups.channel.title') }}</span>
      <div class="divider" />
    </div>
    <grid-layout
      :layout.sync="updatedLayout"
      :col-num="4"
      :row-height="layoutHeight"
      :is-draggable="draggable"
      :is-resizable="resizable"
      :responsive="responsive"
      :vertical-compact="false"
      :use-css-transforms="false"
      @layout-updated="layoutUpdatedEvent"
      id="channel_group_grid"
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
        <div v-if="visibleWidgets !== null && visibleWidgets.length > item.i && visibleWidgets[item.i].widgetType === 5"
             class="widget-item">
          <Table
            :title="visibleWidgets[item.i].content.data.title"
            :fields="visibleWidgets[item.i].content.data.fields"
            :items="visibleWidgets[item.i].content.data.items"
          >
          </Table>
        </div>
      </grid-item>
    </grid-layout>
  </div>
</template>

<script>
  import { GridLayout, GridItem } from 'vue-grid-layout';
  import Table from '@/components/Table.vue';
  import { sizeSplitChar } from '@/shared';
  import { mapActions } from 'vuex';

  export default {
    name: 'ChannelGroup',
    components: {
      GridLayout,
      GridItem,
      Table,
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
      layoutHeight: 0,
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
          this.calculateLayoutHeight();
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
      layoutUpdatedEvent(newLayout) {
        this.updateWidgetPosition(newLayout);
      },
      calculateLayoutHeight() {
        this.visibleWidgets.forEach((visibleWidget) => {
          this.layoutHeight = visibleWidget.content.data.items.length * 56;
        });
        if (this.layoutHeight < 200) {
          this.layoutHeight = 220;
        } else if (this.layoutHeight < 250) {
          this.layoutHeight = 260;
        }
      },
    },
  };
</script>
