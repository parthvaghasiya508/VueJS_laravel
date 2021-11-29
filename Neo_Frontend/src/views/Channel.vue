<template>
  <div class="page-channel">
    <b-modal
      no-close-on-backdrop
      id="promoModal"
      ref="promoModal"
      no-fade
      static
      centered
      size="md"
      modal-class="form-modal"
      :ok-title="promoOkTitle"
      :ok-variant="promo.outdated ? 'outline-primary' : 'primary'"
      :ok-only="promo.outdated"
      :cancel-title="$t('buttons.cancel')"
      cancel-variant="outline-primary"
      :ok-disabled="updatePending || !promoFormValid"
      :cancel-disabled="updatePending"
      :no-close-on-esc="updatePending"
      :hide-header-close="updatePending"
      @show="modalScroll"
      @hidden="modalScroll"
      @ok.prevent="processPromoForm"
    >
      <template #modal-header-close>
        <icon width="20" height="20" class="d-none d-md-block" type="times"/>
        <icon width="10" height="18" class="d-md-none" type="arrow-left"/>
      </template>
      <template #modal-title>
        {{ promoModalTitle }}
      </template>
      <ValidationObserver ref="promoForm" slim>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-2">{{ $t(`pages.channels.promo.modal.field-name-${promo.mode}`) }}</h5>
            <ValidatedField id="promo-name" name="name" no-icon
                            rules="required|min:3"
                            :placeholder="$t(`pages.channels.promo.modal.field-name-placeholder-${promo.mode}`)"
                            v-model="promo.name" :disabled="updatePending || promo.outdated"/>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-2">{{ $t(`pages.channels.promo.modal.field-code-${promo.mode}`) }}</h5>
            <ValidatedField name="code" no-icon
                            rules="required|min:3" :error-bag="updateError"
                            :placeholder="$t(`pages.channels.promo.modal.field-code-placeholder-${promo.mode}`)"
                            v-model.trim="promo.code" :disabled="updatePending || promoIsEdit || promo.outdated"/>
          </div>
        </div>
        <div class="row" v-if="promo.mode === 'promo'">
          <div class="col-12">
            <h5 class="pb-2">{{ $t('pages.channels.promo.modal.field-discount') }}</h5>
            <AmountPercent v-model="promo.discount" required
                           :disabled="updatePending || promo.outdated"
                           class="mb-3" min="1" max="99" />
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <h5 class="pb-4 pt-2">{{ $t('pages.channels.promo.modal.field-plans') }}</h5>
            <products-selector
              v-if="!promoIsEdit"
              plans-only
              :plans="promoCreatePlans"
              :rooms="rooms"
              :selected-plans.sync="promo.plans"
              :disabled="updatePending"
            />
            <div v-else>
              <p v-for="({ id, text, rid, rtext }, idx) in promoRates(promo)" :key="`pre-${promo.code}-${idx}`">
                {{ rtext }} ({{ rid }}) &centerdot; {{ text }} ({{ id }})
              </p>
            </div>
          </div>
        </div>
        <h5 class="pb-2 pt-3">{{ $t('pages.channels.promo.modal.field-validity') }}</h5>
        <div class="row">
          <div class="col-12 col-md-6">
            <label class="text-xs">{{ $t('date.from') }}</label>
            <date-picker
              id="promo-from" v-model="promo.from" :min-date="today" grow="md-up" ref="promoFrom"
              :disabled="updatePending || promo.outdated" :placeholder="$t('date.from-placeholder')"
              @input="promoFromChanged" />
          </div>
          <div class="col-12 col-md-6">
            <label class="text-xs">{{ $t('date.until') }}</label>
            <date-picker
              id="promo-until" v-model="promo.until" :min-date="today" grow="md-up" ref="promoUntil"
              position="left-md-right"
              :disabled="updatePending || promo.outdated" :placeholder="$t('date.until-placeholder')"
              @input="promoUntilChanged" />
          </div>
        </div>
      </ValidationObserver>
    </b-modal>
    <div>
      <div class="panel-title position-relative w-100 title">
        <p>{{ channelName }}</p>
      </div>
      <div class="row" v-if="loaded && channel.type === 'push'">
        <push-channel-info-modal
          :pending="updatePending"
          ref="updateModal"
          @ok="updateChannel"
        />
        <non-easy-mapping-rate-plans-modal
          :pending="updatePending"
          ref="updateRatePlansModal"
          @ok="updateRatePlans"
        />
        <div class="col">
          <div class="row" v-if="channel.period.number > 0">
            <div class="col cell-field">{{ $t('pages.channels.connect.transmission-period') }}</div>
            <div class="col cell-value">{{ periodValue }}</div>
          </div>
          <div class="row" v-if="channel.period.until != null">
            <div class="col cell-field">{{ $t('pages.channels.connect.transmission-end-date') }}</div>
            <div class="col cell-value">{{ endDateValue }}</div>
          </div>
          <div class="row" v-for="field in fieldsValues" :key="`ccf-${field.key}`">
            <div class="col cell-field">{{ field.title }}</div>
            <div class="col cell-value">{{ field.value }}</div>
          </div>
          <div class="row">
            <div class="col cell-change-settings">
              <b-btn variant="outline-primary" @click="openUpdateModal">
                {{ $t('pages.channels.connect.btn-change-settings') }}
              </b-btn>
            </div>
            <div class="col cell-change-settings" v-if="!isEasyMapping">
              <b-btn variant="outline-primary" @click="openRatePlansModal">
                {{ $t('pages.channels.connect.btn-update-rateplans') }}
              </b-btn>
            </div>
          </div>
        </div>
      </div>

      <floater v-if="channelIsValid && !isPullChannel" :shown="cmap.selected.length > 0">
        <template #content>
          <div class="floater-table">
            <div class="connect-heading">
              <p class="headline">{{ channel.name }}</p>
              <p class="separator"></p>
              <p class="headline">{{ $t('pages.channels.connect.my-rate-plans') }}</p>
            </div>
            <div class="connect-table">
              <div class="connect-row" v-for="{ plan, cplan, uniq, mode, ctype, ptype } in cmap.selected"
                   :key="`cnn-${uniq}`">
                <div class="cell-dst">
                  <p class="room"><b>{{ $t('pages.channels.connect.mapped-room') }}:</b>&nbsp;{{ ctype }}
                    ({{ cplan.typeid }})</p>
                  <p><b>{{ $t('pages.channels.connect.mapped-plan') }}:&nbsp;</b>{{ cplan.name }} ({{ cplan.id }})</p>
                </div>
                <div class="cell-link"><icon type="link" width="20" height="20"/></div>
                <div class="cell-src">
                  <p class="room"><b>{{ $t('pages.channels.connect.mapped-room') }}:</b>&nbsp;{{ ptype.text }}
                    ({{ ptype.id }})</p>
                  <p><b>{{ $t('pages.channels.connect.mapped-plan') }}:&nbsp;</b>{{ plan.text }} ({{ plan.id }})</p>
                </div>
                <div class="cell-upd text-nowrap">
                  <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret dropup right
                              :disabled="updatePending">
                    <template #button-content>
                      {{ updateTypeText(mode) }}
                      <icon width="18" height="18" type="expand"/>
                    </template>
                    <b-dropdown-item v-for="{ id, text } in updateTypes" :key="`mode-${uniq}-${id}`"
                                     :disabled="updatePending || id === mode" @click="changeUpdateType(uniq, id)"
                    >{{ $t(text) }}</b-dropdown-item>
                  </b-dropdown>
                </div>
                <div class="cell-action">
                  <b-btn class="btn-icon btn-tiny" @click="unlinkPlan(uniq)" :disabled="updatePending">
                    <icon type="delete" width="14" height="16"/>
                  </b-btn>
                </div>
              </div>
            </div>
          </div>
        </template>
        <template #footer>
          <span class="connections-count">
            {{ $tc('pages.channels.connect.selected-connections-count', cmap.selected.length) }}
          </span>
          <b-btn pill variant="outline-primary" size="sm" :disabled="updatePending"
                 @click="connectPlans">{{ $t('pages.channels.connect.btn-connect') }}</b-btn>
        </template>
      </floater>

      <floater v-if="channelIsValid && isPullChannel" :shown="hasPullChanges" no-content>
        <template #footer>
          <span class="connections-count">
            {{ $tc('pages.channels.connect.selected-changes-count', pullChangesCount) }}
          </span>
          <b-btn pill variant="outline-primary" size="sm" :disabled="updatePending || cstateUpdatePending"
                 @click="updatePullMappings">{{ $t('buttons.update') }}</b-btn>
        </template>
      </floater>

      <tabs :items="tabs" v-model="tab" with-content
            v-if="channelIsValid" :hideOverlay="tab === 'product'" @switch="seluniq = null">
        <template #tab(pending) v-if="!isPullChannel">
          <div class="tablist-none" v-if="!pendingCTypes.length">
            {{ $t('pages.channels.connect.no-pending') }}
          </div>
          <div class="pending-table" v-else>
            <div class="dst-list">
              <p class="headline">{{ channel.name }}</p>
              <div class="rates-list" :class="{ active: seluniq != null }">
                <div class="type-item" v-for="{ name: tname, typeid, plans } in pendingCTypes"
                     :key="`ctype-${typeid}`">
                  <h6 :class="{ opened: isOpen(typeid) }" @click="toggleOpen(typeid)">
                    {{ tname }}&nbsp;({{ typeid }})
                    <icon width="13" height="7" stroke-width="2" type="arrow-down" class="icon-open"/>
                    <icon width="13" height="7" stroke-width="2" type="arrow-up" class="icon-close"/>
                  </h6>
                  <div>
                    <p v-for="{ id, name, uniq } in plans" :key="`cplan-${id}-${typeid}`"
                       @click="setCRate(uniq)" :class="{ active: isActiveCRate(uniq) }">
                      <icon width="20" height="20" type="link"/>{{ name }} ({{ id }})
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="src-list" :class="{ 'd-none': seluniq == null }">
              <p class="headline">{{ $t('pages.channels.connect.my-rate-plans') }}</p>
              <div class="rates-list">
                <div class="type-item" v-for="({ pid, id, text, rates }) in pendingRooms" :key="`type-${id}`">
                  <h6 :class="{ opened: isOpen(id) }" @click="toggleOpen(id)">
                    {{ text }}&nbsp;({{ id }})
                    <icon width="13" height="7" stroke-width="2" type="arrow-down" class="icon-open"/>
                    <icon width="13" height="7" stroke-width="2" type="arrow-up" class="icon-close"/>
                  </h6>
                  <div>
                    <p v-for="plan in rates" :key="`plan-${plan.id}-${id}`"
                       @click="linkPlan(plan)">
                      <icon width="20" height="20" type="link"/>{{ plan.text }} ({{ plan.id }})
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
        <template #tab(connected) v-if="!isPullChannel">
          <div class="tablist-none" v-if="!hasMappedPlans">
            {{ $t('pages.channels.connect.no-connected') }}
          </div>
          <div class="mapped-table" v-else>
            <div class="mapped-heading">
              <p class="headline">{{ channel.name }}</p>
              <p class="separator"></p>
              <p class="headline">{{ $t('pages.channels.connect.my-rate-plans') }}</p>
            </div>
            <table class="w-100">
              <tbody v-for="item in mappedPlans" :key="`mapped-${item.uniq}`">
                <tr>
                  <td class="cell-dst">
                    <p class="room"><b>{{ $t('pages.channels.connect.mapped-room') }}:&nbsp;</b>{{ item.ctype }}
                      ({{ item.cplan.typeid }})</p>
                    <p><b>{{ $t('pages.channels.connect.mapped-plan') }}:&nbsp;</b>{{ item.cplan.name }}
                      ({{ item.cplan.id }})</p>
                  </td>
                  <td class="cell-src">
                    <p class="room"><b>{{ $t('pages.channels.connect.mapped-room') }}:&nbsp;</b>{{ item.room.text }}
                      ({{ item.room.id }})</p>
                    <p><b>{{ $t('pages.channels.connect.mapped-plan') }}:&nbsp;</b>{{ item.plan.text }}
                      ({{ item.plan.id }})</p>
                    <p>
                      <b-dropdown size="sm" toggle-tag="span" variant="link" no-caret dropup right
                                  :disabled="updatePending">
                        <template #button-content>
                          {{ updateTypeText(item.mode) }}
                          <icon width="18" height="18" type="expand"/>
                        </template>
                        <b-dropdown-item v-for="{ id, text } in updateTypes" :key="`mode-${item.uniq}-${id}`"
                                         :disabled="updatePending || id === item.mode"
                                         @click="updateConnection(item, { mode: id })"
                        >{{ $t(text) }}</b-dropdown-item>
                      </b-dropdown>
                    </p>
                  </td>
                  <td class="cell-action">
                    <b-btn pill variant="danger" size="sm"
                           @click="disconnectPlan(item)" :disabled="updatePending"
                    >{{ $t('pages.channels.connect.btn-disconnect') }}</b-btn>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
        <template #tab(mapping) v-if="isPullChannel">
          <div class="tablist-none" v-if="!hasPlans">
            {{ $t('pages.channels.connect.no-plans') }}
          </div>
          <div class="plans-table" v-else>
            <div class="plans-heading">
              <p class="headline mb-3">
                <check-box :value="isAllMySelected" @input="toggleMyAll" :disabled="updatePending || !plans.length"
                           class="select-all">{{ $t('pages.channels.map-modal.checkbox-toggle-all') }}</check-box>
              </p>
            </div>
            <div class="all-plans">
              <products-selector
                AllPlans
                parted
                :plans="plans"
                :rooms="rooms"
                :selected-plans.sync="cmap.pselected"
                :selected-rooms.sync="cmap.selected"
              />
            </div>
          </div>
        </template>
        <template #tab(urls)>
          <div class="urls-block">
            <p>{{ $t('pages.channels.urls.main-link') }}</p>
            <b-form-row class="link-fields">
              <div class="col cell-link-field copyable copy-icon">
                <button class="btn-icon" @click="copyMainUrl">
                  <icon w="19" h="19" type="copy"></icon>
                </button>
                <b-input readonly class="mb-0" ref="urlMain" :value="urlMain" />
                <b-form-valid-feedback tooltip ref="urlMainTip">
                  {{ $t('setup.steps.5.msg-copied') }}
                </b-form-valid-feedback>
              </div>
              <div class="col cell-link-tip">
                <a :href="urlMain" target="_blank">
                  {{ $t('pages.channels.urls.open-in-new-tab') }}
                  <icon w="16" h="16" type="new-window" class="ml-1" />
                </a>
              </div>
            </b-form-row>
            <p class="mt-4">{{ $t('pages.channels.urls.custom-link') }}</p>
            <b-form-row class="link-fields">
              <div class="col cell-link-field copyable copy-icon">
                <button class="btn-icon" @click="copyCustomUrl" :disabled="!urlCustom">
                  <icon w="19" h="19" type="copy"></icon>
                </button>
                <b-input readonly class="mb-0" ref="urlCustom" :value="urlCustom" />
                <b-form-valid-feedback tooltip ref="urlCustomTip">
                  {{ $t('setup.steps.5.msg-copied') }}
                </b-form-valid-feedback>
              </div>
              <div class="col cell-link-tip">
                <a :href="urlCustom" target="_blank" :disabled="!urlCustom" :class="{ disabled: !urlCustom }">
                  {{ $t('pages.channels.urls.open-in-new-tab') }}
                  <icon w="16" h="16" type="new-window" class="ml-1" />
                </a>
              </div>
              <p class="col-12 text-sm mt-3">
                {{ $t('pages.channels.urls.custom-link-tip') }}
              </p>
            </b-form-row>
            <products-selector
              parted
              v-if="mapped.length > 0"
              :plans="mappedMyPlansForSelector"
              :rooms="rooms"
              :selected-plans.sync="umap.plans"
              :selected-rooms.sync="umap.rooms"
            />
            <p v-else>{{ $t('pages.channels.urls.no-mapped-products') }}</p>
          </div>
        </template>
        <template #tab(promos)>
          <promotion
            mode="promo"
            type="channel"
            :filter="promoFilter.promo"
            :all="promoAll.promo"
            :updating="updatePending"
            :promos="promoItems('promo')"
            @create="openCreatePromo('promo')"
            @edit="editPromo($event)"
            @delete="deletePromo($event)"
            @filter="promoFilter.promo = $event"
            @all="promoAll.promo = $event"
          />
        </template>
        <template #tab(settings)>
          <div class="settings-block">
            <div class="panel position-relative panel-content my-40">
              <div class="list left-edge" :class="{ 'px-0 pb-0': colorOpened }">
                <p class="head-line color-customization" :class="{ opened: colorOpened }"
                   @click="colorOpened=!colorOpened">
                  <icon width="20" height="20" stroke-width="2" class="icon" type="brush"/>
                  {{ $t('pages.channels.settings.color-customization.title') }}
                  <icon width="12" height="7" stroke-width="2" class="icon-arrow" type="arrow-down"/>
                </p>
                <div>
                  <div class="d-flex color-switcher align-items-center">
                    <p :class="{ active: !isAdvanced }">
                      {{ $t('pages.channels.settings.color-customization.light-mode') }}
                    </p>
                    <switcher medium v-model="isAdvanced" :disabled="updatePending"
                              class="switcher" />
                    <p :class="{ active: isAdvanced }">
                      {{ $t('pages.channels.settings.color-customization.advanced-mode') }}
                    </p>
                  </div>
                  <div class="d-flex" :class="{ 'advanced-color-border': isAdvanced }">
                    <div class="advanced-setting" v-show="isAdvanced">
                      <ul class="advanced-menu w-100">
                        <li class="advanced-menu-item advanced-menu-item-dropdown"
                            :class="{ active: isBackgroundsActive }">
                          <a @click.prevent="toggleMenu('backgrounds')">
                            <span>
                              <span>{{ $t('pages.channels.settings.color-customization.backgrounds') }}</span>
                              <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
                            </span>
                          </a>
                          <div class="advanced-menu-dropdown">
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.main-background') }}
                              </p>
                              <drop-down
                                id="main-background-type"
                                :disabled="updatePending"
                                v-model="colorCollection.template_background_type"
                                :items="typeList"
                              />
                              <div class="color-form" v-show="colorCollection.template_background_type=='flat'">
                                <div class="d-flex">
                                  <span
                                    id="template-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.template_background_color }"
                                  />
                                  <b-popover
                                    target="template-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.template_background_color"
                                      @input="changeColorSchema('template_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="template-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="template-background-color"
                                    v-model="colorCollection.template_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.template_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.top-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="template-background-color-top"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.template_background_color_top }"
                                  />
                                  <b-popover
                                    target="template-background-color-top"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.template_background_color_top"
                                      @input="changeColorSchema('template_background_color_top', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="template-background-color-top"
                                    class="color-editor mb-0"
                                    type="text" id="template-background-color-top"
                                    v-model="colorCollection.template_background_color_top"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.template_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.bottom-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="template-background-color-bottom"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.template_background_color_bottom }"
                                  />
                                  <b-popover
                                    target="template-background-color-bottom"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.template_background_color_bottom"
                                      @input="changeColorSchema('template_background_color_bottom', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="template-background-color-bottom"
                                    class="color-editor mb-0"
                                    type="text" id="template-background-color-bottom"
                                    v-model="colorCollection.template_background_color_bottom"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.header-background') }}
                              </p>
                              <drop-down
                                id="header-background-type"
                                :disabled="updatePending"
                                v-model="colorCollection.header_background_type"
                                :items="typeList"
                              />
                              <div class="color-form" v-show="colorCollection.header_background_type=='flat'">
                                <div class="d-flex">
                                  <span
                                    id="header-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.header_background_color }"
                                  />
                                  <b-popover
                                    target="header-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.header_background_color"
                                      @input="changeColorSchema('header_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="header-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="header-background-color"
                                    v-model="colorCollection.header_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.header_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.top-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="header-background-color-top"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.header_background_color_top }"
                                  />
                                  <b-popover
                                    target="header-background-color-top"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.header_background_color_top"
                                      @input="changeColorSchema('header_background_color_top', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="header-background-color-top"
                                    class="color-editor mb-0"
                                    type="text" id="header-background-color-top"
                                    v-model="colorCollection.header_background_color_top"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.header_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.bottom-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="header-background-color-bottom"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.header_background_color_bottom }"
                                  />
                                  <b-popover
                                    target="header-background-color-bottom"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.header_background_color_bottom"
                                      @input="changeColorSchema('header_background_color_bottom', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="header-background-color-bottom"
                                    class="color-editor mb-0"
                                    type="text" id="header-background-color-bottom"
                                    v-model="colorCollection.header_background_color_bottom"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <div class="color-info">
                                <p class="color-title">
                                  {{ $t('pages.channels.settings.color-customization.product-background') }}
                                </p>
                                <span id="info-include-sections">
                                  <icon stroke-width="2" width="20" height="20" type="alert-info"/>
                                </span>
                                <b-popover
                                  target="info-include-sections"
                                  placement="top"
                                  container="body"
                                  triggers="hover"
                                >
                                  <p class="popover-info"
                                     v-html="$t('pages.channels.settings.color-customization.include-sections')" />
                                </b-popover>
                              </div>
                              <drop-down
                                id="product-background-type"
                                :disabled="updatePending"
                                v-model="colorCollection.product_background_type"
                                :items="typeList"
                              />
                              <div class="color-form" v-show="colorCollection.product_background_type=='flat'">
                                <div class="d-flex">
                                  <span
                                    id="product-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_background_color }"
                                  />
                                  <b-popover
                                    target="product-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_background_color"
                                      @input="changeColorSchema('product_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="product-background-color"
                                    v-model="colorCollection.product_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.product_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.top-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="product-background-color-top"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_background_color_top }"
                                  />
                                  <b-popover
                                    target="product-background-color-top"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_background_color_top"
                                      @input="changeColorSchema('product_background_color_top', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-background-color-top"
                                    class="color-editor mb-0"
                                    type="text" id="product-background-color-top"
                                    v-model="colorCollection.product_background_color_top"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form" v-show="colorCollection.product_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.bottom-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="product-background-color-bottom"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_background_color_bottom }"
                                  />
                                  <b-popover
                                    target="product-background-color-bottom"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_background_color_bottom"
                                      @input="changeColorSchema('product_background_color_bottom', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-background-color-bottom"
                                    class="color-editor mb-0"
                                    type="text" id="product-background-color-bottom"
                                    v-model="colorCollection.product_background_color_bottom"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </li>
                        <li class="advanced-menu-item advanced-menu-item-dropdown"
                            :class="{ active: isfontsActive }">
                          <a @click.prevent="toggleMenu('fonts')">
                            <span>
                              <span>{{ $t('pages.channels.settings.color-customization.fonts') }}</span>
                              <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
                            </span>
                          </a>
                          <div class="advanced-menu-dropdown">
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.language-selection') }}
                              </p>
                              <div class="color-form">
                                <div class="d-flex">
                                  <span
                                    id="language-block-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.language_block_color }"
                                  />
                                  <b-popover
                                    target="language-block-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.language_block_color"
                                      @input="changeColorSchema('language_block_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="language-block-color"
                                    class="color-editor mb-0"
                                    type="text" id="language-block-color"
                                    v-model="colorCollection.language_block_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.check-color') }}
                              </p>
                              <div class="color-form">
                                <div class="d-flex">
                                  <span
                                    id="check-in-out-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.check_in_out_color }"
                                  />
                                  <b-popover
                                    target="check-in-out-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.check_in_out_color"
                                      @input="changeColorSchema('check_in_out_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="check-in-out-color"
                                    class="color-editor mb-0"
                                    type="text" id="check-in-out-color"
                                    v-model="colorCollection.check_in_out_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.date-color') }}
                              </p>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="date-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.date_color }"
                                  />
                                  <b-popover
                                    target="date-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.date_color"
                                      @input="changeColorSchema('date_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="date-color"
                                    class="color-editor mb-0"
                                    type="text" id="date-color"
                                    v-model="colorCollection.date_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.background-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="date-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.date_background_color }"
                                  />
                                  <b-popover
                                    target="date-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.date_background_color"
                                      @input="changeColorSchema('date_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="date-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="date-background-color"
                                    v-model="colorCollection.date_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <div class="color-info">
                                <p class="color-title">
                                  {{ $t('pages.channels.settings.color-customization.product-name') }}
                                </p>
                                <span id="info-travel-period">
                                  <icon stroke-width="2" width="20" height="20" type="alert-info"/>
                                </span>
                                <b-popover
                                  target="info-travel-period"
                                  placement="top"
                                  container="body"
                                  triggers="hover"
                                >
                                  <p class="popover-info"
                                     v-html="$t('pages.channels.settings.color-customization.travel-period')" />
                                </b-popover>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="product-name-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_name_color }"
                                  />
                                  <b-popover
                                    target="product-name-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_name_color"
                                      @input="changeColorSchema('product_name_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-name-color"
                                    class="color-editor mb-0"
                                    type="text" id="product-name-color"
                                    v-model="colorCollection.product_name_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-style') }}
                                </div>
                                <div class="d-flex">
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-name-font-style"
                                    class="color-editor mb-0"
                                    type="text" id="product-name-font-style"
                                    v-model="colorCollection.product_name_font_style"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_name_font_style=='bold' }"
                                        @click="changeFontStyle('product_name_font_style', 'bold')">
                                    <icon width="12" height="12" type="bold"/>
                                  </span>
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_name_font_style=='italic' }"
                                        @click="changeFontStyle('product_name_font_style', 'italic')">
                                    <icon width="12" height="12" type="italic"/>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.meals') }}
                              </p>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.color-included') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="meals-included-colour"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.meals_included_colour }"
                                  />
                                  <b-popover
                                    target="meals-included-colour"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.meals_included_colour"
                                      @input="changeColorSchema('meals_included_colour', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="meals-included-colour"
                                    class="color-editor mb-0"
                                    type="text" id="meals-included-colour"
                                    v-model="colorCollection.meals_included_colour"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.color-excluded') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="meals-excluded-colour"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.meals_excluded_colour }"
                                  />
                                  <b-popover
                                    target="meals-excluded-colour"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.meals_excluded_colour"
                                      @input="changeColorSchema('meals_excluded_colour', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="meals-excluded-colour"
                                    class="color-editor mb-0"
                                    type="text" id="meals-excluded-colour"
                                    v-model="colorCollection.meals_excluded_colour"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.booking-summary') }}
                              </p>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="title-bar-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.title_bar_color }"
                                  />
                                  <b-popover
                                    target="title-bar-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.title_bar_color"
                                      @input="changeColorSchema('title_bar_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="title-bar-color"
                                    class="color-editor mb-0"
                                    type="text" id="title-bar-color"
                                    v-model="colorCollection.title_bar_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.background-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="title-bar-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.title_bar_background_color }"
                                  />
                                  <b-popover
                                    target="title-bar-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.title_bar_background_color"
                                      @input="changeColorSchema('title_bar_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="title-bar-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="title-bar-background-color"
                                    v-model="colorCollection.title_bar_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <div class="color-info">
                                <p class="color-title">
                                  {{ $t('pages.channels.settings.color-customization.product-note') }}
                                </p>
                                <span id="info-same-change">
                                  <icon stroke-width="2" width="20" height="20" type="alert-info"/>
                                </span>
                                <b-popover
                                  target="info-same-change"
                                  placement="top"
                                  container="body"
                                  triggers="hover"
                                >
                                  <p class="popover-info"
                                     v-html="$t('pages.channels.settings.color-customization.same-change')" />
                                </b-popover>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="product-note-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_note_color }"
                                  />
                                  <b-popover
                                    target="product-note-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_note_color"
                                      @input="changeColorSchema('product_note_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-note-color"
                                    class="color-editor mb-0"
                                    type="text" id="product-note-color"
                                    v-model="colorCollection.product_note_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-style') }}
                                </div>
                                <div class="d-flex">
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-note-font-style"
                                    class="color-editor mb-0"
                                    type="text" id="product-note-font-style"
                                    v-model="colorCollection.product_note_font_style"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_note_font_style=='bold' }"
                                        @click="changeFontStyle('product_note_font_style', 'bold')">
                                    <icon width="12" height="12" type="bold"/>
                                  </span>
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_note_font_style=='italic' }"
                                        @click="changeFontStyle('product_note_font_style', 'italic')">
                                    <icon width="12" height="12" type="italic"/>
                                  </span>
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.product-amount') }}
                              </p>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </div>
                                <div class="d-flex">
                                  <span
                                    id="product-amount-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.product_amount_color }"
                                  />
                                  <b-popover
                                    target="product-amount-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.product_amount_color"
                                      @input="changeColorSchema('product_amount_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-amount-color"
                                    class="color-editor mb-0"
                                    type="text" id="product-amount-color"
                                    v-model="colorCollection.product_amount_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <div class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-style') }}
                                </div>
                                <div class="d-flex">
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="product-amount-font-style"
                                    class="color-editor mb-0"
                                    type="text" id="product-amount-font-style"
                                    v-model="colorCollection.product_amount_font_style"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_amount_font_style=='bold' }"
                                        @click="changeFontStyle('product_amount_font_style', 'bold')">
                                    <icon width="12" height="12" type="bold"/>
                                  </span>
                                  <span class="font-picker"
                                        :class="{ active: colorCollection.product_amount_font_style=='italic' }"
                                        @click="changeFontStyle('product_amount_font_style', 'italic')">
                                    <icon width="12" height="12" type="italic"/>
                                  </span>
                                </div>
                              </div>
                            </div>
                          </div>
                        </li>
                        <li class="advanced-menu-item advanced-menu-item-dropdown"
                            :class="{ active: isButtonsActive }">
                          <a @click.prevent="toggleMenu('buttons')">
                            <span>
                              <span>{{ $t('pages.channels.settings.color-customization.buttons') }}</span>
                              <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
                            </span>
                          </a>
                          <div class="advanced-menu-dropdown">
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.search') }}
                              </p>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="search-button-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.search_button_color }"
                                  />
                                  <b-popover
                                    target="search-button-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.search_button_color"
                                      @input="changeColorSchema('search_button_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="search-button-color"
                                    class="color-editor mb-0"
                                    type="text" id="search-button-color"
                                    v-model="colorCollection.search_button_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.normal-state') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="search-button-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.search_button_background_color }"
                                  />
                                  <b-popover
                                    target="search-button-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.search_button_background_color"
                                      @input="changeColorSchema('search_button_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="search-button-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="search-button-background-color"
                                    v-model="colorCollection.search_button_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.mouse-over') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="search-button-background-hover-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.search_button_background_hover_color }"
                                  />
                                  <b-popover
                                    target="search-button-background-hover-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.search_button_background_hover_color"
                                      @input="changeColorSchema('search_button_background_hover_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="search-button-background-hover-color"
                                    class="color-editor mb-0"
                                    type="text" id="search-button-background-hover-color"
                                    v-model="colorCollection.search_button_background_hover_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                            <div class="color-option">
                              <p class="color-title">
                                {{ $t('pages.channels.settings.color-customization.other-buttons') }}
                              </p>
                              <drop-down
                                id="other-buttons-background-type"
                                :disabled="updatePending"
                                v-model="colorCollection.other_buttons_background_type"
                                :items="typeList"
                              />
                              <p class="sub-title">
                                {{ $t('pages.channels.settings.color-customization.background-normal') }}
                              </p>
                              <div class="color-form" v-show="colorCollection.other_buttons_background_type=='flat'">
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_color }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_color"
                                      @input="changeColorSchema('other_buttons_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-color"
                                    v-model="colorCollection.other_buttons_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form"
                                   v-show="colorCollection.other_buttons_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.top-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-color-top"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_color_top }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-color-top"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_color_top"
                                      @input="changeColorSchema('other_buttons_background_color_top', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-color-top"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-color-top"
                                    v-model="colorCollection.other_buttons_background_color_top"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form"
                                   v-show="colorCollection.other_buttons_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.bottom-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-color-bottom"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_color_bottom }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-color-bottom"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_color_bottom"
                                      @input="changeColorSchema('other_buttons_background_color_bottom', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-color-bottom"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-color-bottom"
                                    v-model="colorCollection.other_buttons_background_color_bottom"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <p class="sub-title">
                                {{ $t('pages.channels.settings.color-customization.background-mouse') }}
                              </p>
                              <div class="color-form" v-show="colorCollection.other_buttons_background_type=='flat'">
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-hover-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_hover_color }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-hover-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_hover_color"
                                      @input="changeColorSchema('other_buttons_background_hover_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-hover-color"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-hover-color"
                                    v-model="colorCollection.other_buttons_background_hover_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form"
                                   v-show="colorCollection.other_buttons_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.top-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-hover-color-top"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_hover_color_top }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-hover-color-top"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_hover_color_top"
                                      @input="changeColorSchema('other_buttons_background_hover_color_top', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-hover-color-top"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-hover-color-top"
                                    v-model="colorCollection.other_buttons_background_hover_color_top"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form"
                                   v-show="colorCollection.other_buttons_background_type=='gradient'">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.bottom-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-background-hover-color-bottom"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_background_hover_color_bottom }"
                                  />
                                  <b-popover
                                    target="other-buttons-background-hover-color-bottom"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_background_hover_color_bottom"
                                      @input="changeColorSchema('other_buttons_background_hover_color_bottom', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-background-hover-color-bottom"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-background-hover-color-bottom"
                                    v-model="colorCollection.other_buttons_background_hover_color_bottom"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="other-buttons-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.other_buttons_color }"
                                  />
                                  <b-popover
                                    target="other-buttons-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.other_buttons_color"
                                      @input="changeColorSchema('other_buttons_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="other-buttons-color"
                                    class="color-editor mb-0"
                                    type="text" id="other-buttons-color"
                                    v-model="colorCollection.other_buttons_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </li>
                        <li class="advanced-menu-item advanced-menu-item-dropdown"
                            :class="{ active: isInputFieldsActive }">
                          <a @click.prevent="toggleMenu('inputFields')">
                            <span>
                              <span>{{ $t('pages.channels.settings.color-customization.input-fields') }}</span>
                              <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
                            </span>
                          </a>
                          <div class="advanced-menu-dropdown">
                            <div class="color-option">
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.background-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="input-background-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.input_background_color }"
                                  />
                                  <b-popover
                                    target="input-background-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.input_background_color"
                                      @input="changeColorSchema('input_background_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="input-background-color"
                                    class="color-editor mb-0"
                                    type="text" id="input-background-color"
                                    v-model="colorCollection.input_background_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.font-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="input-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.input_color }"
                                  />
                                  <b-popover
                                    target="input-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.input_color"
                                      @input="changeColorSchema('input_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="input-color"
                                    class="color-editor mb-0"
                                    type="text" id="input-color"
                                    v-model="colorCollection.input_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.error-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="input-error-border-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.input_error_border_color }"
                                  />
                                  <b-popover
                                    target="input-error-border-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.input_error_border_color"
                                      @input="changeColorSchema('input_error_border_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="input-error-border-color"
                                    class="color-editor mb-0"
                                    type="text" id="input-error-border-color"
                                    v-model="colorCollection.input_error_border_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                              <div class="color-form">
                                <p class="sub-title">
                                  {{ $t('pages.channels.settings.color-customization.success-color') }}
                                </p>
                                <div class="d-flex">
                                  <span
                                    id="input-success-border-color"
                                    class="color-picker"
                                    tabindex="-1"
                                    :style="{ background: colorCollection.input_success_border_color }"
                                  />
                                  <b-popover
                                    target="input-success-border-color"
                                    no-fade
                                    triggers="focus"
                                    placement="topright"
                                    container="body"
                                  >
                                    <chrome-picker
                                      :value="colorCollection.input_success_border_color"
                                      @input="changeColorSchema('input_success_border_color', $event)"
                                    />
                                  </b-popover>
                                  <ValidatedField
                                    :disabled="updatePending"
                                    name="input-success-border-color"
                                    class="color-editor mb-0"
                                    type="text" id="input-success-border-color"
                                    v-model="colorCollection.input_success_border_color"
                                    no-icon
                                    no-validate
                                    rules="required"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>
                        </li>
                      </ul>
                      <div class="advanced-buttons">
                        <button type="button" class="btn btn-primary" @click="updateColor"
                                :disabled="updatePending">
                          {{ $t('pages.channels.settings.btn-save') }}
                        </button>
                        <button type="button" class="btn btn-outline-primary" @click="resetColorCollection"
                                :disabled="updatePending">
                          {{ $t('pages.channels.settings.btn-reset') }}
                        </button>
                      </div>
                    </div>
                    <div class="main" :style="customStyle">
                      <div class="header row">
                        <div class="col-6 offset-3">
                          <img v-if="hotel.logo && colorCollection.hotel_logo_show"
                               :src="hotel.logo" alt="logo" class="logo">
                          <img v-else-if="colorCollection.hotel_logo_show"
                               src="/assets/images/no-image.jpg" alt="no-logo" class="logo">
                        </div>
                        <div class="col-3 d-flex">
                          <b-dropdown v-if="colorCollection.language_block_show"
                                      size="sm" toggle-tag="div" toggle-class="lang-menu" variant="link"
                                      class="header-lang-selector" no-caret>
                            <template #button-content>
                              <icon width="16" height="16" type="globe" class="globe-icon" />
                              {{ defaultLang }}
                              <icon stroke-width="1" width="12" height="7" type="arrow-down" class="arrow-icon"/>
                            </template>
                            <b-dropdown-item v-for="({ id, text }) in hotelLangList" :key="`lang-${id}`"
                                             @click.prevent="changeLang(id)">
                              {{ text }}</b-dropdown-item>
                          </b-dropdown>
                        </div>
                      </div>
                      <div class="header-search">
                        <div class="row">
                          <div class="col-xl-4 col-lg-6 col-md-12">
                            <label
                              for="promo-from"
                              class="text-sm check-label">
                              {{ $t('pages.reservations.headers.checkin') }}
                            </label>
                            <date-picker
                              id="promo-from" :value="checkIn" :min-date="today" grow="md-down"
                              :disabled="updatePending" :placeholder="$t('date.from-placeholder')"
                            />
                          </div>
                          <div class="col-xl-4 col-lg-6 col-md-12">
                            <label
                              for="promo-from"
                              class="text-sm check-label">
                              {{ $t('pages.reservations.headers.checkout') }}
                            </label>
                            <date-picker
                              id="promo-from" :value="checkOut" :min-date="today" grow="md-down"
                              :disabled="updatePending" :placeholder="$t('date.from-placeholder')"
                            />
                          </div>
                          <div class="col-xl-4 col-lg-6 col-md-12 search-container">
                            <button
                              class="btn btn-primary btn-search"
                              @click="show = !show">
                              {{ $t('pages.channels.settings.search') }}
                            </button>
                            <div class="pointer pointer-left"
                                 id="btn-color-picker"
                                 tabindex="1"
                                 v-show="!isAdvanced">
                              <div class="btn-icon"><icon height="9" width="9" type="pencil"/></div>
                            </div>
                            <b-popover
                              class="normal-color-picker"
                              target="btn-color-picker"
                              triggers="focus"
                              no-fade
                              placement="bottomleft"
                              container="body"
                            >
                              <tabs
                                :items="btnColorTabs"
                                v-model="btnColorTab"
                                with-content
                                @switch="seluniq = null"
                                class="group-color-picker"
                              >
                                <template #tab(normal)>
                                  <chrome-picker
                                    :value="colorCollection.search_button_background_color"
                                    @input="changeColorSchema('search_button_background_color', $event)"
                                  />
                                </template>
                                <template #tab(mouse)>
                                  <chrome-picker
                                    :value="colorCollection.search_button_background_hover_color"
                                    @input="changeColorSchema('search_button_background_hover_color', $event)"
                                  />
                                </template>
                                <template #tab(text)>
                                  <chrome-picker
                                    :value="colorCollection.search_button_color"
                                    @input="changeColorSchema('search_button_color', $event)"
                                  />
                                </template>
                              </tabs>
                            </b-popover>
                          </div>
                        </div>
                        <div v-if="colorCollection.promo_code_show" class="d-flex align-items-center mt-2">
                          <span class="text-sm promo-code mr-2">
                            <icon width="18" height="18" stroke-width="2" type="gift"/>
                          </span>
                          <span class="promo-code">
                            Have a promo code?
                          </span>
                        </div>
                      </div>
                      <div class="room-details container"
                           :class="{ 'pt-0': !isAdvanced }">
                        <div class="pointer pointer-top main-color-picker"
                             id="main-color-picker"
                             tabindex="1"
                             v-show="!isAdvanced">
                          <div class="btn-icon"><icon height="9" width="9" type="pencil"/></div>
                        </div>
                        <b-popover
                          class="normal-color-picker"
                          target="main-color-picker"
                          no-fade
                          triggers="focus"
                          placement="bottomleft"
                          container="body"
                        >
                          <tabs
                            :items="mainColorTabs"
                            v-model="mainColorTab"
                            with-content
                            @switch="seluniq = null"
                            class="group-color-picker"
                          >
                            <template #tab(main)>
                              <chrome-picker
                                :value="colorCollection.header_background_color"
                                @input="changeColorSchema('header_background_color', $event)"
                              />
                            </template>
                            <template #tab(text)>
                              <chrome-picker
                                :value="colorCollection.check_in_out_color"
                                @input="changeColorSchema('check_in_out_color', $event)"
                              />
                            </template>
                          </tabs>
                        </b-popover>
                        <div class="hotel-description">
                          <div class="row">
                            <div class="col-xl-5 col-lg-12">
                              <div class="rooms-details-img">
                                <img src="https://cms.cultuzz.com/cms_uploads//service/_img/bv/2018/246/AA/090946_5_copy.jpg" alt="">
                                <a class="text-xs" href="#">5 {{ $t('pages.channels.settings.pictures') }}</a>
                              </div>
                            </div>
                            <div class="col-xl-7 col-lg-12">
                              <div class="rooms-details-text">
                                <div class="title">
                                  <h3 :style="fontStyle(colorCollection.product_name_font_style)">
                                    Premier King Room with Spa
                                  </h3>
                                  <div class="pointer pointer-left"
                                       id="text-color-picker"
                                       tabindex="1"
                                       v-show="!isAdvanced">
                                    <div class="btn-icon"><icon height="9" width="9" type="pencil"/></div>
                                  </div>
                                  <b-popover
                                    class="normal-color-picker"
                                    target="text-color-picker"
                                    no-fade
                                    triggers="focus"
                                    placement="topleft"
                                    container="body"
                                  >
                                    <tabs
                                      :items="textColorTabs"
                                      v-model="textColorTab"
                                      with-content
                                      @switch="seluniq = null"
                                      class="group-color-picker"
                                    >
                                      <template #tab(text)>
                                        <chrome-picker
                                          :value="colorCollection.product_name_color"
                                          @input="changeColorSchema('product_name_color', $event)"
                                        />
                                      </template>
                                    </tabs>
                                  </b-popover>
                                </div>
                                <div class="description text-sm">
                                  The room price includes: accomodation, break-fast, SPA access
                                  (dry sauna, hammam with aromotherapy, Hot Tub) and free access
                                  to the locker room with heating for ski/snowboard boots. Free
                                  Wi-Fi Internet in all the rooms. Free parking (20 places). LCD TV
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="hotel-description">
                          <div class="row">
                            <div class="col-xl-5 col-lg-9 rate-details">
                              <h5 class="meal-included">
                                <icon width="12" height="12" stroke-width="2" type="restaurant"/>
                                {{ $t('pages.channels.settings.full-included') }}
                              </h5>
                              <h4 :style="fontStyle(colorCollection.product_note_font_style)">Third</h4>
                              <span class="rate-policy">
                                <span :style="fontStyle(colorCollection.product_name_font_style)">
                                  {{ $t('pages.channels.settings.rate-details') }}
                                </span>
                                &nbsp;|&nbsp;
                                <span :style="fontStyle(colorCollection.product_name_font_style)">
                                  {{ $t('pages.channels.settings.cancellation-policy') }}
                                </span>
                              </span>
                            </div>
                            <div class="col-xl-3 col-lg-4 persons">
                              <h5>{{ $t('pages.channels.settings.persons') }}</h5>
                              <div class="row">
                                <div class="col-6">
                                  <drop-down
                                    id="main-background-type"
                                    :disabled="updatePending"
                                    v-model="defaultAmount"
                                    :items="availableAmount"
                                  />
                                </div>
                                <div class="col-6 adults">
                                  <p>{{ $t('pages.channels.settings.adults') }}</p>
                                </div>
                              </div>
                              <p class="person-limit">{{ $t('pages.channels.settings.max-persons') }}</p>
                            </div>
                            <div class="col-xl-3 col-lg-8 price-room">
                              <h5>{{ $t('pages.channels.settings.price-per-room') }}</h5>
                              <p :style="fontStyle(colorCollection.product_amount_font_style)"
                                 class="product-price">EUR 412.00</p>
                            </div>
                            <div class="col-xl-1 col-lg-3 rooms-amount">
                              <h5>{{ $t('pages.channels.settings.rooms') }}</h5>
                              <drop-down
                                id="main-background-type"
                                :disabled="updatePending"
                                v-model="defaultRooms"
                                :items="availableRooms"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="footer">
                        <div class="row text-sm">
                          <div class="col-xl-4 col-lg-12 d-flex privacy-policy">
                            {{ $t('pages.channels.settings.legal-notice') }}&nbsp;|&nbsp;
                            {{ $t('pages.channels.settings.privacy-policy') }}
                          </div>
                          <div class="address col-xl-4 col-lg-12 text-center"
                               v-show="colorCollection.address_show">
                            <p>{{ hotelData.name }}</p>
                            <p>{{ hotelData.street }}, {{ hotelData.zip }} {{ hotelData.city }},
                              {{ countryName }}</p>
                            <p>{{ $t('pages.channels.settings.telephone') }}: {{ hotelData.tel }}</p>
                            <p>{{ $t('pages.channels.settings.email') }}: {{ hotelData.email }}</p>
                            <p>http://{{ hotelData.website }}</p>
                            <b-button link size="sm" class="show-on-map mt-1"
                                      v-show="colorCollection.show_on_map"
                                      :disabled="updatePending"
                                      target="_blank"
                                      :href="locationUrl">
                              <icon width="12" height="12" stroke-width="2" type="map-tag"/>
                              {{ $t('pages.channels.settings.show-map') }}
                            </b-button>
                          </div>
                          <div class="col-xl-4 col-lg-12 d-flex powered-by">
                            <div v-if="colorCollection.powered_by" class="d-flex">
                              <span class="mr-1">{{ $t('pages.channels.settings.powered-by') }}</span>
                              <img src="/assets/images/logo-full.svg" alt="">
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="panel position-relative panel-content my-40">
              <div class="list left-edge">
                <p class="head-line" :class="{ opened: languageOpened }" @click="languageOpened=!languageOpened">
                  <icon width="20" height="20" stroke-width="2" class="icon" type="language"/>
                  {{ $t('pages.channels.settings.language') }}
                  <icon width="12" height="7" stroke-width="2" class="icon-arrow" type="arrow-down"/>
                </p>
                <div>
                  <div class="default-language">
                    <p class="text-sm mb-2">{{ $t('pages.channels.settings.default-language') }}</p>
                    <drop-down
                      id="default-language"
                      :disabled="updatePending"
                      v-model="colorCollection.default_language"
                      :items="langList"
                    />
                  </div>
                  <div class="supported-languages">
                    <p class="text-sm mb-2 title">{{ $t('pages.channels.settings.supported-languages') }}</p>
                    <div v-for="(name, option) in $t('langs')"
                         :key="option" class="feature">
                      <switcher medium :checked="colorCollection.hotel_languages.includes(option)"
                                :disabled="updatePending" @change="updateLangs(option)" class="switcher" />
                      <p>{{name}}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="panel position-relative panel-content my-40">
              <div class="list left-edge">
                <p class="head-line" :class="{ opened: featuresOpened }" @click="featuresOpened=!featuresOpened">
                  <icon width="20" height="20" stroke-width="2" class="icon" type="list"/>
                  {{ $t('pages.channels.settings.additional-features.title') }}
                  <icon width="12" height="7" stroke-width="2" class="icon-arrow" type="arrow-down"/>
                </p>
                <div>
                  <div class="additional-features">
                    <div v-for="(name, option) in $t('pages.channels.settings.additional-features.features')"
                         :key="option" class="feature">
                      <switcher medium v-model="colorCollection[option]" :disabled="updatePending"
                                class="switcher" />
                      <p>{{name}}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="cell-buttons">
              <button type="button" class="btn btn-outline-primary" @click="resetColorCollection"
                      :disabled="updatePending">
                {{ $t('pages.channels.settings.btn-cancel') }}
              </button>
              <button type="button" class="btn btn-primary" @click="updateColor"
                      :disabled="updatePending">
                {{ $t('pages.channels.settings.btn-done') }}
              </button>
            </div>
          </div>
        </template>
        <template #tab(communication)>
          <div class="communication-block">
            <div class="panel position-relative panel-content my-40">
              <div class="list left-edge">
                <p class="head-line" :class="{ opened: emailOpened }" @click="emailOpened=!emailOpened">
                  {{ $t('pages.channels.communication.guest-email') }}
                  <icon width="12" height="7" stroke-width="2" class="icon-arrow" type="arrow-down"/>
                </p>
                <div>
                  <hr class="hrGroup">
                  <lang-selector v-model="lang" :valid="langsValid" ref="langSel" />
                  <div class="guest-email" v-for="code in langCodes" :key="code" v-show="lang === code">
                    <div class="d-flex justify-content-between">
                      <div class="logo-area">
                        <p class="text-sm">{{ $t('pages.channels.communication.logo-area') }}</p>
                        <p class="ft-10">{{ $t('pages.channels.communication.logo-size') }}</p>
                      </div>
                      <div class="date">
                        <p class="text-sm d-flex align-items-center">
                          {{ formatTime(today) }}<icon type="calendar" height="16" width="16" class="icon"/>
                        </p>
                      </div>
                    </div>
                    <div class="introduction">
                      <p class="text-sm">
                        {{ $t('pages.channels.communication.dear') }}
                        <span class="name">Oleksii Sverdlychenko,</span>
                      </p>
                      <ValidatedField type="richtext" name="introduction" :disabled="updatePending" no-icon
                                      :placeholder="$t('pages.channels.communication.introduction-placeholder')"
                                      :rules="rulesForEmail" @input="toggleLangValid($event, code)"
                                      v-model="emailCollection[code].introduction" class="email-textarea"
                                      v-show="!isPreview"/>
                      <div v-html="emailCollection[code].introduction" v-show="isPreview" class="email-preview"></div>
                    </div>
                    <div class="reservation">
                      <p class="text-md title">{{ $t('pages.channels.communication.reservation.title') }}</p>
                      <div class="reservation-data">
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.booking-number') }}</span>
                          <span>60114-15094050-15209180</span>
                        </div>
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.check-in') }}</span>
                          <span>19 Jan 2019</span>
                        </div>
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.check-out') }}</span>
                          <span>25 Jan 2019</span>
                        </div>
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.nights') }}</span>
                          <span>2</span>
                        </div>
                        <div class="data flex-column">
                          <div class="room">
                            <span class="title">{{ $t('pages.channels.communication.reservation.rooms') }}</span>
                            <span class="title">{{ $t('pages.channels.communication.reservation.room-price') }}</span>
                          </div>
                          <div class="room">
                            <span>1 x Double Room Standard incl. Breakfast</span>
                            <span>EUR 140.00</span>
                          </div>
                          <div class="room">
                            <span>1 x Double Room Standard incl. Breakfast</span>
                            <span>EUR 140.00</span>
                          </div>
                          <div class="room">
                            <span>1 x Double Room Standard incl. Breakfast</span>
                            <span>EUR 140.00</span>
                          </div>
                        </div>
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.total-rooms') }}</span>
                          <span>7</span>
                        </div>
                        <div class="data">
                          <span class="title">{{ $t('pages.channels.communication.reservation.total-persons') }}</span>
                          <span>13</span>
                        </div>
                      </div>
                    </div>
                    <div class="cancellation">
                      <p class="text-md title">{{ $t('pages.channels.communication.cancellation.title') }}</p>
                      <div class="cancellation-desc">
                        <span>{{ $t('pages.channels.communication.cancellation.description') }}</span>
                      </div>
                      <div class="price">
                        <p>EUR 420.00</p>
                        <span>{{ $t('pages.channels.communication.cancellation.total-price') }}</span>
                      </div>
                      <ValidatedField type="richtext" name="body" :disabled="updatePending" no-icon
                                      :placeholder="$t('pages.channels.communication.body-placeholder')"
                                      :rules="rulesForEmail" v-model="emailCollection[code].body"
                                      class="email-textarea" v-show="!isPreview" />
                      <div v-html="emailCollection[code].body" v-show="isPreview" class="email-preview"></div>
                      <div class="cancellation-info">
                        <p class="title text-md">
                          {{ $t('pages.channels.communication.cancellation.info-title') }}
                        </p>
                        <hr class="hrGroup">
                        <div class="info">
                          <icon width="10" height="10" type="diamond"/>
                          <p class="text-xs">{{ $t('pages.channels.communication.cancellation.to-cancel') }}</p>
                        </div>
                        <div class="info">
                          <icon width="10" height="10" type="diamond"/>
                          <p class="text-xs">
                            {{ $t('pages.channels.communication.cancellation.enter-booking') }}
                          </p>
                        </div>
                        <b-btn variant="primary" class="btn-cancel-booking" size="sm">
                          {{ $t('pages.channels.communication.cancellation.btn-cancel') }}
                        </b-btn>
                        <div class="cancel-desc">
                          <icon width="20" height="20" type="info"/>
                          <span>{{ $t('pages.channels.communication.cancellation.cancel-booking') }}</span>
                        </div>
                        <ValidatedField type="richtext" name="signature" :disabled="updatePending" no-icon
                                        :placeholder="$t('pages.channels.communication.signature-placeholder')"
                                        :rules="rulesForEmail" class="email-textarea" v-show="!isPreview"
                                        v-model="emailCollection[code].signature"/>
                        <div v-html="emailCollection[code].signature" v-show="isPreview" class="email-preview"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="cell-buttons">
              <button type="button" class="btn btn-outline-primary" @click="resetEmailCollection"
                      :disabled="updatePending">
                {{ $t('pages.channels.settings.btn-cancel') }}
              </button>
              <button type="button" class="btn btn-primary" @click="updateEmail"
                      :disabled="updatePending || !emailFormValid">
                {{ $t('pages.channels.settings.btn-done') }}
              </button>
              <button type="button" class="btn btn-secondary" @click="isPreview = !isPreview"
                      :disabled="updatePending">
                {{ isPreview ? $t('pages.channels.settings.btn-edit') : $t('pages.channels.settings.btn-preview') }}
              </button>
            </div>
          </div>
        </template>
        <template #tab(product)>
          <div class="product-block">
            <div class="product-top-section product-section">
              <div class="row">
                <div class="col-md-6 center-align">
                  <div class="product-top-section--typography">
                    <h1>{{ $t('pages.channels.product.top.header') }}</h1>
                    <p>{{ $t('pages.channels.product.top.description') }}</p>
                    <button :disabled="channelIsActive || pending || cstateUpdatePending"
                            @click="activateChannel()"
                            class="btn btn-secondary">{{ $t('pages.channels.product.top.btn-activate') }}</button>
                  </div>
                </div>
                <div class="col-md-6 d-md-block d-none center-align">
                  <div class="product-top-section--illustration">
                    <img src="/assets/images/product/header-calendar.png" alt="">
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-7">
                  <div class="product-top-section--info">
                    <h4>{{ $t('pages.channels.product.top.info') }}</h4>
                  </div>
                </div>
              </div>
            </div>
            <div class="product-features-section product-section">
              <div class="row">
                <div class="col-md-10">
                  <div class="product-features-section--item">
                    <icon width="100" height="100" type="calendar-circle-fill"/>
                    <p v-html="$t('pages.channels.product.features.calendar')"></p>
                  </div>
                </div>
                <div class="col-md-10">
                  <div class="product-features-section--item">
                    <icon width="100" height="100" type="note-circle-fill"/>
                    <p>{{ $t('pages.channels.product.features.booking') }}</p>
                  </div>
                </div>
                <div class="col-md-10">
                  <div class="product-features-section--item">
                    <icon width="100" height="100" type="code-circle-fill"/>
                    <p>{{ $t('pages.channels.product.features.commission') }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="product-try-section-head product-section">
              <div class="row">
                <div class="col-md-12">
                  <div class="product-try-section-head--text">
                    <h4>{{ $t('pages.channels.product.try.title') }}</h4>
                    <p v-html="$t('pages.channels.product.try.description')"></p>
                  </div>
                </div>
              </div>
            </div>
            <div class="product-try-section product-section">
              <div class="row">
                <div class="col-md-4 cell-date-picker">
                  <label class="text-xs mb-1" for="check-in">{{ $t('pages.channels.product.try.check-in') }}</label>
                  <date-picker v-model="checkIn" :min-date="today" id="check-in" />
                </div>
                <div class="col-md-4 cell-date-picker">
                  <label class="text-xs mb-1" for="check-out">{{ $t('pages.channels.product.try.check-out') }}</label>
                  <date-picker v-model="checkOut" :min-date="today" id="check-out" />
                </div>
                <div class="col-md-4 cell-buttons">
                  <b-button class="w-100" variant="secondary">
                    {{ $t('pages.channels.product.try.search') }}
                  </b-button>
                </div>
                <div class="col-12 promo-code">
                  <icon width="18" height="18" type="gift"/>
                  <a href="#">{{ $t('pages.channels.product.try.promo-code') }}</a>
                </div>
              </div>
            </div>
            <div class="product-details-section top product-section">
              <div class="row product-details-section--item">
                <div class="col-md-7 center-align">
                  <img src="/assets/images/product/detail-responsive.png" alt="Responsive">
                </div>
                <div class="col-md-5 center-align detail-text">
                  <div>
                    <h4>{{ $t('pages.channels.product.details.responsive.title') }}</h4>
                    <p v-html="$t('pages.channels.product.details.responsive.description.top')"></p>
                    <p>{{ $t('pages.channels.product.details.responsive.description.bottom') }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="product-details-section product-section">
              <div class="row flex-md-row flex-column-reverse product-details-section--item">
                <div class="col-md-6 center-align detail-text">
                  <div>
                    <h4>{{ $t('pages.channels.product.details.booking.title') }}</h4>
                    <p>{{ $t('pages.channels.product.details.booking.description') }}</p>
                  </div>
                </div>
                <div class="col-md-6 center-align">
                  <img src="/assets/images/product/detail-booking.png" alt="Booking">
                </div>
              </div>
            </div>
            <div class="product-details-section bottom product-section">
              <div class="row product-details-section--item">
                <div class="col-md-6 center-align">
                  <img src="/assets/images/product/detail-conversion.svg" alt="Conversion">
                </div>
                <div class="col-md-6 center-align detail-text">
                  <div>
                    <h4>{{ $t('pages.channels.product.details.conversion.title') }}</h4>
                    <p>{{ $t('pages.channels.product.details.conversion.description.top') }}</p>
                    <p>{{ $t('pages.channels.product.details.conversion.description.bottom') }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="product-details-section end product-section">
              <div class="row flex-md-row flex-column-reverse product-details-section--item">
                <div class="col-md-6 center-align detail-text">
                  <div>
                    <h4>{{ $t('pages.channels.product.details.integration.title') }}</h4>
                    <p v-html="$t('pages.channels.product.details.integration.description.top')"></p>
                    <p>{{ $t('pages.channels.product.details.integration.description.bottom') }}</p>
                  </div>
                </div>
                <div class="col-md-6 center-align">
                  <img src="/assets/images/product/detail-integration.png" alt="Integration">
                </div>
              </div>
            </div>
            <div class="product-footer-section product-section">
              <div class="row">
                <div class="col-12">
                  <div class="product-footer-section--head">
                    <h4>{{ $t('pages.channels.product.footer.title') }}</h4>
                  </div>
                </div>
                <div class="col-12">
                  <div class="product-footer-section--actions">
                    <button :disabled="channelIsActive || pending || cstateUpdatePending"  @click="activateChannel()"
                            class="btn btn-secondary">{{ $t('pages.channels.product.footer.btn-activate') }}</button>
                    <button  @click="askAQuestion()"
                             class="btn btn-outline-light">{{ $t('pages.channels.product.footer.btn-ask') }}</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </tabs>

      <b-alert v-if="channelIsInvalid && !updatePending" variant="danger" show>
        <h4 class="alert-heading">{{ $t('error') }}</h4>
        <p class="mb-0">{{ $t('pages.channels.connect.error-invalid-property-data') }}</p>
      </b-alert>
      <b-alert v-if="pmsError" variant="danger" show>
        <h4 class="alert-heading">{{ $t('error') }}</h4>
        <p class="mb-0">{{ pmsError.response ? pmsError.response.data.message : pmsError }}</p>
      </b-alert>
    </div>
  </div>
</template>

<script>
  import moment from 'moment';
  import {
    mapActions, mapGetters, mapState, mapMutations,
  } from 'vuex';
  import { channelUpdateTypes, langCodes } from '@/shared';
  import { HttpError, PMSError, ValidationError } from '@/errors';
  import PushChannelInfoModal from '@/components/PushChannelInfoModal.vue';
  import NonEasyMappingRatePlansModal from '@/components/NonEasyMappingRatePlansModal.vue';
  import { Chrome } from 'vue-color';

  export default {
    name: 'Channel',
    components: {
      PushChannelInfoModal,
      NonEasyMappingRatePlansModal,
      'chrome-picker': Chrome,
    },
    data: () => ({
      period: {
        number: '',
        unit: 'd',
        until: null,
      },
      cmap: {
        selected: [],
        open: [],
        pselected: [],
      },
      id: null,
      tab: null, // 'connected',
      btnColorTab: 'normal',
      mainColorTab: 'main',
      textColorTab: 'text',

      seluniq: null,

      umap: {
        rooms: [],
        plans: [],
      },

      promoFilter: { promo: '', contract: '' },
      promoAll: { promo: true, contract: true },
      promo: {
        mode: 'promo',
        plans: [],
        discount: {
          value: '',
          mode: 'percent',
        },
      },
      today: moment(),
      colorOpened: false,
      languageOpened: false,
      featuresOpened: false,
      emailOpened: false,
      colorCollection: [],
      emailCollection: [],
      lang: 'en',
      langsValid: [],
      isAdvanced: false,
      advancedMenu: {
        backgrounds: false,
        fonts: false,
        buttons: false,
        inputFields: false,
      },
      defaultLang: 'en',
      hotelData: null,
      defaultRooms: 1,
      defaultAmount: 1,
      checkIn: moment(),
      checkOut: moment().add(1, 'day'),
      isPreview: false,
    }),
    // async created() {
    //   await this.fetchData(true);
    // },
    mounted() {
      this.setChannelId(this.$route.params.id);
    },
    watch: {
      colors() {
        this.colorCollection = JSON.parse(JSON.stringify(this.colors));
      },
      email() {
        this.emailCollection = JSON.parse(JSON.stringify(this.email));
      },
      $route() {
        this.setChannelId(this.$route.params.id);
      },
    },
    computed: {
      ...mapGetters('data', ['countries']),
      ...mapGetters('channel', ['loaded', 'channel', 'ctypes', 'cplans', 'mapped', 'rooms', 'plans', 'settingsHotelId', 'colors', 'email']),
      ...mapState('channel', ['error', 'pmsError', 'pending', 'updatePending', 'updateError']),
      ...mapState('channels', { cstateUpdatePending: (state) => state.updatePending }),
      ...mapGetters('user', ['hotelID', 'currency', 'engineURL', 'hotel']),

      langCodes: () => langCodes,
      tabs() {
        if (this.channel.type === 'push') {
          const tabs = [
            { id: 'connected', title: this.$t('pages.channels.connect.tab-connected') },
          ];
          if (this.pendingCTypes.length === 0) {
            tabs.push(
              { id: 'pending', title: this.$t('pages.channels.connect.tab-pending') },
            );
          } else {
            tabs.unshift(
              { id: 'pending', title: this.$t('pages.channels.connect.tab-pending') },
            );
          }
          return tabs;
        }
        const tabs = [
          { id: 'mapping', title: this.$t('pages.channels.headers.mapping') },
        ];
        if (this.channel.default) {
          tabs.push(
            { id: 'urls', title: this.$t('pages.channels.tabs.urls') },
            { id: 'promos', title: this.$t('pages.channels.tabs.promos') },
            { id: 'settings', title: this.$t('pages.channels.tabs.settings') },
            { id: 'communication', title: this.$t('pages.channels.tabs.communication') },
            { id: 'product', title: this.$t('pages.channels.tabs.product') },
          );
        }
        return tabs;
      },
      btnColorTabs() {
        const tabs = [
          { id: 'normal', title: this.$t('pages.channels.settings.normal') },
          { id: 'mouse', title: this.$t('pages.channels.settings.mouse') },
          { id: 'text', title: this.$t('pages.channels.settings.text') },
        ];
        return tabs;
      },
      mainColorTabs() {
        const tabs = [
          { id: 'main', title: this.$t('pages.channels.settings.main') },
          { id: 'text', title: this.$t('pages.channels.settings.text') },
        ];
        return tabs;
      },
      textColorTabs() {
        const tabs = [
          { id: 'text', title: this.$t('pages.channels.settings.text') },
        ];
        return tabs;
      },
      channelName() {
        if (!this.loaded) return '...';
        return this.channel.name;
      },
      channelIsValid() {
        return this.loaded && !this.channel.invalid;
      },
      channelIsInvalid() {
        return this.loaded && this.channel.invalid;
      },
      periodValue() {
        const { period } = this.channel;
        return this.$tc(`pages.channels.connect.time-units-values.${period.unit}`, period.number);
      },
      endDateValue() {
        const { period } = this.channel;
        return moment(period.until).format(this.$t('pages.channels.connect.transmission-format'));
      },
      fieldsValues() {
        const { values = {}, fields = [] } = this.channel;
        return fields.map(({ key, title, subtype }) => ({
          key,
          title,
          subtype,
          value: values[key],
        })).filter(({ value, subtype }) => (value != null && subtype !== 'password'));
      },
      notEasyMappingFieldsValues() {
        const { notEasyMappingFields = [] } = this.channel;
        return notEasyMappingFields.map(({ key, title, subtype }) => ({
          key,
          title,
          subtype,
        })).filter(({ subtype }) => (subtype !== 'password'));
      },
      updateTypes: () => channelUpdateTypes,
      pendingCTypes() {
        const { ctypes } = this;
        return Object.keys(ctypes)
          .map((typeid) => ({
            typeid,
            name: ctypes[typeid],
            plans: this.pendingRatesOfType(typeid),
          }))
          .filter(({ plans }) => plans.length);
      },
      pendingRooms() {
        const { rooms } = this;
        return rooms
          .map((room) => ({
            ...room,
            rates: this.pendingPlansOfRoom(room.id),
          }))
          .filter(({ rates }) => rates.length);
      },
      hasPlans() {
        return this.plans.length > 0;
      },
      hasMappedPlans() {
        return Object.keys(this.mapped).length > 0;
      },
      mappedPlans() {
        if (this.isPullChannel) return [];
        return Object.keys(this.mapped).map((id) => {
          const { uniq, mode } = this.mapped[id];
          const cplan = this.cplans ? this.cplans.find((plan) => plan.uniq === uniq) : [];
          const ctype = cplan ? this.ctypes[cplan.typeid] : null;
          // eslint-disable-next-line radix
          const plan = this.plans.find((rate) => parseInt(rate.id) === parseInt(id));
          // eslint-disable-next-line no-shadow
          const room = this.rooms.find(({ id }) => id === plan.room);
          return {
            uniq, cplan, plan, ctype, room, mode,
          };
        });
      },
      mappedMyPlans() {
        if (!this.isPullChannel) return [];
        return this.plans.map((plan) => {
          const room = this.rooms.find(({ id }) => id === plan.room);
          return {
            plan, room,
          };
        }).sort((a, b) => {
          if (a.room.text > b.room.text) {
            return 1;
          } if (b.room.text > a.room.text) {
            return -1;
          }
          return 0;
        });
      },
      mappedMyPlansForSelector() {
        const { mapped } = this;
        if (!this.isPullChannel || !this.channel.default || !mapped.length) return [];
        return this.plans.filter(({ id }) => mapped.includes(JSON.stringify(id)));
      },
      isAllMySelected() {
        return this.plans.length && this.cmap.pselected.length === this.plans.length;
      },
      isPullChannel() {
        return this.channel.type === 'pull';
      },
      isEasyMapping() {
        return this.channel.easyMapping === 1;
      },
      hasPullChanges() {
        return this.pullChangesCount > 0;
      },
      pullChangesCount() {
        const add = this.cmap.pselected.filter((id) => !this.mapped.map((x) => parseInt(x, 10)).includes(id));
        const del = this.mapped.map((x) => parseInt(x, 10)).filter((id) => !this.cmap.pselected.includes(id));
        return add.length + del.length;
      },
      urlMain() {
        return `${this.engineURL}&hotelcode=${this.hotelID}`;
      },
      urlCustom() {
        const { plans, rooms } = this.umap;
        if (!plans.length && !rooms.length) return '';
        const pc = plans.length ? `&pc=${plans.join(',')}` : '';
        const rc = rooms.length ? `&rc=${rooms.join(',')}` : '';
        return `${this.engineURL}&hotelcode=${this.hotelID}${pc}${rc}`;
      },
      promoCreatePlans() {
        return this.plans.filter(({ promo }) => promo == null);
      },
      promoModalTitle() {
        const {
          id, code, outdated, mode,
        } = this.promo;
        if (id == null) {
          return this.$t(`pages.channels.promo.modal.title-add-${mode}`);
        }
        if (outdated) {
          return this.$t(`pages.channels.promo.modal.title-view-${mode}`, { code });
        }
        return this.$t(`pages.channels.promo.modal.title-edit-${mode}`, { code });
      },
      promoOkTitle() {
        const { id, outdated } = this.promo;
        if (id == null) {
          return this.$t('buttons.save');
        }
        if (outdated) {
          return this.$t('buttons.close');
        }
        return this.$t('buttons.update');
      },
      promoFormValid() {
        if (this.promo.outdated) return true;
        const { promoForm } = this.$refs;
        if (promoForm == null) return false;
        const { from, until, plans } = this.promo;
        return promoForm.flags.valid && from != null && until != null
          && (this.promoIsEdit || (plans != null && plans.length > 0));
      },
      promoIsEdit() {
        return this.promo.id != null;
      },
      langList() {
        const langs = [];
        Object.keys(this.$t('langs')).forEach((key) => {
          langs.push({ id: key, text: this.$t('langs')[key] });
        });
        return langs;
      },
      hotelLangList() {
        const langs = [];
        const availableLangs = this.colorCollection.hotel_languages.length ? this.colorCollection.hotel_languages : ['en', 'de'];
        availableLangs.forEach((code) => {
          langs.push({ id: code, text: this.$t('langs')[code] });
        });
        return langs;
      },
      typeList() {
        const types = [];
        Object.keys(this.$t('pages.channels.settings.color-customization.background-type')).forEach((key) => {
          types.push({ id: key, text: this.$t('pages.channels.settings.color-customization.background-type')[key] });
        });
        return types;
      },
      availableAmount() {
        const amount = [];
        for (let i = 1; i <= 3; i += 1) {
          amount.push({ id: i, text: i });
        }
        return amount;
      },
      availableRooms() {
        const amount = [];
        for (let i = 1; i <= 10; i += 1) {
          amount.push({ id: i, text: i });
        }
        return amount;
      },
      customStyle() {
        const styleCollection = {
          '--lang-selector-style': this.colorCollection.template_background_color,
          '--input-background-color': this.colorCollection.input_background_color,
          '--button-color': this.colorCollection.search_button_color,
          '--button-background-color': this.colorCollection.search_button_background_color,
          '--button-background-color--hover': this.colorCollection.search_button_background_hover_color,
          '--product-background-color': this.colorCollection.product_background_type === 'flat' ? this.colorCollection.product_background_color_top : `linear-gradient(${this.colorCollection.product_background_color_top}, ${this.colorCollection.product_background_color_bottom})`,
          '--header-background-color': this.colorCollection.header_background_type === 'flat' ? this.colorCollection.header_background_color : `linear-gradient(${this.colorCollection.header_background_color_top}, ${this.colorCollection.header_background_color_bottom})`,
          '--template-background-color': this.colorCollection.template_background_type === 'flat' ? this.colorCollection.template_background_color : `linear-gradient(${this.colorCollection.template_background_color_top}, ${this.colorCollection.template_background_color_bottom})`,
          '--check-in-out-color': this.colorCollection.check_in_out_color,
          '--language-block-color': this.colorCollection.language_block_color,
          '--date-background-color': this.colorCollection.date_background_color,
          '--date-color': this.colorCollection.date_color,
          '--product-name-color': this.colorCollection.product_name_color,
          '--meals-included-colour': this.colorCollection.meals_included_colour,
          '--meals-excluded-colour': this.colorCollection.meals_excluded_colour,
          '--product-note-color': this.colorCollection.product_note_color,
          '--product-amount-color': this.colorCollection.product_amount_color,
        };
        return styleCollection;
      },
      edgeEmailLang() {
        return this.langsValid.includes('en');
      },
      emailFormFilled() {
        const status = [];
        this.langsValid.forEach((code) => {
          status.push(!!this.emailCollection[code].body
            && !!this.emailCollection[code].introduction
            && !!this.emailCollection[code].signature);
        });
        const isValid = (arr) => arr.every((v) => v === true);
        return isValid(status);
      },
      emailFormValid() {
        return this.edgeEmailLang && this.emailFormFilled;
      },
      isBackgroundsActive() {
        return this.advancedMenu.backgrounds;
      },
      isfontsActive() {
        return this.advancedMenu.fonts;
      },
      isButtonsActive() {
        return this.advancedMenu.buttons;
      },
      isInputFieldsActive() {
        return this.advancedMenu.inputFields;
      },
      countryName() {
        let country = '';
        Object.keys(this.countries).forEach((key) => {
          if (this.countries[key].code === this.hotelData.country) {
            country = this.countries[key].name;
          }
        });
        return country;
      },
      locationUrl() {
        return `https://www.google.com/maps/place/${this.hotelData.latitude},${this.hotelData.longitude}`;
      },
      channelIsActive() {
        return this.channel.status !== 'inactive';
      },
      rulesForEmail() {
        const rules = {};
        rules.required = true;
        return rules;
      },
    },
    methods: {
      ...mapActions('channel', [
        'fetchData', 'connectRatePlans', 'disconnectRatePlan', 'channelMappings',
        'updateChannelData', 'updatePlanConnection', 'updateRatePlansData',
        'createContract', 'updateContract', 'deleteContract', 'fetchHotel', 'fetchLanguages',
        'fetchColor', 'saveColor', 'fetchEmail', 'saveEmail',
      ]),
      ...mapActions('user', ['getHotel']),
      ...mapActions('data', ['fetchCountries']),
      ...mapActions('channels', ['channelState']),
      ...mapMutations('channel', ['clearErrors']),

      toggleMyAll(val) {
        if (!val) {
          this.cmap.pselected = [];
        } else {
          this.cmap.pselected = this.plans.map(({ id }) => id);
        }
      },
      async setChannelId(id) {
        this.id = id;
        try {
          await Promise.allSettled([
            this.fetchHotel(this.hotelID),
            this.fetchData({ id, force: true }),
            this.fetchLanguages(),
            this.fetchCountries(),
            this.getHotel().then((data) => {
              this.hotelData = data;
              return true;
            }),
          ]);
          await Promise.allSettled([
            this.fetchColor(this.settingsHotelId),
            this.fetchEmail(this.settingsHotelId),
          ]);
        } catch (e) {
          if (e instanceof HttpError && e.errorCode === 400) {
            await this.$router.replace({ name: 'channels' });
            return;
          }
        }
        this.freshData();
      },
      freshData() {
        if (this.channel.type === 'push') {
          this.tab = (this.pendingCTypes.length === 0) ? 'connected' : 'pending';
        } else {
          this.colorCollection = this.colors ? JSON.parse(JSON.stringify(this.colors)) : this.colors;
          this.emailCollection = JSON.parse(JSON.stringify(this.email));
          this.cmap.pselected = [...this.mapped].map((x) => parseInt(x, 10));
          this.tab = 'mapping';
        }

        Object.keys(this.emailCollection).forEach((code) => {
          this.toggleLangValid(this.emailCollection[code].introduction, code);
          const signature = `${this.$t('pages.channels.communication.best-regards')}<br>${this.hotelData.name},<br>${this.hotelData.street}<br>${this.hotelData.city}, ${this.hotelData.zip}<br>${this.countryName}.<br>${this.$t('addr.phone')}: ${this.hotelData.tel}`;
          if (!this.emailCollection[code].signature) {
            this.emailCollection[code].signature = signature;
          }
        });
      },
      copyMainUrl() {
        this.$refs.urlMain.select();
        document.execCommand('copy');
        navigator.clipboard.writeText(this.urlMain);
        const tip = this.$refs.urlMainTip;
        tip.classList.add('visible');
        setTimeout(() => tip.classList.remove('visible'), 1000);
      },
      copyCustomUrl() {
        this.$refs.urlCustom.select();
        document.execCommand('copy');
        navigator.clipboard.writeText(this.urlCustom);
        const tip = this.$refs.urlCustomTip;
        tip.classList.add('visible');
        setTimeout(() => tip.classList.remove('visible'), 1000);
      },
      isActiveCRate(uniq) {
        return this.seluniq === uniq;
      },
      setCRate(uniq) {
        if (this.updatePending) return;
        if (this.isActiveCRate(uniq)) {
          this.seluniq = null;
        } else {
          this.seluniq = uniq;
        }
      },
      pendingRatesOfType(typeid) {
        const { mapped } = this;
        const selected = this.cmap.selected.map(({ uniq }) => uniq);
        const del = Object.keys(mapped).map((id) => mapped[id].uniq);
        return this.cplans.filter((plan) => (
          plan.typeid === typeid
          && !del.includes(plan.uniq)
          && !selected.includes(plan.uniq)
        ));
      },
      pendingPlansOfRoom(pid) {
        const { mapped } = this;
        const selected = this.cmap.selected.map(({ plan: { id } }) => id);
        const del = Object.keys(mapped);
        return this.plans.filter((plan) => (
          plan.room === pid
          && !del.includes(plan.id)
          && !selected.includes(plan.id)
        ));
      },
      linkPlan(plan) {
        if (this.updatePending) return;
        const uniq = this.seluniq;
        const cplan = this.cplans.find((rate) => rate.uniq === uniq);
        const ctype = this.ctypes[cplan.typeid];
        const ptype = this.rooms.find(({ id }) => id === plan.room);
        this.cmap.selected.push({
          plan,
          ptype,
          cplan,
          ctype,
          uniq,
          mode: 0,
        });
        this.seluniq = null;
      },
      unlinkPlan(uniq) {
        const idx = this.cmap.selected.findIndex((plan) => plan.uniq === uniq);
        if (idx !== -1) {
          this.cmap.selected.splice(idx, 1);
        }
      },
      updateTypeText(mode) {
        return this.$t(`channel-update-types.${mode}`);
      },
      changeUpdateType(uniq, mode) {
        const sel = this.cmap.selected.find((s) => s.uniq === uniq);
        if (sel != null) sel.mode = mode;
      },
      formatDate(row, field = 'dt', emptyField = 'enabled') {
        return row[field] && (!emptyField || row[emptyField]) ? moment(row[field]).format('D MMM YYYY') : '';
      },
      promoItems(mode) {
        const { contractor } = this.channel;
        let codes = contractor == null || contractor.codes == null ? false : contractor.codes;
        if (codes === false) return [];
        codes = codes.filter((i) => i.mode === mode);
        const filter = this.promoFilter[mode].trim().toLowerCase();
        if (!filter && this.promoAll[mode]) return codes;
        if (filter) {
          codes = codes.filter(({ code, name }) => (
            code.toLowerCase().includes(filter) || name.toLowerCase().includes(filter)
          ));
        }
        if (!this.promoAll[mode]) {
          codes = codes.filter(({ outdated }) => !outdated);
        }
        return codes;
      },
      promoDiscount(contract, addsign = false) {
        const rate = this.plans.find(({ promo }) => promo === contract.code);
        if (rate == null) return addsign ? '—' : {};
        const d = { ...rate.price.stdcalc.reduction };
        if (!addsign) return d;
        const { value, mode } = d;
        return `${value}${mode === 'percent' ? '%' : this.currency.symbol}`;
      },
      promoRates(contract) {
        return this.plans
          .filter(({ promo }) => promo === contract.code)
          .map(({ id, text, room }) => {
            const { id: rid, text: rtext } = this.rooms.find(({ pid }) => pid === room);
            return {
              id, text, rid, rtext,
            };
          });
      },
      promoFromChanged(dt) {
        if (dt.isAfter(this.promo.until, 'date')) {
          this.promo.until = moment(dt);
        }
        this.$nextTick(() => {
          this.$refs.promoUntil.$el.focus();
        });
      },
      promoUntilChanged(dt) {
        if (dt.isBefore(this.promo.from, 'date')) {
          this.promo.from = moment(dt);
        }
      },
      modalScroll(ev) {
        const modal = ev.target;
        this.$nextTick(() => {
          if (modal != null) modal.scrollTop = 0;
        });
        this.clearErrors();
      },
      resetPromoForm(mode, reset = true) {
        this.$set(this.promo, 'plans', []);
        this.promo = {
          mode,
          name: '',
          code: '',
          discount: {
            value: '',
            mode: 'percent',
          },
          from: moment(),
          until: moment().add(1, 'month'),
          plans: [],
        };
        if (reset && this.$refs.promoForm != null) {
          this.$refs.promoForm.reset();
        }
      },
      openCreatePromo(mode) {
        this.resetPromoForm(mode);
        this.$nextTick(this.$refs.promoModal.show);
      },
      editPromo(contract) {
        this.resetPromoForm(contract.mode, false);
        const discount = this.promoDiscount(contract);
        this.promo = {
          ...this.promo,
          ...JSON.parse(JSON.stringify(contract)),
          discount,
        };
        this.$nextTick(() => {
          this.$refs.promoForm.reset();
          this.$refs.promoModal.show();
        });
      },
      async deletePromo(promo) {
        const { id } = this.channel;
        try {
          await this.deleteContract({ id, promo });
        } catch (error) {
          this.$toastr.e(error.message, this.$t('error'));
        }
      },
      async processPromoForm() {
        this.$refs.promoForm.reset();
        const { promo } = this;
        if (promo.outdated) {
          this.$nextTick(this.$refs.promoModal.hide);
          return;
        }
        ['from', 'until'].forEach((k) => {
          const v = promo[k];
          if (moment.isMoment(v)) {
            promo[k] = v.format('YYYY-MM-DD');
          }
        });

        const { id } = this.channel;
        try {
          if (promo.id != null) {
            await this.updateContract({ id, promo });
          } else {
            await this.createContract({ id, promo });
          }
          this.$refs.promoModal.hide();
        } catch (error) {
          if (!(error instanceof ValidationError)) {
            this.$toastr.e(error.message, this.$t('error'));
          }
        }
      },
      isOpen(id) {
        return this.cmap.open.indexOf(id) === -1;
      },
      toggleOpen(id) {
        const idx = this.cmap.open.indexOf(id);
        if (idx !== -1) {
          this.cmap.open.splice(idx, 1);
        } else {
          this.cmap.open.push(id);
        }
      },
      openUpdateModal() {
        const {
          id, fields, values, period, name,
        } = this.channel;
        this.$refs.updateModal.show(id, fields, name, { ...values, period: { ...period } });
      },
      openRatePlansModal() {
        const {
          id, notEasyMappingFields, name,
        } = this.channel;
        this.$refs.updateRatePlansModal.show(id, notEasyMappingFields, name);
      },
      async updateRatePlans({ id, values }) {
        try {
          await this.updateRatePlansData({ id, payload: values });
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
          this.$refs.updateRatePlansModal.hide();
        } catch (err) {
          if (err instanceof PMSError) {
            this.$toastr.e(err.message, this.$t('error'));
          }
        }
      },
      async updateChannel({ id, values }) {
        try {
          await this.updateChannelData({ id, payload: values });
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
          if (!this.invalid) {
            this.freshData();
          }
          this.$refs.updateModal.hide();
        } catch (err) {
          if (err instanceof PMSError) {
            this.$toastr.e(err.message, this.$t('error'));
          }
        }
      },
      async connectPlans() {
        try {
          await this.connectRatePlans({ id: this.id, list: this.cmap.selected });
          this.cmap.selected = [];
          this.$toastr.s({
            title: this.$t('pages.channels.connect.alert-connected'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      async disconnectPlan(item) {
        try {
          await this.disconnectRatePlan({ id: this.id, list: [item] });
          this.$toastr.s({
            title: this.$t('pages.channels.connect.alert-disconnected'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      async updateConnection(item, updates) {
        try {
          await this.updatePlanConnection({ id: this.id, room: item, updates });
          this.$toastr.s({
            title: this.$t('pages.channels.connect.alert-updated'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      async activateChannel() {
        if (this.channel.status === 'inactive') {
          await Promise.allSettled([
            this.processActivateChannel(),
            this.fetchData({ id: this.id }),
          ]);
          this.$toastr.s({
            title: this.$t('pages.channels.connect.alert-activated'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        }
      },
      async processActivateChannel() {
        const { id } = this;
        const mode = 'start';
        try {
          await this.channelState({ id, mode });
        } catch (e) {
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      askAQuestion() {
        const feedbackFormButton = document.getElementById('feedback-form');
        if (feedbackFormButton) {
          feedbackFormButton.click();
        }
      },
      async updatePullMappings() {
        const { pselected } = this.cmap;
        const pselect = pselected.map((x) => parseInt(x, 10));
        const rooms = this.plans.map(({ id }) => ({ id, inv: pselect.includes(id) }));
        const { id } = this;
        if (this.channel.status === 'inactive') {
          await this.processActivateChannel();
        }
        try {
          await this.channelMappings({ id, rooms });
          this.$toastr.s({
            title: this.$t('pages.channels.connect.alert-updated'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
          this.cmap.pselected = [...this.mapped];
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      updateLangs(lang) {
        const index = this.colorCollection.hotel_languages.indexOf(lang);
        if (index > -1) {
          this.colorCollection.hotel_languages.splice(index, 1);
        } else {
          this.colorCollection.hotel_languages.push(lang);
        }
      },
      changeColorSchema(type, value) {
        if (value == null) return;
        const { hex } = value;
        this.colorCollection[type] = hex;
      },
      async updateColor() {
        try {
          await this.saveColor(this.colorCollection);
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      async updateEmail() {
        try {
          await this.saveEmail(this.emailCollection);
          this.$toastr.s({
            title: this.$t('pages.masterdata.alert-saved'),
            msg: '',
            timeout: 3000,
            progressbar: false,
          });
        } catch (e) {
          if (e instanceof PMSError) {
            this.$toastr.e(e.message, this.$t('error'));
          }
          // eslint-disable-next-line no-console
          console.error(e.message);
        }
      },
      formatTime(date) {
        const fdate = this.$t('pages.channels.communication.date-format');
        return moment.utc(date).format(`${fdate}`);
      },
      toggleLangValid(val, code) {
        const idx = this.langsValid.indexOf(code);
        if (val != null && `${val}`.trim()) {
          if (idx === -1) {
            this.langsValid.push(code);
          }
        } else if (idx !== -1) {
          this.langsValid.splice(idx, 1);
        }
      },
      toggleMenu(name = null) {
        Object.keys(this.advancedMenu).forEach((k) => {
          this.advancedMenu[k] = k === name ? !this.advancedMenu[k] : false;
        });
      },
      changeFontStyle(type, val) {
        const defaultStyle = 'normal';
        if (this.colorCollection[type] !== val) {
          this.colorCollection[type] = val;
        } else {
          this.colorCollection[type] = defaultStyle;
        }
      },
      changeLang(code) {
        this.defaultLang = code;
      },
      fontStyle(type) {
        switch (type) {
          case 'bold':
            return 'font-weight: bold';
          case 'italic':
            return 'font-style: italic';
          default:
            return '';
        }
      },
      resetColorCollection() {
        this.colorCollection = JSON.parse(JSON.stringify(this.colors));
      },
      resetEmailCollection() {
        this.emailCollection = JSON.parse(JSON.stringify(this.email));
      },
    },
  };
</script>
