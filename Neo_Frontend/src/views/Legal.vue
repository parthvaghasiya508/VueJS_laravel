<template>
  <div class="page-legal">
    <template v-if="main">
      <div class="legal-breadcrumb d-none d-md-block">
        <span>{{ $t('pages.legal.title') }}</span>
      </div>
      <div class="panel-title position-relative w-100 title">
        {{ $t('pages.legal.title-main') }}
      </div>
      <hr class="directur-separator">
      <div class="pb-2">
        <p v-for="{ slug, title } in legalPages" :key="`link-${slug}`">
          <router-link
            :to="{ name: 'legal', params: { page: slug } }"
          >{{ title }}</router-link>
        </p>
      </div>
    </template>
    <template v-else-if="page != null">
      <div class="legal-breadcrumb d-none d-md-block">
        <router-link :to="{ name: 'legal' }">{{ $t('pages.legal.title') }}</router-link>
        <icon type="arrow-right" w="14" h="14" />
        <span>{{ page.title }}</span>
      </div>
      <directus-page
        v-if="page != null"
        :page="page"
        pdf
      />
    </template>
  </div>
</template>

<script>
  import DirectusPage from '@/components/Pages/DirectusPage.vue';

  export default {
    name: 'Legal',
    components: { DirectusPage },
    data() {
      return {
        main: false,
      };
    },
    created() {
      this.updatePage();
    },
    computed: {
      legalPages() {
        return this.$t('pages.legal.pages');
      },
      page() {
        const slug = this.$route.params.page;
        if (!slug) {
          return {};
        }
        return {
          id: this.legalPages.find((p) => p.slug === slug).id,
          title: this.legalPages.find((p) => p.slug === slug).title,
        };
      },
    },
    watch: {
      $route() {
        this.updatePage();
      },
    },
    methods: {
      updatePage() {
        this.main = false;
        const slug = this.$route.params.page;
        if (!slug) {
          this.main = true;
          return;
        }
        this.$nextTick(() => {
          const page = this.legalPages.find((p) => p.slug === slug);
          if (page == null) return;
          this.$store.commit('pageTitle', page.title);
        });
      },
    },
  };
</script>
