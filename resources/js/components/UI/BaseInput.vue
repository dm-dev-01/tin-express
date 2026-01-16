<template>
  <div class="w-full">
    <label v-if="label" :for="id" class="block text-sm font-semibold text-slate-700 mb-1.5">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <input
        :id="id"
        v-bind="$attrs"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 hover:border-slate-400 disabled:bg-slate-50 disabled:text-slate-500"
        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': error }"
      />
      <div v-if="$slots.icon" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
        <slot name="icon"></slot>
      </div>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-600 animate-fade-in-up">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  label: String,
  id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
  error: String,
  required: Boolean
});
defineEmits(['update:modelValue']);
</script>
