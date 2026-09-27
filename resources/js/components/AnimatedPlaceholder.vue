<template>
  <!-- membuat custom class -->
  <div class="placeholder">
    &nbsp;
  </div>
</template>

<script setup>
  defineProps({
    height: {
      type: String,
    },
    width: {
      type: String,
    },
    borderRadius: {
      type: String,
    },
    customClass: {
      type: String,
    },
  })
</script>

<style scoped>

@keyframes bgAnimate {
  0% {
    background-position: 200% 0%;
  }
  50% {
    background-position: 0% 0%;
  }
  100% {
    background-position: 200% 0%;
  }
}

.placeholder{
  height: v-bind(height);
  width: v-bind(width);
  border-radius: v-bind(borderRadius);
  background-image: linear-gradient(
    to right,
    #d6d7d8 0%,
    #e2e3e4 10%,
    #d6d7d8 20%,
    #d6d7d8 100%
  );
  background-size: 200% 100%;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1),
  0 2px 4px -2px rgb(0 0 0 / 0.1);
  animation: bgAnimate 2.2s linear infinite;
}
</style>