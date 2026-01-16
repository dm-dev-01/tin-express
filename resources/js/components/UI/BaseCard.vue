<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-card hover:shadow-card-hover transition-all duration-500 ease-out group">
    <div v-if="$slots.header" class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
        <slot name="header"></slot>
    </div>
    
    <div class="p-6">
        <slot></slot>
    </div>

    <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50/50 border-t border-slate-50 rounded-b-2xl">
        <slot name="footer"></slot>
    </div>
  </div>
</template>
