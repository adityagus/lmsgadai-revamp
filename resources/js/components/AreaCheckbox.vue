<script setup>
import { defineProps, defineEmits, h } from 'vue';
const props = defineProps({
  area: Object,
  checked: Array
});
const emit = defineEmits(['update:checked']);

function getAllChildIds(area) {
  let ids = [String(area.id_area)];
  if (area.children && area.children.length > 0) {
    area.children.forEach(child => {
      ids = ids.concat(getAllChildIds(child));
    });
  }
  return ids;
}
function isParentChecked(area) {
  const checkedStrings = (props.checked || []).map(String);
  if (!area.children || area.children.length === 0) return checkedStrings.includes(String(area.id_area));
  return area.children.every(child => isParentChecked(child));
}
function toggleParent(area) {
  const allIds = getAllChildIds(area);
  const checkedStrings = (props.checked || []).map(String);
  const allChecked = allIds.every(id => checkedStrings.includes(id));
  let newChecked;
  if (allChecked) {
    newChecked = props.checked.filter(id => !allIds.includes(String(id)));
  } else {
    newChecked = Array.from(new Set([...props.checked.map(String), ...allIds]));
  }
  emit('update:checked', newChecked);
}
</script>

<template>
  <div :style="{ marginLeft: '0.5rem' }">
    <label class="flex items-center gap-2">
      <input type="checkbox" :checked="isParentChecked(props.area)"
        :indeterminate="!isParentChecked(props.area) && props.area.children && props.area.children.length > 0 && props.area.children.some(child => isParentChecked(child))"
        @change="() => toggleParent(props.area)" />
      {{ props.area.nm_area }}
    </label>
    <div v-if="props.area.children && props.area.children.length > 0" :style="{ marginLeft: '1.5rem' }">
      <AreaCheckbox v-for="child in props.area.children" :key="child.id_area" :area="child" :checked="props.checked"
        @update:checked="$emit('update:checked', $event)" />
    </div>
  </div>
</template>
