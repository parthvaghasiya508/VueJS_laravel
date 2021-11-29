<template>
  <div class="page-policies">
    <div class="page-cxl-pol">
      <div class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.policies.title') }}</p>
      </div>
      <b-alert v-if="pmsError" variant="danger" show>
        <h4 class="alert-heading">{{ $t('error') }}</h4>
        <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
      </b-alert>

      <div class="panel position-relative panel-content">
        <!--Cancellation Policies (desktop)-->
        <div class="list d-none d-md-block left-edge">
          <p class="head-line" :class="{ opened: cxlPolOpened }" @click="cxlPolOpened=!cxlPolOpened">
            {{ $t('pages.policies.cancel.title') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </p>
          <div class="policies-table">
            <table class="w-100">
              <thead>
                <tr>
                  <th class="w-id">{{ $t('id') }}</th>
                  <th class="w-name">{{ $t('pages.policies.cancel.headers.name') }}</th>
                  <th class="w-cxl-time">{{ $t('pages.policies.cancel.headers.time') }}</th>
                  <th class="w-cxl-fee">{{ $t('pages.policies.cancel.headers.fee') }}</th>
                  <th class="w-actions">{{ $t('actions') }}</th>
                </tr>
              </thead>

              <tbody v-for="row in cxlPols" :key="row.id">
                <tr class="separator before"></tr>
                <tr>
                  <td>
                    {{ row.id }}
                  </td>
                  <td>
                    <p class="c-name">{{ row.langs.en.name }}</p>
                  </td>
                  <td>
                    <p>{{ cxlTimeDesc(row.cancellationTime) }}</p>
                  </td>
                  <td>
                    <p>{{ cxlFeeDesc(row.cancellationFee) }}</p>
                  </td>
                  <td class="actions">
                    <b-btn class="btn-icon btn-tiny" @click="editCxlPol(row)"
                           :disabled="updatePending || row.protected">
                      <icon width="17" height="17" type="edit"/>
                    </b-btn>
                    <!--
                    <b-btn class="btn-icon btn-tiny" @click="duplicateCxlPol(row)" :disabled="updatePending">
                      <icon width="18" height="18" type="copy"/>
                    </b-btn>
                    -->
                    <b-btn class="btn-icon btn-tiny" @click="deleteCxlPol(row.id)"
                           :disabled="updatePending || row.protected">
                      <icon width="19" height="19" type="delete"/>
                    </b-btn>
                  </td>
                </tr>
                <tr class="separator after"></tr>
              </tbody>
            </table>
          </div>
          <div v-if="loaded">
            <b-btn pill variant="outline-primary" class="add-new-pol"
                   @click="openCxlCreateForm">
              <icon width="10" height="10" type="plus"/>
              {{ $t('pages.policies.cancel.button-add') }}
            </b-btn>
          </div>
        </div>
        <!--./end of Cancellation policies (desktop)-->

        <!--Cancellation Policies (mobile)-->
        <p class="head-line d-md-none" :class="{ opened: cxlPolOpened }" @click="cxlPolOpened=!cxlPolOpened">
          {{ $t('pages.policies.cancel.title') }}
          <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
        </p>
        <div class="d-md-none list-item" v-for="row in cxlPols" :key="row.id">
          <div class="d-flex" >
            <div class="c-1"></div>
            <div class="dots">
              <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right :disabled="updatePending">
                <template #button-content>
                  <icon width="20" height="19" class="label" type="dots-h"/>
                </template>
                <b-dropdown-item @click="editCxlPol(row)"
                                 :disabled="updatePending || row.protected">{{ $t('buttons.edit') }}</b-dropdown-item>
                <!--
                <b-dropdown-item @click="duplicateCxlPol(row)"
                                 :disabled="updatePending">{{ $t('buttons.duplicate') }}</b-dropdown-item>
                -->
                <b-dropdown-item @click="deleteCxlPol(row.id)"
                                 :disabled="updatePending || row.protected">{{ $t('buttons.delete') }}</b-dropdown-item>
              </b-dropdown>
            </div>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('id') }}</p>
            <p>{{ row.id }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.cancel.headers.name') }}</p>
            <p class="c-name">{{ row.langs.en.name }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.cancel.headers.time') }}</p>
            <p>{{ cxlTimeDesc(row.cancellationTime) }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.cancel.headers.fee') }}</p>
            <p>{{ cxlFeeDesc(row.cancellationFee) }}</p>
          </div>
        </div>
        <!--Create Button of Cancellation Policies (mobile)-->
        <div class="d-md-none add-new-pol-block" v-if="loaded">
          <b-btn pill variant="outline-primary" class="add-new-pol" @click="openCxlCreateForm">
            <icon width="10" height="10" type="plus"/>
            {{ $t('pages.policies.cancel.button-add') }}
          </b-btn>
        </div><!--./end of Create Button of Cancellation Policies (mobile)-->
        <!--./end of Cancellation Policies (mobile)-->
      </div>

      <!--Cancellation Policy Modal-->
      <b-modal
        no-close-on-backdrop
        id="cxlPolModal"
        ref="cxlPolModal"
        no-fade
        centered
        static
        size="lg"
        modal-class="form-modal"
        :ok-title="$t(`buttons.${cxlPol.id != null ? 'update' : 'save'}`)"
        ok-variant="primary"
        :cancel-title="$t('buttons.cancel')"
        cancel-variant="outline-primary"
        :ok-disabled="updatePending || !$refs.cxlPolForm || !cancelFormValid || !$refs.cxlPolForm.flags.valid"
        :cancel-disabled="updatePending"
        :no-close-on-esc="updatePending"
        :hide-header-close="updatePending"
        @ok.prevent="processCxlForm"
      >
        <template #modal-header-close>
          <icon width="20" height="20" class="d-none d-md-block" type="times"/>
          <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
        </template>
        <template #modal-title>
          {{ $t(`pages.policies.cancel.modal.title-${cxlPol.id != null ? 'edit' : 'add'}`) }}
        </template>
        <ValidationObserver ref="cxlPolForm" slim>
          <div class="edge" :class="{ active: edgeLang }">
            <!--Cancellation Policy Name-->
            <div class="row row-edit row-cp-name">
              <div class="col cell-edit-label double">
                {{ $t('pages.policies.cancel.modal.name') }}
              </div>
            </div>
            <lang-selector v-model="lang" :valid="langsValid" ref="langSel" />
            <div class="lang-choice" v-for="code in langCodes" :key="code" v-show="lang === code">
              <ValidatedField type="text" name="cp-name" class="cp-name" no-icon
                              v-model="cxlPol.langs[code].name" :disabled="updatePending"
                              @input="toggleLangValid($event, code)"
                              :rules="rulesForLang(code, 'name')"/>
              <ValidatedField type="richtext" name="cp-desc" class="cp-desc"
                              :placeholder="$t('pages.policies.cancel.modal.description')"
                              v-model="cxlPol.langs[code].desc" :disabled="updatePending"
                              :rules="rulesForLang(code, 'desc')"/>
            </div>
            <!--./end of Cancellation Policy Description-->
          </div>
          <div class="edge" :class="{ active: edgeCPForm }">
            <!--Cancellation Policy Time-->
            <div class="row row-edit row-cp-time">
              <div class="col cell-edit-label double">
                {{ $t('pages.policies.cancel.modal.section-time') }}
              </div>
              <div class="col cp-time-wrapper">
                <div class="cp-time-overlay">
                  <div class="cell-edit-field smaller">
                    <ValidatedField type="number" integer purenumber no-icon no-tooltip
                                    name="cp-time" class="cp-time" min="0" max="99"
                                    v-model="cxlPol.cancellationTime.unitMultiplier" :disabled="updatePending"
                                    :rules="rulesFor('maxpmt')"/>
                  </div>
                  <div class="cell-edit-field cp-time-unit">
                    <drop-down
                      id="dd-cxl-time"
                      v-model="cxlTimeUnit"
                      :items="timeUnits"
                      rules="required"
                      :disabled="updatePending || isZeroCxlUnit"
                    />
                  </div>
                </div>
                <div class="cell-edit-field cp-time-drop">
                  <drop-down
                    id="dd-cxl-drop"
                    v-model="cxlDropTime"
                    :items="dropTimes"
                    rules="required"
                    :disabled="updatePending || isZeroCxlUnit"
                  />
                </div>
              </div>
            </div>
            <!--./end of Cancellation Policy Time-->

            <!--Cancellation Policy Fee-->
            <div class="cp-fee-title">{{ $t('pages.policies.cancel.modal.section-fee') }}</div>
            <div class="row row-edit row-cp-fee">
              <div class="col cell-edit-label">
                {{ $t('pages.policies.cancel.modal.fee-relation') }}
              </div>
              <div class="cell-edit-field cp-fee-relation">
                <drop-down
                  id="dd-cxl-fee-relation"
                  v-model="cxlPol.cancellationFee.basisType"
                  :items="basisTypes"
                  rules="required"
                  :disabled="updatePending"
                />
              </div>
            </div>
            <div class="row row-edit row-cp-fee">
              <div class="col cell-edit-label">
                {{ $t('pages.policies.cancel.modal.fee') }}
              </div>
              <div class="col cell-edit-field bigger">
                <amount-percent v-if="cxlPol.cancellationFee.basisType === 'FullStay'"
                                :price="cxlPol.cancellationFee.value"
                                v-model="cxlFee"
                                :required="true"
                                :accept-zero="true"
                                :disabled="updatePending"
                />
                <ValidatedField v-else
                                type="text" name="cp-fee-multiplier" class="cp-fee-multiplier" no-icon
                                v-model="cxlPol.cancellationFee.nmbrOfNights" :disabled="updatePending"
                                rules="required"/>
              </div>
            </div>
            <!--./end of Cancellation Policy Fee-->
          </div>
        </ValidationObserver>
      </b-modal>
      <!--./end of Cancellation Policy Modal-->
    </div>

    <div class="page-bgarant">
      <div class="panel position-relative panel-content">
        <!--Booking Guarantees (desktop)-->
        <div class="list d-none d-md-block left-edge">
          <p class="head-line" :class="{ opened: bgarantOpened }" @click="bgarantOpened=!bgarantOpened">
            {{ $t('pages.policies.bgarant.title') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </p>
          <div class="policies-table" v-if="loaded">
            <table class="w-100">
              <thead>
                <tr>
                  <th class="w-type">{{ $t('pages.policies.bgarant.headers.type') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody v-for="row in bgarants" :key="row.id">
                <tr class="separator before"></tr>
                <tr>
                  <td>
                    <p>{{ row.title }}</p>
                  </td>
                  <td></td>
                </tr>
                <tr class="separator after"></tr>
              </tbody>
            </table>
          </div>
        </div>
        <!--./end of Booking Guarantees (desktop)-->

        <!--Booking Guarantees (mobile)-->
        <p class="head-line d-md-none" :class="{ opened: bgarantOpened }" @click="bgarantOpened=!bgarantOpened">
          {{ $t('pages.policies.bgarant.title') }}
          <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
        </p>
        <div class="d-md-none list-item" v-for="row in bgarants" :key="row.id">
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.bgarant.headers.type') }}</p>
            <p>{{ row.title }}</p>
          </div>
        </div>
        <!--./end of Booking Guarantees (mobile)-->
      </div>
    </div>

    <div class="page-pymt-pol">
      <div class="panel position-relative panel-content">
        <!--Payment Policies (desktop)-->
        <div class="list d-none d-md-block left-edge">
          <p class="head-line" :class="{ opened: pymtPolOpened }" @click="pymtPolOpened=!pymtPolOpened">
            {{ $t('pages.policies.payment.title') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </p>
          <div class="policies-table">
            <table class="w-100">
              <thead>
                <tr>
                  <th class="w-id">{{ $t('id') }}</th>
                  <th class="w-name">{{ $t('pages.policies.payment.headers.name') }}</th>
                  <th class="w-pymt-time">{{ $t('pages.policies.payment.headers.time') }}</th>
                  <th class="w-pymt-type">{{ $t('pages.policies.payment.headers.type') }}</th>
                  <th class="w-pymt-fee">{{ $t('pages.policies.payment.headers.fee') }}</th>
                  <th class="w-actions">{{ $t('actions') }}</th>
                </tr>
              </thead>

              <tbody v-for="row in pymtPols" :key="row.id">
                <tr class="separator before"></tr>
                <tr>
                  <td>
                    {{ row.id }}
                  </td>
                  <td>
                    <p class="c-name">{{ row.name }}</p>
                  </td>
                  <td>
                    <p>{{ pyTimeDesc(row.paymentTime) }}</p>
                  </td>
                  <td>
                    <p>{{ pyTypeDesc(row.paymentType) }}</p>
                  </td>
                  <td>
                    <p>{{ pyFeeDesc(row.paymentFee) }}</p>
                  </td>
                  <td class="actions">
                    <b-btn class="btn-icon btn-tiny" @click="editPymtPol(row)" :disabled="updatePending">
                      <icon width="17" height="17" type="edit"/>
                    </b-btn>
                    <!--
                    <b-btn class="btn-icon btn-tiny" @click="duplicatePymtPol(row)" :disabled="updatePending">
                      <icon width="18" height="18" type="copy"/>
                    </b-btn>
                    -->
                    <b-btn class="btn-icon btn-tiny" @click="deletePymtPol(row.id)"
                           :disabled="updatePending || row.protected">
                      <icon width="19" height="19" type="delete"/>
                    </b-btn>
                  </td>
                </tr>
                <tr class="separator after"></tr>
              </tbody>
            </table>
          </div>
          <div v-if="loaded">
            <b-btn pill variant="outline-primary" class="add-new-pol"
                   @click="openPymtCreateForm">
              <icon width="10" height="10" type="plus"/>
              {{ $t('pages.policies.payment.button-add') }}
            </b-btn>
          </div>
        </div>
        <!--./end of Payment policies (desktop)-->

        <!--Payment Policies (mobile)-->
        <p class="head-line d-md-none" :class="{ opened: pymtPolOpened }" @click="pymtPolOpened=!pymtPolOpened">
          {{ $t('pages.policies.payment.title') }}
          <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
        </p>
        <div class="d-md-none list-item" v-for="row in pymtPols" :key="row.id">
          <div class="d-flex" >
            <div class="c-1"></div>
            <div class="dots">
              <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right :disabled="updatePending">
                <template #button-content>
                  <icon width="20" height="19" class="label" type="dots-h"/>
                </template>
                <b-dropdown-item @click="editPymtPol(row)"
                                 :disabled="updatePending">{{ $t('buttons.edit') }}</b-dropdown-item>
                <!--
                <b-dropdown-item @click="duplicatePymtPol(row)"
                                 :disabled="updatePending">{{ $t('buttons.duplicate') }}</b-dropdown-item>
                -->
                <b-dropdown-item @click="deletePymtPol(row.id)"
                                 :disabled="updatePending || row.protected">{{ $t('buttons.delete') }}</b-dropdown-item>
              </b-dropdown>
            </div>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('id') }}</p>
            <p>{{ row.id }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.payment.headers.name') }}</p>
            <p class="c-name">{{row.name}}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.payment.headers.time') }}</p>
            <p>{{ pyTimeDesc(row.paymentTime) }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.payment.headers.type') }}</p>
            <p>{{ pyTypeDesc(row.paymentType) }}</p>
          </div>
          <div class="d-flex line">
            <p class="label">{{ $t('pages.policies.payment.headers.fee') }}</p>
            <p>{{ pyFeeDesc(row.paymentFee) }}</p>
          </div>
        </div>
        <!--Create Button of Payment Policies (mobile)-->
        <div class="d-md-none add-new-pol-block" v-if="loaded">
          <b-btn pill variant="outline-primary" class="add-new-pol" @click="openPymtCreateForm">
            <icon width="10" height="10" type="plus"/>
            {{ $t('pages.policies.payment.button-add') }}
          </b-btn>
        </div><!--./end of Create Button of Payment Policies (mobile)-->
        <!--./end of Payment Policies (mobile)-->
      </div>

      <!--Payment Policy Modal-->
      <b-modal
        no-close-on-backdrop
        id="pymtPolModal"
        ref="pymtPolModal"
        no-fade
        centered
        static
        size="lg"
        modal-class="form-modal"
        :ok-title="$t(`buttons.${pymtPol.id != null ? 'update' : 'save'}`)"
        ok-variant="primary"
        :cancel-title="$t('buttons.cancel')"
        cancel-variant="outline-primary"
        :ok-disabled="updatePending || !$refs.pymtPolForm || !$refs.pymtPolForm.flags.valid"
        :cancel-disabled="updatePending"
        :no-close-on-esc="updatePending"
        :hide-header-close="updatePending"
        @ok.prevent="processPymtForm"
      >
        <template #modal-header-close>
          <icon width="20" height="20" class="d-none d-md-block" type="times"/>
          <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
        </template>
        <template #modal-title>
          {{ $t(`pages.policies.payment.modal.title-${pymtPol.id != null ? 'edit' : 'add'}`) }}
        </template>
        <ValidationObserver ref="pymtPolForm" slim>
          <div class="edge active">
            <!--Payment Policy Name-->
            <ValidatedField type="text" name="pp-name" class="pp-name" no-icon
                            :placeholder="$t('pages.policies.payment.modal.name')"
                            v-model="pymtPol.name" :disabled="updatePending"
                            rules="required"/>
            <!--./end of Payment Policy Name-->

            <!--Payment Policy Description-->
            <ValidatedField type="textarea" name="pp-desc" class="pp-desc"
                            :placeholder="$t('pages.policies.payment.modal.description')"
                            v-model="pymtPol.desc" :disabled="updatePending"
                            rules="required"/>
            <!--./end of Payment Policy Description-->

            <!--Payment Policy Type-->
            <div class="row row-edit row-pp-type">
              <div class="col cell-edit-label">
                {{ $t('pages.policies.payment.modal.section-type') }}
              </div>
              <div class="col cell-edit-field">
                <drop-down
                  id="dd-pymt-type"
                  v-model="pymtPol.paymentType"
                  :items="paymentTypes"
                  rules="required"
                  :disabled="updatePending"
                />
              </div>
            </div>
            <!--./end of Payment Policy Type-->

            <!--Payment Policy Fee-->
            <div class="row row-edit row-pp-fee">
              <div class="col cell-edit-label smaller">
                {{ $t('pages.policies.payment.modal.section-fee') }}
              </div>
              <div class="col cell-edit-field bigger">
                <amount-percent
                  :price="pymtPol.paymentFee.value"
                  v-model="pymtPol.paymentFee"
                  required
                  accept-zero
                  :disabled="updatePending"
                />
              </div>
            </div>
            <!--./end of Payment Policy Fee-->

            <!--Payment Policy Time-->
            <div class="row row-edit row-pp-time">
              <div class="col cell-edit-label">
                {{ $t('pages.policies.payment.modal.section-time') }}
              </div>
              <div class="col pp-time-wrapper">
                <div class="cell-edit-field smaller">
                  <ValidatedField type="number" integer purenumber no-icon no-tooltip
                                  name="pp-time" class="pp-time" min="0" max="99"
                                  v-model="pymtPol.paymentTime.unitMultiplier" :disabled="updatePending"
                                  :rules="rulesFor('maxpmt')"/>
                </div>
                <div class="cell-edit-field pp-time-unit">
                  <drop-down
                    id="dd-pymt-time"
                    v-model="pymtTimeUnit"
                    :items="timeUnits"
                    rules="required"
                    :disabled="updatePending || isZeroPymtUnit"
                  />
                </div>
              </div>
            </div>
            <!--./end of Payment Policy Time-->
          </div>
        </ValidationObserver>
      </b-modal>
      <!--./end of Payment Policy Modal-->
    </div>
  </div>
</template>
<script>
  import { mapState, mapGetters, mapActions } from 'vuex';
  import {
    timeUnits, dropTimes,
    basisTypes, langCodes,
  } from '@/shared';

  export default {
    name: 'Policies',
    data() {
      return {
        lang: 'en',
        langsValid: [],
        cxlPol: {},
        pymtPol: {},
        cxlPolOpened: false,
        bgarantOpened: false,
        pymtPolOpened: false,
      };
    },
    async created() {
      this.resetCxlPolModal();
      this.resetPymtPolModal();
      await this.fetchData(true);
    },
    computed: {
      ...mapGetters('policies', ['loaded', 'cxlPols', 'pymtPols', 'bgarants', 'pmts']),
      ...mapState('policies', [
        'error', 'pmsError', 'pending',
        'updatePending',
      ]),
      ...mapGetters('user', ['currency']),
      edgeLang() {
        return this.langsValid.includes('en');
      },
      edgeCPForm() {
        return this.cxlPol.cancellationTime.unitMultiplier
          && (this.cxlPol.cancellationFee.basisType === 'FullStay' ? this.cxlFee.value : this.cxlPol.cancellationFee.nmbrOfNights);
      },
      timeUnits() {
        return timeUnits.map((id) => ({ id, text: this.$tc(`pages.policies.time-units.${id}`, 0) }));
      },
      dropTimes() {
        return dropTimes.map((id) => ({ id, text: this.$t(`pages.policies.cancel.drop-times.${id}`).capitalizeFirst() }));
      },
      basisTypes() {
        return basisTypes.map((id) => ({ id, text: this.$t(`pages.policies.cancel.basis-types.${id}`).capitalizeFirst() }));
      },
      paymentTypes() {
        return this.pmts.map((pmt) => ({
          ...pmt,
          text: this.$t(`pages.policies.payment.types.${pmt.text}`),
        }));
      },
      cancelFormValid() {
        return this.edgeLang && this.edgeCPForm;
      },
      langCodes: () => langCodes,
      isZeroCxlUnit() {
        return parseInt(this.cxlPol.cancellationTime.unitMultiplier, 10) === 0;
      },
      cxlTimeUnit: {
        get() {
          return !this.isZeroCxlUnit ? this.cxlPol.cancellationTime.timeUnit : false;
        },
        set(tu) {
          this.cxlPol.cancellationTime.timeUnit = tu;
        },
      },
      cxlDropTime: {
        get() {
          return !this.isZeroCxlUnit ? this.cxlPol.cancellationTime.dropTime : false;
        },
        set(dt) {
          this.cxlPol.cancellationTime.dropTime = dt;
        },
      },
      cxlFee: {
        get() {
          return this.cxlPol.cancellationFee;
        },
        set(fee) {
          this.cxlPol.cancellationFee.value = fee.value;
          this.cxlPol.cancellationFee.mode = fee.mode;
        },
      },
      isZeroPymtUnit() {
        return parseInt(this.pymtPol.paymentTime.unitMultiplier, 10) === 0;
      },
      pymtTimeUnit: {
        get() {
          return !this.isZeroPymtUnit ? this.pymtPol.paymentTime.timeUnit : false;
        },
        set(tu) {
          this.pymtPol.paymentTime.timeUnit = tu;
        },
      },
    },
    watch: {
      'cxlPol.cancellationFee.basisType': function watchCxlFee(basisType) {
        if (basisType === 'FullStay') {
          this.cxlPol.cancellationFee.nmbrOfNights = 0;
        }
        if (basisType === 'Nights') {
          this.cxlPol.cancellationFee.value = 0;
          this.cxlPol.cancellationFee.mode = 'amount';
        }
      },
    },
    methods: {
      ...mapActions('policies', [
        'fetchData',
        'createCxlPol', 'updateCxlPol', 'duplicateCxlPol', 'deleteCxlPol',
        'createPymtPol', 'updatePymtPol', 'duplicatePymtPol', 'deletePymtPol',
      ]),
      toggleLangValid(val, code) {
        const idx = this.langsValid.indexOf(code);
        if (`${val}`.trim()) {
          if (idx === -1) {
            this.langsValid.push(code);
          }
        } else if (idx !== -1) {
          this.langsValid.splice(idx, 1);
        }
      },
      rulesForLang(lang, type) {
        const rules = {};
        if (lang === 'en' && type === 'name') {
          rules.required = true;
        }
        rules.max = type === 'name' ? 200 : 500;
        return rules;
      },
      cxlTimeDesc(ctu) {
        if (!ctu.unitMultiplier) {
          return this.$t('pages.policies.cancel.cancel-time-none');
        }
        const what = this.$tc(`pages.policies.time-units.${ctu.timeUnit}`, ctu.unitMultiplier);
        const when = this.$t(`pages.policies.cancel.drop-times.${ctu.dropTime}`);
        return this.$t('pages.policies.cancel.cancel-time', { what, when });
      },
      cxlFeeDesc(cf) {
        const fs = cf.basisType === 'FullStay';
        // eslint-disable-next-line no-nested-ternary
        if (fs
          ? (cf.mode === 'amount' ? cf.value < 0.01 : cf.value < 1)
          : cf.nmbrOfNights < 1) {
          return this.$t('pages.policies.cancel.cancel-fee-none');
        }
        const percents = cf.mode !== 'amount';
        const { symbol } = this.currency;
        return this.$tc(`pages.policies.cancel.cancel-fee.${cf.basisType}`,
                        fs ? (1 + percents) : cf.nmbrOfNights, { amount: cf.value, percents: cf.value, symbol });
      },
      resetCxlPolModal() {
        this.lang = 'en';
        this.langsValid = [];
        this.cxlPol = {
          langs: Object.fromEntries(langCodes.map((c) => [c, {}])),
          cancellationFee: {
            value: 0,
            mode: 'amount',
            nmbrOfNights: 0,
            basisType: 'FullStay',
          },
          cancellationTime: {
            unitMultiplier: 0,
            timeUnit: 'hour',
            dropTime: 'BeforeArrival',
          },
        };
        this.$nextTick(() => {
          this.$refs.langSel.resetScroller();
        });
        if (this.$refs.cxlPolForm != null) {
          this.$refs.cxlPolForm.reset();
        }
      },
      openCxlCreateForm() {
        this.resetCxlPolModal();
        this.$nextTick(this.$refs.cxlPolModal.show);
      },
      editCxlPol(cxlPol) {
        this.cxlPol = {};
        this.resetCxlPolModal();
        this.cxlPol = {
          ...this.cxlPol,
          ...JSON.parse(JSON.stringify(cxlPol)), // deep clone without bindings
        };
        Object.keys(this.cxlPol.langs).forEach((code) => {
          this.toggleLangValid(this.cxlPol.langs[code].name, code);
        });
        this.$nextTick(this.$refs.cxlPolModal.show);
      },
      async processCxlForm() {
        const cxlPol = { ...this.cxlPol };
        // console.log(cxlPol);
        try {
          if (cxlPol.id != null) {
            await this.updateCxlPol(cxlPol);
          } else {
            await this.createCxlPol(cxlPol);
          }
          this.$refs.cxlPolModal.hide();
        } catch (error) {
          // eslint-disable-next-line no-console
          console.error(error.message);
        }
      },
      pyTimeDesc(ptu) {
        if (!ptu.unitMultiplier) {
          return this.$t('pages.policies.payment.payment-time-none');
        }
        return this.$tc(`pages.policies.time-units.${ptu.timeUnit}`, ptu.unitMultiplier);
      },
      pyTypeDesc(code) {
        const pmt = this.pmts.find((bg) => (bg.id - code) === 0);
        return pmt != null ? this.$t(`pages.policies.payment.types.${pmt.text}`) : '';
      },
      pyFeeDesc(pyf) {
        const amt = pyf.mode === 'amount';
        if (pyf.value < (amt ? 0.01 : 1)) {
          return this.$t('pages.policies.payment.payment-fee-none');
        }
        const { symbol } = this.currency;
        return this.$tc('pages.policies.payment.payment-fee',
                        +amt, { amount: pyf.value, percents: pyf.value, symbol });
      },
      resetPymtPolModal() {
        this.pymtPol = {
          name: '',
          desc: '',
          paymentType: 5,
          paymentFee: {
            value: '',
            mode: 'amount',
          },
          paymentTime: {
            unitMultiplier: 0,
            timeUnit: 'hour',
          },
        };
        if (this.$refs.pymtPolForm != null) {
          this.$refs.pymtPolForm.reset();
        }
      },
      openPymtCreateForm() {
        this.resetPymtPolModal();
        this.$nextTick(this.$refs.pymtPolModal.show);
      },
      editPymtPol(pymtPol) {
        this.pymtPol = {};
        this.resetPymtPolModal();
        this.pymtPol = {
          ...this.pymtPol,
          ...JSON.parse(JSON.stringify(pymtPol)), // deep clone without bindings
        };
        this.$nextTick(this.$refs.pymtPolModal.show);
      },
      async processPymtForm() {
        const pymtPol = { ...this.pymtPol };
        // console.log(pymtPol);
        try {
          if (pymtPol.id != null) {
            await this.updatePymtPol(pymtPol);
          } else {
            await this.createPymtPol(pymtPol);
          }
          this.$refs.pymtPolModal.hide();
        } catch (error) {
          // eslint-disable-next-line no-console
          console.error(error.message);
        }
      },
      rulesFor(field) {
        const rules = {
          required: true,
        };
        switch (field) {
          case 'maxpmt':
            rules.between = { min: 0, max: 99 };
            rules.numeric = true;
            break;
          default:
            break;
        }
        return rules;
      },
    },
  };
</script>
