<template>
  <div class="page-descriptions">
    <div class="page-cxl-pol">
      <div ref="title" class="panel-title position-relative w-100 title">
        <p>{{ $t('pages.description.title') }}</p>
      </div>
    </div>
    <b-alert v-if="pmsError" variant="danger" show>
      <h4 class="alert-heading">{{ $t('error') }}</h4>
      <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
    </b-alert>
    <ValidationObserver ref="form">
      <div class="component-descriptions">
        <div class="descriptions-wrapper d-none d-md-block mb-10"
             v-for="(item, index) in items" :key="index">
          <div class="panel position-relative panel-content">
            <!--Description Page (desktop)-->
            <div class="list d-none d-md-block left-edge">
              <div :class="{ 'active-text': item.open }"
                   class="cursor" @click="item.open = !item.open">
                {{ item.title }}
                <icon width="12" height="7" :class="{ active: item.open }"
                      class="icon-angle" stroke-width="2" type="arrow-down"/>
              </div>
              <div class="field-wrapper mt-20" v-show="item.open">
                <lang-selector v-model="item.lang" :valid="item.langsValid" />
                <div class="lang-choice" v-for="code in langCodes" :key="code" v-show="item.lang === code">
                  <ValidatedField
                    type="richtext"
                    v-model="item.langs[code]"
                    :rows="rowLength"
                    :name="`description_${index}_${code}`"
                    :id="`description-${index}-${code}`"
                    :placeholder="$t('pages.description.title')"
                    @input="toggleLangValid(item, $event, code)"
                    :rules="rulesForLang()"
                    :disabled="pending"
                  />
                </div>
                <SubmitButton
                  @click="onSubmit"
                  variant="secondary"
                  class="btn-save"
                  :disabled="pending && !loaded"
                  :loading="pending"
                  inline
                >
                  {{ $t('buttons.save') }}
                </SubmitButton>
              </div>
            </div>
          <!--./end of Descriptions (desktop)-->
          </div>
        </div>
        <!-- Descriptions (mobile) -->
        <div class="descriptions-wrapper" v-for="(item, index) in items" :key="`mobile-${index}`">
          <div :class="[{'reset-shadow' : index === 0}, 'list-item head-line d-md-none page-description' ]">
            <div :class="{ 'active-text': item.open }" class="cursor" @click="item.open = !item.open">
              {{ item.title }}
              <icon width="12" height="7" :class="{ active: item.open }"
                    class="icon-angle" stroke-width="2" type="arrow-down"/>
            </div>
            <div class="field-wrapper mt-20" v-show="item.open">
              <lang-selector v-model="item.lang" :valid="item.langsValid" />
              <div class="lang-choice" v-for="code in langCodes" :key="code" v-show="item.lang === code">
                <ValidatedField
                  type="textarea"
                  v-model="item.langs[code]"
                  :rows="rowLength"
                  :name="`description-${index}-${code}`"
                  :id="`description-mob-${index}-${code}`"
                  :placeholder="$t('pages.description.title')"
                  @input="toggleLangValid(item, $event, code)"
                  rules="max:1000"
                  :disabled="pending"
                />
              </div>
              <SubmitButton
                @click="onSubmit"
                variant="secondary"
                class="btn-save"
                :disabled="pending && !loaded"
                :loading="pending"
                inline
              >
                {{ $t('buttons.save') }}
              </SubmitButton>
            </div>
          </div>
        </div>
      </div>
    </ValidationObserver>
  </div>
</template>

<script>
  import { langCodes } from '@/shared';
  import { mapState, mapActions, mapGetters } from 'vuex';

  export default {
    name: 'Descriptions',
    data: () => ({
      rowLength: 15,
      items: {},
    }),

    async mounted() {
      await this.getDescription();
      this.updateForm();
    },

    computed: {
      ...mapState('description', ['pending', 'pmsError', 'descriptions']),
      ...mapGetters('description', ['loaded']),
      langCodes: () => langCodes,
    },

    methods: {
      ...mapActions('description', ['updateDescription', 'getDescription']),
      isLangValid(name, code) {
        const { langValid } = this;
        return langValid[name] != null && Array.isArray(langValid[name]) && langValid[name].includes(code);
      },
      toggleLangValid(item, val, code) {
        const idx = item.langsValid.indexOf(code);
        if (`${val}`.trim()) {
          if (idx === -1) {
            item.langsValid.push(code);
          }
        } else if (idx !== -1) {
          item.langsValid.splice(idx, 1);
        }
      },
      updateForm() {
        const fields = this.$t('pages.description.fields');
        const data = {};
        fields.forEach(({ name, title }) => {
          data[name] = {
            langs: {
              ...Object.fromEntries(langCodes.map((c) => [c, ''])),
              ...(this.descriptions[name]?.langs || {}),
            },
            title,
            lang: 'en',
            open: false,
            langsValid: [],
          };
          Object.keys(data[name].langs).forEach((code) => {
            this.toggleLangValid(data[name], data[name].langs[code], code);
          });
        });
        this.items = data;
      },
      async onSubmit() {
        this.$refs.form.validate();
        if (this.$refs.form.flags.invalid) return;
        try {
          await this.updateDescription({ descriptions: this.items });
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
      rulesForLang() {
        const rules = {};
        rules.max = 2000;
        return rules;
      },
    },
  };

</script>
