<template>
  <b-modal
    no-close-on-backdrop
    id="bankTransferModal"
    centered
    top
    no-fade
    size="lg"
    hide-footer
    ref="modal"
    static
  >
    <template #modal-header-close>
      <icon width="20" height="20" class="d-none d-md-block" type="times"/>
      <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
    </template>
    <div class="content-modal content-bank">
      <div class="header-bank-modal">
        <h4> {{ $t('pages.payments.methods.bank-transfer') }} </h4>
        <icon width="45" height="42" type="bank-icon" />
      </div>
      <div class="info">
        <div class="table">
          <div class="hold-info">
            <div class="title">
              {{ $t('components.bank-transfer.labels.holder') }}
            </div>
            <div class="title-normal">
              {{ bankDetails.client }}
            </div>
          </div>
          <div class="hold-info">
            <div class="title">
              {{ $t('components.bank-transfer.labels.bank') }}
            </div>
            <div class="title-normal">
              {{ bankDetails.bank }}
            </div>
          </div>
          <div class="hold-info">
            <div class="title">
              {{ $t('components.bank-transfer.labels.iban') }}
            </div>
            <div class="title-normal">
              {{ bankDetails.iban }}
            </div>
          </div>
          <div class="hold-info">
            <div class="title">
              {{ $t('components.bank-transfer.labels.bic') }}
            </div>
            <div class="title-normal">
              {{ bankDetails.bic }}
            </div>
          </div>
        </div>
        <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
          <b-form @submit.prevent.stop="handleSubmit(submitPaymentMethod)" novalidate>
            <submit-button class="mt-3">
              <icon class="icon mb-1" width="16" height="16" type="clock-icon"/> {{ $t('buttons.save') }}
            </submit-button>
          </b-form>
        </ValidationObserver>
      </div>
    </div>
  </b-modal>
</template>

<script>
  import { paymentMethods, bankDetails } from '@/shared';

  export default {
    name: 'BankTransferModal',
    data: () => ({
      form: {
      },
      isSaving: false,
    }),
    computed: {
      paymentMethods: () => paymentMethods,
      bankDetails: () => bankDetails,
    },
    methods: {
      async submitPaymentMethod() {
        // const { setupIntent, error } = await this.$stripe.confirmBancontactSetup(
        //   this.intent.client_secret, {
        //     payment_method: {
        //       billing_details: {
        //         name: 'Jenny Rosen',
        //         email: 'jenny@example.com',
        //       },
        //     },
        //     return_url: window.location.href,
        //   },
        // );
        this.$nextTick(this.$refs.modal.hide);
        this.$emit('validateMethod');
      },
    },
  };
</script>
