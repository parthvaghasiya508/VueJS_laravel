<template>
  <div class="page-directus">
    <div class="panel-title position-relative w-100 title">
      {{ page.title }}
      <b-btn
        v-if="pdf"
        @click="exportToPDF()"
        variant="primary"
        :disabled="body == null"
        class="download-btn d-none d-md-flex align-items-center">
        <icon class="icon mr-2" width="16" height="20" type="download"/>
        {{ $t('pages.legal.button-download-pdf') }}
      </b-btn>
    </div>
    <div class="d-flex d-md-none justify-content-center" v-if="pdf">
      <b-btn
        @click="exportToPDF()"
        variant="primary"
        :disabled="body == null"
        size="sm"
        class="download-btn d-flex align-items-center mt-3">
        <icon class="icon mr-2" width="14" height="16" type="download"/>
        {{ $t('pages.legal.button-download-pdf') }}
      </b-btn>
    </div>
    <hr class="directur-separator" />
    <div
      ref="content"
      class="document-content"
      v-html="body"
    />
  </div>
</template>

<script>
  import ApiService from '@/services/api.service';
  import { apiEndpoint } from '@/shared';
  import html2pdf from 'html2pdf.js';

  export default {
    name: 'DirectusPage',
    props: {
      page: {
        required: true,
        type: Object,
      },
      pdf: {
        type: Boolean,
        default: false,
      },
    },
    data: () => ({
      body: null,
    }),
    async created() {
      await this.callDirectusApi();
    },
    methods: {
      exportToPDF() {
        html2pdf(this.$refs.content, {
          margin: 0.4,
          filename: this.page.title,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { dpi: 192, letterRendering: true },
          jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' },
        });
      },
      async callDirectusApi() {
        try {
          const { data: { body } } = await ApiService.get(`${apiEndpoint}/directus/pages/${this.page.id}`, {
            params: {
              fields: 'title,body',
            },
          });
          this.body = body;
        } catch (err) {
          this.$toastr.e('404 not found', this.$t('error'));
        }
      },
    },
    watch: {
      async page() {
        await this.callDirectusApi();
        this.$store.commit('pageTitle', this.page.title);
      },
    },
  };
</script>
