<template>
  <div class="form-control d-flex align-items-center justify-content-between drop-down-container drop-down"
       :id="$uid"
       :disabled="disabled"
       :tabindex="disabled ? -1 : 0"
       @focus="focused"
       @mousedown="lastEvent = 'm'"
       @keydown="lastEvent = 'k'"
       @click="clicked"
  >
    <span>{{ ddTitle() }}</span>
    <icon class="arrow" stroke-width="2" width="12" height="7" type="arrow-down"/>
    <ul ref="menu" class="drop-down-menu position-absolute d-none" :class="menuClass">
      <template v-if="items != null">
        <template>
          <li>
            <input
              ref="searchInput"
              v-model="search"
              @blur="blurred"
              id="search-filter"
              placeholder="Search items"
              class="input-group-field form-control"
              type="text"
              autocomplete="off">
          </li>
          <li class="divider"><hr></li>
        </template>
        <template v-if="multiple && selectAll && isHide">
          <li>
            <check-box @input="toggleAllItems($event)" :value="isSelectedAll">
              {{ $t(titleAll) }}
            </check-box>
          </li>
          <li class="divider"><hr></li>
        </template>
        <li v-if="!filteredItems.length">No results for "<b>{{ this.search }}</b>"</li>
        <li v-else v-for="item in filteredItems" :key="`dd-item-${item[idKey]}`" @click="selectItem(item)"
            :class="{ selected: isSelected(item), disabled: item.disabled }">
          <span v-if="!multiple">{{ item[labelKey] }}</span>
          <check-box v-else @input="toggleItem(item, $event)" :value="isSelected(item)">
            {{ item[labelKey] }}
          </check-box>
        </li>
      </template>
    </ul>
  </div>
</template>

<script>
  export default {
    name: 'DropDown',
    props: {
      title: {
        type: String,
        default: null,
        required: false,
      },
      titleAll: {
        type: String,
        default: null,
        required: false,
      },
      items: {
        required: true,
        validator: (prop) => Array.isArray(prop) || prop == null,
      },
      value: {
        required: true,
      },
      position: {
        type: String,
        default: 'left',
        validate: (p) => ['left', 'right'].indexOf(p) !== -1,
      },
      grow: {
        type: String,
        default: 'down',
        validate: (g) => ['up', 'down', 'md-up'].indexOf(g) !== -1,
      },
      multiple: {
        type: Boolean,
        default: false,
      },
      selectAll: {
        type: Boolean,
        default: false,
      },
      idKey: {
        type: String,
        default: 'id',
      },
      labelKey: {
        type: String,
        default: 'text',
      },
      disabled: Boolean,
    },
    data: () => ({
      // shown: false,
      lastEvent: null,
      preventClick: false,
      menuClass: '',
      search: '',
      isHide: true,
    }),
    created() {
      this.menuClass = `from-${this.position} ${this.multiple && 'dd-multiple'} grow-${this.grow}`;
    },
    computed: {
      isSelectedAll() {
        return this.value.length === this.items.length;
      },
      filteredItems() {
        if (!this.search) {
          this.toggleHide(true);
          return this.items;
        }
        this.toggleHide(false);
        const itemsData = this.items;
        return itemsData.filter((row) => {
          const id = '';
          let text = '';
          let type = '';
          let name = '';
          let countryId = '';
          let value = '';
          let title = '';
          let time = '';
          if (row.id) {
            row.id.toString();
          }
          if (row.text) {
            text = row.text.toString().toLowerCase();
          }
          if (row.type) {
            type = row.type.toString().toLowerCase();
          }
          if (row.name) {
            name = row.name.toString().toLowerCase();
          }
          if (row.country_id) {
            countryId = row.country_id.toString();
          }
          if (row.value) {
            value = row.value.toString().toLowerCase();
          }
          if (row.title) {
            title = row.title.toString().toLowerCase();
          }
          if (row.time) {
            time = row.time.toString().toLowerCase();
          }
          const searchTerm = this.search.toString().toLowerCase();
          return id.includes(searchTerm)
            || countryId.includes(searchTerm)
            || text.includes(searchTerm)
            || type.includes(searchTerm)
            || name.includes(searchTerm)
            || value.includes(searchTerm)
            || title.includes(searchTerm)
            || time.includes(searchTerm);
        });
      },
    },
    methods: {
      toggleHide(val) {
        this.isHide = val;
      },
      ddTitle() {
        if (this.items == null || !this.items.length) return '';
        if (!this.multiple || (Array.isArray(this.value) && this.value.length === 1)) {
          const id = this.multiple ? this.value[0] : this.value;
          const sel = this.items.find((i) => i[this.idKey] === id);
          return sel != null ? sel[this.labelKey] : '';
        }
        const count = this.value.length;
        if (count === this.items.length) return this.$tc(this.titleAll);
        return this.$tc(this.title, count);
      },
      focused() {
        if (this.disabled) return;
        this.preventClick = this.lastEvent === 'm';
        this.$refs.menu.classList.remove('d-none');
        // this.shown = true;
      },
      blurred() {
        this.$refs.menu.classList.add('d-none');
        // this.shown = false;
      },
      clicked(e) {
        this.$refs.searchInput.focus();
        if (!this.disabled) {
          const { classList, parentNode: { classList: parentClassList } } = e.target;
          if (!classList.contains('drop-down') && !parentClassList.contains('drop-down')) return;
          if (!this.preventClick) {
            this.$refs.menu.classList.toggle('d-none');
            // this.shown = !this.shown;
          }
          this.preventClick = false;
        }
      },
      isSelected(item) {
        if (!this.multiple) {
          return item[this.idKey] === this.value;
        }
        return this.value.indexOf(item[this.idKey]) !== -1;
      },
      toggleAllItems(on) {
        this.$nextTick(() => {
          if (!on) {
            this.value.splice(0, this.value.length);
          } else if (this.value.length !== this.items.length) {
            this.items.forEach(({ id }) => {
              if (!this.value.includes(id)) {
                this.value.push(id);
              }
            });
          }
        });
      },
      toggleItem(item, on) {
        this.$nextTick(() => {
          const { id } = item;
          if (on) {
            if (!this.value.includes(id)) {
              this.value.push(id);
            }
          } else {
            const idx = this.value.indexOf(id);
            if (idx !== -1) {
              this.value.splice(idx, 1);
            }
          }
        });
      },
      selectItem(item) {
        if (this.multiple || item.disabled) return;
        this.$emit('input', item[this.idKey]);
        this.$refs.menu.classList.add('d-none');
        this.ddTitle();
        this.$emit('update');
      },
    },
  };
</script>
