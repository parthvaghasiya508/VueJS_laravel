<template>
  <ValidationProvider slim :rules="rules" #default="{ errors, passed, dirty }" :vid="name" ref="provider" :mode="mode">
    <b-form-group class="position-relative outline-none" :class="[groupClass, {'input-no-icon': noIcon}]">
      <b-form-input v-if="['text','email'].indexOf(type) !== -1"
                    :type="type"
                    :placeholder="placeholder"
                    :name="name"
                    :id="id"
                    :autocomplete="autocomplete"
                    v-focus:[autofocus]
                    :inputmode="type==='text'?'text':'email'"
                    :value="value"
                    @input="$emit('input', $event)"
                    @blur="$emit('blur', $event)"
                    :state="inputState(errors, passed, dirty)"
                    :disabled="disabled"
                    :readonly="readonly"
      />
      <b-form-input v-if="['number'].indexOf(type) !== -1"
                    type="text"
                    pattern="[-+0-9.,]*"
                    :inputmode="integer?'numeric':'decimal'"
                    :placeholder="placeholder"
                    :name="name"
                    :id="id"
                    :autocomplete="autocomplete"
                    :min="min"
                    :max="max"
                    number
                    v-focus:[autofocus]
                    :value="value"
                    @input="$emit('input', transformNumber($event))"
                    :formatter="transformNumber"
                    :state="inputState(errors, passed, dirty)"
                    :disabled="disabled"
      />
      <b-form-input v-if="type==='password'"
                    type="password"
                    :name="name"
                    :id="id"
                    v-password-generator:[autogenerator]
                    :placeholder="placeholder"
                    v-password-toggler:[visibility]
                    :autocomplete="autocomplete"
                    v-focus:[autofocus]
                    :value="value"
                    @input="$emit('input', $event)"
                    :state="inputState(errors, passed, dirty)"
                    :disabled="disabled"
      />
      <b-form-textarea v-if="type==='textarea'" no-resize
                       :placeholder="placeholder"
                       :name="name"
                       :id="id"
                       :autocomplete="autocomplete"
                       v-focus:[autofocus]
                       :value="value"
                       @input="$emit('input', $event)"
                       :state="inputState(errors, passed, dirty)"
                       :disabled="disabled"
                       :rows="rows"
      />
      <rich-text-editor v-if="type==='richtext'"
                        :placeholder="placeholder"
                        :name="name"
                        :id="id"
                        :autocomplete="autocomplete"
                        v-focus:[autofocus]
                        :value="value"
                        @input="$emit('input', $event)"
                        :disabled="disabled"
                        :rows="rows"
                        :rules="rules"
      />
      <tel-input v-if="type==='tel'"
                 :placeholder="placeholder"
                 :name="name"
                 :id="id"
                 :value="value"
                 :national-mode="nationalMode"
                 @input="$emit('input', $event)"
                 @valid="telValid = $event"
                 @error="telError = $event"
                 :state="inputState(errors, passed&&telValid, dirty)"
                 :disabled="disabled"
      />
      <slim-select v-if="type==='select'"
                   :placeholder="placeholder"
                   :name="name"
                   :id="id"
                   :value="value"
                   :track-by="trackBy"
                   :label-by="labelBy"
                   :disabled="disabled"
                   :state="inputState(errors, passed, dirty)"
                   :options="options"
                   :searchable="searchable"
                   @input="$emit('input', $event)"
      />
      <check-box v-if="type==='checkbox'"
                 :name="name"
                 :id="id"
                 :value="value"
                 :disabled="disabled"
                 :state="inputState(errors, passed, dirty)"
                 @input="$emit('input', $event)"
      >
        <slot/>
      </check-box>
      <b-form-invalid-feedback
        tooltip v-if="!noTooltip"
        :class="{ 'd-block': forceShowError(inputState(errors, passed&&telValid, dirty)) }">
        {{ inputError(errors, dirty) }}
      </b-form-invalid-feedback>
    </b-form-group>
  </ValidationProvider>
