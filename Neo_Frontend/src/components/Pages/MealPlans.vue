<template>
  <div class="component-mealplans">
    <b-alert v-if="pmsError" variant="danger" show>
      <h4 class="alert-heading">{{ $t('error') }}</h4>
      <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
    </b-alert>
    <b-modal
      no-close-on-backdrop
      id="mealModal"
      ref="mealModal"
      no-fade
      centered
      static
      size="lg"
      modal-class="form-modal"
      :ok-title="$t(`buttons.${meal.id != null ? 'update' : 'save'}`)"
      ok-variant="primary"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      :ok-disabled="updatePending || !$refs.mealForm || !formValid || !$refs.mealForm.flags.valid"
      :cancel-disabled="updatePending"
      :no-close-on-esc="updatePending"
      :hide-header-close="updatePending"
      @show="modalScroll"
      @hidden="modalScroll"
      @ok.prevent="processForm"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        {{ modalTitle }}
      </template>
      <ValidationObserver ref="mealForm" slim>
        <div class="edge" :class="{ active: edgeMealLang }">
          <h3 :content="$t('pages.mealplans.modal.section-meal-name')"></h3>
          <lang-selector v-model="lang" :valid="langsValid" ref="langSel"/>
          <div class="link-figure">
            <icon width="22" height="22" class="link-figure-icon" type="link"/>
            <div class="lang-choice" v-for="code in langCodes" :key="code" v-show="lang === code">
              <ValidatedField type="text" :name="`lang-${code}`" class="plan-name" no-icon
                              v-model="meal.langs[code].name" :disabled="updatePending"
                              @input="toggleLangValid($event, code)"
                              :rules="rulesForLang(code, 'name')"/>
              <b-textarea no-resize class="plan-desc" :placeholder="$t('pages.mealplans.modal.description')"
                          no-icon :rules="rulesForLang(code, 'desc')" :name="`lang-${code}-desc`"
                          v-model="meal.langs[code].desc" :disabled="updatePending"/>
            </div>
            <h5>{{ $t('pages.mealplans.modal.section-meal-type') }}</h5>
          </div>
          <drop-down
            id="dd-typecodes"
            v-model="meal.typecode"
            :items="typecodes"
            :disabled="updatePending || meal.protected"
          />
        </div>
        <div class="edge edge-amount" :class="{ active: edgeMealAmount }">
          <h3 :content="$t('pages.mealplans.modal.section-basic-rate')"></h3>
          <div class="row row-edit">
            <div class="col cell-edit-field">
              <amount-percent simple accept-zero v-model="meal.price" :disabled="updatePending"/>
            </div>
          </div>
          <h6 :class="{ opened: pricesOpened }" @click="pricesOpened=!pricesOpened">
            {{ $t('date.price-periods') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </h6>
          <div>
            <div class="row row-validity mx-0" v-for="(price, idx) in meal.prices" :key="`pr-${idx}`">
              <div class="row row-travel d-none d-md-flex">
                <div class="col cell-travel">
                  <div class="cell-travel-title">
                    <p v-for="key in travelOpts" :key="`tt-${key}`"
                       v-text="key?$t(`pages.mealplans.modal.travel-opts.${key}`):''"></p>
                  </div>
                  <div class="cell-travel-alls">
                    <p v-for="key in travelOpts" :key="`ta-${key}`">
                      <template v-if="!key">{{ $t('pages.rateplans.modal.days-all') }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelAll(key, price.id, 'prices')"
                                 @input="toggleTravelAll(key, $event, price.id, 'prices')"/>
                    </p>
                  </div>
                  <div class="cell-travel-wd" v-for="(wd, idxx) in weekdays" :key="`twd-${wd}`">
                    <p v-for="key in travelOpts" :key="`td-${key}`">
                      <template v-if="!key">{{ $t('pages.mealplans.modal.period-days')[idxx] }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelDay(key, wd,  price.id, 'prices')"
                                 @input="toggleTravelDay(key, wd, $event, price.id, 'prices')"/>
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-12 cell-val-from">
                <label class="text-xs" :for="`date-block-from-${idx}`">{{ $t('date.from') }}</label>
                <date-picker :id="`date-block-from-${idx}`" v-model="price.from" :min-date="today" grow="md-up"
                             :disabled="updatePending" :format="dateFormat"
                             @input="validityFromChanged($event, idx,'prices' )"/>
              </div>
              <div class="col-12 cell-val-until">
                <label class="text-xs" :for="`date-block-until-${idx}`">{{ $t('date.until') }}</label>
                <date-picker id="date-val-until" v-model="price.until" :min-date="today" grow="md-up"
                             ref="validityUntil" position="left-md-right"
                             :disabled="updatePending" :format="dateFormat"
                             @input="validityUntilChanged($event, idx, 'prices')"/>
              </div>
              <div class="col-12 cell-edit-field cell-price-time">
                <label class="text-xs" :for="`date-block-price-${idx}`">{{ $t('date.price') }}</label>
                <amount-percent simple accept-zero v-model="price.price" :disabled="updatePending"
                                :id="`date-block-price-${idx}`"/>
              </div>
              <div class="col cell-block-delete position-static">
                <label class="text-xs invisible">_</label>
                <b-btn class="btn-icon btn-tiny" @click="removeValidity(idx, 'prices')">
                  <icon width="18" height="18" type="delete"/>
                </b-btn>
              </div>
            </div>
            <div class="row row-blockout mx-0">
              <div class="col">
                <b-btn pill variant="outline-primary" class="add-new-price" @click="addPricePeriod">
                  <icon width="10" height="10" type="plus"/>
                  {{ $t('date.button-add-price-period') }}
                </b-btn>
              </div>
            </div>
          </div>
        </div>
        <div class="edge active">
          <h3 :content="$t('date.customize-validity')"></h3>
          <h6 :class="{ opened: validityOpened }" @click="validityOpened=!validityOpened">
            {{ $t('date.validity-periods') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </h6>
          <div>
            <div class="row row-blockout" v-if="!meal.validity.length">
              <div class="col text-center mt-3 mb-0 py-0 font-weight-bold">
                {{ $t('pages.mealplans.modal.unlimited-validity') }}
              </div>
            </div>
            <div class="row row-validity position-relative mx-0"
                 v-for="(validity, idx) in meal.validity" :key="`vl-${idx}`">
              <div class="row row-travel d-none d-md-flex">
                <div class="col cell-travel">
                  <div class="cell-travel-title">
                    <p v-for="key in travelOpts" :key="`tt-${key}`"
                       v-text="key?$t(`pages.mealplans.modal.travel-opts.${key}`):''"></p>
                  </div>
                  <div class="cell-travel-alls">
                    <p v-for="key in travelOpts" :key="`ta-${key}`">
                      <template v-if="!key">{{ $t('pages.rateplans.modal.days-all') }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelAll(key, validity.id, 'validity')"
                                 @input="toggleTravelAll(key, $event, validity.id, 'validity')"/>
                    </p>
                  </div>
                  <div class="cell-travel-wd" v-for="(wd, idxx) in weekdays" :key="`twd-${wd}`">
                    <p v-for="key in travelOpts" :key="`td-${key}`">
                      <template v-if="!key">{{ $t('pages.mealplans.modal.period-days')[idxx] }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelDay(key, wd,  validity.id, 'validity')"
                                 @input="toggleTravelDay(key, wd, $event, validity.id, 'validity')"/>
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-12 cell-val-from">
                <label class="text-xs" :for="`date-val-from-${idx}`">{{ $t('date.from') }}</label>
                <date-picker :id="`date-val-from-${idx}`" v-model="validity.from" :min-date="today" grow="md-up"
                             :disabled="updatePending" :format="dateFormat"
                             @input="validityFromChanged($event, idx, validity.id, 'validity')"/>
              </div>
              <div class="col-12 cell-val-until">
                <label class="text-xs" :for="`date-val-until-${idx}`">{{ $t('date.until') }}</label>
                <date-picker :id="`date-val-until-${idx}`"  v-model="validity.until" :min-date="today" grow="md-up"
                             ref="validityUntil" position="left-md-right"
                             :disabled="updatePending" :format="dateFormat"
                             @input="validityUntilChanged($event, idx, 'validity')"/>
              </div>
              <div class="col-12 cell-block-delete">
                <label class="text-xs invisible d-none d-md-inline-block">_</label>
                <b-btn class="btn-icon btn-tiny" @click="removeValidity(idx, 'validity')">
                  <icon width="18" height="18" type="delete"/>
                </b-btn>
              </div>
            </div>
            <div class="row row-blockout">
              <div class="col">
                <b-btn pill variant="outline-primary" class="add-new-validity" @click="addValidityPeriod">
                  <icon width="10" height="10" type="plus"/>
                  {{ $t('date.button-add-validity') }}
                </b-btn>
              </div>
            </div>
          </div>
          <h6 class="mt-3" :class="{ opened: blockoutsOpened }" @click="blockoutsOpened=!blockoutsOpened">
            {{ $t('date.blockout-periods') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </h6>
          <div class="danger">
            <div class="row row-blockout" v-for="(blockout, idx) in meal.blockouts" :key="`bl-${idx}`">
              <div class="row row-travel d-none d-md-flex">
                <div class="col cell-travel">
                  <div class="cell-travel-title">
                    <p v-for="key in travelOpts" :key="`tt-${key}`"
                       v-text="key?$t(`pages.mealplans.modal.travel-opts.${key}`):''"></p>
                  </div>
                  <div class="cell-travel-alls">
                    <p v-for="key in travelOpts" :key="`ta-${key}`">
                      <template v-if="!key">{{ $t('pages.rateplans.modal.days-all') }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelAll(key, blockout.id, 'blockouts')"
                                 @input="toggleTravelAll(key, $event, blockout.id, 'blockouts')"/>
                    </p>
                  </div>
                  <div class="cell-travel-wd" v-for="(wd, idx) in weekdays" :key="`twd-${wd}`">
                    <p v-for="key in travelOpts" :key="`td-${key}`">
                      <template v-if="!key">{{ $t('pages.mealplans.modal.period-days')[idx] }}</template>
                      <check-box v-else empty :disabled="updatePending"
                                 :value="isTravelDay(key, wd, blockout.id, 'blockouts')"
                                 @input="toggleTravelDay(key, wd, $event, blockout.id, 'blockouts')"/>
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-12 cell-block-from">
                <label class="text-xs" :for="`date-block-from-${idx}`">{{ $t('date.from') }}</label>
                <date-picker :id="`date-block-from-${idx}`" v-model="blockout.from" :min-date="today"
                             grow="up" :disabled="updatePending" :format="dateFormat"
                             @input="validityFromChanged($event, idx,'blockouts')"/>
              </div>
              <div class="col-12 cell-block-until">
                <label class="text-xs" :for="`date-block-until-${idx}`">{{ $t('date.until') }}</label>
                <date-picker :id="`date-block-until-${idx}`" v-model="blockout.until" :min-date="today"
                             grow="up" ref="blockUntil" position="left-md-right"
                             :disabled="updatePending" :format="dateFormat"
                             @input="validityUntilChanged($event, idx, 'blockouts')"/>
              </div>
              <div class="col-12 cell-block-delete">
                <b-btn class="btn-icon btn-tiny" @click="removeValidity(idx, 'blockouts')">
                  <icon width="18" height="18" type="delete"/>
                </b-btn>
              </div>
            </div>
            <div class="row row-blockout">
              <div class="col">
                <b-btn pill variant="outline-danger" class="add-new-blockout" @click="addBlockout">
                  <icon width="10" height="11" type="plus"/>
                  {{ $t('date.button-add-blockout') }}
                </b-btn>
              </div>
            </div>
          </div>
        </div>
      </ValidationObserver>
    </b-modal>

    <div class="panel position-relative panel-content">
      <div class="list d-none d-md-block left-edge">
        <p class="head-line"><span>{{ $t('pages.mealplans.heading') }}</span><spinner v-if="!loaded" /></p>
        <div class="plans-table" v-if="loaded">
          <table class="w-100">
            <thead>
              <tr>
                <th class="w-id" :class="active === 1 ? 'active' : ''" @click="integerSort(); activate(1)">
                  <div>
                    {{ $t('id') }}
                    <div>
                      <icon width="12" height="12" type="arrow-up"/>
                      <icon width="12" height="12" type="arrow-down"/>
                    </div>
                  </div>
                </th>
                <th class="w-name" :class="active === 2 ? 'active' : ''" @click="stringSort(); activate(2)">
                  <div>
                    {{ $t('pages.mealplans.headers.name') }}
                    <div>
                      <icon width="12" height="12" type="arrow-up"/>
                      <icon width="12" height="12" type="arrow-down"/>
                    </div>
                  </div>
                </th>
                <th class="w-name">
                  {{ $t('pages.mealplans.headers.type') }}
                </th>
                <th class="w-until">
                  {{ $t('pages.roomtypes.headers.validuntil') }}
                </th>
                <th class="w-actions">{{ $t('actions') }}</th>
              </tr>
            </thead>

            <tbody v-if="noMeals">
              <tr>
                <td colspan="6" class="w-empty">{{ $t('pages.mealplans.no-meals') }}</td>
              </tr>
            </tbody>
            <tbody v-for="row in mealplans" :key="row.id">
              <tr class="separator before"></tr>
              <tr>
                <td>
                  {{ row.id }}
                </td>
                <td>
                  <p class="text-name">{{ row.langs.en.name }}</p>
                </td>
                <td>
                  <p>{{ mealtype(row.typecode) }}</p>
                </td>
                <td>
                  <p v-for="(values, index) in row.validity" :key="index">{{ untilDateFormat(values.until) }}</p>
                </td>
                <td class="actions">
                  <b-btn class="btn-icon btn-tiny" @click="editMeal(row)" :disabled="updatePending">
                    <icon width="17" height="17" type="edit"/>
                  </b-btn>
                  <b-btn class="btn-icon btn-tiny" @click="duplicateMeal(row.id)" :disabled="updatePending">
                    <icon width="18" height="18" type="copy"/>
                  </b-btn>
                  <b-btn class="btn-icon btn-tiny" @click="processDeleteMeal(row.id)"
                         :disabled="updatePending || row.protected || row.deletable">
                    <icon width="19" height="19" type="delete"/>
                  </b-btn>
                </td>
              </tr>
              <tr class="separator after"></tr>
            </tbody>
          </table>
        </div>
        <div v-if="loaded">
          <b-btn pill variant="outline-primary" class="add-new-plan"
                 @click="openCreateForm">
            <icon width="10" height="11" type="plus"/>
            {{ $t('pages.mealplans.button-add') }}
          </b-btn>
        </div>
      </div>

      <p class="head-line d-md-none"><span>{{ $t('pages.mealplans.heading') }}</span><spinner v-if="!loaded" /></p>
      <div class="d-md-none list-item" v-if="noMeals">
        <div class="w-empty">{{ $t('pages.mealplans.no-meals') }}</div>
      </div>
      <div class="d-md-none list-item" v-for="row in mealplans" :key="row.id">
        <div class="d-flex">
          <div class="c-1">
            <p class="label">{{ $t('pages.mealplans.headers.name') }}</p>
            <p>{{ row.langs.en.name }}</p>
          </div>
          <div class="dots">
            <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right :disabled="updatePending">
              <template #button-content>
                <icon width="20" height="19" class="label" type="dots-h"/>
              </template>
              <b-dropdown-item @click="duplicateMeal(row.id)">{{ $t('buttons.duplicate') }}</b-dropdown-item>
              <b-dropdown-item @click="editMeal(row)">{{ $t('buttons.edit') }}</b-dropdown-item>
              <b-dropdown-item @click="processDeleteMeal(row.id)"
                               :disabled="row.protected || row.deletable">{{ $t('buttons.delete') }}</b-dropdown-item>
            </b-dropdown>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('id') }}</p>
            <p>{{ row.id }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.mealplans.headers.type') }}</p>
            <p>{{ mealtype(row.typecode) }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="d-md-none add-new-plan-block" v-if="loaded">
      <b-btn pill variant="outline-primary" class="add-new-plan" @click="openCreateForm">
        <icon width="10" height="10" type="plus"/>
        {{ $t('pages.mealplans.button-add') }}
      </b-btn>
    </div>
  </div>
</template>

<script>
  import moment from 'moment';
  import { mapState, mapGetters, mapActions } from 'vuex';
  import { langCodes, weekdays } from '@/shared';

  const travelOpts = ['', 'days'];

  export default {
    name: 'MealPlans',
    props: {
      payload: {
        validator: (v) => ['object', 'undefined'].includes(typeof v),
        default: null,
      },
    },
    data: () => ({
      meal: {},
      mealplan: [],
      lang: 'en',
      langsValid: [],
      pricesOpened: false,
      validityOpened: false,
      blockoutsOpened: false,
      today: moment(),
      dateFormat: null,
      active: 1,
    }),
    async created() {
      this.resetMealModal();
      await this.fetchData(true);
      this.mealplan = Object.values(this.mealplans);
      // eslint-disable-next-line camelcase
      const { date_format: dateFormat } = this.settings;
      this.dateFormat = dateFormat;
      // document.querySelector('.add-new-plan').click();
    },
    computed: {
      ...mapGetters('mealplans', ['loaded', 'noMeals', 'mealplans', 'typecodes']),
      ...mapGetters('user', ['settings']),
      ...mapState('mealplans', ['error', 'pmsError', 'pending', 'updatePending']),
      langCodes: () => langCodes,
      weekdays: () => weekdays,
      travelOpts: () => travelOpts,
      travelOptsFiltered: () => travelOpts.filter((key) => !!key),
      edgeMealLang() {
        return this.langsValid.includes('en') && !!this.meal.typecode;
      },
      edgeMealAmount() {
        return this.meal.price !== '';
      },
      formValid() {
        return this.edgeMealLang && this.edgeMealAmount;
      },
      modalTitle() {
        if (this.meal.id == null) {
          return this.$t('pages.mealplans.modal.title-add');
        }
        const { id, langs: { en: { name } } } = this.meal;
        return this.$t('pages.mealplans.modal.title-edit', { id, name });
      },
    },
    methods: {
      ...mapActions('mealplans', ['fetchData', 'createMeal', 'updateMeal', 'duplicateMeal', 'deleteMeal']),
      mealtype(type) {
        return (this.typecodes.find(({ id }) => id === type) || { text: '' }).text;
      },
      activate(int) {
        this.active = int;
      },
      integerSort() {
        this.mealplan.sort((a, b) => ((this.order) ? (a.id - b.id) : (b.id - a.id)));
        this.order = !this.order;
      },
      stringSort() {
        this.mealplan.sort((a, b) => {
          const nameA = a.langs.en.name.toUpperCase();
          const nameB = b.langs.en.name.toUpperCase();
          if (nameA < nameB) {
            return (this.order) ? -1 : 1;
          }
          if (nameA > nameB) {
            return (this.order) ? 1 : -1;
          }

          return 0;
        });
        this.order = !this.order;
      },
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
      },
      resetMealModal() {
        this.lang = 'en';
        this.langsValid = [];
        this.occupancyOpened = false;
        this.blockoutsOpened = false;
        this.meal = {
          langs: Object.fromEntries(langCodes.map((c) => [c, {}])),
          price: '',
          typecode: '',
          validity: [],
          prices: [],
          blockouts: [],
        };
        this.$nextTick(() => {
          this.$refs.langSel.resetScroller();
        });
        if (this.$refs.mealForm != null) {
          this.$refs.mealForm.reset();
        }
      },
      normalizeLangs() {
        langCodes.forEach((c) => {
          if (this.meal.langs[c] == null) {
            this.meal.langs[c] = {};
          }
        });
      },
      openCreateForm() {
        this.resetMealModal();
        this.$nextTick(this.$refs.mealModal.show);
      },
      editMeal(meal) {
        this.meal = {};
        this.resetMealModal();
        this.meal = {
          ...this.meal,
          ...JSON.parse(JSON.stringify(meal)), // deep clone without bindings
        };
        this.normalizeLangs();
        this.pricesOpened = this.meal.prices.length > 0;
        this.validityOpened = this.meal.validity.length > 0;
        this.blockoutsOpened = this.meal.blockouts.length > 0;
        Object.keys(this.meal.langs).forEach((code) => {
          this.toggleLangValid(this.meal.langs[code].name, code);
        });
        this.$nextTick(this.$refs.mealModal.show);
      },
      async processDeleteMeal(id) {
        try {
          await this.deleteMeal(id);
          this.showSuccessMessage('deleted');
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async processForm() {
        const meal = { ...this.meal };
        if (this.payload != null) {
          Object.keys(this.payload).forEach((k) => {
            meal[k] = this.payload[k];
          });
        }
        ['validity', 'blockouts', 'prices'].forEach((t) => {
          (meal[t] || []).forEach((b) => {
            ['from', 'until'].forEach((k) => {
              const v = b[k];
              if (moment.isMoment(v)) {
                // eslint-disable-next-line no-param-reassign
                b[k] = v.format('YYYY-MM-DD');
              }
            });
          });
        });
        try {
          if (meal.id != null) {
            await this.updateMeal(meal);
          } else {
            await this.createMeal(meal);
          }
          this.showSuccessMessage('saved');
          this.$refs.mealModal.hide();
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      showSuccessMessage(type) {
        const title = type === 'saved'
          ? this.$t('pages.mealplans.alert-saved')
          : this.$t('pages.mealplans.alert-deleted');
        this.$toastr.s({
          title,
          msg: '',
          timeout: 3000,
          progressbar: false,
        });
      },
      toggleLangValid(val, code) {
        const idx = this.langsValid.indexOf(code);
        if (val != null && `${val}`.trim()) {
          if (idx === -1) {
            this.langsValid.push(code);
          }
        } else if (idx !== -1) {
          this.langsValid.splice(idx, 1);
        }
      },
      validityFromChanged(dt, idx, type) {
        const items = this.meal[type];
        if (dt.isAfter(items[idx].until, 'date')) {
          items[idx].until = moment(dt);
        }
        this.$nextTick(() => {
          this.$refs.validityUntil[idx].$el.focus();
        });
      },
      validityUntilChanged(dt, idx, type) {
        const items = this.meal[type];
        if (dt.isBefore(items[idx].from, 'date')) {
          items[idx].from = moment(dt);
        }
      },
      removeValidity(idx, type) {
        const items = this.meal[type];
        items.splice(idx, 1);
      },
      addBlockout() {
        this.meal.blockouts.push({
          days: [...weekdays],
          from: moment(),
          until: moment(),
        });
      },
      addValidityPeriod() {
        this.meal.validity.push({
          days: [...weekdays],
          from: moment(),
          until: moment(),
        });
      },
      addPricePeriod() {
        this.meal.prices.push({
          days: [...weekdays],
          from: moment(),
          until: moment(),
          price: parseFloat(this.meal.price || 0).toFixed(2),
        });
      },
      rulesForLang(lang, type) {
        const rules = {};
        if (lang === 'en' && type === 'name') {
          rules.required = true;
        }
        rules.max = type === 'name' ? 200 : 500;
        return rules;
      },
      isTravelAll(key, idx, type) {
        const items = this.meal[type];
        const periodIndex = items.findIndex(({ id }) => id === idx);
        return items[periodIndex][key].length === weekdays.length;
      },
      toggleTravelAll(key, on, idx, type) {
        const items = this.meal[type];
        const periodIndex = items.findIndex(({ id }) => id === idx);
        items[periodIndex][key] = on ? [...weekdays] : [];
      },
      isTravelDay(key, wd, idx, type) {
        const items = this.meal[type];
        const periodIndex = items.findIndex(({ id }) => id === idx);
        return items[periodIndex][key].includes(wd);
      },
      toggleTravelDay(key, wd, on, idxx, type) {
        let idx;
        let validityIndex;
        const items = this.meal[type];
        Object.keys(items).forEach((i) => {
          const item = items[i];
          if (item.id === idxx) {
            idx = item[key].indexOf(wd);
            validityIndex = i;
          }
        });
        if (on) {
          if (idx < 0) {
            items[validityIndex][key].push(wd);
          }
        } else if (idx >= 0) {
          items[validityIndex][key].splice(idx, 1);
        }
      },
      untilDateFormat(date) {
        return moment(date).format('DD MMM YYYY');
      },
    },
  };
</script>
