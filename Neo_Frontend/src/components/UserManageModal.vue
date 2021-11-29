<template>
  <b-modal
    no-close-on-backdrop
    id="userModal"
    ref="modal"
    no-fade
    centered
    static
    size="lg"
    modal-class="form-modal"
    ignore-enforce-focus-selector=".ss-search > input"
    :ok-title="$t(`buttons.${user.id != null ? 'update' : 'save'}`)"
    ok-variant="primary"
    :cancel-title="$t('buttons.cancel')"
    cancel-variant="outline-primary"
    @show="modalScroll"
    @hidden="modalScroll"
    @ok.prevent="submitForm"
    :ok-disabled="pending || !$refs.userForm || userFormInvalid"
    :cancel-disabled="pending"
    :no-close-on-esc="pending"
    :hide-header-close="pending"

  >
    <template #modal-header-close>
      <icon width="20" height="20" class="d-none d-md-block" type="times"/>
      <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
    </template>
    <template #modal-title>
      {{ modalTitle }}
    </template>
    <ValidationObserver ref="userForm" slim tag="form" novalidate>
      <div class="row" v-if="!groupOwner">
        <div class="col-12">
          <AvatarSelector v-model="avatar" />
        </div>
      </div>
      <div class="row d-flex justify-content-around" v-if="!isNewUser">
        <div class="col-12 col-md-6">
          <label class="text-xs" for="first_name">
            {{ $t('pages.users.modal.newUser.field.name.first.title') }}<span class="required">*</span>
          </label>
          <ValidatedField rules="required_string|max:100" group-class="mb-3" no-icon
                          type="text" name="first_name" id="first_name"
                          :placeholder="$t('pages.users.modal.newUser.field.name.first.placeholder')"
                          v-model="user.profile.first_name"
                          :disabled="pending"

          />
        </div>
        <div class="col-12 col-md-6">
          <label class="text-xs" for="las_name">
            {{ $t('pages.users.modal.newUser.field.name.last.title') }}<span class="required">*</span>
          </label>
          <ValidatedField rules="required_string|max:100" group-class="mb-3" no-icon
                          type="text" name="last_name" id="last_name"
                          :placeholder="$t('pages.users.modal.newUser.field.name.last.placeholder')"
                          v-model="user.profile.last_name"
                          :disabled="pending"

          />
        </div>
      </div>
      <div class="row d-flex justify-content-around">
        <div class="col-12 col-md-6">
          <label class="text-xs" for="user-email">
            {{ $t('pages.users.modal.newUser.field.login.title') }}<span class="required">*</span>
          </label>
          <ValidatedField rules="required|email" group-class="mb-2" no-icon
                          :placeholder="$t('pages.users.modal.newUser.field.login.placeholder')"
                          type="email" name="email" id="user-email" v-model.trim="user.email"
                          autocomplete="email"
                          :error-bag="errorBag"
                          :disabled="pending"
          />
        </div>
        <div class="col-12 col-md-6" v-if="!isNewUser">
          <label class="text-xs" for="tel">
            {{ $t('pages.profile.phone-number') }}<span class="required">*</span>
          </label>
          <ValidatedField v-model.trim="user.profile.tel" no-icon
                          group-class="mb-2"
                          name="tel" type="tel" id="tel"
                          national-mode
                          autocomplete="tel"
                          rules="required|max:20"
                          :disabled="pending"
          />
        </div>
      </div>
      <!--
      <div class="row">
        <div class="col-md-6 col-sm">
          <p class="head-line">{{ $t('pages.users.modal.newUser.field.language.title') }}</p>
          <drop-down
            name="user-lang"
            class="mb-0"
            v-model="user.lang"
            :items="languages"
          />
        </div>
      </div>
      -->
      <hr class="line-separator" v-if="!isNewUser">
      <div class="row d-flex justify-content-around" v-if="!isNewUser">
        <div class="col-12 col-md-6">
          <label class="text-xs" for="password">
            {{ $t('pages.users.modal.newUser.field.password.title') }}<span class="required">*</span>
          </label>
          <ValidatedField :rules="rulesForPassword()" id="password" type="password" name="password"
                          :placeholder="passwordPlaceholder" v-model="user.password"
                          autocomplete="new-password" group-class="mb-2" no-icon
                          :disabled="pending"
          />
        </div>
        <div class="col-12 col-md-6">
          <label class="text-xs" for="password">
            {{ $t('pages.users.modal.newUser.field.password.title-confirm') }}<span class="required">*</span>
          </label>
          <ValidatedField :rules="rulesForPassword(true)"
                          type="password" name="password_confirmation"
                          id="password_confirmation" no-icon
                          v-model="user.password_confirmation" autocomplete="new-password"
                          :placeholder="$t('reset-pwd.password-confirm')"  group-class="mb-2"
                          :disabled="pending"
          />
        </div>
      </div>
      <hr class="line-separator" v-if="!groupOwner">
      <div class="row" v-if="!groupOwner">
        <div class="col-12 col-md-6">
          <template v-if="allowedUserPages.length">
            <pages-selector mode="user" :allowed="allowedUserPages" :disabled="pending"
                            v-model="user.apages" />
            <hr class="line-separator">
          </template>
          <label class="text-xs">{{ $t('pages.users.modal.newUser.properties-access') }}</label>
          <radio v-model="user.all_group_hotels" name="agh" :val="false" class="mb-2"
                 :disabled="pending">
            {{ $t('pages.users.modal.newUser.perms-by-roles') }}
            <icon id="users-perms-roles" type="info" h="16" w="16" class="ml-1" />
            <b-tooltip target="users-perms-roles">
              {{ $t('pages.users.modal.newUser.perms-by-roles-tip') }}
            </b-tooltip>
          </radio>
          <radio v-model="user.all_group_hotels" name="agh" :val="true" class="mb-2"
                 :disabled="pending">
            {{ $t('pages.users.modal.newUser.perms-all-hotels') }}
            <icon id="users-perms-all" type="info" h="16" w="16" class="ml-1" />
            <b-tooltip target="users-perms-all">
              {{ $t('pages.users.modal.newUser.perms-all-hotels-tip') }}
            </b-tooltip>
          </radio>
          <hr class="line-separator">
          <label class="text-xs" for="user-roles">{{ $t('pages.users.modal.newUser.field.role')  }}</label>
          <drop-down
            id="user-roles"
            multiple
            title="pages.users.modal.invite.roles"
            title-all="pages.users.modal.invite.roles-all"
            :items="allowedRoles"
            v-model="user.roles"
            :disabled="pending"
          />
        </div>
        <div class="col-12 col-md-6">
          <label class="text-xs">
            {{ $t('pages.users.modal.newUser.default-perms')  }}:
            <icon id="users-perms-default" type="info" h="14" w="14" class="ml-1" />
            <b-tooltip target="users-perms-default">
              {{ $t('pages.users.modal.newUser.default-perms-tip') }}
            </b-tooltip>
          </label>
          <pages-selector mode="hotel" v-model="user.pages" :allowed="allowedPages" show-all
                          :disabled="pending" />
        </div>
      </div>
    </ValidationObserver>
  </b-modal>
