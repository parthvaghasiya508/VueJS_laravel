<template>
  <div class="page-group">
    <div class="">
      <div class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.group.title') }}</p>
      </div>
      <ValidationObserver ref="formGroup" tag="div" v-slot="{ handleSubmit, invalid }">
        <div class="panel panel-content d-none d-md-block">
          <div class="list d-none d-md-block left-edge groupModal">
            <form @submit.prevent.stop="handleSubmit(onSubmit)" novalidate>
              <div class="row">
                <div class="col-12">
                  <label class="text-xs" for="name">{{ $t('pages.groups.modal.field.group.title') }}</label>
                  <ValidatedField
                    :disabled="pending"
                    type="text" id="name" name="name" class="mb-2"
                    v-model.trim="form.name"
                    :placeholder="$t('pages.groups.modal.field.group.placeholder')"
                    rules="required|max:80"
                  />
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <label class="text-xs">{{ $t('pages.groups.modal.field.bdomain.title') }}</label>
                  <ValidatedField
                    :disabled="pending || form.domains_locked"
                    v-model.trim="form.b_domain"
                    :placeholder="$t('pages.groups.modal.field.bdomain.placeholder')"
                    type="text" id="b_domain" name="b_domain" class="mb-2"
                    rules="required|domain|max:80"
                    :error-bag="groupValidationError"
                  />
                </div>
                <div class="col-12">
                  <label class="text-xs">{{ $t('pages.groups.modal.field.edomain.title') }}</label>
                  <ValidatedField
                    :disabled="pending || form.domains_locked"
                    v-model.trim="form.e_domain"
                    :placeholder="$t('pages.groups.modal.field.edomain.placeholder')"
                    type="text" id="e_domain" name="e_domain" class="mb-2"
                    rules="required|domain|max:80"
                    :error-bag="groupValidationError"
                  />
                </div>
              </div>
              <hr class="hrGroup">
              <div class="row">
                <div class="col-12">
                  <label class="text-xs">{{ $t('pages.groups.modal.color.title') }}</label>
                  <ImageSelector v-model="logo" tiny right contain />
                </div>
              </div>
              <div class="row">
                <div class="col-12 wrapperColorPicker">
                  <div v-if="form.style.color_schema.rgba">
                    <p v-html="$t('pages.groups.modal.color.main')"></p>
                    <span
                      id="group-color-picker"
                      tabindex="-1"
                      class="colorPicker"
                      :style="`background: ${rgbaFrom(form.style.color_schema)}`"
                    />
                    <b-popover
                      target="group-color-picker"
                      no-fade
                      triggers="focus"
                      placement="topright"
                      container="group-color-picker"
                    >
                      <chrome-picker :value="form.style.color_schema" @input="changeColorSchema" />
                    </b-popover>
                  </div>

                  <div
                    class="colorText">
                    <p v-html="$t('pages.groups.modal.color.text.title')"></p>

                    <span
                      id="font-color-picker"
                      tabindex="-1"
                      class="colorPicker"
                      :style="`background: ${rgbaFrom(form.style.color_font)}`"
                    />
                    <b-popover
                      target="font-color-picker"
                      no-fade
                      triggers="focus"
                      placement="topright"
                      container="font-color-picker"
                    >
                      <chrome-picker :value="form.style.color_font" @input="changeColorFont" />
                    </b-popover>

                    <!-- <div class="wrapperColor">
                      <div>
                        <span
                          aria-label="white"
                          @click="changeFontColor('white')"
                          class="colorPicker"
                          :style="`background: ${rgbaFrom(defaultTextColors.white)}`"
                        />
                        <radio v-model="textColor" val="white" :disabled="pending">
                          {{ $t('pages.groups.modal.color.text.colorwhite') }}
                        </radio>
                      </div>

                      <div>
                        <span
                          aria-label="black"
                          @click="changeFontColor('black')"
                          class="colorPicker"
                          :style="`background: ${rgbaFrom(defaultTextColors.black)}`"
                        />
                        <radio v-model="textColor" val="black" :disabled="pending">
                          {{ $t('pages.groups.modal.color.text.colordark') }}
                        </radio>
                      </div>
                    </div> -->
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="panel-footer d-none d-md-block">
          <div class="col-12 cell-buttons justify-content-between justify-content-md-start">
            <SubmitButton type="submit" inline :disabled="invalid"
                          :loading="pending" @click="handleSubmit(onSubmit)"
            >{{ $t('buttons.save') }}</SubmitButton>
            <b-btn variant="outline-primary" :disabled="pending" @click="resetForm(true)">
              {{ $t('buttons.revert') }}
            </b-btn>
          </div>
          <div class="col-12 d-md-none cell-buttons justify-content-between justify-content-start">
            <SubmitButton type="submit" :disabled="invalid"
                          :loading="pending" @click="handleSubmit(onSubmit)"
            >{{ $t('buttons.save') }}</SubmitButton>
            <b-btn block variant="outline-primary" :disabled="pending" class="mt-2" @click="resetForm(true)">
              {{ $t('buttons.revert') }}
            </b-btn>
          </div>
        </div>
      </ValidationObserver>
    </div>
  </div>
