<template>
  <b-modal
    no-close-on-backdrop
    id="creditCardModal"
    centered
    top
    no-fade
    size="md"
    hide-footer
    ref="modal"
    @show="showPopup"
    @hidden="closePopup"
    static
  >
    <template #modal-header-close>
      <icon width="20" height="20" class="d-none d-md-block" type="times"/>
      <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
    </template>
    <div class="content-modal credit-content">
      <div class="header-bank-modal">
        <h4>
          {{$t('components.credit-card.title')}}
        </h4>
        <icon width="45" height="35" type="credit-card"/>
      </div>
      <ValidationObserver ref="form" slim v-slot="{ handleSubmit }">
        <b-form @submit.prevent.stop="handleSubmit(submitPaymentMethod)" novalidate>
          <label class="text-md">
            {{ $t('components.credit-card.form.labels.card-holder') }}
          </label>
          <ValidatedField v-model="form.cardHolder"
                          :placeholder="$t('components.credit-card.form.placeholders.card-holder')"
                          rules="required_string"
                          type="text"
                          id="cardHolder"
                          name="cardHolder"/>
          <label class="text-md">
            {{ $t('components.credit-card.form.labels.card-number') }}
          </label>
          <span id="card" class="form-control stripe-element-container">
            <!-- Stripe Card Element -->
          </span>
          <div class="row">
            <div class="col-6">
              <label class="text-md">
                {{ $t('components.credit-card.form.labels.expiration-month') }}
              </label>
              <span id="exp" class="form-control stripe-element-container">
              </span>
            </div>
            <div class="col-6">
              <label class="text-md">
                {{ $t('components.credit-card.form.labels.cvc') }}
              </label>
              <span id="cvc" class="form-control stripe-element-container"></span>
            </div>
          </div>
          <submit-button class="mt-3" :disabled="isSaving">
            <icon class="icon mb-1" width="16" height="16" type="clock-icon"/> {{ $t('buttons.save') }}
          </submit-button>
        </b-form>
      </ValidationObserver>
    </div>
  </b-modal>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';
  import { paymentMethods } from '@/shared';

  export default {
    name: 'CreditCardModal',
    data() {
      return {
        form: {
          cardHolder: '',
          cardType: '',
          cardNumber: '',
          expMonth: '',
          expYear: '',
          cvc: '',
          exp: '',
          saving: '',
          updatePaymentMethod: false,
          createPaymentInCultData: true,
        },
        expireDates: [],
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
      initForm() {
        this.form = {
          createPaymentInCultData: true,
        };
        if (this.paymentMethodDetails) {
          this.form.createPaymentInCultData = false;
        }
        if (!this.paymentMethodDetailsPending) {
          this.initCardNumber();
        }
      },
      async submitPaymentMethod() {
        this.isSaving = true;
        this.cardMessage = '';
        const { setupIntent, error } = await this.$stripe.confirmCardSetup(
          this.intent.client_secret, {
            payment_method: {
              card: this.form.cardNumber,
              billing_details: {
                name: this.form.cardHolder,
              },
            },
          },
        );
        if (error) {
          this.$toastr.e(error.message, this.$t('error'));
        } else {
          this.form.updatePaymentMethod = true;
          const obj = { payment_method: setupIntent.payment_method, ...this.form };
          await this.savePaymentMethod(obj);
          this.$toastr.s({
            title: this.$t('pages.payments.alert-updated'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
          this.$nextTick(this.$refs.modal.hide);
          this.$emit('validateMethod');
        }
        this.isSaving = false;
      },
      initCardNumber() {
        const style = {
          base: {
            fontSize: '16px',
            color: '#495057',
            fontFamily: 'apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif',
          },
        };
        this.form.cardNumber = this.stripeElements.create('cardNumber', {
          placeholder: '',
          style,
        });
        this.form.exp = this.stripeElements.create('cardExpiry', {
          placeholder: '',
          style,
        });
        this.form.cvc = this.stripeElements.create('cardCvc', {
          placeholder: '',
          style,
        });

        this.form.exp.mount('#exp');
        this.form.cardNumber.mount('#card');
        this.form.cvc.mount('#cvc');
      },
      async showPopup() {
        const obj = {
          paymentMethod: paymentMethods.card,
        };
        await this.fetchPaymentMethod(obj);
        this.initForm();
      },
      closePopup() {
        this.form.cardNumber.destroy();
        this.form.exp.destroy();
        this.form.cvc.destroy();
        this.form.cardHolder = '';
        this.$refs.form.reset();
      },
    },
  };
</script>
