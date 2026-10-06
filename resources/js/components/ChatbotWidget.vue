
<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
    <!-- Floating Chat Button -->
    <button @click="toggleChat" class="bg-sidebar text-white rounded-full shadow-lg w-16 h-16 flex items-center justify-center bg-white hover:shadow-2xl border hover:border-purple-700 hover:shadow-purple-700 transition focus:outline-none">
     
      <img v-if="!open" src="/assets/images/icons/cartoon-ai.jpg" alt="Bot" class="w-10 h-10 rounded-full border-2 border-white bg-white object-cover" />
      <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-purple-700">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
    <!-- Chatbot Panel -->
    <transition name="fade">
      <div v-if="open" class="w-80 h-[430px] bg-white rounded-2xl shadow-2xl mt-4 flex flex-col overflow-hidden border border-gray-200 animate-fadeIn">
        <!-- Header -->
        <div class="bg-sidebar text-gray-800 px-4 py-3 flex items-center gap-3 relative">
          <img src="/assets/images/icons/cartoon-ai.jpg" alt="Bot" class="w-10 h-10 rounded-full border-2 border-white bg-white object-cover" />
          <div class="flex flex-col">
            <span class="font-semibold leading-tight">Bella Assistant</span>
            <span class="text-xs text-green-500 font-semibold flex items-center gap-1">
              <span class="inline-block w-2 h-2 bg-green-400 rounded-full"></span> Online
            </span>
          </div>
          <button @click="toggleChat" class="absolute right-3 top-3 text-white hover:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <!-- Chat Body -->
        <div class="flex-1 p-4 overflow-y-auto bg-gray-50" ref="chatBody">
          <div v-for="(msg, idx) in messages" :key="idx" class="mb-3 flex group" :class="msg.from === 'user' ? 'justify-end' : 'justify-start'">
            <div class="flex items-end gap-2" v-if="msg.from === 'bot'">
              <img src="/assets/images/icons/cartoon-ai.jpg" alt="Bot" class="w-7 h-7 rounded-full border border-gray-200 bg-white object-cover" />
              <div class="bg-white text-gray-800 rounded-xl px-4 py-2 max-w-[70%] shadow border border-gray-100">
                <span class="whitespace-pre-line" :class='msg.style'>{{ msg.text }}</span>
              </div>
            </div>
            <div class="flex items-end gap-2" v-else>
              <div class="bg-sidebar text-gray-800 rounded-xl px-4 py-2 max-w-[70%] shadow">
                <span class="whitespace-pre-line">{{ msg.text }}</span>
              </div>
              <img src="/assets/images/photos/photo-1.png" alt="You" class="w-7 h-7 rounded-full border border-gray-200 bg-white object-cover" />
            </div>
          </div>
        </div>
        <!-- Input -->
        <form @submit.prevent="sendMessage" class="flex p-3 border-t border-gray-200 bg-white">
          <input v-model="input" type="text" placeholder="Tulis pesan..." class="flex-1 border rounded-l px-3 py-2 focus:outline-sidebar focus:ring-2 focus:ring-sidebar" autocomplete="off" />
          <button type="submit" class="bg-sidebar border text-gray-500 px-4 py-2 rounded-r hover:bg-purple-700 hover:text-white transition">Kirim</button>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { sendAssistantMessage } from '@/services/mutations/assistantMutation.ts';

const open = ref(false);
const input = ref('');
const messages = ref([
  { from: 'bot', text: 'Halo👋! Saya Bella Assistant. Ada yang bisa saya bantu hari ini?' }
]);
const chatBody = ref(null);

function toggleChat() {
  open.value = !open.value;
  if (open.value) {
    nextTick(() => {
      if (chatBody.value) chatBody.value.scrollTop = chatBody.value.scrollHeight;
    });
  }
}

const token = '';

function sendMessage() {
  if (!token) {
    messages.value.push({from:'bot', text:'Layanan asisten belum dikonfigurasi.'});
    return;
  }
  if (!input.value.trim()) return;
  messages.value.push({ from: 'user', text: input.value });
  const userMsg = input.value;
  input.value = '';
  nextTick(() => {
    if (chatBody.value) chatBody.value.scrollTop = chatBody.value.scrollHeight;
  });
  // Animasi mengetik
  let typingMsg = { from: 'bot', text: 'Sedang mengetik...', style: 'italic text-gray-400 animate-pulse' };
  messages.value.push(typingMsg);
  setTimeout(() => {
    sendAssistantMessage(userMsg, token)
      .then(response => {
        let reply = response.data?.choices?.[0]?.message?.content;
        // Hapus animasi mengetik sebelum push reply
        const typingIndex = messages.value.findIndex(m => m.text === typingMsg.text);
        if (typingIndex !== -1) {
          messages.value.splice(typingIndex, 1);
        }
        messages.value.push({ from: 'bot', text: reply });
      })
      .catch((err) => {
        console.log(err.message);
        let reply = 'Maaf, saya masih bot sederhana. Pertanyaan Anda: ' + userMsg;
        if (/halo|hai|hi|assalam/i.test(userMsg)) reply = 'Halo juga! Ada yang bisa saya bantu?';
        if (/dokumen|upload/i.test(userMsg)) reply = 'Untuk upload dokumen, silakan gunakan menu Pengumuman atau Formulir.';
        if (/pengumuman/i.test(userMsg)) reply = 'Menu Pengumuman berisi informasi terbaru. Silakan cek di sidebar.';
        const typingIndex = messages.value.findIndex(m => m.text === typingMsg.text);
        if (typingIndex !== -1) {
          messages.value.splice(typingIndex, 1);
        }
        messages.value.push({ from: 'bot', text: reply });
      })
      .finally(() => {
        nextTick(() => {
          if (chatBody.value) chatBody.value.scrollTop = chatBody.value.scrollHeight;
        });
      });
  }, 800);
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.animate-fadeIn {
  animation: fadeIn 0.25s;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