</template>

<script>
  import ValidationError from '@/errors/ValidationError';

  export default {
    name: 'ValidatedField',
    inheritAttrs: false,
    data() {
      return {
        telValid: false,
        telError: null,
      };
    },
    props: {
      mode: {
        type: String,
        required: false,
        default: 'aggressive',
        validator: (prop) => prop == null || ['aggressive', 'lazy', 'eager', 'passive'].includes(prop),
      },
      value: {
        required: true,
      },
      type: {
        type: String,
        default: 'text',
        validator: (type) => [
          'text', 'tel', 'email', 'textarea',
          'number', 'password', 'checkbox',
          'select', 'custom', 'richtext',
        ].indexOf(type) !== -1,
      },
      min: [Number, String],
      max: [Number, String],
      purenumber: {
        type: Boolean,
        default: false,
      },
      integer: {
        type: Boolean,
        default: false,
      },
      placeholder: {
        type: String,
        default: '',
      },
      autocomplete: {
        type: String,
        default: 'new-password', // workaround to disable Chrome's aggressive autocomplete
      },
      autofocus: {
        type: Boolean,
        default: false,
      },
      autovisible: {
        type: Boolean,
        default: false,
      },
      autogenerator: {
        type: Boolean,
        default: false,
      },
      rules: {
        type: [String, Object],
        required: true,
      },
      id: {
        type: String,
        required: false,
      },
      name: {
        type: String,
        required: true,
      },
      options: {
        validator: (prop) => prop === null || Array.isArray(prop),
      },
      searchable: {
        type: Boolean,
        default: false,
      },
      trackBy: {
        type: String,
        default: 'id',
      },
      labelBy: {
        type: String,
        default: 'text',
      },
      local: {
        type: Boolean,
        default: false,
      },
      groupClass: {
        type: [Object, String],
      },
      errorBag: {
        type: ValidationError,
        default: null,
      },
      disabled: Boolean,
      readonly: Boolean,
      noIcon: Boolean,
      noTooltip: Boolean,
      noValidate: Boolean,
      rows: {
        type: Number,
        default: 6,
      },
      nationalMode: {
        type: Boolean,
        default: false,
      },
    },
    mounted() {
      this.$on('focus', () => {
        const input = this.$el.querySelector('input');
        if (input != null) input.focus();
      });
      this.$on('reset', () => this.$refs.provider.reset());
    },
    beforeDestroy() {
      this.$off(['focus', 'reset']);
    },
    computed: {
      visibility() {
        return this.autovisible ? 'visible' : 'invisible';
      },
    },
    methods: {
      transformNumber(number) {
        let n = `${number}`.replace(/[^-+.,0-9]*/g, '').replace(',', '.');
        if (this.purenumber) n = n.replace(/[-+]*/g, '');
        n = n.replace(/^(\d*\.\d*)\D+/, '$1');
        if (this.integer) n = n.replace(/\..*/g, '');
        return n;
      },
      forceShowError(state) {
        return this.type === 'tel' ? (state === false) : false;
      },
      inputState(errors, passed, dirty) {
        if (this.disabled || this.noValidate) return undefined;
        let errs = errors;
        if (this.type === 'tel' && !errs[0] && !this.telValid) {
          errs = [this.telError];
        }

        return this.local
          ? this.VState(errs, passed)
          : this.VVState(errs, passed, this.name, dirty, this.errorBag);
      },
      inputError(errors, dirty) {
        if (this.disabled || this.noValidate) return null;
        let errs = errors;
        if (this.type === 'tel' && !errs[0] && !this.telValid) {
          errs = [this.telError];
        }

        return this.local
          ? errs[0]
          : this.VVError(errs, this.name, dirty, this.errorBag);
      },
      validate() {
        return this.$refs.provider.validate();
      },
    },
  };
</script>