</template>
<script>
  import { mapActions, mapGetters, mapState } from 'vuex';
  import { ValidationError } from '@/errors';
  import {
    defaultTextColors,
  } from '@/shared';
  import { Chrome } from 'vue-color';

  export default {
    name: 'Group',
    components: {
      'chrome-picker': Chrome,
    },
    data() {
      return {
        form: {
          name: '',
          domain: '',
          style: {
            color_schema: { rgba: null },
            color_font: { rgba: null },
          },
        },
        logo: {},
        textColor: 'white',
      };
    },
    watch: {
    //   user: function watchUser(userData) {
    //     if (userData) {
    //       this.resetForm();
    //     }
    //   },
    },
    async mounted() {
      this.resetForm();
      await this.refreshGroup();
      this.resetForm(true);
    },
    computed: {
      ...mapGetters('user', { group: 'group', groupValidationError: 'validationError' }),
      ...mapState('user', { pending: 'groupUpdatePending' }),
      defaultTextColors: () => defaultTextColors,
    },
    methods: {
      ...mapActions('user', ['refreshGroup', 'updateGroup']),
      rgbaFrom(item) {
        if (item == null || item.rgba == null) return 'none';
        const {
          r, g, b, a,
        } = item.rgba;
        return `rgba(${r}, ${g}, ${b}, ${a})`;
      },
      changeColorSchema(value) {
        if (value == null) return;
        const { rgba } = value;
        this.form.style.color_schema = { rgba };
      },
      changeColorFont(value) {
        if (value == null) return;
        const { rgba } = value;
        this.form.style.color_font = { rgba };
      },
      async onSubmit() {
        try {
          const { upload, remove } = this.logo;

          this.$nextTick(() => this.$refs.formGroup.reset());
          await this.updateGroup({
            ...this.form,
            logo: { upload, remove },
          });
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          if (!(error instanceof ValidationError)) this.$toastr.e(error.message, this.$t('error'));
        }
        // this.$refs.title.scrollIntoView();
      },
      resetForm(resetForm = true) {
        const {
          // eslint-disable-next-line camelcase
          logo, name, b_domain, e_domain, style, domains_locked,
        } = JSON.parse(JSON.stringify(this.group));
        this.form = {
          // eslint-disable-next-line camelcase
          logo, name, b_domain, e_domain, style, domains_locked,
        };
        this.logo = {
          original: this.form.logo || null,
          upload: null,
          remove: false,
        };
        this.textColor = 'white';
        if (resetForm) {
          this.$nextTick(() => {
            this.$refs.formGroup.reset();
          });
        }
      },
    },
  };
</script>
