<template>
  <div class="component-facilities">
    <div class="panel position-relative panel-content">
      <div class="facilities-area">
        <p class="head-line"><span>{{ $t('pages.facilities.heading') }}</span><spinner v-if="!loaded && pending" /></p>
        <p class="description-line"><span>{{ $t('pages.facilities.description') }}</span></p>
        <div class="row pt-6" v-if="loaded">
          <div
            class="col-xl-3 col-md-6 col-sm-12"
            v-for="{ id, text } in available"
            :key="`hac-${id}`"
          >
            <check-box :disabled="pending" v-model="selected" :val="id">
              <span class="facility-item" data-toggle="tooltip" :title="text">{{ text }}</span>
            </check-box>
          </div>
        </div>
      </div>
    </div>
    <div class="panel-footer row">
      <div class="col-xl-3 col-lg-4 col-md-6 col-12">
        <b-button class="w-100" variant="secondary" @click="saveFacilities" :disabled="pending || !hasChanges">
          {{ $t('buttons.save') }}
        </b-button>
      </div>
      <div class="col-xl-3 col-lg-4 col-md-6 col-12 d-none d-md-block">
        <b-button class="w-100" variant="outline-primary" @click="resetFacilities" :disabled="pending || !hasChanges">
          {{ $t('pages.facilities.button-revert-changes') }}
        </b-button>
      </div>
    </div>
  </div>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';

  export default {
    name: 'Facilities',
    async created() {
      await this.getFacilities();
      this.resetFacilities();
    },
    data() {
      return {
        selected: [],
      };
    },
    computed: {
      ...mapState('facilities', ['facilities', 'available', 'pending']),
      ...mapGetters('facilities', ['loaded']),
      hasChanges() {
        const selected = [...this.selected];
        const facilities = [...this.facilities];
        if (selected.length !== facilities.length) return true;
        selected.sort();
        facilities.sort();
        return !selected.every((v, k) => facilities[k] === v);
      },
    },
    methods: {
      ...mapActions('facilities', ['updateFacilities', 'getFacilities']),
      resetFacilities() {
        this.selected = [...this.facilities];
      },
      async saveFacilities() {
        try {
          await this.updateFacilities(this.selected);
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          this.$toastr.e(error.message, 'Error');
        }
      },
    },
  };
</script>
