<template>
  <div class="products-selector">
    <div class="products-selector-header">
      <check-box :value="isAllSelected" @input="toggleAll" :disabled="disabled" v-if="!AllPlans">
        {{ $t('components.products-selector.checkbox-toggle-all') }}
      </check-box>
      <div class="part-indicator" v-if="hasSelectedRooms">
        {{ $tc('components.products-selector.rooms-selected', selectedRooms.length) }}
        <button class="btn-icon" @click="deselectRooms" :disabled="disabled">
          <icon w="11" h="11" type="times"/>
        </button>
      </div>
      <div class="part-indicator" v-if="hasSelectedPlans">
        {{ $tc('components.products-selector.plans-selected', selectedPlans.length) }}
        <button class="btn-icon" @click="deselectPlans" :disabled="disabled">
          <icon w="11" h="11" type="times"/>
        </button>
      </div>
    </div>
    <div class="products-selector-tree">
      <div class="pst-item" v-for="room in tree" :key="`pstrt-${room.id}`">
        <div class="pst-head d-flex align-items-center">
          <check-box
            AllPlans
            :disabled="disabled"
            :value="isRoomSelected(room)" class="pst-room"
            @input="toggleRoom(room)"
          >{{ room.text }} ({{ room.id }})</check-box>
          <button class="btn-icon text-semigray" @click="room.open = !room.open">
            <icon w="13" h="9" sw="1.5"
                  :type="room.open ? 'arrow-up' : 'arrow-down'" />
          </button>
        </div>
        <div class="pst-plans" :class="{ open: room.open }">
          <div class="link-figure">
            <icon width="22" height="22" class="link-figure-icon" type="link"/>
            <check-box
              :disabled="disabled || (parted && isRoomSelected(room))"
              class="pst-plan"
              v-for="plan in room.plans" :key="`pstrp-${plan.id}`"
              :value="plan.selected"
              @input="togglePlan(room, plan)"
            >{{ plan.text }} ({{ plan.id }})</check-box>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import { only } from '@/helpers';

  export default {
    name: 'ProductsSelector',
    props: {
      rooms: {
        type: Array,
        required: true,
      },
      plans: {
        type: Array,
        required: true,
      },
      selectedRooms: {
        validator: (v) => v == null || Array.isArray(v),
      },
      selectedPlans: {
        validator: (v) => v == null || Array.isArray(v),
      },
      disabled: {
        type: Boolean,
        default: false,
      },
      parted: {
        type: Boolean,
        default: false,
      },
      plansOnly: {
        type: Boolean,
        default: false,
      },
      AllPlans: {
        type: Boolean,
        default: false,
      },
    },
    data() {
      return {
        tree: [],
      };
    },
    created() {
      this.updateTree();
    },
    watch: {
      plans() {
        this.updateTree();
      },
      rooms() {
        this.updateTree();
      },
      selectedPlans() {
        this.updateTree();
      },
      selectedRooms() {
        this.updateTree();
      },
    },
    computed: {
      hasSelectedRooms() {
        return this.selectedRooms != null && this.selectedRooms.length > 0;
      },
      hasSelectedPlans() {
        return this.selectedPlans != null && this.selectedPlans.length > 0;
      },
      hideRoomsSelector() {
        return this.selectedRooms == null;
      },
      isAllSelected() {
        if (this.parted) {
          return this.tree.reduce((cnt, { checked }) => (cnt + (checked ? 1 : 0)), 0) === this.tree.length;
        }
        const { selected, total } = this.tree.reduce(({ selected: s0, total: t0 }, { selected: s, total: t }) => ({
          selected: s0 + s, total: t0 + t,
        }), { selected: 0, total: 0 });
        return selected === total;
      },
    },
    methods: {
      updateValues() {
        const rooms = [];
        const plans = [];
        const { parted } = this;
        this.tree
          .filter(({ selected }) => (parted || selected > 0))
          .forEach((r) => {
            const allPlans = r.selected === r.total;
            if (parted ? r.checked : (allPlans && !this.plansOnly)) {
              rooms.push(r.id);
            }
            if (parted ? !r.checked : (!allPlans || this.plansOnly)) {
              plans.push(...r.plans.filter(({ selected }) => selected).pluck('id'));
            }
          });
        if (!this.plansOnly) {
          this.$emit('update:selectedRooms', rooms);
        }
        this.$emit('update:selectedPlans', plans);
      },
      updateTree() {
        const ids = new Set(this.plans.pluck('room'));
        const { selectedPlans: sp, selectedRooms: sr = [] } = this;
        this.tree = this.rooms
          .filter(({ id }) => ids.has(id))
          .map((room) => {
            const ret = {
              ...only(room, 'id', 'text'),
              plans: this.plans
                .filter(({ room: r }) => r === room.id)
                .map((p) => ({ ...only(p, 'id', 'room', 'text'), selected: sp.includes(p.id) })),
              open: true,
              checked: sr.includes(room.id),
            };
            ret.selected = ret.plans.filter(({ selected }) => selected).length;
            ret.total = ret.plans.length;
            return ret;
          });
      },
      isRoomSelected(room) {
        return this.parted ? room.checked : (room.selected === room.total);
      },
      toggleRoom(room, force, plansOnly = false) {
        /* eslint-disable no-param-reassign, no-return-assign */
        if (this.parted) {
          if (!plansOnly) {
            room.checked = force == null ? !room.checked : force;
          }
          room.plans.forEach((p) => p.selected = false);
        } else if ((force == null && room.selected !== room.total) || force === true) {
          room.selected = room.total;
          room.plans.forEach((p) => p.selected = true);
        } else if (force == null || force === false) {
          room.selected = 0;
          room.plans.forEach((p) => p.selected = false);
        }
        /* eslint-enable no-param-reassign, no-return-assign */
        if (force == null) {
          this.updateValues();
        }
      },
      togglePlan(room, plan) {
        /* eslint-disable no-param-reassign */
        if (plan.selected) {
          plan.selected = false;
          room.selected -= 1;
        } else {
          plan.selected = true;
          room.selected += 1;
        }
        /* eslint-enable no-param-reassign */
        this.updateValues();
      },
      toggleAll() {
        const allSelected = this.isAllSelected;
        this.tree.forEach((r) => {
          this.toggleRoom(r, !allSelected);
        });
        this.updateValues();
      },
      deselectRooms() {
        const { parted } = this;
        this.tree
          .filter(({ selected, total, checked }) => (parted ? checked : selected === total))
          .forEach((room) => {
            this.toggleRoom(room, false);
          });
        this.updateValues();
      },
      deselectPlans() {
        const { parted, plansOnly } = this;
        this.tree
          .filter(({ selected, total }) => (parted || plansOnly ? selected > 0 : selected !== total))
          .forEach((room) => {
            this.toggleRoom(room, false, true);
          });
        this.updateValues();
      },
    },
  };
</script>
