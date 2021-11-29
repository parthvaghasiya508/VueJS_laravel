<template>
  <div>
    <spinner v-if="dashboardDataPending" />
    <div class="page-dashboard" v-if="!dashboardDataPending">
      <div class="panel">
        <div class="row">
          <div class="col">
            <div class="panel-dropdown-item">
              <span class="panel-dropdown-item-title">{{timePeriod.title}}</span>
              <DropDown
                :title="timePeriod.title"
                :items="timePeriod.items"
                :value="timePeriod.value"
                :idKey="timePeriod.idKey"
                :labelKey="timePeriod.labelKey"
                @update="updateDashboardData"
                v-model="currentTimePeriod"
              >
              </DropDown>
            </div>
          </div>
          <div class="col setting-option">
            <div class="btn-wrapper">
              <button class="btn-setting" @click="onClickDashboardSettings()">
                <icon width="24" height="24" type="sliders" class="sliders-icon" />
                {{ $t('pages.dashboard.button-settings') }}
              </button>
            </div>
          </div>
          <div class="col download-button">
            <div class="btn-wrapper">
              <b-btn variant="primary" pill class="download-btn" v-if="report != null" target="_blank" :href="pdfUrl"
                     :disabled="report == null">
                {{ $t('pages.dashboard.button-download') }}
                <icon width="24" height="24" type="download-icon"/>
              </b-btn>
            </div>
          </div>
        </div>
      </div>
      <div class="group-wrapper">
        <KPIGroup
          :widgets="kpiGroupData"
        >
        </KPIGroup>
        <BookGroup
          :widgets="booksGroupData"
        >
        </BookGroup>
        <IBEGroup
          :widgets="ibeGroupData"
        >
        </IBEGroup>
        <ChannelGroup
          :widgets="channelGroupData"
        >
        </ChannelGroup>
        <TermGroup
          :widgets="termGroupData"
        >
        </TermGroup>
      </div>

      <b-modal
        no-close-on-backdrop
        centered
        size="lg"
        ref="setting-modal"
        hide-footer
        :title="$t('pages.dashboard.setting.title')">
        <WidgetStatus
          @hide="hide"
          :kpiGroupData="kpiGroupData"
          :booksGroupData="booksGroupData"
          :ibeGroupData="ibeGroupData"
          :channelGroupData="channelGroupData"
          :termGroupData="termGroupData"
        >
        </WidgetStatus>
      </b-modal>
    </div>
  </div>
</template>

