<template>
  <div class="page-widget-status">
    <div class="widget-status-group" v-if="!isAllhide(1)">
      <div class="widget-status-group-header">
        <h3 class="widget-status-group-header-title">{{ $t('pages.dashboard.groups.kpi.title') }}</h3>
        <div class="widget-status-group-header-activate">
          <span v-if="isActivateAllPerGroup(1)" @click="toggleActivateAll(1)">
            {{ $t('pages.dashboard.setting.deactivate') }}
          </span>
          <span v-else @click="toggleActivateAll(1)">{{ $t('pages.dashboard.setting.activate') }}</span>
        </div>
      </div>
      <div class="widget-status-group-content">
        <div class="widget-status-group-content-item" v-for="item in kpiGroupData" :key="item.widgetId">
          <switcher
            :checked="settingData[item.widgetId - 1].visible" colored
            :on-label="$t('pages.booking.on')"
            :off-label="$t('pages.booking.off')"
            @change="onChangeStatus(item.widgetId, $event)"
          />
          <span class="widget-status-group-content-item-title">{{item.content.title}}</span>
        </div>
      </div>
    </div>
    <div class="widget-status-group" v-if="!isAllhide(2)">
      <div class="widget-status-group-header">
        <h3 class="widget-status-group-header-title">{{ $t('pages.dashboard.groups.book.title') }}</h3>
        <div class="widget-status-group-header-activate">
          <span v-if="isActivateAllPerGroup(2)" @click="toggleActivateAll(2)">
            {{ $t('pages.dashboard.setting.deactivate') }}
          </span>
          <span v-else @click="toggleActivateAll(2)">{{ $t('pages.dashboard.setting.activate') }}</span>
        </div>
      </div>
      <div class="widget-status-group-content">
        <div class="widget-status-group-content-item" v-for="item in booksGroupData" :key="item.widgetId">
          <switcher
            :checked="settingData[item.widgetId - 1].visible" colored
            :on-label="$t('pages.booking.on')"
            :off-label="$t('pages.booking.off')"
            @change="onChangeStatus(item.widgetId, $event)"
          />
          <span class="widget-status-group-content-item-title">{{item.content.title}}</span>
        </div>
      </div>
    </div>
    <div class="widget-status-group" v-if="!isAllhide(3)">
      <div class="widget-status-group-header">
        <h3 class="widget-status-group-header-title">{{ $t('pages.dashboard.groups.ibe.title') }}</h3>
        <div class="widget-status-group-header-activate">
          <span v-if="isActivateAllPerGroup(3)" @click="toggleActivateAll(3)">
            {{ $t('pages.dashboard.setting.deactivate') }}
          </span>
          <span v-else @click="toggleActivateAll(3)">{{ $t('pages.dashboard.setting.activate') }}</span>
        </div>
      </div>
      <div class="widget-status-group-content">
        <div class="widget-status-group-content-item" v-for="item in ibeGroupData" :key="item.widgetId">
          <switcher
            :checked="settingData[item.widgetId - 1].visible" colored
            :on-label="$t('pages.booking.on')"
            :off-label="$t('pages.booking.off')"
            @change="onChangeStatus(item.widgetId, $event)"
          />
          <span class="widget-status-group-content-item-title">{{item.content.title}}</span>
        </div>
      </div>
    </div>
    <div class="widget-status-group" v-if="!isAllhide(4)">
      <div class="widget-status-group-header">
        <h3 class="widget-status-group-header-title">{{ $t('pages.dashboard.groups.channel.title') }}</h3>
        <div class="widget-status-group-header-activate">
          <span v-if="isActivateAllPerGroup(4)" @click="toggleActivateAll(4)">
            {{ $t('pages.dashboard.setting.deactivate') }}
          </span>
          <span v-else @click="toggleActivateAll(4)">{{ $t('pages.dashboard.setting.activate') }}</span>
        </div>
      </div>
      <div class="widget-status-group-content">
        <div class="widget-status-group-content-item" v-for="item in channelGroupData" :key="item.widgetId">
          <switcher
            :checked="settingData[item.widgetId - 1].visible" colored
            :on-label="$t('pages.booking.on')"
            :off-label="$t('pages.booking.off')"
            @change="onChangeStatus(item.widgetId, $event)"
          />
          <span class="widget-status-group-content-item-title">{{item.content.data.title}}</span>
        </div>
      </div>
    </div>
    <div class="widget-status-group" v-if="!isAllhide(5)">
      <div class="widget-status-group-header">
        <h3 class="widget-status-group-header-title">{{ $t('pages.dashboard.groups.term.title') }}</h3>
        <div class="widget-status-group-header-activate">
          <span v-if="isActivateAllPerGroup(5)" @click="toggleActivateAll(5)">
            {{ $t('pages.dashboard.setting.deactivate') }}
          </span>
          <span v-else @click="toggleActivateAll(5)">{{ $t('pages.dashboard.setting.activate') }}</span>
        </div>
      </div>
      <div class="widget-status-group-content">
        <div class="widget-status-group-content-item" v-for="item in termGroupData" :key="item.widgetId">
          <switcher
            :checked="settingData[item.widgetId - 1].visible" colored
            :on-label="$t('pages.booking.on')"
            :off-label="$t('pages.booking.off')"
            @change="onChangeStatus(item.widgetId, $event)"
          />
          <span class="widget-status-group-content-item-title">{{item.title}}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapState, mapActions } from 'vuex';

  export default {
    name: 'WidgetStatus',
    props: {
      kpiGroupData: {
        type: Array,
        default: null,
      },
      booksGroupData: {
        type: Array,
        default: null,
      },
      ibeGroupData: {
        type: Array,
        default: null,
      },
      channelGroupData: {
        type: Array,
        default: null,
      },
      termGroupData: {
        type: Array,
        default: null,
      },
    },
    data() {
      return {
        prevSettingData: null,
      };
    },
    created() {
      this.prevSettingData = JSON.parse(JSON.stringify(this.settingData));
    },
    computed: {
      ...mapState('dashboard', ['settingData']),
    },
    methods: {
      ...mapActions('dashboard', ['settingDataUpdate', 'settingGroupActivateAll', 'settingGroupDeActivateAll']),
      ...mapActions('dashboard', ['settingItemVisibleChange', 'updateWidgetVisiblity', 'settingDataRestore']),

      // check if the specific group state is "activate all" or "deactivate all"
      isActivateAllPerGroup(groupId) {
        for (let i = 0; i < this.settingData.length; i += 1) {
          if (this.settingData[i].group.id === groupId) {
            if (!this.settingData[i].visible) return false;
          }
        }
        return true;
      },
      onChangeStatus(id, value) {
        this.settingItemVisibleChange({ id, visibleState: value });
        this.updateWidgetVisiblity({ id, visibleState: value, allGroup: false });
      },
      // eleminate the setting group if there isn't the specific group data
      isAllhide(index) {
        if (this.settingData && this.settingData.length === 0) return true;
        let data = [];
        switch (index) {
          case 1:
            data = [...this.kpiGroupData];
            break;
          case 2:
            data = [...this.booksGroupData];
            break;
          case 3:
            data = [...this.ibeGroupData];
            break;
          case 4:
            data = [...this.channelGroupData];
            break;
          case 5:
            data = [...this.termGroupData];
            break;
          default:
            data = [];
            break;
        }
        return !(data.length > 0);
      },
      toggleActivateAll(groupId) {
        if (this.isActivateAllPerGroup(groupId)) {
          this.updateWidgetVisiblity({ id: groupId, visibleState: false, allGroup: true });
          this.settingGroupDeActivateAll(groupId);
        } else {
          this.updateWidgetVisiblity({ id: groupId, visibleState: true, allGroup: true });
          this.settingGroupActivateAll(groupId);
        }
      },
    },
  };
</script>
