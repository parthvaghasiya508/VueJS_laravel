<template>
  <fragment>
    <nav class="navbar-wrapper navbar--mobile" :class="{ 'app-loading': !hostLoaded }">
      <div class="navbar d-md-none">
        <a href="#" class="position-absolute d-lg-none burger-button hamburger"
           @click.prevent="mobileShown = !mobileShown" :class="{ collapsed: mobileShown, 'd-none': !loggedIn }">
          <svg width="25" height="16" viewBox="0 0 25 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="25" height="2" fill="currentColor"/>
            <rect y="7" width="25" height="2" fill="currentColor"/>
            <rect y="14" width="25" height="2" fill="currentColor"/>
          </svg>
        </a>
        <router-link class="navbar-brand p-0 mx-auto" :to="{ name:'home' }">
          <svg class="logo" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M23.8421 6.79683C23.0149 6.07761 21.6484 4.92685 20.4257 3.84801C19.9223 3.38051 19.4548 3.12878 18.9513 3.12878C17.9804 3.12878 16.9375 3.95589 16.1104 4.71108C17.4769 5.8978 20.2459 8.27125 21.6125 9.45797C22.5115 10.2132 22.5834 10.7885 22.5834 11.4358V17.5133C22.5834 18.9517 21.3967 20.1385 19.9582 20.1385H10.3206C8.88217 20.1385 7.69545 18.9517 7.69545 17.5133V12.8743C7.19199 13.2699 6.54469 13.5216 5.82546 13.5216C5.25008 13.5216 4.71066 13.3418 4.24316 13.0901V17.4773C4.24316 20.8217 6.97622 23.5548 10.3206 23.5548H19.9223C23.2667 23.5548 25.9997 20.8217 25.9997 17.4773V11.3999C25.9997 10.1053 25.748 8.41509 23.8421 6.79683Z"
                  class="shape"/>
            <path d="M13.4495 4.53112C13.5214 4.4592 13.6293 4.35131 13.7732 4.20747C14.5643 3.38036 15.8949 1.97787 17.4772 1.43845C17.3333 1.29461 17.2254 1.15076 17.0816 1.00692L16.9737 0.934993C16.3624 0.395574 15.6072 0 14.5643 0C13.1259 0 12.4066 0.683264 11.5436 1.5823L5.71784 7.51591C5.60996 7.40802 5.46611 7.30014 5.35823 7.19225C5.28631 7.12033 5.21438 7.04841 5.14246 7.01245C5.1065 6.97649 5.07054 6.94053 5.03458 6.90456C3.59613 5.71784 1.7621 5.60996 0 7.30014C1.33057 8.23513 3.12863 9.78147 4.56708 11.184C4.89073 11.5076 5.32227 11.6874 5.78976 11.6874C6.25726 11.6874 6.6888 11.5076 7.01245 11.184L13.4495 4.53112Z"
                  fill="#F7981C"/>
          </svg>
        </router-link>
        <div class="profile">
          <b-dropdown v-if="asLoggedIn && hasHotels" size="sm" toggle-tag="div"
                      toggle-class="navbar-id-dropdown profile-menu" menu-class="w-100"
                      variant="link" boundary="viewport" class="profile-menu--mobile" no-caret>
            <template #button-content>
              <span>ID {{ hotelID }}</span>
              <icon v-if="multipleHotels" stroke-width="2" width="12" height="7" type="arrow-down" class="ml-2 caret"/>
            </template>

            <b-dropdown-group v-if="multipleHotels">
              <b-dropdown-form @submit.stop.prevent>
                <search-filter class="w-100"
                               v-model="hotelsFilter" autofocus :placeholder="$t('placeholder.filter-hotel')"/>
              </b-dropdown-form>
              <b-dropdown-item v-for="{id, name, city} in filteredHotels" :key="`hotel-${id}`" class="text-nowrap"
                               @click="switchHotel(id)"
                               :disabled="id === hotelID">
                <span class="text-primary hotel-id">{{ id }}</span>
                <span class="separator"></span>
                <span>{{ name }}</span><span v-if="city">{{ ', '+city }}</span>
              </b-dropdown-item>
              <b-dropdown-header v-if="!filteredHotels.length">
                {{ $t('pages.hotels.filter-no-hotels') }}
              </b-dropdown-header>
            </b-dropdown-group>
          </b-dropdown>
          <div class="lang-switcher only-mobs">
            <b-dropdown size="sm" toggle-tag="div" toggle-class="lang-menu" variant="link" no-caret>
              <template #button-content>
                <icon width="16" height="16" type="globe" class="globe-icon" />
                {{ lang.title }}
                <icon stroke-width="1" width="12" height="7" type="arrow-down" class="arrow-icon"/>
              </template>
              <input v-model="searchLang" class="lang-search" type="text">
              <b-dropdown-item v-if="!filteredLangs.length">
                No results for "<b>{{this.searchLang}}</b>"
              </b-dropdown-item>
              <b-dropdown-item v-else v-for="({ code, title}) in filteredLangs" :key="`lang-${code}`"
                               :disabled="lang.code === code" :class="{ 'text-primary': lang.code === code }"
                               @click.prevent="setLang(code)">{{ title }}</b-dropdown-item>
            </b-dropdown>
          </div>
          <b-dropdown v-if="asLoggedIn" size="sm" toggle-tag="div"
                      toggle-class="profile-menu" variant="link"
                      no-caret boundary="viewport" class="profile-menu--mobile" menu-class="w-100"
                      @hidden="collapseProfile">
            <template #button-content>
              <icon width="14" height="14" type="user" class="ml-4"/>
            </template>

            <b-dropdown-text class="text-nowrap text-center email">
              <b-avatar size="1.25rem"></b-avatar>
              <span class="email">{{ user.email }}</span>
            </b-dropdown-text>
            <b-dropdown-text class="text-nowrap text-center">
              <span class="user">{{ userName }}</span>
            </b-dropdown-text>
            <!--
            <b-dropdown-text class="text-nowrap text-center plan mb-2">
              <span>{{ $t('plans.freemium-plan') }}</span>
            </b-dropdown-text>
            <b-dropdown-text class="text-center manage-plan mb-4">
              <b-btn class="btn-sm" block variant="outline-primary">
                {{ $t('buttons.manage-plans') }}
              </b-btn>
            </b-dropdown-text>
            -->

            <b-dropdown-item :to="{ name: 'profile' }" v-if="userPageAllowed('profile')">
              <div class="profile-menu-item">
                <div class="profile-menu-icon">
                  <icon width="14" height="18" type="user" class="mb-1 mr-1"/>
                </div>
                <span>{{ $t('menu.profile') }}</span>
              </div>
            </b-dropdown-item>

            <b-dropdown-group v-b-toggle.billing-collapse class="accordion-items" v-if="pageAllowed('invoices')">
              <div class="accordion-collapse-btn">
                <icon width="6" height="13" type="arrow-right" class="accordion-arrow"/>
                <div class="profile-menu-item">
                  <div class="profile-menu-icon">
                    <icon width="12" height="18" type="legal-profile" class="mb-1 mr-2"/>
                  </div>
                  <span class="accordion-item">{{ $t('menu.billing') }}</span>
                </div>
              </div>
              <b-collapse id="billing-collapse" class="accordion-sub-items">
                <b-dropdown-item
                  class="subitem"
                  v-for="{ name, index } in billingPages" :key="index"
                  :to="{ name }"
                >
                  {{ $t(`menu.${name}`) }}
                </b-dropdown-item>
              </b-collapse>
            </b-dropdown-group>

            <b-dropdown-group v-b-toggle.legal-collapse class="accordion-items" v-if="pageAllowed('legal')">
              <div class="accordion-collapse-btn">
                <icon width="6" height="13" type="arrow-right" class="accordion-arrow"/>
                <div class="profile-menu-item">
                  <div class="profile-menu-icon">
                    <icon width="12" height="18" type="legal-profile" class="mb-1 mr-2"/>
                  </div>
                  <span class="accordion-item">{{ $t('menu.legal') }}</span>
                </div>
              </div>
              <b-collapse id="legal-collapse" class="accordion-sub-items">
                <b-dropdown-item
                  class="subitem"
                  v-for="{ slug, title } in legalPagesList" :key="`page-${slug}`"
                  :to="{ name: 'legal', params: { page: slug }}"
                >
                  {{ title }}
                </b-dropdown-item>
              </b-collapse>
            </b-dropdown-group>

            <b-dropdown-divider/>

            <b-dropdown-item @click.prevent="logout">
              <div class="profile-menu-item">
                <div class="profile-menu-icon">
                  <icon width="14" height="14" type="sign-out" class="mr-1"/>
                </div>
                <span>{{ $t('menu.logout') }}</span>
              </div>
            </b-dropdown-item>
          </b-dropdown>
        </div>
      </div>
    </nav>

    <div class="d-flex main-container" :class="{ 'mobile-shown': mobileShown, 'app-loading': !hostLoaded }">
      <div class="sidenav"
           :style="currentBackground"
           :class="{
             'd-md-block': sidebar, 'is-placeholder':
               !sidebar, 'closed': collapsed && !hovered && !mobileShown
           }"
           @mouseenter="hovered = true"
           @mouseleave="hovered = false"
      >
        <nav
          class="sidenav-header d-none d-md-flex align-items-center"
        >
          <router-link class="p-0 mx-auto ml-sm-0" :to="{ name:'home' }">
            <img v-if="currentLogo != null"
                 class="logo" :src="currentLogo" alt="logo">
            <svg v-else class="logo" viewBox="0 0 26 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M23.8421 6.79683C23.0149 6.07761 21.6484 4.92685 20.4257 3.84801C19.9223 3.38051 19.4548 3.12878 18.9513 3.12878C17.9804 3.12878 16.9375 3.95589 16.1104 4.71108C17.4769 5.8978 20.2459 8.27125 21.6125 9.45797C22.5115 10.2132 22.5834 10.7885 22.5834 11.4358V17.5133C22.5834 18.9517 21.3967 20.1385 19.9582 20.1385H10.3206C8.88217 20.1385 7.69545 18.9517 7.69545 17.5133V12.8743C7.19199 13.2699 6.54469 13.5216 5.82546 13.5216C5.25008 13.5216 4.71066 13.3418 4.24316 13.0901V17.4773C4.24316 20.8217 6.97622 23.5548 10.3206 23.5548H19.9223C23.2667 23.5548 25.9997 20.8217 25.9997 17.4773V11.3999C25.9997 10.1053 25.748 8.41509 23.8421 6.79683Z"
                    class="shape"/>
              <path d="M13.4495 4.53112C13.5214 4.4592 13.6293 4.35131 13.7732 4.20747C14.5643 3.38036 15.8949 1.97787 17.4772 1.43845C17.3333 1.29461 17.2254 1.15076 17.0816 1.00692L16.9737 0.934993C16.3624 0.395574 15.6072 0 14.5643 0C13.1259 0 12.4066 0.683264 11.5436 1.5823L5.71784 7.51591C5.60996 7.40802 5.46611 7.30014 5.35823 7.19225C5.28631 7.12033 5.21438 7.04841 5.14246 7.01245C5.1065 6.97649 5.07054 6.94053 5.03458 6.90456C3.59613 5.71784 1.7621 5.60996 0 7.30014C1.33057 8.23513 3.12863 9.78147 4.56708 11.184C4.89073 11.5076 5.32227 11.6874 5.78976 11.6874C6.25726 11.6874 6.6888 11.5076 7.01245 11.184L13.4495 4.53112Z"
                    fill="#F7981C"/>
            </svg>
          </router-link>
          <a href="#" v-if="asLoggedIn" @click.prevent="collapsed = !collapsed; hovered = false"
             class="burger-button" :class="{ collapsed }">
            <svg width="25" height="16" viewBox="0 0 25 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="25" height="2" fill="currentColor"/>
              <rect y="7" width="25" height="2" fill="currentColor"/>
              <rect y="14" width="25" height="2" fill="currentColor"/>
            </svg>
          </a>
        </nav>
        <side-nav
          :colorText="color_font"
        />
      </div>

      <div class="content"
           :class="{ 'no-navbar': !sidebar, 'bg-light': asLoggedIn, 'logged-in': asLoggedIn, 'nosidebar': collapsed }">
        <portal-target name="modals"/>
        <div class="content-header d-none d-md-flex align-items-center justify-content-between"
             v-if="asLoggedIn">
          <div class="lang-switcher">
            <b-dropdown size="sm" toggle-tag="div" toggle-class="lang-menu" variant="link" no-caret v-if="langs">
              <template #button-content>
                <icon width="16" height="16" type="globe" class="globe-icon" />
                {{ lang.title }}
                <icon stroke-width="1" width="12" height="7" type="arrow-down" class="arrow-icon"/>
              </template>
              <input v-model="searchLang" class="lang-search" type="text">
              <b-dropdown-item v-if="!filteredLangs.length">
                No results for "<b>{{this.searchLang}}</b>"
              </b-dropdown-item>
              <b-dropdown-item v-else v-for="({ code, title}) in filteredLangs" :key="`lang-${code}`"
                               :disabled="lang.code === code" :class="{ 'text-primary': lang.code === code }"
                               @click.prevent="setLang(code)">{{ title }}</b-dropdown-item>
            </b-dropdown>
          </div>
          <div class="profile">
            <template v-if="hasHotels">
              <span class="text-primary">{{ hotelID }}</span>
              <div class="separator"></div>
              <span v-if="!multipleHotels">{{ hotelName }}</span>
            </template>
            <b-dropdown v-if="multipleHotels" size="sm" toggle-tag="div" toggle-class="profile-menu"
                        menu-class="mt-3 hotels-dropdown" variant="link" no-caret right @show="hotelsFilter=''">
              <template #button-content>
                <span>{{ hotelName }}</span>
                <icon class="ml-2" stroke-width="2" width="12" height="7" type="arrow-down"/>
              </template>
              <b-dropdown-form @submit.stop.prevent>
                <search-filter v-model="hotelsFilter" autofocus :placeholder="$t('placeholder.filter-hotel')" />
              </b-dropdown-form>
              <b-dropdown-item v-for="{id, name, city} in filteredHotels" :key="`hotel-${id}`" class="text-nowrap"
                               @click="switchHotel(id)"
                               :disabled="id === hotelID">
                <span class="text-primary">{{id}}</span>
                <span class="separator"></span>{{ name }}
                <span v-if="city">{{', '+ city }}</span>
              </b-dropdown-item>
              <b-dropdown-header v-if="!filteredHotels.length">
                {{ $t('pages.hotels.filter-no-hotels') }}
              </b-dropdown-header>
            </b-dropdown>
            <b-dropdown size="sm" toggle-tag="div" toggle-class="profile-menu navbar-settings-dropdown" variant="link"
                        no-caret right menu-class="mt-3 profile-dropdown" @hidden="collapseProfile"
                        v-if="!isAgentUser"
            >
              <template #button-content>
                <icon width="14" height="14" type="user" class="ml-4"/>
                <icon class="ml-2" stroke-width="2" width="12" height="7" type="arrow-down"/>
              </template>
              <b-dropdown-text class="text-nowrap text-center email">
                <b-avatar class="mr-2" size="1.25rem"></b-avatar>
                <span class="email">{{ user.email }}</span>
              </b-dropdown-text>
              <b-dropdown-text class="text-nowrap text-center">
                <span class="user">{{ userName }}</span>
              </b-dropdown-text>
              <!--
              <b-dropdown-text class="text-nowrap text-center plan mb-1">
                {{ $t('plans.freemium-plan') }}
              </b-dropdown-text>
              <b-dropdown-text class="text-center manage-plan mb-3">
                <b-btn class="btn-sm" block variant="outline-primary">
                  {{ $t('buttons.manage-plans') }}
                </b-btn>
              </b-dropdown-text>
              -->

              <b-dropdown-group v-b-toggle.billing-collapse class="accordion-items" v-if="pageAllowed('invoices')">
                <div class="accordion-collapse-btn">
                  <icon width="6" height="13" type="arrow-right" class="accordion-arrow"/>
                  <div class="profile-menu-item">
                    <div class="profile-menu-icon">
                      <icon width="12" height="18" type="legal-profile" class="mb-1 mr-2"/>
                    </div>
                    <span class="accordion-item">{{ $t('menu.billing') }}</span>
                  </div>
                </div>
                <b-collapse id="billing-collapse" class="accordion-sub-items">
                  <b-dropdown-item
                    class="subitem"
                    v-for="{ name, index } in billingPages" :key="index"
                    :to="{ name }"
                  >
                    {{ $t(`menu.${name}`) }}
                  </b-dropdown-item>
                </b-collapse>
              </b-dropdown-group>

              <b-dropdown-item :to="{ name: 'profile' }" v-if="userPageAllowed('profile')">
                <div class="profile-menu-item">
                  <div class="profile-menu-icon">
                    <icon width="14" height="18" type="user" class="mb-1 mr-1"/>
                  </div>
                  <span>{{ $t('menu.profile') }}</span>
                </div>
              </b-dropdown-item>

              <b-dropdown-group v-b-toggle.legal-collapse-desktop class="accordion-items" v-if="pageAllowed('legal')">
                <div class="accordion-collapse-btn">
                  <icon width="6" height="13" type="arrow-right" class="accordion-arrow"/>
                  <div class="profile-menu-item">
                    <div class="profile-menu-icon">
                      <icon width="12" height="18" type="legal-profile" class="mb-1 mr-2"/>
                    </div>
                    <span class="accordion-item">{{ $t('menu.legal') }}</span>
                  </div>
                </div>
                <b-collapse id="legal-collapse-desktop" class="accordion-sub-items">
                  <b-dropdown-item
                    class="subitem"
                    v-for="{ slug, title } in legalPagesList" :key="`page-${slug}`"
                    :to="{ name: 'legal', params: { page: slug }}"
                  >
                    {{ title }}
                  </b-dropdown-item>
                </b-collapse>
              </b-dropdown-group>

              <b-dropdown-divider/>

              <b-dropdown-item @click.prevent="logout">
                <div class="profile-menu-item">
                  <div class="profile-menu-icon">
                    <icon width="14" height="14" type="sign-out" class="mr-1"/>
                  </div>
                  <span>{{ $t('menu.logout') }}</span>
                </div>
              </b-dropdown-item>
            </b-dropdown>
            <div v-else class="ml-2">
              <b-btn size="sm" variant="secondary" @click.prevent="logout" class="text-uppercase">
                {{ $t('links.home') }}
              </b-btn>
            </div>
          </div>
        </div>
        <main :class="mainClass" @click="closeMobileMenu">
          <router-view/>
          <b-modal
            id="expiredModal"
            ref="expiredModal"
            modal-class="session-expired-modal"
            no-fade
            static
            centered
            hide-header
            size="sm"
            :ok-title="$t('auth.expired.button')"
            ok-variant="danger"
            ok-only
            no-close-on-backdrop
            no-close-on-esc
            hide-header-close
            @ok.prevent="reload"
            :title="$t('auth.expired.title')"
          >
            <h5 class="text-danger">{{ $t('auth.expired.head') }}</h5>
            <p>{{ $t('auth.expired.text') }}</p>
          </b-modal>
        </main>
        <footer class="d-none" :class="{ 'd-sm-block': !asLoggedIn }">
          <ul class="footer-menu">
            <template v-if="!hideLoginFooterLinks">
              <li><a :href="`https://www.cultbooking.com/${lang.code}/`"
                     target="_blank">{{ $t('links.home') }}</a></li>
              <li><a :href="`https://www.cultbooking.com/${lang.code}/#benefits`"
                     target="_blank">{{ $t('links.benefits') }}</a></li>
              <li><a :href="`https://www.cultbooking.com/${lang.code}/highlights/`"
                     target="_blank">{{ $t('links.highlights') }}</a></li>
              <li><a :href="`https://www.cultbooking.com/${lang.code}/developers-connect/`"
                     target="_blank">{{ $t('links.devconnect') }}</a></li>
              <li><a :href="`https://www.cultbooking.com/${lang.code}/price/`"
                     target="_blank">{{ $t('links.price') }}</a></li>
            </template>
            <li>
              <div class="lang-switcher">
                <b-dropdown size="sm" toggle-tag="div" toggle-class="lang-menu" variant="link" no-caret dropup>
                  <template #button-content>
                    <icon width="16" height="16" type="globe" class="globe-icon" />
                    {{ lang.title }}
                    <icon stroke-width="1" width="12" height="7" type="arrow-down" class="arrow-icon"/>
                  </template>
                  <b-dropdown-item v-for="({ code, title}) in langs" :key="`lang-${code}`"
                                   :disabled="lang.code === code" :class="{ 'text-primary': lang.code === code }"
                                   @click.prevent="setLang(code)">{{ title }}</b-dropdown-item>
                </b-dropdown>
              </div>
            </li>
          </ul>
        </footer>
      </div>
    </div>
  </fragment>
