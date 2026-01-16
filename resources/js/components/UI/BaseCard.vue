<template>
  <div class="bg-[var(--bg-card)] border border-[var(--border-color)] rounded-xl shadow-sm overflow-hidden flex flex-col h-full transition-all duration-300 hover:shadow-md hover:border-[var(--border-color)] group">
    
    <div v-if="$slots.header" class="px-6 py-4 border-b border-[var(--border-color)] bg-[var(--bg-surface)] flex items-center justify-between">
        <div class="text-[var(--text-main)] font-bold tracking-tight text-sm uppercase">
            <slot name="header"></slot>
        </div>
        <div class="flex gap-1.5 opacity-20 group-hover:opacity-100 transition-opacity">
            <div class="w-1.5 h-1.5 rounded-full bg-[var(--text-main)]"></div>
            <div class="w-1.5 h-1.5 rounded-full bg-[var(--text-main)]"></div>
        </div>
    </div>
    
    <div class="p-6 flex-1 text-[var(--text-body)]">
        <slot></slot>
    </div>

    <div v-if="$slots.footer" class="px-6 py-4 bg-[var(--bg-surface)] border-t border-[var(--border-color)] text-sm">
        <slot name="footer"></slot>
    </div>
  </div>
</template>