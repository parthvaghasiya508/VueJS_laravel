<template>
  <b-modal
    id="bookingStatusConfirmModal"
    ref="modal"
    no-fade
    centered
    static
    size="md"
    modal-class="cancel-modal"
    :title="$t('pages.booking.modal.title')"
    header-class="justify-content-center"
    :ok-title="confirmButtonTitle"
    :ok-variant="confirmButtonVariant"
    :ok-disabled="pending"
    :cancel-disabled="pending"
    cancel-variant="outline-primary"
    no-close-on-esc
    no-close-on-backdrop
    hide-header-close
    @ok.prevent="emitOk"
    @cancel="hide"
  >
    <i18n tag="p" class="question" path="pages.booking.modal.question">
      <template #all>
        <b>{{ $t('pages.booking.all') }}</b>
      </template>
      <template #mode>
        <b :class="`text-${confirmButtonVariant}`">
          {{ $t(`pages.booking.${futureStatus ? 'bookable' : 'unbookable'}`) }}
        </b>
      </template>
    </i18n>
  </b-modal>
</template>

<script>
  export default {
    name: 'BookingStatusConfirmModal',
    data: () => ({ futureStatus: null, payload: null }),
    props: {
      pending: Boolean,
    },
    computed: {
      confirmButtonVariant() {
        return this.futureStatus ? 'success' : 'danger';
      },
      confirmButtonTitle() {
        return this.$t(`pages.booking.modal.button-${this.futureStatus ? 'on' : 'off'}`);
      },
    },
    methods: {
      show(futureStatus, payload = null) {
        this.futureStatus = futureStatus;
        this.payload = payload;
        this.$nextTick(this.$refs.modal.show);
      },
      hide() {
        this.$nextTick(this.$refs.modal.hide);
      },
      emitOk() {
        this.$emit('ok', { status: this.futureStatus, payload: this.payload });
      },
    },
  };
</script>
