<template>
  <b-modal
    no-close-on-backdrop
    id="sepaModal"
    centered
    top
    static
    no-fade
    size="md"
    ref="modal"
    hide-footer
    @show="showPopup"
    @hidden="closePopup"
  >
    <template #modal-header-close>
      <icon width="20" height="20" class="d-none d-md-block" type="times"/>
      <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
    </template>
    <template #modal-title>
    </template>
    <div>
      <div class="content-modal content-modal-sepa">
        <div class="modal-header">
          <img src="/assets/icons/sepa.svg" alt="sepa-logo" class="logo"/>
          <p>
            <icon class="icon mr-1 mb-1" width="12" height="12" type="info-circle"/> {{ $t('components.sepa.info') }}
          </p>
        </div>
        <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
          <b-form @submit.prevent.stop="handleSubmit(submitPaymentMethod)" novalidate>
            <label class="text-md">
              {{ $t('components.sepa.form.labels.creditorId') }}
            </label>
            <ValidatedField v-model="form.creditorId"
                            :placeholder="$t('components.sepa.form.labels.creditorId')"
                            rules="required_string|max:100"
                            type="text"
                            id="creditorId"
                            :disabled="true"
                            name="creditorId"/>
            <label class="text-md">
              {{ $t('components.sepa.form.labels.account') }}
            </label>
            <ValidatedField v-model="form.accountHolder"
                            :placeholder="$t('components.sepa.form.labels.account')"
                            rules="required_string"
                            type="text"
                            name="accountHolder"/>
            <label class="text-md">
              {{ $t('components.sepa.form.labels.email') }}
            </label>
            <ValidatedField v-model="form.email"
                            :placeholder="$t('components.sepa.form.labels.email')"
                            rules="required_string|email"
                            type="email"
                            name="email"/>
            <label class="text-md">
              {{ $t('components.sepa.form.labels.iban') }}
            </label>
            <span id="iban" class="form-control stripe-element-container">
            <!-- Stripe Iban Element -->
            </span>
            <submit-button class="mt-3" :disabled="isSaving">
              <icon class="icon mb-1" width="16" height="16" type="clock-icon"/> {{ $t('buttons.save') }}
            </submit-button>
          </b-form>
        </ValidationObserver>
      </div>
    </div>
  </b-modal>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';
  import { paymentMethods } from '@/shared';

  export default {
    name: 'SepaModal',
    data() {
      return {
        form: {
          creditorId: null,
          accountHolder: null,
          iban: null,
          updatePaymentMethod: false,
          createPaymentInCultData: true,
          email: null,
        },
        isSaving: false,
      };
    },
    created() {
    },
    computed: {
      ...mapState('payments', ['pending', 'paymentMethodDetailsPending']),
      ...mapGetters('payments', ['intent', 'creditorId', 'loaded', 'pending', 'paymentMethodDetails', 'defaultPaymentMethod']),
      stripeElements() {
        return this.$stripe.elements();
      },
      paymentMethods: () => paymentMethods,
    },
    mounted() {
    },
    methods: {
      ...mapActions('payments', ['savePaymentMethod', 'fetchPaymentMethod']),
      async submitPaymentMethod() {
        this.isSaving = true;
        const { setupIntent, error } = await this.$stripe.confirmSepaDebitSetup(
          this.intent.client_secret, {
            payment_method: {
              sepa_debit: this.form.iban,
              billing_details: {
                name: this.form.creditorId,
                email: this.form.email,
              },
            },
          },
        );
        if (error) {
          this.$toastr.e(error.message, this.$t('error'));
        } else {
          this.form.updatePaymentMethod = true;
          const obj = { payment_method: setupIntent.payment_method, ...this.form };
          this.savePaymentMethod(obj);
          this.$nextTick(this.$refs.modal.hide);
          this.$emit('validateMethod');
          this.$toastr.s({
            title: this.$t('pages.payments.alert-updated'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        }
        this.isSaving = false;
      },
      initForm() {
        this.form = {
          creditorId: this.creditorId,
          createPaymentInCultData: true,
        };
        if (this.paymentMethodDetails) {
          this.form.createPaymentInCultData = false;
          this.form.accountHolder = this.paymentMethodDetails.accountHolder;
        }
        if (!this.paymentMethodDetailsPending) {
          this.initIbanElement();
        }
      },
      initIbanElement() {
        const style = {
          base: {
            fontSize: '16px',
            color: '#495057',
          },
        };
        const options = {
          supportedCountries: ['SEPA'],
          placeholderCountry: 'DE',
          style,
        };
        this.form.iban = this.stripeElements.create('iban', options);
        this.form.iban.mount('#iban');
      },
      async showPopup() {
        const obj = {
          paymentMethod: paymentMethods.sepa,
        };
        await this.fetchPaymentMethod(obj);
        await this.initForm();
      },
      closePopup() {
        this.form.iban.destroy();
        this.form.accountHolder = '';
        this.$refs.form.reset();
      },
    },
  };
</script>

<style scoped>
.stripe-element-container {
  padding-top: .85rem;
}
.StripeElement--invalid  {
  border-color: #eb6057;
}
</style>
