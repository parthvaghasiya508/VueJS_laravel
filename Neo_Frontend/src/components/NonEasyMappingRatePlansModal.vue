<template>
  <b-modal
    no-close-on-backdrop
    id="NonEasyMappingRatePlansModal"
    ref="modal"
    no-fade
    centered
    static
    size="lg"
    modal-class="form-modal"
    :ok-title="okTitle"
    ok-variant="primary"
    :cancel-title="$t('buttons.cancel')"
    cancel-variant="outline-primary"
    :ok-disabled="pending || !formValid"
    :cancel-disabled="pending"
    :no-close-on-esc="pending"
    :hide-header-close="pending"
    @ok.prevent="emit"
  >
    <template #modal-header-close>
      <icon width="20" height="20" class="d-none d-md-block" type="times"/>
      <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
    </template>
    <template #modal-title>
      {{ modalTitle }}
    </template>
    <ValidationObserver ref="form" slim>
      <div class="edge" :class="{ active: edgeData }">
        <form>
          <h3 :content="$t('pages.channels.connect.property-data')"></h3>
          <div class="row row-edit" v-for="field in fields" :key="field.key">
            <div class="col cell-edit-label double">
              {{ field.title }}
            </div>
            <div class="col cell-edit-value cell-channel-field">
              <ValidatedField :type="typeFor(field.subtype)" no-icon no-tooltip
                              :name="field.key" group-class="mb-0"
                              v-model="values[field.key]"
                              :disabled="pending"
                              :rules="rulesFor(field.subtype)"/>
            </div>
          </div>
        </form>
      </div>
    </ValidationObserver>
  </b-modal>
</template>

<script>
  export default {
    name: 'PushChannelInfoModal',
    props: {
      pending: Boolean,
    },
    data: () => ({
      id: null,
      edit: false,
      values: {},
      fields: null,
      name: null,
    }),
    computed: {
      okTitle() {
        return this.$t(`buttons.${this.edit ? 'update' : 'save'}`);
      },
      modalTitle() {
        return this.$t(`pages.channels.title-${this.edit ? 'update' : 'activate'}`, { name: this.name });
      },
      timeUnits() {
        return ['d', 'w', 'm', 'y'].map((id) => ({
          id,
          text: this.$tc(`pages.channels.connect.time-units.${id}`, this.values.period.number),
        }));
      },
      isZeroUnit() {
        const n = parseInt(this.values.period.number, 10);
        // eslint-disable-next-line no-restricted-globals
        return n === 0 || isNaN(n);
      },
      edgeData() {
        if (this.values == null || this.fields == null) return false;
        return this.fields.every(({ key }) => (this.values[key] != null && this.values[key] !== ''));
      },
      formValid() {
        return this.edgeData && this.$refs.form.flags.valid;
      },
    },
    methods: {
      show(id, fields, name, values = null) {
        this.edit = values != null;
        this.id = id;
        this.fields = fields;
        this.name = name;
        const v = { ...values };
        this.values = v;
        this.$nextTick(this.$refs.modal.show);
      },
      hide() {
        this.$refs.modal.hide();
      },
      rulesFor(field) {
        const rules = {
          // required: true,
        };
        switch (field) {
          default:
            rules.required = true;
            break;
        }
        return rules;
      },
      typeFor(subtype) {
        switch (subtype) {
          case 'text':
          case 'password':
            return subtype;
          default:
            break;
        }
        return '';
      },
      emit() {
        const { id, values } = this;
        this.$emit('ok', { id, values });
      },
    },
  };
</script>