</template>

<script>
  import { mapActions, mapGetters, mapMutations } from 'vuex';
  import SideNav from '@/components/SideNav.vue';
  import { billingPages } from '@/shared';
  import { Storage, USER_KEY, LANG_KEY } from '@/services/storage.service';

  export default {
    name: 'App',
    components: { SideNav },
    data() {
      return {
        collapsed: false,
        hovered: false,
        mobileShown: false,
        logoMenu: null,
        hotelsFilter: '',
        showOverlay: true,
        color_schema: {
          r: 74,
          g: 143,
          b: 221,
        },
        color_font: {
          r: 255,
          g: 255,
          b: 255,
        },
        showChat: process.env.VUE_APP_ZAMMAD_CHAT_ENABLED === 'true',
        billingPages,
        searchLang: '',
      };
    },
    computed: {
      ...mapGetters('data', ['languages']),
      ...mapGetters(['pageTitle', 'sidebar', 'centered', 'stretch']),
      ...mapGetters('user', [
        'user', 'hasHotels', 'hotelName', 'hotelID', 'loggedIn', 'sessionExpired', 'lang', 'multipleHotels', 'hotels',
        'currentLogo', 'currentColorSchema', 'pageAllowed', 'userPageAllowed', 'isAgentUser',
        'hostLoaded', 'hostInfo', 'currentDomainConfig',
      ]),
      filteredLangs() {
        if (!this.searchLang) {
          return this.langs;
        }
        return this.langs.filter((item) => item.title.toLowerCase().indexOf(this.searchLang.toLowerCase()) > -1);
      },
      asLoggedIn() {
        return this.loggedIn && !this.centered;
      },
      mainClass() {
        return this.asLoggedIn && !this.stretch
          ? `logged-in ${this.mobileShown ? 'mobile-shown' : ''}`
          : `d-flex align-items-sm-center justify-content-center flex-grow-1 flex-direction-column
              ${this.stretch ? 'stretched' : ''}`;
      },
      langs() {
        return this.languages.map(({ nativeName, code }) => ({ code, title: `${nativeName}` }));
      },
      titleLocalized() {
        if (this.pageTitle == null || !this.pageTitle) return false;
        if (this.pageTitle.charAt(0) !== '*') {
          return this.pageTitle;
        }
        const path = this.pageTitle.slice(1);
        return this.$te(path) ? this.$t(path).capitalizeAll() : false;
      },
      filteredHotels() {
        const filter = this.hotelsFilter.trim().toLowerCase();
        if (!filter) return this.hotels;
        return this.hotels.filter(({ id, name, city }) => (`${id}`.includes(filter) || name.toLowerCase().includes(filter) || (city !== '' && city !== null && city.toLowerCase().includes(filter))));
      },
      userName() {
        const { user } = this;
        if (user != null && user.profile != null) return user.profile.name;
        return '';
      },
      legalPagesList() {
        return this.$t('pages.legal.pages').map(({ slug, title }) => ({
          slug,
          title,
        }));
      },
      currentBackground() {
        const {
          r, g, b, a,
        } = this.currentColorSchema.rgba;
        return `background-color: rgba(${r}, ${g}, ${b}, ${a}); --group-color: rgba(${r}, ${g}, ${b}, ${a})`;
      },
      hideLoginFooterLinks() {
        return this.currentDomainConfig['hide-login-footer-links'] === true;
      },
    },
    watch: {
      $route() {
        this.mobileShown = false;
        if (this.$route.name === 'login') {
          this.resetColorScheme();
        }
      },
      async asLoggedIn() {
        if (this.asLoggedIn) {
          this.logo();
        }
      },
      pageTitle() {
        this.updateTitle();
      },
      titleLocalized() {
        this.updateTitle();
      },
      sessionExpired(yes) {
        if (yes) {
          this.$refs.expiredModal.show();
        } else {
          this.$refs.expiredModal.hide();
        }
      },

    },
    async created() {
      await Promise.allSettled([
        this.fetchLanguages(),
      ]);
    },
    mounted() {
      Storage.addListener(LANG_KEY, (locale) => {
        this.setLang(locale);
      });
      Storage.addListener(USER_KEY, (user) => {
        if (user != null && !this.loggedIn) {
          // login from other tab
          this.login({ syncUser: user });
        } else if (user == null && this.loggedIn) {
          // logout from other tab
          this.logout({ forced: true, stay: true });
        } else if (user != null && user.id !== this.user.id) {
          // something weird has happened
          window.location.reload();
        }
      });

      if (this.hasHotels) {
        this.logo();
      }

      this.$root.$on('bv::collapse::state', (collapseId, isJustShown) => {
        this.collapseId = collapseId;
        this.isJustShown = isJustShown;
      });

      if (this.showChat) {
        this.initZammadChat();
      }
    },
    methods: {
      ...mapActions('data', ['fetchLanguages']),
      ...mapMutations('user', ['setLang']),
      ...mapActions('auth', ['login', 'logout']),
      logo() {
        this.resetColorScheme();
        if (this.hotelID && this.asLoggedIn) {
          this.user.hotels.forEach((t) => {
            if (t.id === this.hotelID) {
              if (t.property_group) {
                this.logoMenu = (t.property_group.logo);
                this.color_font = JSON.parse(t.property_group.style).color_font.rgba;
                this.color_schema = JSON.parse(t.property_group.style).color_schema.rgba;
              }
            }
          });
        }
      },
      resetColorScheme() {
        this.color_schema = {
          r: 74,
          g: 143,
          b: 221,
        };
        this.color_font = {
          r: 255,
          g: 255,
          b: 255,
        };
        this.logoMenu = null;
      },
      updateTitle() {
        const tl = this.titleLocalized;
        let title = 'CultBooking';
        if (tl !== false) {
          title = `${title} - ${tl}`;
        }
        document.querySelector('head title').textContent = title;
      },
      reload() {
        this.logout({ forced: true, stay: true });
      },
      switchHotel(id) {
        Storage.setHotel(id);
        window.location.reload();
      },
      closeMobileMenu(ev) {
        if (this.mobileShown) {
          ev.preventDefault();
          ev.stopPropagation();
          ev.stopImmediatePropagation();
          this.mobileShown = false;
        }
      },
      collapseProfile() {
        if (this.isJustShown) {
          this.$root.$emit('bv::toggle::collapse', this.collapseId);
        }
      },
      initZammadChat() {
        let tries = 15;
        let interval;
        const {
          r, g, b, a,
        } = this.currentColorSchema.rgba;
        const checkZammad = () => {
          tries -= 1;
          if (!tries) {
            clearInterval(interval);
          }
          if (window.jQuery != null) {
            try {
              new ZammadChat({ // eslint-disable-line no-new
                background: `rgba(${r}, ${g}, ${b}, ${a})`,
                fontSize: '12px',
                chatId: 3,
              });
              clearInterval(interval);
            } catch (error) {
              console.warn('ZammadChat is absent'); // eslint-disable-line no-console
            }
          }
        };
        interval = setInterval(checkZammad, 100);
      },
    },
  };
</script>
