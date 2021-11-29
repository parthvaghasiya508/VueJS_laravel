<template>
  <div class="page-promotions">
    <b-modal
      no-close-on-backdrop
      id="promoModal"
      ref="promoModal"
      no-fade
      static
      centered
      size="md"
      modal-class="form-modal"
      :ok-title="promoOkTitle"
      :ok-variant="promo.outdated ? 'outline-primary' : 'primary'"
      :ok-only="promo.outdated"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      :ok-disabled="updatePending || !promoFormValid"
      :cancel-disabled="updatePending"
      :no-close-on-esc="updatePending"
      :hide-header-close="updatePending"
      @show="modalScroll"
      @hidden="modalScroll"
      @ok.prevent="processPromoForm"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times" />
        <icon width="10" height="18" class="d-md-none" type="arrow-left" />
      </template>
      <template #modal-title>
        {{ promoModalTitle }}
      </template>
      <ValidationObserver ref="promoForm" slim>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-2">{{ $t(`pages.channels.promo.modal.field-name-${promo.mode}`) }}</h5>
            <ValidatedField
              id="promo-name"
              name="name"
              no-icon
              rules="required|min:3"
              :placeholder="$t(`pages.channels.promo.modal.field-name-placeholder-${promo.mode}`)"
              v-model="promo.name"
              :disabled="updatePending || promo.outdated"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-2">{{ $t(`pages.channels.promo.modal.field-code-${promo.mode}`) }}</h5>
            <ValidatedField
              name="code"
              no-icon
              rules="required|min:3"
              :error-bag="updateError"
              :placeholder="$t(`pages.channels.promo.modal.field-code-placeholder-${promo.mode}`)"
              v-model.trim="promo.code"
              :disabled="updatePending || promoIsEdit || promo.outdated"
            />
          </div>
        </div>
        <div class="row" v-if="promo.mode === 'promo'">
          <div class="col-12">
            <h5 class="pb-2">{{ $t("pages.channels.promo.modal.field-discount") }}</h5>
            <AmountPercent
              v-model="promo.discount"
              required
              :disabled="updatePending || promo.outdated"
              class="mb-3"
              min="1"
              max="99"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-4 pt-2">{{ $t("pages.channels.promo.modal.field-plans") }}</h5>
            <products-selector
              v-if="!promoIsEdit"
              plans-only
              :plans="promoCreatePlans"
              :rooms="rooms"
              :selected-plans.sync="promo.plans"
              :disabled="updatePending"
            />
            <div v-else>
              <p v-for="({ id, text, rid, rtext }, idx) in promoRates(promo)" :key="`pre-${promo.code}-${idx}`">
                {{ rtext }} ({{ rid }}) &centerdot; {{ text }} ({{ id }})
              </p>
            </div>
          </div>
        </div>
        <h5 class="pb-2 pt-3">{{ $t("pages.channels.promo.modal.field-validity") }}</h5>
        <div class="row">
          <div class="col-12 col-md-6">
            <label class="text-xs">{{ $t("date.from") }}</label>
            <date-picker
              id="promo-from"
              v-model="promo.from"
              :min-date="today"
              grow="md-up"
              ref="promoFrom"
              :disabled="updatePending || promo.outdated"
              :placeholder="$t('date.from-placeholder')"
              @input="promoFromChanged"
            />
          </div>
          <div class="col-12 col-md-6">
            <label class="text-xs">{{ $t("date.until") }}</label>
            <date-picker
              id="promo-until"
              v-model="promo.until"
              :min-date="today"
              grow="md-up"
              ref="promoUntil"
              position="left-md-right"
              :disabled="updatePending || promo.outdated"
              :placeholder="$t('date.until-placeholder')"
              @input="promoUntilChanged"
            />
          </div>
        </div>
      </ValidationObserver>
    </b-modal>
    <div class="panel-title position-relative w-100 title">
      <p>{{ $t("pages.promotions.title") }}</p>
    </div>
    <div class="component-mealplans">
      <div class="panel position-relative panel-content">
        <div class="list d-none d-md-block left-edge">
          <p class="head-line">
            <span>{{ $t("pages.promotions.heading") }}</span
            ><spinner v-if="!loaded" />
          </p>
          <promotion
            v-if="loaded"
            mode="promo"
            type="product"
            :filter="filter"
            :all="all"
            :updating="updatePending"
            :promos="promoItems()"
            @create="openCreatePromo()"
            @edit="editPromo($event)"
            @delete="deletePromo($event)"
            @filter="filter = $event"
            @all="all = $event"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import moment from 'moment';
  import {
    mapState, mapGetters, mapMutations, mapActions,
  } from 'vuex';
  import { ValidationError } from '@/errors';
  import { bookingEngineId } from '@/shared';

  export default {
    name: 'PagePromotions',
    data() {
      return {
        id: null,
        filter: '',
        all: true,
        today: moment(),
        promo: {
          mode: 'promo',
          plans: [],
          discount: {
            value: '',
            mode: 'percent',
          },
        },
      };
    },
    computed: {
      ...mapGetters('channel', ['loaded', 'channel', 'rooms', 'plans']),
      ...mapState('channel', ['error', 'pending', 'updatePending', 'updateError']),
      promoCreatePlans() {
        return this.plans.filter(({ promo }) => promo == null);
      },
      promoModalTitle() {
        const {
          id, code, outdated, mode,
        } = this.promo;
        if (id == null) {
          return this.$t(`pages.channels.promo.modal.title-add-${mode}`);
        }
        if (outdated) {
          return this.$t(`pages.channels.promo.modal.title-view-${mode}`, { code });
        }
        return this.$t(`pages.channels.promo.modal.title-edit-${mode}`, { code });
      },
      promoOkTitle() {
        const { id, outdated } = this.promo;
        if (id == null) {
          return this.$t('buttons.save');
        }
        if (outdated) {
          return this.$t('buttons.close');
        }
        return this.$t('buttons.update');
      },
      promoFormValid() {
        if (this.promo.outdated) return true;
        const { promoForm } = this.$refs;
        if (promoForm == null) return false;
        const { from, until, plans } = this.promo;
        return (
          promoForm.flags.valid
          && from != null
          && until != null
          && (this.promoIsEdit || (plans != null && plans.length > 0))
        );
      },
      promoIsEdit() {
        return this.promo.id != null;
      },
    },
    mounted() {
      this.setChannelId(bookingEngineId);
    },
    methods: {
      ...mapActions('channel', ['fetchData', 'createContract', 'updateContract', 'deleteContract']),
      ...mapMutations('channel', ['clearErrors']),
      async setChannelId(id) {
        this.id = id;
        try {
          await this.fetchData({ id, force: true });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      promoItems() {
        const { contractor } = this.channel;
        let codes = contractor == null || contractor.codes == null ? false : contractor.codes;
        if (codes === false) return [];
        codes = codes.filter((i) => i.mode === 'promo');
        const filter = this.filter.trim().toLowerCase();
        if (!filter && this.all) return codes;
        if (filter) {
          codes = codes.filter(
            ({ code, name }) => code.toLowerCase().includes(filter) || name.toLowerCase().includes(filter),
          );
        }
        if (!this.all) {
          codes = codes.filter(({ outdated }) => !outdated);
        }
        return codes;
      },
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
        this.clearErrors();
      },
      openCreatePromo() {
        this.resetPromoForm('promo');
        this.$nextTick(this.$refs.promoModal.show);
      },
      promoDiscount(contract, addsign = false) {
        const rate = this.plans.find(({ promo }) => promo === contract.code);
        if (rate == null) return addsign ? '—' : {};
        const d = { ...rate.price.stdcalc.reduction };
        if (!addsign) return d;
        const { value, mode } = d;
        return `${value}${mode === 'percent' ? '%' : this.currency.symbol}`;
      },
      promoRates(contract) {
        return this.plans
          .filter(({ promo }) => promo === contract.code)
          .map(({ id, text, room }) => {
            const { id: rid, text: rtext } = this.rooms.find(({ pid }) => pid === room);
            return {
              id,
              text,
              rid,
              rtext,
            };
          });
      },
      promoFromChanged(dt) {
        if (dt.isAfter(this.promo.until, 'date')) {
          this.promo.until = moment(dt);
        }
        this.$nextTick(() => {
          this.$refs.promoUntil.$el.focus();
        });
      },
      promoUntilChanged(dt) {
        if (dt.isBefore(this.promo.from, 'date')) {
          this.promo.from = moment(dt);
        }
      },
      resetPromoForm(mode, reset = true) {
        this.$set(this.promo, 'plans', []);
        this.promo = {
          mode,
          name: '',
          code: '',
          discount: {
            value: '',
            mode: 'percent',
          },
          from: moment(),
          until: moment().add(1, 'month'),
          plans: [],
        };
        if (reset && this.$refs.promoForm != null) {
          this.$refs.promoForm.reset();
        }
      },
      editPromo(contract) {
        this.resetPromoForm(contract.mode, false);
        const discount = this.promoDiscount(contract);
        this.promo = {
          ...this.promo,
          ...JSON.parse(JSON.stringify(contract)),
          discount,
        };
        this.$nextTick(() => {
          this.$refs.promoForm.reset();
          this.$refs.promoModal.show();
        });
      },
      async deletePromo(promo) {
        const { id } = this.channel;
        try {
          await this.deleteContract({ id, promo });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async processPromoForm() {
        this.$refs.promoForm.reset();
        const { promo } = this;
        if (promo.outdated) {
          this.$nextTick(this.$refs.promoModal.hide);
          return;
        }
        ['from', 'until'].forEach((k) => {
          const v = promo[k];
          if (moment.isMoment(v)) {
            promo[k] = v.format('YYYY-MM-DD');
          }
        });

        const { id } = this.channel;
        try {
          if (promo.id != null) {
            await this.updateContract({ id, promo });
          } else {
            await this.createContract({ id, promo });
          }
          this.$refs.promoModal.hide();
        } catch (error) {
          if (!(error instanceof ValidationError)) {
            this.$toastr.e(error.message, this.$t('error'));
          }
        }
      },
    },
  };
</script>
