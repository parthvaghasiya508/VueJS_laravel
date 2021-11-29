<template>
  <div class="page-invoices" id="page-invoices">
    <div ref="title" class="panel-title position-relative w-100 title">
      <p>{{ $t('pages.invoices.title') }}</p>
    </div>
    <div class="panel position-relative panel-content">
      <div class="list d-none d-md-block">
        <div class="no-invoices" v-if="!pending && !invoicesData.length">
          {{ $t('pages.invoices.no-unpaid-invoices') }}
        </div>
        <div v-else>
          <p class="head-line justify-content-between">
            <span>{{ $t('pages.invoices.heading') }}<spinner v-if="pending" /></span>
            <search-filter
              v-model="filterUnpaid"
              :disabled="pending"
              :placeholder="$t('pages.invoices.filter-by-invoice')"
            />
          </p>
          <div class="invoices-table">
            <b-table
              id="invoice-table-unpaid"
              ref="invoicesTableUnpaid"
              :items="filteredInvoices"
              :fields="fieldsInvoice"
              responsive="sm"
              v-if="!pending"
            >
              <template #head(beneficiary)>
                {{ $t('pages.invoices.headers.beneficiary') }}
                <sort-indicator v-model="sort" field="beneficiary" />
              </template>
              <template #head(invoice)>
                {{ $t('pages.invoices.headers.invoice') }}
                <sort-indicator v-model="sort" field="invoice" />
              </template>
              <template #head(produced)>
                {{ $t('pages.invoices.headers.produced') }}
                <sort-indicator v-model="sort" field="produced" />
              </template>
              <template #head(period_from)>
                {{ $t('pages.invoices.headers.period-from') }}
                <sort-indicator v-model="sort" field="period_from" />
              </template>
              <template #head(period_to)>
                {{ $t('pages.invoices.headers.period-to') }}
                <sort-indicator v-model="sort" field="period_to" />
              </template>
              <template #head(sum)>
                {{ $t('pages.invoices.headers.sum') }}
                <sort-indicator v-model="sort" field="sum" />
              </template>
              <template #cell(download)="data">
                <a :href="data.item.download.pdf" class="download" v-if="data.item.download.pdf !== null">
                  <icon width="56" height="24" type="pdf"/>
                </a>
                <a :href="data.item.download.csv" class="download" v-if="data.item.download.csv !== null">
                  <icon width="56" height="24" type="xlsx"/>
                </a>
              </template>
            </b-table>
          </div>
          <pagination
            :total-rows="totalRows"
            :per-page.sync="perPage"
            :current-page.sync="currentPage"
            v-if="!pending && totalRows >= 10"
          />
        </div>
      </div>
      <div class="d-md-none list-item" v-for="row in filteredInvoices" :key="row.id">
        <div class="d-flex line">
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.beneficiary') }}</p>
            <p>{{ row.beneficiary }}</p>
          </div>
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.invoice') }}</p>
            <p>{{ row.invoice }}</p>
          </div>
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.produced') }}</p>
            <p>{{ row.produced }}</p>
          </div>
        </div>
        <div class="d-flex line">
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.period-from') }}</p>
            <p>{{ row.period_from }}</p>
          </div>
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.period-to') }}</p>
            <p>{{ row.period_to }}</p>
          </div>
          <div class="w-33">
            <p class="label">{{ $t('pages.invoices.headers.sum') }}</p>
            <p>{{ row.sum }}</p>
          </div>
        </div>
        <div class="line">
          <div class="w-100">
            <p class="label">{{ $t('pages.invoices.headers.download') }}</p>
            <p>
              <a :href="row.download.pdf" class="download float-left" v-if="row.download.pdf !== null">
                <icon width="56" height="24" type="pdf"/>
              </a>
              <a :href="row.download.csv" class="download" v-if="row.download.csv !== null">
                <icon width="56" height="24" type="xlsx"/>
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
  import moment from 'moment';
  import { mapActions, mapState } from 'vuex';
  import { pick, formatNumberWithCurrency } from '@/helpers';

  export default {
    name: 'InvoicesData',
    data() {
      return {
        sort: '+periodFrom',
        filterUnpaid: '',
        currentPage: 1,
        perPage: 10,
        fieldsInvoice: [
          'beneficiary',
          'invoice',
          'produced',
          'period_from',
          'period_to',
          'sum',
          'download',
        ],
        currencyMap: {
          EUR: '€',
          USD: '$',
        },
      };
    },
    created() {
      const { currentPage: page, perPage: limit } = this;
      this.updateInvoices(page, limit);
    },
    watch: {
      perPage(val) {
        const page = this.currentPage <= Math.ceil(this.totalRows / val)
          ? this.currentPage
          : Math.ceil(this.totalRows / val);
        this.updateInvoices(page, val);
      },
      currentPage(val) {
        const limit = this.perPage;
        this.updateInvoices(val, limit);
      },
    },
    methods: {
      ...mapActions('invoices', ['fetchData']),

      async updateInvoices(page, limit) {
        await this.fetchData({ page, limit });
      },

      filterInvoices(invoices, filterStr) {
        const filter = filterStr.trim().toLowerCase();
        let ret = [...invoices];
        if (filter) {
          ret = ret.filter(({ invoice, beneficiary }) => (`${invoice}`?.includes(filter) || beneficiary?.toLowerCase().includes(filter)));
        }
        const field = this.sort.substr(1);
        const k = this.sort.charAt(0) === '+' ? 1 : -1;
        ret = ret.sort((a, b) => {
          if (field === 'periodFrom' || field === 'period_from') {
            const v1 = new Date(moment(a.period_from).format('YYYY-MM-DD'));
            const v2 = new Date(moment(b.period_from).format('YYYY-MM-DD'));
            // eslint-disable-next-line no-nested-ternary
            return k * (v2 > v1 ? 1 : (v2 < v1 ? -1 : 0));
          }
          const v1 = pick(a, field);
          const v2 = pick(b, field);
          // eslint-disable-next-line no-nested-ternary
          return k * (v1 > v2 ? 1 : (v1 < v2 ? -1 : 0));
        });
        return ret;
      },
    },
    computed: {
      ...mapState('invoices', ['error', 'pending', 'data']),
      ...mapState('user', ['numberFormat', 'pending', 'data']),

      totalRows() {
        return this.data.totalRecords;
      },

      invoicesData() {
        return this.invoices.filter(({ paymentDate }) => paymentDate == null);
      },

      filteredInvoices() {
        return this.filterInvoices(this.invoicesData, this.filterUnpaid);
      },

      invoices() {
        if (this.data) {
          return this.data.data.map((item) => ({
            beneficiary: item?.provider?.name,
            invoice: item?.invoiceName,
            produced: moment(item?.generatedDate).format(this.$t('pages.invoices.format')),
            period_from: moment(item?.periodFrom).format(this.$t('pages.invoices.format')),
            period_to: moment(item?.periodTo).format(this.$t('pages.invoices.format')),
            sum: formatNumberWithCurrency(item?.totalNet.toFixed(2), this.numberFormat, item?.currency),
            paid: item?.paymentDate,
            download: { pdf: item?.pdf, csv: item?.xlsx },
          }));
        }
        return [];
      },
    },
  };
</script>
