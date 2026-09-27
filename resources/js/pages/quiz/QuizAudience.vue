<template>
  <fieldset class="stack quiz-fieldset audience" :disabled="disabled">
    <legend>Siapa yang boleh mengakses kuis?</legend>
    <p class="muted">Kosongkan pilihan untuk semua peserta. Jika beberapa kelompok diatur, peserta harus cocok dengan jabatan dan cabang yang dipilih.</p>
    <p v-if="error" class="error" role="alert">{{ error }} <button type="button" @click="load">Coba lagi</button></p>
    <div class="form-grid">
      <div v-for="group in groups" :key="group.key" class="stack">
        <h3>{{ group.label }}</h3>
        <input v-model="search[group.key]" type="search" :placeholder="'Cari ' + group.label.toLowerCase()" :aria-label="'Cari ' + group.label.toLowerCase()">
        <div class="audience-options">
          <label v-for="item in options(group.key)" :key="item.value" class="check">
            <input type="checkbox" :checked="modelValue[group.key].includes(item.value)" @change="toggle(group.key, item.value, $event.target.checked)">
            <span>{{ item.label }}<small class="muted"> · {{ item.value }}</small></span>
          </label>
          <p v-if="!options(group.key).length" class="muted">{{ loading ? 'Memuat pilihan...' : 'Tidak ada pilihan yang cocok.' }}</p>
        </div>
        <div class="row"><small class="muted">{{ modelValue[group.key].length ? modelValue[group.key].length + ' dipilih' : 'Semua ' + group.label.toLowerCase() }}</small><button v-if="modelValue[group.key].length" type="button" @click="clear(group.key)">Reset</button></div>
      </div>
    </div>
    <small class="muted">Peserta khusus tetap mengikuti batasan akses ini. Perubahan akses juga berlaku untuk tautan pengerjaan yang sudah dibuka.</small>
  </fieldset>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { api, errorMessage } from './api';
const props = defineProps({modelValue:{type:Object,required:true},disabled:Boolean});
const emit = defineEmits(['update:modelValue']);
const groups = [{key:'positions',label:'Jabatan'},{key:'branches',label:'Cabang'}];
const directory = ref({positions:[],branches:[]});
const loading=ref(true),error=ref(''),search=ref({positions:'',branches:''});
function options(key) {
  const values=[...directory.value[key]];
  for(const value of props.modelValue[key]) if(!values.some(item=>item.value===value)) values.push({value,label:value+' (tersimpan)'});
  const query=(search.value[key]||'').trim().toLowerCase();
  return values.filter(item=>(item.label+' '+item.value).toLowerCase().includes(query));
}
function toggle(key,value,checked) {emit('update:modelValue',{...props.modelValue,[key]:checked?[...props.modelValue[key],value]:props.modelValue[key].filter(item=>item!==value)});}
function clear(key) {emit('update:modelValue',{...props.modelValue,[key]:[]});}
async function load() {
  loading.value=true;error.value='';
  try {const data=await api('get','audience-options');for(const key of ['positions','branches'])directory.value[key]=data[key].map(item=>({...item,value:String(item.value)}));}
  catch(e){error.value='Pilihan jabatan/cabang belum dapat dimuat. '+errorMessage(e);}
  finally {loading.value=false;}
}
onMounted(load);
</script>
<style scoped>
.audience {border-top:1px solid #eceaf2;padding-top:16px;margin-top:10px;}
.audience legend {font-weight:600;padding-top:16px;}
.audience-options {max-height:200px;overflow:auto;border:1px solid #eceaf2;border-radius:12px;padding:12px;display:grid;gap:10px;}
</style>
