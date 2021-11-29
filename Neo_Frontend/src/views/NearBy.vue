<template>
  <div class="page-nearby">
    <div class="">
      <div ref="title" class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.nearby.title') }}</p>
      </div>
      <div class="panel position-relative panel-content mb-30">
        <div class="list left-edge">
          <p class="head-line" :class="{ opened: trnsPortOpened }" @click="trnsPortOpened=!trnsPortOpened">
            {{ $t('pages.nearby.transportation') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
            <spinner v-if="!loaded && pending"/>
          </p>
          <div v-if="loaded && !pending">
            <ValidationObserver ref="form">
              <div class="mb-40">
                <div class="row">
                  <div class="col-sm-12 item-head-line">
                    {{ $t('pages.nearby.next-airport') }}
                  </div>
                </div>
                <div class="row" v-for="(airport, idx) in airports_modified" :key="`tab-${idx}`">
                  <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.airport-code') }}</p>
                    <ValidatedField
                      required
                      rules="required|min:1|max:15"
                      type="text"
                      :name="`airport_code_${idx}`"
                      :id="`airport-code-${idx}`"
                      v-model="airport.code"
                      @input="isChanged('airports', idx)"
                      :placeholder="$t('pages.nearby.airport-code')"
                    />
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-5 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.distance') }}</p>
                    <amount-percent
                      :no-icon="false"
                      :no-tolltip="false"
                      simple
                      symbol=" "
                      rules="required|min:1|max:5"
                      v-model="airport.distance"
                      required
                      @input="isChanged('airports', idx)"
                    />
                  </div>
                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.units') }}</p>
                    <drop-down
                      @input="isChanged('airports', idx)"
                      v-model="airport.unit"
                      :items="unitsList(airport.distance)"
                    />
                  </div>
                  <b-btn class="btn-icon btn-tiny btn-item-delete d-none d-md-block"
                         @click="removeTransportData('airports', idx)">
                    <icon width="18" height="18" type="delete" color="red"/>
                  </b-btn>
                  <div class="col-12 mb-4 d-md-none">
                    <b-btn pill block variant="danger" class="justify-content-center"
                           @click="removeTransportData('airports', idx)">
                      <icon width="16" height="16" type="delete"/>
                      <span>{{ $t('buttons.remove') }}</span>
                    </b-btn>
                  </div>
                </div>
                <b-btn pill variant="outline-primary" class="add-more" :disabled="!loaded"
                       @click="addTransportData('airports')">
                  <icon width="10" height="11" type="plus"/>
                  <span v-if="airports_modified.length">{{ $t('buttons.add-more') }}</span>
                  <span v-else>{{ $t('buttons.add-new') }}</span>
                </b-btn>
              </div>

              <div class="mb-40">
                <div class="row">
                  <div class="col-sm-12 item-head-line">
                    {{ $t('pages.nearby.next-train-station') }}
                  </div>
                </div>
                <div class="row" v-for="(train, idx) in trains_modified" :key="`tab-${idx}`">
                  <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.next-train-station-code') }}</p>
                    <ValidatedField
                      required
                      rules="required|min:1|max:15"
                      type="text"
                      :name="`trains_code_${idx}`"
                      :id="`trains-code-${idx}`"
                      v-model="train.code"
                      @input="isChanged('trains', idx)"
                      :placeholder="$t('pages.nearby.next-train-station-code')"
                    />
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-5 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.distance') }}</p>
                    <amount-percent
                      :no-icon="false"
                      :no-tolltip="false"
                      simple
                      symbol=" "
                      rules="required|min:1|max:5"
                      v-model="train.distance"
                      required
                      @input="isChanged('trains', idx)"
                    />
                  </div>
                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.units') }}</p>
                    <drop-down
                      @input="isChanged('trains', idx)"
                      v-model="train.unit"
                      :items="unitsList(train.distance)"
                    />
                  </div>
                  <b-btn class="btn-icon btn-tiny btn-item-delete d-none d-md-block"
                         @click="removeTransportData('trains', idx)">
                    <icon width="18" height="18" type="delete" color="red"/>
                  </b-btn>
                  <div class="col-12 mb-4 d-md-none">
                    <b-btn pill block variant="danger" class="justify-content-center"
                           @click="removeTransportData('trains', idx)">
                      <icon width="16" height="16" type="delete"/>
                      <span>{{ $t('buttons.remove') }}</span>
                    </b-btn>
                  </div>
                </div>
                <b-btn pill variant="outline-primary" class="add-more" :disabled="!loaded"
                       @click="addTransportData('trains')">
                  <icon width="10" height="11" type="plus"/>
                  <span v-if="trains_modified.length">{{ $t('buttons.add-more') }}</span>
                  <span v-else>{{ $t('buttons.add-new') }}</span>
                </b-btn>
              </div>

              <div class="mb-40">
                <div class="row">
                  <div class="col-sm-12 item-head-line">
                    {{ $t('pages.nearby.next-mortorway-exit') }}
                  </div>
                </div>
                <div class="row" v-for="(motor, idx) in motors_modified" :key="`tab-${idx}`">
                  <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.motorway-code') }}</p>
                    <ValidatedField
                      required
                      rules="required|min:1|max:15"
                      type="text"
                      :name="`motors_code_${idx}`"
                      :id="`motors-code-${idx}`"
                      v-model="motor.code"
                      @input="isChanged('motors', idx)"
                      :placeholder="$t('pages.nearby.motorway-code')"
                    />
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-5 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.distance') }}</p>
                    <amount-percent
                      :no-icon="false"
                      :no-tolltip="false"
                      simple
                      symbol=" "
                      rules="required|min:1|max:5"
                      v-model="motor.distance"
                      required
                      @input="isChanged('motors', idx)"
                    />
                  </div>
                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.units') }}</p>
                    <drop-down
                      @input="isChanged('motors', idx)"
                      v-model="motor.unit"
                      :items="unitsList(motor.distance)"
                    />
                  </div>
                  <b-btn class="btn-icon btn-tiny btn-item-delete d-none d-md-block"
                         @click="removeTransportData('motors', idx)">
                    <icon width="18" height="18" type="delete" color="red"/>
                  </b-btn>
                  <div class="col-12 mb-4 d-md-none">
                    <b-btn pill block variant="danger" class="justify-content-center"
                           @click="removeTransportData('motors', idx)">
                      <icon width="16" height="16" type="delete"/>
                      <span>{{ $t('buttons.remove') }}</span>
                    </b-btn>
                  </div>
                </div>
                <b-btn pill variant="outline-primary" class="add-more" :disabled="!loaded"
                       @click="addTransportData('motors')">
                  <icon width="10" height="11" type="plus"/>
                  <span v-if="motors_modified.length">{{ $t('buttons.add-more') }}</span>
                  <span v-else>{{ $t('buttons.add-new') }}</span>
                </b-btn>
              </div>

              <div class="mb-40">
                <div class="row">
                  <div class="col-sm-12 item-head-line">
                    {{ $t('pages.nearby.public-transportation') }}
                  </div>
                </div>
                <div class="row" v-for="(publics, idx) in publics_modified" :key="`tab-${idx}`">
                  <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.type') }}</p>
                    <drop-down
                      v-model="publics.code"
                      :items="transportation_types"
                      @input="isChanged('publics', idx)"
                    />
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-5 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.distance') }}</p>
                    <amount-percent
                      :no-icon="false"
                      :no-tolltip="false"
                      simple
                      symbol=" "
                      rules="required|min:1|max:5"
                      v-model="publics.distance"
                      required
                      @input="isChanged('publics', idx)"
                    />
                  </div>
                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.units') }}</p>
                    <drop-down
                      v-model="publics.unit"
                      :items="unitsList(publics.distance)"
                      @input="isChanged('publics', idx)"
                    />
                  </div>
                  <b-btn class="btn-icon btn-tiny btn-item-delete d-none d-md-block"
                         @click="removeTransportData('publics', idx)">
                    <icon width="18" height="18" type="delete" color="red"/>
                  </b-btn>
                  <div class="col-12 mb-4 d-md-none">
                    <b-btn pill block variant="danger" class="justify-content-center"
                           @click="removeTransportData('publics', idx)">
                      <icon width="16" height="16" type="delete"/>
                      <span>{{ $t('buttons.remove') }}</span>
                    </b-btn>
                  </div>
                </div>
                <b-btn pill variant="outline-primary" class="add-more" :disabled="!loaded"
                       @click="addTransportData('publics')">
                  <icon width="10" height="11" type="plus"/>
                  <span v-if="publics_modified.length">{{ $t('buttons.add-more') }}</span>
                  <span v-else>{{ $t('buttons.add-new') }}</span>
                </b-btn>
              </div>

              <div class="mb-40">
                <div class="row">
                  <div class="col-sm-12 item-head-line">
                    {{ $t('pages.nearby.city-center') }}
                  </div>
                </div>
                <div class="row" v-for="(center, idx) in city_centers_modified" :key="`tab-${idx}`">
                  <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.city-center-code') }}</p>
                    <ValidatedField
                      required
                      rules="required|min:1|max:15"
                      type="text"
                      :name="`city_centers_code_${idx}`"
                      :id="`city-centers-code-${idx}`"
                      v-model="center.code"
                      @input="isChanged('city_centers', idx)"
                      :placeholder="$t('pages.nearby.city-center-code')"
                    />
                  </div>
                  <div class="col-xl-2 col-lg-2 col-md-5 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.distance') }}</p>
                    <amount-percent
                      :no-icon="false"
                      :no-tolltip="false"
                      simple
                      symbol=" "
                      rules="required|min:1|max:5"
                      v-model="center.distance"
                      required
                      @input="isChanged('city_centers', idx)"
                    />
                  </div>
                  <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 input-padding-right">
                    <p class="head-line-nearby">{{ $t('pages.nearby.units') }}</p>
                    <drop-down
                      @input="isChanged('city_centers', idx)"
                      v-model="center.unit"
                      :items="unitsList(center.distance)"
                    />
                  </div>
                  <b-btn class="btn-icon btn-tiny btn-item-delete d-none d-md-block"
                         @click="removeTransportData('city_centers', idx)">
                    <icon width="18" height="18" type="delete" color="red"/>
                  </b-btn>
                  <div class="col-12 mb-4 d-md-none">
                    <b-btn pill block variant="danger" class="justify-content-center"
                           @click="removeTransportData('city_centers', idx)">
                      <icon width="16" height="16" type="delete"/>
                      <span>{{ $t('buttons.remove') }}</span>
                    </b-btn>
                  </div>
                </div>
                <b-btn pill variant="outline-primary" class="add-more" :disabled="!loaded"
                       @click="addTransportData('city_centers')">
                  <icon width="10" height="11" type="plus"/>
                  <span v-if="city_centers_modified.length">{{ $t('buttons.add-more') }}</span>
                  <span v-else>{{ $t('buttons.add-new') }}</span>
                </b-btn>
              </div>
            </ValidationObserver>
          </div>
        </div>
      </div>
      <div class="panel position-relative panel-content mb-30">
        <div class="list left-edge">
          <p class="head-line" :class="{ opened: shoppingDiningOpened }"
             @click="shoppingDiningOpened=!shoppingDiningOpened">
            {{ $t('pages.nearby.shopping-and-dining') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </p>
        </div>
      </div>
      <div class="panel position-relative panel-content">
        <div class="list left-edge">
          <p class="head-line" :class="{ opened: cultureHistoryOpened }"
             @click="cultureHistoryOpened=!cultureHistoryOpened">
            {{ $t('pages.nearby.culture-and-history') }}
            <icon width="12" height="7" stroke-width="2" type="arrow-down"/>
          </p>
        </div>
      </div>
      <div class="panel-footer row">
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
          <SubmitButton
            @click="onSubmit"
            :loading="!loaded || pending"
            :disabled="!hasChanges && !wasSomethingChanged || pending || !loaded"
          >
            {{ $t('buttons.save') }}
          </SubmitButton>
        </div>
        <div class="col-xl-3 col-lg-4 col-md-6 col-12 d-none d-md-block">
          <b-button class="w-100" variant="outline-primary" @click="revertChanges"
                    :disabled="!hasChanges && !wasSomethingChanged || pending || !loaded">
            {{ $t('pages.nearby.button-revert-changes') }}
          </b-button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
  import { mapState, mapActions, mapGetters } from 'vuex';
  import { distanceUnits } from '@/shared';

  export default {
    name: 'Nearby',
    async created() {
      await this.getNearby();
      this.revertChanges();
    },
    computed: {
      ...mapState('nearby', ['airports', 'trains', 'motors', 'publics', 'city_centers', 'pending']),
      ...mapGetters('nearby', ['loaded']),
      wasSomethingChanged() {
        return ['airports', 'motors', 'trains', 'publics', 'city_centers'].some((k) => this[`${k}_modified`].some(({ changed }) => changed));
      },
    },
    data() {
      return {
        hasChanges: false,
        trnsPortOpened: false,
        shoppingDiningOpened: false,
        cultureHistoryOpened: false,
        airports_modified: [],
        trains_modified: [],
        motors_modified: [],
        publics_modified: [],
        city_centers_modified: [],
        transportation_types: [
          {
            id: 'bus',
            text: this.$t('pages.nearby.bus-station'),
          },
          {
            id: 'ferry',
            text: this.$t('pages.nearby.ferry-station'),
          },
          {
            id: 'metro',
            text: this.$t('pages.nearby.metro-station'),
          },
          // {
          //   id: 'trolley',
          //   text: this.$t('pages.nearby.trolley-station'),
          // },
        ],
      };
    },
    methods: {
      ...mapActions('nearby', ['updateNearby', 'getNearby']),
      unitsList(number) {
        return distanceUnits.map((unit) => ({
          id: unit,
          text: this.$tc(`distance-units.${unit}`, number),
        }));
      },
      objectsEqual(o1, o2) {
        return Object.keys(o1).length === Object.keys(o2).length
          && Object.keys(o1)
            .every((p) => o1[p] === o2[p]);
      },
      addTransportData(type) {
        this.hasChanges = true;
        const newData = {
          code: '',
          distance: 1,
          unit: 'km',
          changed: true,
        };
        this[`${type}_modified`].push(newData);
      },
      removeTransportData(type, idx) {
        this.hasChanges = true;
        this[`${type}_modified`].splice(idx, 1);
      },
      revertChanges() {
        this.airports_modified = this.airports?.length ? [...this.airports].map((el) => ({
          changed: false, ...el,
        })) : [];
        this.trains_modified = this.trains?.length ? [...this.trains].map((el) => ({
          changed: false, ...el,
        })) : [];
        this.motors_modified = this.motors?.length ? [...this.motors].map((el) => ({
          changed: false, ...el,
        })) : [];
        this.publics_modified = this.publics?.length ? [...this.publics].map((el) => ({
          changed: false, ...el,
        })) : [];
        this.city_centers_modified = this.city_centers?.length ? [...this.city_centers].map((el) => ({
          changed: false, ...el,
        })) : [];
        this.hasChanges = false;
      },
      isChanged(type, idx) {
        this.$nextTick(() => {
          const newObject = { ...(this[`${type}_modified`][idx]) };
          if (newObject.id == null) return;
          const oldObject = this[type].find(({ id }) => id === newObject.id);
          delete newObject.changed;
          this[`${type}_modified`][idx].changed = !this.objectsEqual(newObject, oldObject);
        });
      },
      async onSubmit() {
        this.$refs.form.validate();
        if (this.$refs.form.flags.invalid) return;

        try {
          await this.updateNearby({
            airports: this.airports_modified,
            trains: this.trains_modified,
            motors: this.motors_modified,
            publics: this.publics_modified,
            city_centers: this.city_centers_modified,
          });
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
    },
  };
</script>