<script>
  import { mapState, mapActions, mapGetters } from 'vuex';

  import ApiService from '@/services/api.service';
  import { apiEndpoint, widgetId, timePeriodDashboardKeys } from '@/shared';
  import { formatNumber } from '@/helpers';

  import KPIGroup from '@/components/KPIGroup.vue';
  import BookGroup from '@/components/BookGroup.vue';
  import IBEGroup from '@/components/IBEGroup.vue';
  import ChannelGroup from '@/components/ChannelGroup.vue';
  import TermGroup from '@/components/TermGroup.vue';
  import WidgetStatus from '@/views/WidgetStatus.vue';
  import moment from 'moment';

  export default {
    name: 'Dashboard',
    components: {
      KPIGroup,
      BookGroup,
      IBEGroup,
      ChannelGroup,
      TermGroup,
      WidgetStatus,
    },
    data: () => ({
      report: null,
      kpiGroupData: [],
      booksGroupData: [],
      ibeGroupData: [],
      channelGroupData: [],
      termGroupData: [],
      // values for periods dropdown menu
      timePeriod: {},
      currentChannel: '',
      currentTimePeriod: '',
      // value from response data
      apiData: {
        GrossBookings: null,
        Cancellations: null,
        NetBookings: null,
        GrossBookingVolume: null,
        CancellationVolume: null,
        NetBookingVolume: null,
        GrossSoldRoomnights: null,
        CancellationSoldRoomnights: null,
        NetSoldRoomnights: null,
        AvgBookingValue: null,
        AvgDailyRoomRate: null,
        CancellationRate: null,
        BookingsDay: null,
        RoomnightsBooking: null,
        CalendarDays: null,
        AvailableRooms: null,
        RoomnightsToSell: null,
        OnlineOccupancyRate: null,
        OnlineRevPAR: null,
        ProducingChannels: null,
        IbeSearches: null,
        UniqueVisitors: null,
        Bookings: null,
        SearchedVisitors: null,
        Average: null,
        Deviation: null,
        Conversion: null,
        BookingTableValues: null,
        BookingVolumeTableValues: null,
        NetBookingVolumePerMonth: null,
        DirectPercentage: null,
      },
      // report: null,
    }),
    async created() {
      this.initDropdownMenuValues();
      try {
        await this.dashboardDataFetch();
        await this.settingDataFetch();

        const { data: report } = await ApiService.get(`${apiEndpoint}/reports/recent`);
        this.report = report || null;
      } catch (err) {
        // do nothing
      }
    },
    computed: {
      ...mapState('dashboard', ['dashboardData', 'settingData', 'dashboardDataPending']),
      ...mapGetters('user', ['settings', 'currency', 'numberFormat']),
      pdfUrl() {
        if (this.report == null) return '';
        return this.report.pdf;
      },
      timePeriodKeys() {
        const periodKeys = [];
        timePeriodDashboardKeys.forEach((key) => {
          periodKeys.push({
            key,
            time: this.$t(`pages.dashboard.time-period.types.${key}`),
          });
        });

        return periodKeys;
      },
      currencyCode() {
        const { code } = this.currency;
        return code || 'EUR';
      },
    },
    watch: {
      dashboardData: {
        deep: true,
        handler() {
          if (this.settingData !== null) {
            this.updateDashboardData();
          }
        },
      },
      settingData: {
        deep: true,
        handler() {
          this.updateWidgetValues();
        },
      },
    },
    methods: {
      ...mapActions('dashboard', ['dashboardDataFetch', 'settingDataFetch']),
      hide(saveStatus) {
        this.$refs['setting-modal'].hide();
        this.$emit('input', saveStatus);
      },

      onClickDashboardSettings() {
        this.$refs['setting-modal'].show();
      },
      fixedOne(num) {
        return parseFloat(num).toFixed(1);
      },
      fixedTwo(num) {
        return parseFloat(num).toFixed(2);
      },
      initDropdownMenuValues() {
        // default selected value
        this.currentTimePeriod = this.timePeriodKeys[2].key;
        // set timePeriod value
        this.timePeriod = {
          title: this.$t('pages.dashboard.time-period.label'),
          items: this.timePeriodKeys,
          value: this.timePeriodKeys[2].time,
          idKey: 'key',
          labelKey: 'time',
        };
      },
      getDatasetsForLineChart(inputObject) {
        const datasets = [];
        const colors = ['#4A90E270', '#27AE6070', '#F7981C70', '#47381C70'];
        const currentYear = moment().format('Y');
        const currentMonth = moment().format('M');
        let colorIndex = 0;
        Object.entries(inputObject).forEach(([key, value]) => {
          const [year, month] = key.split('-');
          const result = datasets.filter((obj) => obj.label === year);
          if (result.length === 0) {
            datasets.push({
              data: (new Array(12)).fill(null),
              label: year.toString(),
              borderColor: colors[colorIndex],
              fill: false,
              lineTension: 0,
            });
            colorIndex += 1;
          }
          datasets.forEach((yearData, index) => {
            if (yearData.label === year) {
              datasets[index].data[parseInt(month, 10) - 1] = parseFloat(value).toFixed(1);
            }
            if (yearData.label === currentYear) {
              datasets[index].data[currentMonth - 1] = null;
            }
          });
        });
        datasets.reverse();

        if (parseInt(currentMonth, 10) === 1) {
          datasets.pop();
        } else {
          datasets.shift();
        }

        return datasets;
      },
      getDatasetsForAccumulatedLineChart(inputArray) {
        const datasets = [];

        inputArray.forEach((inputItem) => {
          const newData = (new Array(12)).fill(null);
          let prevValue = 0;

          inputItem.data.forEach((item, index) => {
            if (index === 0) {
              newData[0] = 0;
              prevValue = item;
            } else if (item !== null) {
              newData[index] = Math.abs(parseFloat(item) + parseFloat(prevValue)).toFixed(1);
              prevValue = newData[index];
            }
          });
          datasets.push({
            data: newData,
            label: inputItem.label,
            borderColor: inputItem.borderColor,
            fill: inputItem.fill,
            lineTension: 0,
          });
        });

        return datasets;
      },
      initDashboardData() {
        this.apiData.GrossBookings = 0;
        this.apiData.Cancellations = 0;
        this.apiData.NetBookings = 0;
        this.apiData.GrossBookingVolume = 0;
        this.apiData.CancellationVolume = 0;
        this.apiData.NetBookingVolume = 0;
        this.apiData.GrossSoldRoomnights = 0;
        this.apiData.CancellationSoldRoomnights = 0;
        this.apiData.NetSoldRoomnights = 0;
        this.apiData.BookingTableValues = {};
        this.apiData.BookingVolumeTableValues = {};
        this.apiData.NetBookingVolumePerMonth = {};
      },
      updateApiData() {
        if (this.dashboardData === null) return;
        this.initDashboardData();
        if (this.dashboardData.ibe_gross_booking) {
          this.apiData.GrossBookings = this.dashboardData.gross_bookings[this.currentTimePeriod];
        }
        if (this.dashboardData.cancellation_bookings) {
          this.apiData.Cancellations = this.dashboardData.cancellation_bookings[this.currentTimePeriod];
        }
        if (this.dashboardData.net_bookings) {
          this.apiData.NetBookings = this.dashboardData.net_bookings[this.currentTimePeriod];
        }
        if (this.dashboardData.gross_booking_volume) {
          this.apiData.GrossBookingVolume = this.dashboardData.gross_booking_volume[this.currentTimePeriod];
        }
        if (this.dashboardData.cancellation_booking_volume) {
          this.apiData.CancellationVolume = this.dashboardData.cancellation_booking_volume[this.currentTimePeriod];
        }
        if (this.dashboardData.net_booking_volume) {
          this.apiData.NetBookingVolume = this.dashboardData.net_booking_volume[this.currentTimePeriod];
        }
        if (this.dashboardData.gross_sold_roomnights) {
          this.apiData.GrossSoldRoomnights = this.dashboardData.gross_sold_roomnights[this.currentTimePeriod];
        }
        if (this.dashboardData.cancellation_roomnights) {
          this.apiData.CancellationSoldRoomnights = this.dashboardData.cancellation_roomnights[this.currentTimePeriod];
        }
        if (this.dashboardData.net_sold_roomnights) {
          this.apiData.NetSoldRoomnights = this.dashboardData.net_sold_roomnights[this.currentTimePeriod];
        }
        // Avg. Booking Value
        if (this.dashboardData.average_booking_value) {
          this.apiData.AvgBookingValue = this.dashboardData.average_booking_value[this.currentTimePeriod];
        }
        // Avg. Daily Room Rate
        if (this.dashboardData.average_daily_room_rate) {
          this.apiData.AvgDailyRoomRate = this.dashboardData.average_daily_room_rate[this.currentTimePeriod];
        }
        // Cancellation Rate
        if (this.dashboardData.cancellation_rate) {
          this.apiData.CancellationRate = this.dashboardData.cancellation_rate[this.currentTimePeriod];
        }
        if (this.dashboardData.calendar_days) {
          this.apiData.CalendarDays = this.dashboardData.calendar_days[this.currentTimePeriod];
        }
        // Bookings/Day
        if (this.dashboardData.bookings_for_period) {
          this.apiData.BookingsDay = this.dashboardData.bookings_for_period[this.currentTimePeriod];
        }
        // Roomnights/Booking
        if (this.dashboardData.roomnights_per_booking) {
          this.apiData.RoomnightsBooking = this.dashboardData.roomnights_per_booking[this.currentTimePeriod];
        }
        if (this.dashboardData.available_rooms) {
          this.apiData.AvailableRooms = this.dashboardData.available_rooms;
        }
        if (this.dashboardData.roomnights_to_sell) {
          this.apiData.RoomnightsToSell = this.dashboardData.roomnights_to_sell[this.currentTimePeriod];
        }
        // Online Occupancy Rate
        if (this.dashboardData.online_occupancy_rate) {
          this.apiData.OnlineOccupancyRate = this.dashboardData.online_occupancy_rate[this.currentTimePeriod];
        }
        // Online RevPAR
        if (this.dashboardData.online_rev_par) {
          this.apiData.OnlineRevPAR = this.dashboardData.online_rev_par[this.currentTimePeriod];
        }
        // Producing Channels
        if (this.dashboardData.producing_channels) {
          this.apiData.ProducingChannels = this.dashboardData.producing_channels[this.currentTimePeriod];
        }
        if (this.dashboardData.ibe_searches) {
          this.apiData.IbeSearches = this.dashboardData.ibe_searches[this.currentTimePeriod];
        }
        if (this.dashboardData.ibe_unique_visitors) {
          this.apiData.UniqueVisitors = this.dashboardData.ibe_unique_visitors[this.currentTimePeriod];
        }
        if (this.dashboardData.ibe_gross_booking) {
          this.apiData.Bookings = this.dashboardData.ibe_gross_booking[this.currentTimePeriod];
        }
        // Search Visitors
        if (this.apiData.UniqueVisitors === 0) {
          this.apiData.SearchedVisitors = 0;
        } else {
          this.apiData.SearchedVisitors = this.apiData.IbeSearches / this.apiData.UniqueVisitors;
        }
        if (this.dashboardData.ibe_performance) {
          this.apiData.Conversion = this.dashboardData.ibe_performance[this.currentTimePeriod].conversion_rate;
          this.apiData.Average = this.dashboardData.ibe_performance[this.currentTimePeriod].mean;
          this.apiData.Deviation = this.dashboardData.ibe_performance[this.currentTimePeriod].standard_deviation;
        }
        // Booking Table
        const bookingFields = [
          {
            label: this.$t('pages.dashboard.booking-volume-table.labels.channel'),
            key: 'channel',
            sortable: false,
          },
          {
            label: this.$t('pages.dashboard.booking-volume-table.labels.gross-bookings'),
            key: 'gross_bookings',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-volume-table.labels.cancellations'),
            key: 'cancellations',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-volume-table.labels.net-bookings'),
            key: 'net_bookings',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-volume-table.labels.net-bookings-p'),
            key: 'net_bookings_p',
            sortable: true,
          },
        ];
        const bookingItems = [];
        if (this.dashboardData.channels) {
          this.dashboardData.channels.forEach((channel) => {
            if (
              !(channel.booking[this.currentTimePeriod] === 0
                && channel.cancellation[this.currentTimePeriod] === 0
              )) {
              bookingItems.push({
                isActive: true,
                channel: channel.channel_name,
                gross_bookings: this.formatWidgetMoneyNumber(channel.booking[this.currentTimePeriod]),
                cancellations: this.formatWidgetMoneyNumber(channel.cancellation[this.currentTimePeriod]),
                net_bookings: this.formatWidgetMoneyNumber(channel.net_bookings[this.currentTimePeriod]),
                net_bookings_p: parseFloat(
                  (channel.net_bookings[this.currentTimePeriod] / this.apiData.NetBookings) * 100,
                  10,
                ).toFixed(1),
              });
            }
          });

          if (this.apiData.GrossBookings !== 0 || this.apiData.Cancellations !== 0 || this.apiData.NetBookings !== 0) {
            bookingItems.push({
              isActive: true,
              channel: 'Sum',
              gross_bookings: this.apiData.GrossBookings,
              cancellations: this.apiData.Cancellations,
              net_bookings: this.apiData.NetBookings,
              net_bookings_p: parseFloat(100).toFixed(1),
            });
          }
        }
        this.apiData.BookingTableValues = {
          bookingFields,
          bookingItems,
        };
        // Booking Volume Table
        const bookingVolumeTableFields = [
          {
            label: this.$t('pages.dashboard.booking-table.labels.channel'),
            key: 'channel',
            sortable: false,
          },
          {
            label: this.$t('pages.dashboard.booking-table.labels.gross-bookings-volume'),
            key: 'gross_bookings_volume',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-table.labels.cancellations-volume'),
            key: 'cancellations_volume',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-table.labels.net-bookings-volume'),
            key: 'net_bookings_volume',
            sortable: true,
          },
          {
            label: this.$t('pages.dashboard.booking-table.labels.net-bookings-volume-p'),
            key: 'net_bookings_volume_p',
            sortable: true,
          },
        ];
        const bookingVolumeTableItems = [];
        let directNbv = 0;
        if (this.dashboardData.channels) {
          this.dashboardData.channels.forEach((channel) => {
            if (!(channel.bookingGbv[this.currentTimePeriod] === 0
              && channel.cancellationGbv[this.currentTimePeriod] === 0)) {
              if (channel.channel_type === 'Direct') {
                directNbv += channel.net_booking_volume[this.currentTimePeriod];
              }
              bookingVolumeTableItems.push({
                isActive: true,
                channel: channel.channel_name,
                gross_bookings_volume: channel.bookingGbv[this.currentTimePeriod],
                cancellations_volume: channel.cancellationGbv[this.currentTimePeriod],
                net_bookings_volume: channel.net_booking_volume[this.currentTimePeriod],
                net_bookings_volume_p: parseFloat(
                  (channel.net_booking_volume[this.currentTimePeriod] / (this.apiData.NetBookingVolume)) * 100, 10,
                ).toFixed(1),
              });
            }
          });

          if (
            this.apiData.GrossBookingVolume !== 0
            || this.apiData.CancellationVolume !== 0
            || this.apiData.NetBookingVolume !== 0
          ) {
            bookingVolumeTableItems.push({
              isActive: true,
              channel: 'Sum',
              gross_bookings_volume: this.apiData.GrossBookingVolume,
              cancellations_volume: this.apiData.CancellationVolume,
              net_bookings_volume: this.apiData.NetBookingVolume,
              net_bookings_volume_p: parseFloat(100).toFixed(1),
            });
          }
          this.apiData.DirectPercentage = parseFloat(
            (directNbv / this.apiData.NetBookingVolume) * 100,
          ).toFixed(1);
        }
        this.apiData.BookingVolumeTableValues = {
          bookingVolumeTableFields,
          bookingVolumeTableItems,
        };
        // Net Booking Volume Per Month
        if (this.dashboardData.channels && this.dashboardData.channels.length > 0) {
          this.dashboardData.channels.forEach((channel) => {
            Object.keys(channel.net_booking_volume.dates).forEach((key) => {
              if (this.apiData.NetBookingVolumePerMonth[key] !== undefined) {
                this.apiData.NetBookingVolumePerMonth[key] += channel.net_booking_volume.dates[key];
              } else {
                this.apiData.NetBookingVolumePerMonth[key] = channel.net_booking_volume.dates[key];
              }
            });
          });
        }
      },
      updateWidgetValues() {
        if (!this.settingData || this.settingData.length === 0) return;

        this.kpiGroupData.length = 0;
        this.booksGroupData.length = 0;
        this.ibeGroupData.length = 0;
        this.channelGroupData.length = 0;
        this.termGroupData.length = 0;
        let id = -1;

        /** KPI Group Data */
        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.BookingDay].id,
          groupId: this.settingData[widgetId.BookingDay].group.id,
          widgetType: this.settingData[widgetId.BookingDay].type.id,
          widgetVisible: this.settingData[widgetId.BookingDay].visible,
          image: this.settingData[widgetId.BookingDay].image,
          position: this.settingData[widgetId.BookingDay].position,
          size: this.settingData[widgetId.BookingDay].size,
          content: {
            score: this.apiData.BookingsDay ? this.fixedOne(this.apiData.BookingsDay) : this.fixedOne(0.0),
            title: this.settingData[widgetId.BookingDay].title,
            currency: null,
          },
          x: this.settingData[widgetId.BookingDay].x,
          y: this.settingData[widgetId.BookingDay].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.CancellationRate].id,
          groupId: this.settingData[widgetId.CancellationRate].group.id,
          widgetType: this.settingData[widgetId.CancellationRate].type.id,
          widgetVisible: this.settingData[widgetId.CancellationRate].visible,
          image: this.settingData[widgetId.CancellationRate].image,
          position: this.settingData[widgetId.CancellationRate].position,
          size: this.settingData[widgetId.CancellationRate].size,
          content: {
            score: this.apiData.CancellationRate ? this.fixedOne(this.apiData.CancellationRate) : this.fixedOne(0),
            title: this.settingData[widgetId.CancellationRate].title,
            currency: '%',
          },
          x: this.settingData[widgetId.CancellationRate].x,
          y: this.settingData[widgetId.CancellationRate].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.RoomnightsBooking].id,
          groupId: this.settingData[widgetId.RoomnightsBooking].group.id,
          widgetType: this.settingData[widgetId.RoomnightsBooking].type.id,
          widgetVisible: this.settingData[widgetId.RoomnightsBooking].visible,
          image: this.settingData[widgetId.RoomnightsBooking].image,
          position: this.settingData[widgetId.RoomnightsBooking].position,
          size: this.settingData[widgetId.RoomnightsBooking].size,
          content: {
            score: this.apiData.RoomnightsBooking ? this.fixedOne(this.apiData.RoomnightsBooking) : this.fixedOne(0),
            title: this.settingData[widgetId.RoomnightsBooking].title,
            currency: null,
          },
          x: this.settingData[widgetId.RoomnightsBooking].x,
          y: this.settingData[widgetId.RoomnightsBooking].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.ProducingChannels].id,
          groupId: this.settingData[widgetId.ProducingChannels].group.id,
          widgetType: this.settingData[widgetId.ProducingChannels].type.id,
          widgetVisible: this.settingData[widgetId.ProducingChannels].visible,
          image: this.settingData[widgetId.ProducingChannels].image,
          position: this.settingData[widgetId.ProducingChannels].position,
          size: this.settingData[widgetId.ProducingChannels].size,
          content: {
            score: this.apiData.ProducingChannels ? this.apiData.ProducingChannels.toString() : '0',
            title: this.settingData[widgetId.ProducingChannels].title,
            currency: null,
          },
          x: this.settingData[widgetId.ProducingChannels].x,
          y: this.settingData[widgetId.ProducingChannels].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.AvgBookingValue].id,
          groupId: this.settingData[widgetId.AvgBookingValue].group.id,
          widgetType: this.settingData[widgetId.AvgBookingValue].type.id,
          widgetVisible: this.settingData[widgetId.AvgBookingValue].visible,
          image: this.settingData[widgetId.AvgBookingValue].image,
          position: this.settingData[widgetId.AvgBookingValue].position,
          size: this.settingData[widgetId.AvgBookingValue].size,
          content: {
            score: this.formatWidgetMoneyNumber(this.apiData.AvgBookingValue
              ? this.fixedTwo(this.apiData.AvgBookingValue)
              : this.fixedTwo(0)),
            title: this.settingData[widgetId.AvgBookingValue].title,
            currency: this.currencyCode,
          },
          x: this.settingData[widgetId.AvgBookingValue].x,
          y: this.settingData[widgetId.AvgBookingValue].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.OnlineOccupancyRate].id,
          groupId: this.settingData[widgetId.OnlineOccupancyRate].group.id,
          widgetType: this.settingData[widgetId.OnlineOccupancyRate].type.id,
          widgetVisible: this.settingData[widgetId.OnlineOccupancyRate].visible,
          image: this.settingData[widgetId.OnlineOccupancyRate].image,
          position: this.settingData[widgetId.OnlineOccupancyRate].position,
          size: this.settingData[widgetId.OnlineOccupancyRate].size,
          content: {
            score: this.apiData.OnlineOccupancyRate
              ? this.fixedOne(this.apiData.OnlineOccupancyRate) : this.fixedOne(0),
            title: this.settingData[widgetId.OnlineOccupancyRate].title,
            currency: '%',
          },
          x: this.settingData[widgetId.OnlineOccupancyRate].x,
          y: this.settingData[widgetId.OnlineOccupancyRate].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.AvgDailyRoomRate].id,
          groupId: this.settingData[widgetId.AvgDailyRoomRate].group.id,
          widgetType: this.settingData[widgetId.AvgDailyRoomRate].type.id,
          widgetVisible: this.settingData[widgetId.AvgDailyRoomRate].visible,
          image: this.settingData[widgetId.AvgDailyRoomRate].image,
          position: this.settingData[widgetId.AvgDailyRoomRate].position,
          size: this.settingData[widgetId.AvgDailyRoomRate].size,
          content: {
            score: this.formatWidgetMoneyNumber(this.apiData.AvgDailyRoomRate
              ? this.fixedTwo(this.apiData.AvgDailyRoomRate)
              : this.fixedTwo(0)),
            title: this.settingData[widgetId.AvgDailyRoomRate].title,
            currency: this.currencyCode,
          },
          x: this.settingData[widgetId.AvgDailyRoomRate].x,
          y: this.settingData[widgetId.AvgDailyRoomRate].y,
        });

        id += 1;
        this.kpiGroupData.push({
          id,
          widgetId: this.settingData[widgetId.OnlineRevPAR].id,
          groupId: this.settingData[widgetId.OnlineRevPAR].group.id,
          widgetType: this.settingData[widgetId.OnlineRevPAR].type.id,
          widgetVisible: this.settingData[widgetId.OnlineRevPAR].visible,
          image: this.settingData[widgetId.OnlineRevPAR].image,
          position: this.settingData[widgetId.OnlineRevPAR].position,
          size: this.settingData[widgetId.OnlineRevPAR].size,
          content: {
            score: this.formatWidgetMoneyNumber(this.apiData.OnlineRevPAR
              ? this.fixedTwo(this.apiData.OnlineRevPAR)
              : this.fixedTwo(0)),
            title: this.settingData[widgetId.OnlineRevPAR].title,
            currency: this.currencyCode,
          },
          x: this.settingData[widgetId.OnlineRevPAR].x,
          y: this.settingData[widgetId.OnlineRevPAR].y,
        });

        /** ****************** */

        id = -1;
        /** Books Group Data */
        id += 1;
        this.booksGroupData.push({
          id,
          widgetId: this.settingData[widgetId.BookingValue].id,
          groupId: this.settingData[widgetId.BookingValue].group.id,
          widgetType: this.settingData[widgetId.BookingValue].type.id,
          widgetVisible: this.settingData[widgetId.BookingValue].visible,
          image: this.settingData[widgetId.BookingValue].image,
          position: this.settingData[widgetId.BookingValue].position,
          size: this.settingData[widgetId.BookingValue].size,
          content: {
            title: this.settingData[widgetId.BookingValue].title,
            dataPoints: [
              {
                label: this.$t('pages.dashboard.books-group-data.labels.gross-bookings'),
                y: this.apiData.GrossBookings,
                displayValue: this.apiData.GrossBookings,
                color: '',
              },
              {
                label: this.$t('pages.dashboard.books-group-data.labels.cancellations-bookings'),
                y: (-1) * this.apiData.Cancellations,
                displayValue: this.apiData.Cancellations,
                color: '',
              },
              {
                label: this.$t('pages.dashboard.books-group-data.labels.net-bookings'),
                y: (-1) * this.apiData.NetBookings,
                displayValue: this.apiData.NetBookings,
                color: '',
              },
            ],
          },
          x: this.settingData[widgetId.BookingValue].x,
          y: this.settingData[widgetId.BookingValue].y,
        });

        id += 1;
        this.booksGroupData.push({
          id,
          widgetId: this.settingData[widgetId.SoldRoomnights].id,
          groupId: this.settingData[widgetId.SoldRoomnights].group.id,
          widgetType: this.settingData[widgetId.SoldRoomnights].type.id,
          widgetVisible: this.settingData[widgetId.SoldRoomnights].visible,
          image: this.settingData[widgetId.SoldRoomnights].image,
          position: this.settingData[widgetId.SoldRoomnights].position,
          size: this.settingData[widgetId.SoldRoomnights].size,
          content: {
            title: this.settingData[widgetId.SoldRoomnights].title,
            dataPoints: [
              {
                label: this.$t('pages.dashboard.books-group-data.labels.gross-bookings-roomnights'),
                y: this.apiData.GrossSoldRoomnights,
                displayValue: this.apiData.GrossSoldRoomnights,
                color: '',
              },
              {
                label: this.$t('pages.dashboard.books-group-data.labels.cancellations'),
                y: (-1) * this.apiData.CancellationSoldRoomnights,
                displayValue: this.apiData.CancellationSoldRoomnights,
                color: '',
              },
              {
                label: this.$t('pages.dashboard.books-group-data.labels.net-bookings-roomnights'),
                y: (-1) * this.apiData.NetSoldRoomnights,
                displayValue: this.apiData.NetSoldRoomnights,
                color: '',
              },
            ],
          },
          x: this.settingData[widgetId.SoldRoomnights].x,
          y: this.settingData[widgetId.SoldRoomnights].y,
        });
        /** ****************** */
        id = -1;
        /** IBE Group Data */
        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.IBESearches].id,
          groupId: this.settingData[widgetId.IBESearches].group.id,
          widgetType: this.settingData[widgetId.IBESearches].type.id,
          widgetVisible: this.settingData[widgetId.IBESearches].visible,
          image: this.settingData[widgetId.IBESearches].image,
          position: this.settingData[widgetId.IBESearches].position,
          size: this.settingData[widgetId.IBESearches].size,
          content: {
            score: this.apiData.IbeSearches ? this.apiData.IbeSearches.toString() : '0',
            title: this.settingData[widgetId.IBESearches].title,
            currency: null,
          },
          x: this.settingData[widgetId.IBESearches].x,
          y: this.settingData[widgetId.IBESearches].y,
        });

        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.UniqueVisitors].id,
          groupId: this.settingData[widgetId.UniqueVisitors].group.id,
          widgetType: this.settingData[widgetId.UniqueVisitors].type.id,
          widgetVisible: this.settingData[widgetId.UniqueVisitors].visible,
          image: this.settingData[widgetId.UniqueVisitors].image,
          position: this.settingData[widgetId.UniqueVisitors].position,
          size: this.settingData[widgetId.UniqueVisitors].size,
          content: {
            score: this.apiData.UniqueVisitors ? this.apiData.UniqueVisitors.toString() : '0',
            title: this.settingData[widgetId.UniqueVisitors].title,
            currency: null,
          },
          x: this.settingData[widgetId.UniqueVisitors].x,
          y: this.settingData[widgetId.UniqueVisitors].y,
        });

        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.IBEBookings].id,
          groupId: this.settingData[widgetId.IBEBookings].group.id,
          widgetType: this.settingData[widgetId.IBEBookings].type.id,
          widgetVisible: this.settingData[widgetId.IBEBookings].visible,
          image: this.settingData[widgetId.IBEBookings].image,
          position: this.settingData[widgetId.IBEBookings].position,
          size: this.settingData[widgetId.IBEBookings].size,
          content: {
            score: this.apiData.Bookings ? this.apiData.Bookings.toString() : '0',
            title: this.settingData[widgetId.IBEBookings].title,
            currency: null,
          },
          x: this.settingData[widgetId.IBEBookings].x,
          y: this.settingData[widgetId.IBEBookings].y,
        });

        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.SearchesVisitor].id,
          groupId: this.settingData[widgetId.SearchesVisitor].group.id,
          widgetType: this.settingData[widgetId.SearchesVisitor].type.id,
          widgetVisible: this.settingData[widgetId.SearchesVisitor].visible,
          image: this.settingData[widgetId.SearchesVisitor].image,
          position: this.settingData[widgetId.SearchesVisitor].position,
          size: this.settingData[widgetId.SearchesVisitor].size,
          content: {
            score: this.apiData.SearchedVisitors ? parseFloat(this.apiData.SearchedVisitors).toFixed(1) : '0',
            title: this.settingData[widgetId.SearchesVisitor].title,
            currency: null,
          },
          x: this.settingData[widgetId.SearchesVisitor].x,
          y: this.settingData[widgetId.SearchesVisitor].y,
        });

        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.Conversion].id,
          groupId: this.settingData[widgetId.Conversion].group.id,
          widgetType: this.settingData[widgetId.Conversion].type.id,
          widgetVisible: this.settingData[widgetId.Conversion].visible,
          image: this.settingData[widgetId.Conversion].image,
          position: this.settingData[widgetId.Conversion].position,
          size: this.settingData[widgetId.Conversion].size,
          content: {
            score: this.apiData.Conversion ? parseFloat(this.apiData.Conversion).toFixed(1) : '0',
            title: this.settingData[widgetId.Conversion].title,
            currency: '%',
          },
          x: this.settingData[widgetId.Conversion].x,
          y: this.settingData[widgetId.Conversion].y,
        });

        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.IBEPerformance].id,
          groupId: this.settingData[widgetId.IBEPerformance].group.id,
          widgetType: this.settingData[widgetId.IBEPerformance].type.id,
          widgetVisible: this.settingData[widgetId.IBEPerformance].visible,
          image: this.settingData[widgetId.IBEPerformance].image,
          position: this.settingData[widgetId.IBEPerformance].position,
          size: this.settingData[widgetId.IBEPerformance].size,
          content: {
            averageNum: this.apiData.Average ? this.apiData.Average : 0,
            deviationNum: this.apiData.Deviation ? this.apiData.Deviation : 0,
            conversionNum: this.apiData.Conversion ? this.apiData.Conversion : 0,
            title: this.settingData[widgetId.IBEPerformance].title,
            doubleChart: true,
          },
          x: this.settingData[widgetId.IBEPerformance].x,
          y: this.settingData[widgetId.IBEPerformance].y,
        });
        id += 1;
        this.ibeGroupData.push({
          id,
          widgetId: this.settingData[widgetId.DirectIndirect].id,
          groupId: this.settingData[widgetId.DirectIndirect].group.id,
          widgetType: this.settingData[widgetId.DirectIndirect].type.id,
          widgetVisible: this.settingData[widgetId.DirectIndirect].visible,
          image: this.settingData[widgetId.DirectIndirect].image,
          position: this.settingData[widgetId.DirectIndirect].position,
          size: this.settingData[widgetId.DirectIndirect].size,
          content: {
            title: this.settingData[widgetId.DirectIndirect].title,
            data: [
              this.apiData.DirectPercentage ? this.apiData.DirectPercentage : 0,
              100 - (this.apiData.DirectPercentage ? this.apiData.DirectPercentage : 0),
            ],
          },
          x: this.settingData[widgetId.DirectIndirect].x,
          y: this.settingData[widgetId.DirectIndirect].y,
        });
        /** ****************** */

        /** Channels Group Data */
        id = -1;
        id += 1;
        this.channelGroupData.push({
          id,
          widgetId: this.settingData[widgetId.BookingTable].id,
          groupId: this.settingData[widgetId.BookingTable].group.id,
          widgetType: this.settingData[widgetId.BookingTable].type.id,
          widgetVisible: this.settingData[widgetId.BookingTable].visible,
          image: this.settingData[widgetId.BookingTable].image,
          position: this.settingData[widgetId.BookingTable].position,
          size: this.settingData[widgetId.BookingTable].size,
          content: {
            data: {
              title: this.settingData[widgetId.BookingTable].title,
              fields: this.apiData.BookingTableValues ? this.apiData.BookingTableValues.bookingFields : [],
              items: this.apiData.BookingTableValues ? this.apiData.BookingTableValues.bookingItems : [],
            },
          },
          x: this.settingData[widgetId.BookingTable].x,
          y: this.settingData[widgetId.BookingTable].y,
        });

        id += 1;
        this.channelGroupData.push({
          id,
          widgetId: this.settingData[widgetId.BookingVolumeTable].id,
          groupId: this.settingData[widgetId.BookingVolumeTable].group.id,
          widgetType: this.settingData[widgetId.BookingVolumeTable].type.id,
          widgetVisible: this.settingData[widgetId.BookingVolumeTable].visible,
          image: this.settingData[widgetId.BookingVolumeTable].image,
          position: this.settingData[widgetId.BookingVolumeTable].position,
          size: this.settingData[widgetId.BookingVolumeTable].size,
          content: {
            data: {
              title: this.settingData[widgetId.BookingVolumeTable].title,
              fields: this.apiData.BookingVolumeTableValues
                ? this.apiData.BookingVolumeTableValues.bookingVolumeTableFields : [],
              items: this.apiData.BookingVolumeTableValues
                ? this.apiData.BookingVolumeTableValues.bookingVolumeTableItems : [],
            },
          },
          x: this.settingData[widgetId.BookingVolumeTable].x,
          y: this.settingData[widgetId.BookingVolumeTable].y,
        });
        /** ****************** */

        /** Term Group Data */
        const labels = [];
        for (let i = 0; i <= 11; i += 1) {
          labels.push(this.$t(`months-sort.${i}`));
        }
        id = -1;
        if (this.apiData.NetBookingVolumePerMonth) {
          id += 1;
          this.termGroupData.push({
            id,
            widgetId: this.settingData[widgetId.BookingVolume].id,
            groupId: this.settingData[widgetId.BookingVolume].group.id,
            widgetType: this.settingData[widgetId.BookingVolume].type.id,
            widgetVisible: this.settingData[widgetId.BookingVolume].visible,
            image: this.settingData[widgetId.BookingVolume].image,
            position: this.settingData[widgetId.BookingVolume].position,
            size: this.settingData[widgetId.BookingVolume].size,
            title: this.settingData[widgetId.BookingVolume].title,
            content: {
              data: {
                labels,
                datasets: this.getDatasetsForLineChart(this.apiData.NetBookingVolumePerMonth),
              },
            },
            x: this.settingData[widgetId.BookingVolume].x,
            y: this.settingData[widgetId.BookingVolume].y,
          });
        }
        if (this.termGroupData.length > 0) {
          id += 1;
          this.termGroupData.push({
            id,
            widgetId: this.settingData[widgetId.AccumulatedBookingVolume].id,
            groupId: this.settingData[widgetId.AccumulatedBookingVolume].group.id,
            widgetType: this.settingData[widgetId.AccumulatedBookingVolume].type.id,
            widgetVisible: this.settingData[widgetId.AccumulatedBookingVolume].visible,
            image: this.settingData[widgetId.AccumulatedBookingVolume].image,
            position: this.settingData[widgetId.AccumulatedBookingVolume].position,
            size: this.settingData[widgetId.AccumulatedBookingVolume].size,
            title: this.settingData[widgetId.AccumulatedBookingVolume].title,
            content: {
              data: {
                labels,
                datasets: this.getDatasetsForAccumulatedLineChart(this.termGroupData[0].content.data.datasets),
              },
            },
            x: this.settingData[widgetId.AccumulatedBookingVolume].x,
            y: this.settingData[widgetId.AccumulatedBookingVolume].y,
          });
        }
        /** ****************** */
      },
      updateDashboardData() {
        this.updateApiData();
        this.updateWidgetValues();
      },
      formatWidgetMoneyNumber(number) {
        return formatNumber(number, this.numberFormat);
      },
    },
  };
</script>
