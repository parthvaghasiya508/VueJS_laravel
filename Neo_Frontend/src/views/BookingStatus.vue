<template>
  <div class="page-booking-status">
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.booking.title') }}</p>
    </div>

    <booking-status-confirm-modal ref="confirmModal" @ok="changeStatus" :pending="hotelsPending" />

    <i18n path="pages.booking.heading" tag="p" class="status">
      <template #all><b>{{ $t('pages.booking.all') }}</b></template>
      <template #mode>
        <b :class="`text-${hotel.active ? 'success' : 'danger'}`">
          {{ $t(`pages.booking.${hotel.active ? 'bookable' : 'unbookable'}`) }}
        </b>
      </template>
    </i18n>
    <p class="status">
      {{ $t(`pages.booking.tip-${hotel.active ? 'on' : 'off'}`) }}
    </p>
    <switcher :checked="hotel.active" colored lazy
              :on-label="$t('pages.booking.on')" :off-label="$t('pages.booking.off')"
              @willChange="showConfirm" :disabled="hotelsPending" />

    <p class="logs-title"></p>
    <Tabs :items="tabs" v-model="activeTab" withContent>
      <template #tab(1)>
        <div class="logs-table d-none d-md-block" v-if="logsLoaded">
          <table class="w-100 text-left">
            <thead>
              <tr>
                <th class="w-id">{{ $t('id') }}</th>
                <th class="w-name">{{ $t('pages.booking.headers.username') }}</th>
                <th class="w-date">{{ $t('pages.booking.headers.date') }}</th>
                <th class="w-status">{{ $t('pages.booking.headers.changed-to') }}</th>
              </tr>
            </thead>
            <tbody v-if="!booking.length">
              <tr>
                <td colspan="4" class="w-empty">{{ $t('pages.booking.no-logs') }}</td>
              </tr>
            </tbody>
            <tbody v-for="log in booking" :key="`log-${log.id}`">
              <tr class="separator before"></tr>
              <tr>
                <td>{{ log.user ? log.user.id : '' }}</td>
                <td><p>{{ log.user ? log.user.profile.name : '' }}</p></td>
                <td><p>{{ createDate(log) }}</p></td>
                <td>
                  <switcher :checked="log.status" disabled
                            :on-label="$t('pages.booking.on')" :off-label="$t('pages.booking.off')"/>
                </td>
              </tr>
              <tr class="separator after"></tr>
            </tbody>
          </table>
        </div>

        <div class="d-md-none">
          <mobile-table>
            <template #mobile>
              <li v-for="log in booking" :key="log.id">
                <div class="row">
                  <div class="col-6">
                    <h6 class="font-weight-bold">{{ $t('id') }}</h6>
                    <p>{{ log.user ? log.user.id : '' }}</p>
                  </div>
                  <div class="col-6">
                    <h6 class="font-weight-bold">{{ $t('pages.booking.headers.date') }}</h6>
                    <p>{{ createDate(log) }}</p>
                  </div>
                  <div class="col-6">
                    <h6 class="font-weight-bold">{{ $t('pages.booking.headers.username') }}</h6>
                    <p>{{ log.user ? log.user.profile.name : '' }}</p>
                  </div>
                  <div class="col-6">
                    <h6 class="font-weight-bold">{{ $t('pages.booking.headers.changed-to') }}</h6>
                    <switcher :checked="log.status" disabled
                              :on-label="$t('pages.booking.on')" :off-label="$t('pages.booking.off')"/>
                  </div>
                </div>
              </li>
            </template>
          </mobile-table>
        </div>
      </template>
      <template #tab(2)>
        <div class="d-flex align-items-center justify-content-between p-3">
          <span>{{ $t('pages.booking.roomdb-master') }}</span>
          <switcher
            colored
            :checked="hotel.roomdb_is_master"
            :on-label="$t('yes')"
            :off-label="$t('no')"
            :disabled="hotelsPending"
            @change="updateRoomdbIsMaster"
          />
        </div>
      </template>
    </Tabs>
  </div>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';
  import moment from 'moment';
  import BookingStatusConfirmModal from '@/components/BookingStatusConfirmModal.vue';
  import MobileTable from '../components/MobileTable.vue';

  export default {
    name: 'BookingStatus',
    components: { MobileTable, BookingStatusConfirmModal },
    data() {
      return {
        confirm: false,
        activeTab: 1,
        tabs: [
          { id: 1, title: this.$t('pages.booking.title-logs') },
          { id: 2, title: this.$t('pages.booking.title-master') },
        ],
      };
    },
    async created() {
      this.fetchLogs({ key: 'booking', id: this.hotel.id, forced: true });
      await this.getHotel();
    },
    computed: {
      ...mapState('user', ['hotelsPending']),
      ...mapGetters('user', ['hotel']),
      ...mapGetters('logs', ['booking']),
      logsLoaded() {
        return this.booking != null;
      },
    },
    methods: {
      ...mapActions('user', ['getHotel', 'toggleHotelStatus', 'updateHotel']),
      ...mapActions('logs', ['fetchLogs']),
      createDate(row) {
        return moment(row.created_at).format(this.$t('pages.booking.headers.date-format'));
      },
      showConfirm(futureStatus) {
        this.$refs.confirmModal.show(futureStatus);
      },
      async changeStatus({ status }) {
        const { id } = this.hotel;
        try {
          await this.toggleHotelStatus({ id, active: status });
          this.$refs.confirmModal.hide();
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async updateRoomdbIsMaster(val) {
        await this.updateHotel({
          partial: true,
          id: this.hotel.id,
          roomdb_is_master: val,
        });
      },
    },
  };
</script>
