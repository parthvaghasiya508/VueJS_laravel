<template>
  <div class="page-users">
    <b-modal
      no-close-on-backdrop
      id="inviteModal"
      ref="inviteModal"
      no-fade
      centered
      static
      size="lg"
      modal-class="form-modal"
      ignore-enforce-focus-selector=".ss-search > input"
      :ok-title="'Sent Invitation'"
      ok-variant="primary"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      @show="modalScroll"
      @hidden="modalScroll"
      @ok.prevent="processInvite"
      :ok-disabled="usersUpdatePending || !$refs.inviteForm || inviteFormInvalid"
      :cancel-disabled="usersUpdatePending"
      :no-close-on-esc="usersUpdatePending"
      :hide-header-close="usersUpdatePending"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        {{ $t('pages.users.modal.invite.title-add') }}
      </template>
      <ValidationObserver ref="inviteForm" slim>
        <div class="row contactModalRow groupModal">
          <div class="col-md-12 col-sm-12">
            <label class="text-xs" for="email">{{ $t('pages.users.modal.invite.field-email') }}</label>
            <ValidatedField
              type="text" id="email" name="email" class="mb-0" autofocus
              v-model="invite.email"
              :placeholder="$t('pages.users.modal.invite.field-email-placeholder')"
              rules="required|email"
              :error-bag="usersUpdateError"
            />
          </div>
          <div class="col-md-12 col-sm-12">
            <div v-if="!noRoles">
              <label class="text-xs">{{ $t('pages.users.modal.invite.field-role') }}</label>
              <drop-down
                id="invite-roles"
                multiple
                title="pages.users.modal.invite.roles"
                title-all="pages.users.modal.invite.roles-all"
                :items="allowedRoles"
                v-model="invite.roles"
              />
            </div>
          </div>
        </div>
      </ValidationObserver>
    </b-modal>

    <user-manage-modal
      ref="userModal"
      :pending="usersUpdatePending"
      :error-bag="usersUpdateError"
      :allowed-pages="allowedPages"
      :allowed-user-pages="allowedUserPages"
      :allowed-roles="allowedRoles"
      @ok="processUserForm"
    />

    <b-modal
      no-close-on-backdrop
      id="roleModal"
      ref="roleModal"
      no-fade
      centered
      static
      size="lg"
      modal-class="form-modal"
      ignore-enforce-focus-selector=".ss-search > input"
      :ok-title="$t(`buttons.${role.id != null ? 'update' : 'save'}`)"
      ok-variant="primary"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      @show="modalScroll"
      @hidden="modalScroll"
      @ok.prevent="processRoleForm"
      :ok-disabled="rolesUpdatePending || !$refs.roleForm || roleFormInvalid"
      :cancel-disabled="rolesUpdatePending"
      :no-close-on-esc="rolesUpdatePending"
      :hide-header-close="rolesUpdatePending"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        {{ roleModalTitle }}
      </template>
      <div class="col-12">
        <ValidationObserver ref="roleForm" slim>
          <form @submit.prevent="processRoleForm">
            <div class="row">
              <div class="col-md-12 col-sm-12">
                <label class="text-xs" for="roleName">{{ $t('pages.users.modal.role.field-name-role') }}</label>
                <ValidatedField
                  type="text" id="roleName" name="roleName" class="mb-4" no-icon
                  v-model="role.name"
                  :placeholder="$t('pages.users.modal.role.placeholder-name-role')"
                  rules="required|max:80"
                  :disabled="rolesUpdatePending"
                />
              </div>
            </div>
          </form>
        </ValidationObserver>
      </div>
      <div class="col-12">
        <radio v-model="role.inherit_from_user" name="ifu" :val="true" class="mb-2"
               :disabled="rolesUpdatePending">
          {{ $t('pages.users.roles.perms-inherit') }}
          <icon id="roles-perms-inherit" type="info" h="16" w="16" class="ml-1" />
          <b-tooltip target="roles-perms-inherit">
            {{ $t('pages.users.roles.perms-inherit-tip') }}
          </b-tooltip>
        </radio>
        <radio v-model="role.inherit_from_user" name="ifu" :val="false" class="mb-2"
               :disabled="rolesUpdatePending">
          {{ $t('pages.users.roles.perms-override') }}
          <icon id="roles-perms-override" type="info" h="16" w="16" class="ml-1" />
          <b-tooltip target="roles-perms-override">
            {{ $t('pages.users.roles.perms-override-tip') }}
          </b-tooltip>
        </radio>
        <pages-selector mode="hotel" v-model="role.pages" :allowed="allowedPages" show-all class="mt-3"
                        :disabled="role.inherit_from_user || rolesUpdatePending" />
      </div>
    </b-modal>

    <div ref="title" class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.users.title') }}</p>
    </div>

    <tabs :items="tabs" v-model="activetab" withContent>
      <template #tab(users)>
        <div class="tab-content tab-content-user">
          <div class="panel position-relative panel-content panel-content_groups">
            <div class="head-line head-line-users justify-content-between">
              <div class="search_check-wrapper">
                <search-filter v-model="filterUser"
                               :placeholder="$t('pages.users.filter-tip')"
                               :disabled="usersUpdatePending"
                />
              </div>
              <div class="btn_users-wrapper">
                <button
                  @click="openInviteModal()"
                  :disabled="usersUpdatePending"
                  class="new_group_btn new_users_btn">
                  <icon width="20" height="20" type="users-invite" class="icon" />
                  <p>{{$t('pages.users.btnInvite')}}</p>
                </button>
                <button
                  @click="openNewUser()"
                  :disabled="usersUpdatePending"
                  class="new_group_btn new_users_btn">
                  <icon class="arrow" stroke-width="2" width="10" height="10" type="cross"/>
                  <p>{{$t('pages.users.btnAdd')}}</p>
                </button>
              </div>
            </div>
            <div class="list d-none d-md-block">
              <div class="hotels-table users-table">
                <table class="w-100" v-if="usersLoaded">
                  <thead>
                    <tr>
                      <th class="w-id">
                        {{ $t('id') }}<sort-indicator v-model="sort" field="id" />
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.headers.name') }}<sort-indicator v-model="sort" field="name" />
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.headers.login') }}
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.headers.role') }}
                      </th>
                      <th class="w-actions">{{ $t('actions') }}</th>
                    </tr>
                  </thead>

                  <tbody v-for="row in filteredUsers" :key="row.email">
                    <tr class="separator before"></tr>
                    <tr>
                      <td>
                        {{row.id}}
                      </td>
                      <td :class="{ 'font-italic': row.profile == null }">
                        {{ userName(row) }}
                      </td>
                      <td>
                        {{ row.email }}
                      </td>
                      <td>
                        {{ roleNames(row) }}
                      </td>
                      <td class="actions">
                        <b-btn
                          :disabled="protectedUser(row) || usersUpdatePending"
                          class="btn-icon btn-tiny"
                          @click="editUser(row)">
                          <icon width="17" height="17" type="edit"/>
                        </b-btn>

                        <b-btn
                          :disabled="protectedUser(row) || usersUpdatePending"
                          class="btn-icon btn-tiny"
                          @click="openDeleteUserConfirmationModal(row)">
                          <icon class="text-danger" width="17" height="17" type="delete" />
                        </b-btn>
                      </td>
                    </tr>
                    <tr class="separator after"></tr>
                  </tbody>

                  <tbody v-if="noUsers">
                    <tr>
                      <td colspan="5" class="w-empty">{{ $t('pages.users.no-users') }}</td>
                    </tr>
                  </tbody>

                  <tbody v-else-if="filterUser && !filteredUsers.length">
                    <tr>
                      <td colspan="5" class="w-empty">{{ $t('pages.users.filter-no-users') }}</td>
                    </tr>
                  </tbody>

                </table>
              </div>
            </div>

            <div class="d-md-none list-item" v-for="row in filteredUsers" :key="row.email">
              <div class="d-flex">
                <div class="c-1">
                  <p class="label">{{ $t('pages.users.headers.name') }}</p>
                  <p :class="{ 'font-italic': row.profile == null }">{{ userName(row) }}</p>
                </div>
                <div class="dots">
                  <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right>
                    <template #button-content>
                      <icon width="20" height="19" class="label" type="dots-h"/>
                    </template>
                    <b-dropdown-item
                      :disabled="protectedUser(row) || usersUpdatePending"
                      @click="editUser(row)"
                    >{{ $t('buttons.edit') }}</b-dropdown-item>
                    <b-dropdown-item
                      :disabled="protectedUser(row) || usersUpdatePending"
                      @click="openDeleteUserConfirmationModal(row)"
                    >{{ $t('buttons.delete') }}</b-dropdown-item>
                  </b-dropdown>
                </div>
              </div>
              <div class="d-flex line lineSmGroup">
                <div class="w-25">
                  <p class="label">{{ $t('id') }}</p>
                  <p>{{ row.id }}</p>
                </div>
                <div class="w-50">
                  <p class="label">{{ $t('pages.users.headers.login') }}</p>
                  <p>{{ row.email }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
      <template #tab(roles)>
        <div class="tab-content tab-content-user ">
          <div class="panel position-relative panel-content panel-content_groups">
            <div class="head-line head-line-users justify-content-between">
              <div class="search_check-wrapper">
                <search-filter v-model="filter"
                               :placeholder="$t('pages.users.filter-tip')"
                               :disabled="rolesUpdatePending"
                />
              </div>
              <button
                @click="openNewRole"
                class="new_group_btn new_users_btn new_users_btn-2">
                <icon class="arrow" stroke-width="2" width="10" height="10" type="cross"/>
                <p>{{$t('pages.users.roles.btnAdd')}}</p>
              </button>
            </div>
            <div class="list d-none d-md-block">
              <div v-if="rolesLoaded" class="hotels-table users-table">
                <table class="w-100">
                  <thead>
                    <tr>
                      <th class="w-id">
                        {{ $t('id') }}<sort-indicator v-model="sort" field="id" />
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.roles.headers.name') }}<sort-indicator v-model="sort" field="name" />
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.roles.headers.properties') }}
                        <sort-indicator v-model="sort" field="hotel.id" />
                      </th>
                      <th class="w-name">
                        {{ $t('pages.users.roles.headers.users') }}<sort-indicator v-model="sort" field="users_count" />
                      </th>
                      <th class="w-actions">{{ $t('actions') }}</th>
                    </tr>
                  </thead>
                  <tbody v-for="row in filteredRoles" :key="row.id">
                    <tr class="separator before"></tr>
                    <tr>
                      <td>
                        {{ row.id }}
                      </td>
                      <td>
                        {{ row.name }}
                      </td>
                      <td>
                        {{ row.hotel.id }} {{ row.hotel.name }}
                      </td>
                      <td>
                        {{ row.users_count }}
                      </td>
                      <td class="actions">
                        <b-btn class="btn-icon btn-tiny" @click="editRole(row)" :disabled="rolesUpdatePending">
                          <icon width="17" height="17" type="edit"/>
                        </b-btn>
                        <b-btn
                          :disabled="rolesUpdatePending"
                          class="btn-icon btn-tiny"
                          @click="deleteRole(row.id)">
                          <icon class="text-danger" width="19" height="19" type="delete"/>
                        </b-btn>
                      </td>
                    </tr>
                    <tr class="separator after"></tr>
                  </tbody>
                  <tbody>
                    <tr>
                      <td
                        v-if="noRoles"
                        colspan="6" class="w-empty">{{ $t('pages.users.roles.no-hotels') }}</td>
                    </tr>
                  </tbody>
                  <tbody>
                    <tr>
                      <td
                        v-if="filter && !filteredRoles.length"
                        colspan="6" class="w-empty">{{ $t('pages.users.roles.filter-no-hotels') }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="d-md-none list-item" v-for="row in filteredRoles" :key="row.id">
              <div class="d-flex">
                <div class="c-1">
                  <p class="label">{{ $t('pages.users.roles.headers.role') }}</p>
                  <p>{{ row.name }}</p>
                </div>
                <div class="dots">
                  <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right>
                    <template #button-content>
                      <icon width="20" height="19" class="label" type="dots-h"/>
                    </template>
                    <b-dropdown-item
                      @click="editRole(row)"
                      :disabled="rolesUpdatePending">{{ $t('buttons.edit') }}</b-dropdown-item>
                    <b-dropdown-item
                      @click="deleteRole(row.id)"
                      :disabled="rolesUpdatePending">{{ $t('buttons.delete') }}</b-dropdown-item>
                  </b-dropdown>
                </div>
              </div>
              <div class="d-flex line lineSmGroup">
                <div class="w-25">
                  <p class="label">{{ $t('id') }}</p>
                  <p>{{ row.id }}</p>
                </div>
                <div class="w-33">
                  <p class="label">{{ $t('pages.users.roles.headers.name') }}</p>
                  <p>{{ row.name }}</p>
                </div>
                <div class="w-33">
                  <p class="label">{{ $t('pages.users.roles.headers.users') }}</p>
                  <p>{{ row.users_count }}</p>
                </div>
                <div class="w-33">
                  <p class="label"> {{ $t('pages.users.roles.headers.properties') }}</p>
                  <p>{{ row.hotel.id }} {{ row.hotel.name }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </tabs>
  </div>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';
  import { pick } from '@/helpers';
  import UserManageModal from '@/components/UserManageModal.vue';

  export default {
    name: 'Users',
    components: { UserManageModal },
    data() {
      return {
        invite: {
          email: '',
          roles: [],
        },
        filter: '',
        filterUser: '',
        sort: '+id',
        role: {
          id: null,
          name: null,
          inherit_from_user: true,
          pages: [],
        },
        activetab: 'users',
      };
    },
    async created() {
      // const allow = await this.refreshPagesForHotel();
      // if (!allow) {
      //   this.$nextTick(() => window.location.reload());
      //   return;
      // }
      const tasks = [
        this.getUsers(),
      ];
      if (this.hotelID) {
        tasks.push(
          this.getRoles(),
        );
      }
      await Promise.allSettled(tasks);
      // this.openNewUser();
    },
    watch: {
      hotelID(val) {
        if (val) {
          this.getRoles();
        }
      },
    },
    computed: {
      ...mapGetters('user', ['userID', 'hotelID', 'group', 'hotels', 'allowedPages', 'allowedUserPages']),
      ...mapGetters('roles', ['roles', 'noRoles']),
      ...mapGetters('users', ['users', 'noUsers']),

      ...mapGetters('roles', { rolesLoaded: 'loaded' }),
      ...mapGetters('users', { usersLoaded: 'loaded' }),
      ...mapGetters('data', ['languages']),

      ...mapState('roles', { rolesUpdatePending: 'updatePending' }),
      ...mapState('users', { usersUpdatePending: 'updatePending', usersUpdateError: 'updateError' }),

      tabs() {
        const ret = [
          { id: 'users', title: this.$t('pages.users.tabs.users') },
        ];
        if (this.hotelID && this.rolesLoaded) {
          ret.push(
            { id: 'roles', title: this.$t('pages.users.tabs.roles') },
          );
        }
        return ret;
      },
      filteredRoles() {
        const filter = this.filter.trim().toLowerCase();
        let ret = [...this.roles];
        if (filter) {
          ret = ret.filter(({ id, name }) => (`${id}`.includes(filter) || name.toLowerCase().includes(filter)));
        }
        const field = this.sort.substr(1);
        const k = this.sort.charAt(0) === '+' ? 1 : -1;
        ret = ret.sort((a, b) => {
          const v1 = pick(a, field);
          const v2 = pick(b, field);
          // eslint-disable-next-line no-nested-ternary
          return k * (v1 > v2 ? 1 : (v1 < v2 ? -1 : 0));
        });
        return ret;
      },
      filteredUsers() {
        const filter = this.filterUser.trim().toLowerCase();
        let ret = [...this.users];
        if (filter) {
          ret = ret.filter(({ id, profile }) => (`${id}`.includes(filter) || profile.name.toLowerCase().includes(filter)));
        }
        const field = this.sort.substr(1);
        const k = this.sort.charAt(0) === '+' ? 1 : -1;
        ret = ret.sort((a, b) => {
          const v1 = pick(a, field);
          const v2 = pick(b, field);
          // eslint-disable-next-line no-nested-ternary
          return k * (v1 > v2 ? 1 : (v1 < v2 ? -1 : 0));
        });
        return ret;
      },

      roleModalTitle() {
        if (this.role.id == null) {
          return this.$t('pages.users.roles.modal.title-add');
        }
        return this.$t('pages.users.roles.modal.title-edit');
      },
      allowedRoles() {
        return this.roles.map(({ id, name, hotel }) => ({ id, text: `${name} - ${hotel?.id} ${hotel?.name}` }));
      },
      roleFormInvalid() {
        return this.$refs.roleForm && this.$refs.roleForm.flags.invalid;
      },
      inviteFormInvalid() {
        return this.$refs.inviteForm && (this.$refs.inviteForm.flags.invalid || !this.invite.roles?.length);
      },
      roleIsAllPages() {
        return this.role.pages.length === this.allowedPages.length;
      },
      langs() {
        return this.languages.map(({ nativeName, code }) => ({ code, name: `${nativeName}` }));
      },
    },
    methods: {
      ...mapActions('roles', ['getRoles', 'createRole', 'updateRole', 'deleteRole']),
      ...mapActions('users', ['getUsers', 'createInvite', 'createUser', 'updateUser', 'deleteUser']),
      ...mapActions('user', ['refreshPagesForHotel']),
      ...mapActions('data', ['fetchLanguages']),

      protectedUser(user) {
        // eslint-disable-next-line camelcase
        return user.id === this.group?.group_owner || user.id === this.userID;
      },
      userName(user) {
        return user.profile != null ? user.profile.name : this.$t('pages.users.invite-sent');
      },
      roleNames(row) {
        const owner = row.groups.map((g) => (g.group_owner === row.id ? `${this.$t('pages.users.group-owner')} (${g.name})` : ''))
          .filter((el) => el.length > 0);
        const roles = row.all_roles?.map((role) => (role.confirmed ? role.name : `${role.name} (${this.$t('pages.users.invite-sent')})`)) ?? [];
        return [...owner, ...roles].join(',');
      },
      roleToggleAllPages() {
        if (this.roleIsAllPages) {
          this.role.pages = [];
        } else {
          this.role.pages = [...this.allowedPages];
        }
      },
      resetRoleModal() {
        this.role = {
          id: null,
          name: null,
          pages: [],
          inherit_from_user: false,
        };
        if (this.$refs.roleForm != null) {
          this.$refs.roleForm.reset();
        }
      },
      resetInviteModel() {
        this.invite = {
          email: '',
          roles: [],
        };
        if (this.$refs.inviteForm != null) {
          this.$refs.inviteForm.reset();
        }
      },
      openInviteModal() {
        this.resetInviteModel();
        this.$nextTick(this.$refs.inviteModal.show);
      },
      openNewRole() {
        this.resetRoleModal();
        this.$nextTick(this.$refs.roleModal.show);
      },
      openNewUser() {
        this.$refs.userModal.show();
      },
      async openDeleteUserConfirmationModal(payload) {
        const options = {
          title: this.$t('pages.users.modal.delete-user.title'),
          okVariant: 'danger',
          bodyClass: 'px-5 pt-2 text-center',
          okTitle: 'Yes',
          cancelTitle: 'No',
          footerClass: 'pb-5',
          hideHeaderClose: false,
          centered: true,
          noFade: true,
        };
        const message = this.$createElement('h6', {
          domProps: {
            innerHTML: this.$t('pages.users.modal.delete-user.message', { email: payload?.email }),
          },
        });

        try {
          const ok = await this.$bvModal.msgBoxConfirm(message, options);
          if (ok) {
            await this.deleteUser(payload?.id);
            this.$toastr.s({
              title: this.$t('pages.users.modal.delete-user.user-delete-success'),
              msg: '',
              timeout: 3000,
              progressbar: false,
            });
          }
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
      },
      async processInvite() {
        try {
          const ok = await this.createInvite(this.invite);
          this.$refs.inviteForm.reset();
          if (!ok) return;
          this.$refs.inviteModal.hide();
          this.$toastr.s({
            title: this.$t('pages.users.modal.invite.alert-sent'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async processRoleForm() {
        try {
          if (this.role.id != null) {
            await this.updateRole(this.role);
          } else {
            await this.createRole(this.role);
          }

          this.$refs.roleForm.reset();
          this.$refs.roleModal.hide();
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
      async processUserForm(payload) {
        try {
          const id = payload.id || null;
          const ok = id ? await this.updateUser(payload) : await this.createUser(payload);
          this.$refs.userModal.reset();
          if (!ok) return;
          this.$refs.userModal.hide();
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
      async editRole(role) {
        this.resetRoleModal();
        this.role = {
          ...this.role,
          ...JSON.parse(JSON.stringify(role)),
        };
        this.$nextTick(() => {
          this.$refs.roleForm.reset();
          this.$refs.roleModal.show();
        });
      },
      async editUser(row) {
        const roles = (row.roles || []).pluck('id');
        const user = {
          ...JSON.parse(JSON.stringify(row)),
          roles,
        };
        const avatar = {
          original: user.avatar || null,
        };
        this.$refs.userModal.show({ user, avatar });
      },
    },
  };
</script>
