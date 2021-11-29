<template>
  <div class="component-contactpersons">
    <b-alert v-if="pmsError" variant="danger" show>
      <h4 class="alert-heading">{{ $t('error') }}</h4>
      <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
    </b-alert>

    <b-modal
      no-close-on-backdrop
      id="contactModal"
      ref="contactModal"
      no-fade
      centered
      static
      size="lg"
      modal-class="form-modal"
      ignore-enforce-focus-selector=".ss-search > input"
      :ok-title="$t(`buttons.${contact.id != null ? 'update' : 'save'}`)"
      ok-variant="primary"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      :ok-disabled="updatePending || !$refs.contactForm || formInvalid"
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
      <ValidationObserver ref="contactForm" slim>
        <div class="row contactModalRow">
          <div class="col-md-6 col-sm-12">
            <label class="text-xs" for="position">{{ $t('pages.contactpersons.modal.position') }}</label>
            <ValidatedField
              type="text" id="position" name="position" class="mb-0"
              v-model="contact.position" :disabled="updatePending"
              :error-bag="validationError" :rules="rulesFor('position')"
            />
          </div>
          <div class="col-md-3 col-sm-6">
            <label class="text-xs" for="salutation">{{ $t('pages.contactpersons.modal.salutation') }}</label>
            <drop-down
              id="salutation"
              id-key="value"
              label-key="salutation"
              v-model="contact.salutation"
              :items="salutation_list"
              :disabled="updatePending"
              :rules="rulesFor('salutation')"
            />
          </div>
          <div class="col-md-3 col-sm-6">
            <label class="text-xs" for="language">{{ $t('pages.contactpersons.modal.language') }}</label>
            <ValidatedField
              id="language"
              name="language"
              class="mb-0"
              v-model="contact.language"
              type="select"
              :options="langs"
              label-by="name"
              track-by="code"
              searchable
              :disabled="updatePending"
              :error-bag="validationError"
              :rules="rulesFor('language')"
            />
          </div>
          <div class="col-md-6 col-sm-12">
            <label class="text-xs" for="surname">{{ $t('pages.contactpersons.modal.surname') }}</label>
            <ValidatedField
              type="text" id="surname" name="surname" class="mb-0"
              v-model="contact.surname" :disabled="updatePending"
              :rules="rulesFor('surname')"
            />
          </div>
          <div class="col-md-6 col-sm-12">
            <label class="text-xs" for="firstname">{{ $t('pages.contactpersons.modal.first-name') }}</label>
            <ValidatedField
              type="text" id="firstname" name="firstname" class="mb-0"
              v-model="contact.firstname" :disabled="updatePending"
              :rules="rulesFor('firstname')"
            />
          </div>
          <div class="col-md-6 col-sm-12">
            <label class="text-xs" for="mail">{{ $t('pages.contactpersons.modal.email-address') }}</label>
            <ValidatedField
              type="email" id="mail" name="mail" class="mb-0"
              v-model="contact.mail" :disabled="updatePending"
              :rules="rulesFor('mail')"
            />
          </div>
          <div class="col-md-6 col-sm-12">
            <label class="text-xs" for="phone">{{ $t('pages.contactpersons.modal.phone-number') }}</label>
            <ValidatedField
              group-class=""
              name="phone"
              id="phone"
              type="tel"
              autocomplete="tel"
              class="mb-0"
              v-model.trim="contact.phone"
              :disabled="updatePending"
              :rules="rulesFor('phone')"
            />
          </div>
        </div>
      </ValidationObserver>
    </b-modal>

    <div class="panel position-relative panel-content">
      <div class="list d-none d-md-block left-edge">
        <div class="contacts-table" v-if="loaded">
          <table class="w-100">
            <thead>
              <tr>
                <th class="w-position">{{ $t('pages.contactpersons.headers.position') }}</th>
                <th>{{ $t('pages.contactpersons.headers.language') }}</th>
                <th>{{ $t('pages.contactpersons.headers.surname') }}</th>
                <th>{{ $t('pages.contactpersons.headers.first-name') }}</th>
                <th>{{ $t('pages.contactpersons.headers.phone-number') }}</th>
                <th>{{ $t('pages.contactpersons.headers.email-address') }}</th>
                <th class="w-actions">{{ $t('actions') }}</th>
              </tr>
            </thead>
            <tbody v-if="noContacts">
              <tr>
                <td colspan="8" class="w-empty">{{ $t('pages.contactpersons.no-contacts') }}</td>
              </tr>
            </tbody>
            <tbody v-for="row in contacts" :key="row.id">
              <tr class="separator before"></tr>
              <tr>
                <td class="w-position">
                  <a class="w-text-overflow" data-toggle="tooltip" :title="row.position">{{ row.position }}</a>
                </td>
                <td>
                  {{ $t(`langs.${row.language}`) }}
                </td>
                <td>
                  <a class="w-text-overflow" data-toggle="tooltip" :title="row.surname">{{ row.surname }}</a>
                </td>
                <td>
                  <a class="w-text-overflow" data-toggle="tooltip" :title="row.firstname">{{ row.firstname }}</a>
                </td>
                <td class="w-sm-text">
                  {{ row.phone }}
                </td>
                <td class="w-sm-text">
                  <a class="w-text-overflow" data-toggle="tooltip" :title="row.mail">{{ row.mail }}</a>
                </td>
                <td class="actions">
                  <b-btn class="btn-icon btn-tiny" @click="onEditContact(row)" :disabled="updatePending">
                    <icon width="17" height="17" type="edit"/>
                  </b-btn>
                  <b-btn class="btn-icon btn-tiny" @click="onDeleteContact(row.id)" :disabled="updatePending">
                    <icon width="19" height="19" type="delete"/>
                  </b-btn>
                </td>
              </tr>
              <tr class="separator after"></tr>
            </tbody>
          </table>
        </div>
        <div v-if="loaded">
          <b-btn pill variant="outline-primary" class="add-new-contact"
                 @click="openCreateForm">
            <icon width="10" height="11" type="plus"/>
            {{ $t('pages.contactpersons.button-add') }}
          </b-btn>
        </div>
      </div>
      <div class="d-md-none list-item" v-if="noContacts">
        <div class="w-empty">{{ $t('pages.contactpersons.no-contacts') }}</div>
      </div>
      <div class="d-md-none list-item" v-for="row in contacts" :key="row.id">
        <div class="d-flex">
          <div class="c-1">
            <p class="label">{{ $t('pages.contactpersons.headers.position') }}</p>
            <p>{{ row.position }}</p>
          </div>
          <div class="dots">
            <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret right :disabled="updatePending">
              <template #button-content>
                <icon width="20" height="19" class="label" type="dots-h"/>
              </template>
              <b-dropdown-item @click="onEditContact(row)">{{ $t('buttons.edit') }}</b-dropdown-item>
              <b-dropdown-item @click="onDeleteContact(row.id, true)"
                               :disabled="row.protected">{{ $t('buttons.delete') }}</b-dropdown-item>
            </b-dropdown>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.salutation') }}</p>
            <p>{{ getSalutation(row.salutation) }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.language') }}</p>
            <p>{{ $t(`langs.${row.language}`) }}</p>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.surname') }}</p>
            <p class="w-sm-surname" >{{ row.surname }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.first-name') }}</p>
            <p>{{ row.firstname }}</p>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.phone-number') }}</p>
            <p>{{ row.phone }}</p>
          </div>
          <div class="w-50">
            <p class="label">{{ $t('pages.contactpersons.headers.email-address') }}</p>
            <p class="text-truncate" :id="`emailId-${row.id}`" variant="primary">{{ row.mail }}</p>
            <b-popover
              :target="`emailId-${row.id}`"
              triggers="click"
              placement="bottom"
              container="page-contactperson"
            >{{ row.mail }}
            </b-popover>
          </div>
        </div>
      </div>
    </div>
    <div class="d-md-none add-new-contact-block" v-if="loaded">
      <b-btn pill variant="outline-primary" class="add-new-contact" @click="openCreateForm">
        <icon width="10" height="10" type="plus"/>
        {{ $t('pages.contactpersons.button-add') }}
      </b-btn>
    </div>
  </div>
</template>

<script>
  import { mapActions, mapGetters, mapState } from 'vuex';

  export default {
    name: 'ContactPersons',
    data: () => ({
      show: false,
      salutation_list: [
        {
          salutation: 'Mr',
          value: 'Mr',
        },
        {
          salutation: 'Mrs',
          value: 'Mrs',
        },
        {
          salutation: 'Ms',
          value: 'Ms',
        },
        {
          salutation: 'Sir',
          value: 'Sir',
        },
      ],
      contact: {},
    }),
    async created() {
      await Promise.all([
        this.getContacts(),
        this.fetchLanguages(),
      ]);
      this.onLoad();
    },
    computed: {
      ...mapGetters('contacts', ['contacts', 'updatePending', 'loaded', 'validationError']),
      ...mapGetters('data', ['languages']),
      ...mapState('contacts', ['pmsError']),

      langs() {
        return this.languages.map(({ nativeName, code }) => ({ code, name: `${nativeName}` }));
      },
      modalTitle() {
        if (this.contact.id == null) {
          return this.$t('pages.contactpersons.modal.title-add');
        }
        return this.$t('pages.contactpersons.modal.title-edit');
      },
      noContacts() {
        return this.contacts != null && !this.contacts.length;
      },
      formInvalid() {
        return this.$refs.contactForm && this.$refs.contactForm.flags.invalid;
      },
    },
    methods: {
      ...mapActions('contacts', ['getContacts', 'updateContact', 'createContact', 'deleteContact', 'onLoad']),
      ...mapActions('data', ['fetchLanguages']),
      resetContactModel() {
        this.contact = {
          position: '',
          salutation: 'Mr',
          language: 'en',
          surname: '',
          firstname: '',
          mail: '',
          phone: '',
        };
        if (this.$refs.contactForm != null) {
          this.$refs.contactForm.reset();
        }
      },
      getSalutation(val) {
        if (val != null && val !== '') {
          return this.salutation_list.find((el) => el.value === val).salutation;
        }
        return this.salutation_list[0].salutation;
      },
      rulesFor(field) {
        const rules = {
          required: true,
          max: 80,
        };
        switch (field) {
          case 'mail':
            rules.email = true;
            break;
          case 'phone':
            rules.max = 20;
            break;
          case 'language':
            rules.required = true;
            break;
          case 'salutation':
            rules.required = true;
            break;
          case 'position':
          default:
            break;
        }
        return rules;
      },
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
      },
      openCreateForm() {
        this.resetContactModel();
        this.$nextTick(this.$refs.contactModal.show);
      },
      async processForm() {
        const contact = { ...this.contact };
        try {
          if (contact.id != null) {
            await this.updateContact(contact);
          } else {
            await this.createContact(contact);
          }
          this.$nextTick(() => {
            this.$refs.contactModal.hide();
          });
        } catch (error) {
          this.$toastr.e(error.message, 'Error');
        }
      },
      async onDeleteContact(contactId) {
        await this.deleteContact(contactId);
      },
      onEditContact(row) {
        this.resetContactModel();
        this.contact = {
          ...this.contact,
          ...JSON.parse(JSON.stringify(row)), // deep clone without bindings
        };
        this.$nextTick(() => {
          if (this.$refs.contactForm != null) {
            this.$refs.contactForm.reset();
          }
          this.$refs.contactModal.show();
        });
      },
    },
  };
</script>