</template>

<script>
  import ValidationError from '@/errors/ValidationError';
  import PasswordHelper from '@/helpers/password.helper';

  export default {
    name: 'UserManageModal',
    props: {
      groupOwner: {
        type: Boolean,
        default: false,
      },
      group: {
        type: Object,
        default: null,
      },
      pending: Boolean,
      errorBag: {
        type: ValidationError,
        default: null,
      },
      allowedUserPages: {
        required: false,
        validator: (v) => (v == null || Array.isArray(v)),
      },
      allowedPages: {
        required: false,
        validator: (v) => (v == null || Array.isArray(v)),
      },
      allowedRoles: {
        required: false,
        validator: (v) => (v == null || Array.isArray(v)),
      },
    },
    data: () => ({
      avatar: {},
      user: {
        email: '',
        lang: 'en',
        password: '',
        password_confirmation: '',
        profile: {
          first_name: '',
          last_name: '',
          tel: '',
        },
        roles: [],
        all_group_hotels: true,
        apages: [],
        pages: [],
      },
      passwordPlaceholder: PasswordHelper.generateRandomPwd(),
    }),
    computed: {
      isNewUser() {
        return this.user.id == null;
      },
      editFields() {
        return ['password', 'password_confirmation', 'profile'];
      },
      modalTitle() {
        if (this.groupOwner && this.isNewUser) return this.$t('pages.users.modal.newUser.title-add-owner', { group: this.group?.name });
        if (this.isNewUser) return this.$t('pages.users.modal.newUser.title-add');
        return this.$t('pages.users.modal.newUser.title-edit');
      },
      userFormInvalid() {
        if (!this.$refs.userForm) return false;
        const { errors, flags: { invalid } } = this.$refs.userForm;
        if (this.isNewUser) return invalid || this.noRoleSelected;
        return Object.keys(errors).some((k) => errors[k].length > 0) || this.noRoleSelected;
      },
      noRoleSelected() {
        // Do not check "No Selected Role" for Group Owner
        return !this.groupOwner && !this.user.roles.length;
      },
    },
    methods: {
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
      },
      rulesForPassword(confirm = false) {
        const edit = this.user.id != null;
        const rules = {
          required_string: !edit,
          password: true,
          is_not: this.user.email,
        };
        if (confirm) {
          rules.sameAs = '@password';
        }
        return rules;
      },
      show(payload = null) {
        if (payload != null) {
          const { user, avatar } = payload;
          this.user = { ...this.user, ...user };
          this.avatar = { ...this.avatar, ...avatar };
          this.reset();
        } else {
          this.resetModal();
        }
        this.$nextTick(this.$refs.modal.show);
      },
      hide() {
        this.$refs.modal.hide();
      },
      submitForm() {
        const { user } = this;
        const { upload, remove } = this.avatar;
        const payload = { ...user, avatar: { upload, remove } };

        if (this.isNewUser) {
          this.editFields.map((field) => delete payload[field]);
        }
        if (!this.isNewUser && !user.password.length) {
          delete payload.password;
        }
        delete payload.password_confirmation;

        this.$emit('ok', payload);
      },
      reset() {
        if (this.$refs.userForm != null) {
          this.$refs.userForm.reset();
        }
      },
      resetModal() {
        this.user = {
          email: '',
          lang: 'en',
          password: '',
          password_confirmation: '',
          profile: {
            first_name: '',
            last_name: '',
            tel: '',
          },
          roles: [],
          all_group_hotels: false,
          apages: [],
          pages: [],
        };
        this.avatar = {
          original: null,
          upload: null,
          remove: false,
        };
        this.reset();
      },
    },
  };
</script>
