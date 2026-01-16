<template>
  <div class="horizon-shell overflow-x-hidden">
    
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:40px_40px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-[var(--brand-primary)]/10 blur-[120px] rounded-full mix-blend-screen"></div>
    </div>

    <nav :class="['fixed top-0 w-full z-50 transition-all duration-500', isScrolled ? 'py-4 glass-panel border-b' : 'py-8 bg-transparent']">
      <div class="max-w-[1400px] mx-auto px-6 md:px-12 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[var(--text-main)] text-[var(--bg-body)] flex items-center justify-center font-black text-xl tracking-tighter rounded">
                TE
            </div>
            <span class="text-lg font-bold tracking-tight text-[var(--text-main)] uppercase">TinExpress</span>
        </div>

        <div class="hidden md:flex items-center gap-10">
            <a href="#features" class="text-sm font-medium hover:text-[var(--brand-primary)] transition-colors">Platform</a>
            <a href="#network" class="text-sm font-medium hover:text-[var(--brand-primary)] transition-colors">Network</a>
            <a href="#pricing" class="text-sm font-medium hover:text-[var(--brand-primary)] transition-colors">Enterprise</a>
        </div>

        <div class="flex items-center gap-4">
            <router-link to="/login" class="text-sm font-semibold hover:text-[var(--brand-primary)] transition-colors">Log In</router-link>
            <router-link to="/register" class="btn-primary text-sm px-5 py-2.5">
                Start Shipping
            </router-link>
        </div>
      </div>
    </nav>

    <header class="relative pt-40 pb-32 px-6 md:px-12 z-10 max-w-[1400px] mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        
        <div class="space-y-8 animate-slide-up">
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-[var(--text-main)] text-[var(--bg-body)] text-xs font-bold uppercase tracking-widest rounded-sm">
                <span class="w-2 h-2 rounded-full bg-[var(--brand-primary)] animate-pulse"></span>
                System Online
            </div>

            <h1 class="heading-xl">
                Logistics <br />
                <span class="text-[var(--text-muted)]">Without</span> <br />
                Limits.
            </h1>

            <p class="text-xl text-[var(--text-body)] max-w-lg leading-relaxed border-l-2 border-[var(--brand-primary)] pl-6">
                The enterprise operating system for freight. Connect carriers, automate bookings, and scale your logistics network in one unified interface.
            </p>

            <div class="flex flex-wrap gap-4 pt-4">
                <router-link to="/register" class="btn-primary text-lg px-8">
                    Get Access &rarr;
                </router-link>
                <button class="btn-secondary text-lg px-8">
                    Read Documentation
                </button>
            </div>
            
            <div class="pt-12 flex gap-8 opacity-60 grayscale">
                <span class="font-bold text-lg tracking-tight">HUNTER</span>
                <span class="font-bold text-lg tracking-tight">TNT</span>
                <span class="font-bold text-lg tracking-tight">COURIERS PLEASE</span>
                <span class="font-bold text-lg tracking-tight">AU POST</span>
            </div>
        </div>

        <div class="relative lg:h-[700px] w-full perspective-[2000px] animate-slide-up" style="animation-delay: 200ms;">
            <div class="relative w-full h-full bg-[var(--bg-card)] border border-[var(--border-color)] rounded-2xl shadow-2xl transform rotate-y-[-12deg] rotate-x-[5deg] hover:rotate-y-0 hover:rotate-x-0 transition-transform duration-1000 ease-out overflow-hidden group">
                
                <div class="h-14 border-b border-[var(--border-color)] bg-[var(--bg-surface)] flex items-center justify-between px-6">
                    <div class="flex gap-2">
                        <div class="w-3 h-3 rounded-full bg-[var(--border-color)]"></div>
                        <div class="w-3 h-3 rounded-full bg-[var(--border-color)]"></div>
                    </div>
                    <div class="px-3 py-1 bg-[var(--bg-body)] rounded text-[10px] font-mono text-[var(--text-muted)]">SHIPMENT_ID: #882190</div>
                </div>

                <div class="p-8 space-y-8 bg-[linear-gradient(to_bottom,transparent,var(--bg-surface))]">
                    <div class="flex justify-between items-end">
                        <div>
                            <div class="text-sm text-[var(--text-muted)] uppercase tracking-wider font-bold mb-1">Status</div>
                            <div class="text-3xl font-bold text-[var(--brand-primary)] flex items-center gap-3">
                                IN TRANSIT
                                <span class="w-3 h-3 bg-[var(--brand-primary)] rounded-full animate-ping"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-[var(--text-muted)] font-mono">EST DELIVERY</div>
                            <div class="text-xl font-bold text-[var(--text-main)]">TOMORROW, 14:00</div>
                        </div>
                    </div>

                    <div class="h-64 w-full bg-[var(--bg-body)] rounded-lg border border-[var(--border-color)] relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20 bg-[linear-gradient(45deg,var(--text-main)_1px,transparent_1px)] bg-[size:20px_20px]"></div>
                        <svg class="absolute inset-0 w-full h-full" style="stroke: var(--brand-primary); stroke-width: 2; fill: none;">
                            <path d="M 50 200 Q 150 50 350 150 T 600 100" />
                        </svg>
                        <div class="absolute left-[50px] top-[200px] w-4 h-4 bg-[var(--text-main)] rounded-full border-4 border-[var(--bg-card)]"></div>
                        <div class="absolute right-[50px] top-[100px] w-4 h-4 bg-[var(--brand-primary)] rounded-full border-4 border-[var(--bg-card)] shadow-[0_0_20px_var(--brand-primary)]"></div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 bg-[var(--bg-body)] rounded border border-[var(--border-color)]">
                            <div class="text-[10px] text-[var(--text-muted)] uppercase">Weight</div>
                            <div class="font-mono font-bold">24.5 KG</div>
                        </div>
                        <div class="p-4 bg-[var(--bg-body)] rounded border border-[var(--border-color)]">
                            <div class="text-[10px] text-[var(--text-muted)] uppercase">Service</div>
                            <div class="font-mono font-bold">EXPRESS</div>
                        </div>
                        <div class="p-4 bg-[var(--bg-body)] rounded border border-[var(--border-color)]">
                            <div class="text-[10px] text-[var(--text-muted)] uppercase">Cost</div>
                            <div class="font-mono font-bold">$42.00</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="absolute -z-10 -bottom-10 -right-10 w-64 h-64 bg-gradient-to-br from-[var(--brand-primary)] to-transparent opacity-20 blur-3xl rounded-full"></div>
        </div>
      </div>
    </header>

    <div class="border-y border-[var(--border-color)] bg-[var(--bg-card)]">
        <div class="max-w-[1400px] mx-auto grid grid-cols-2 md:grid-cols-4 divide-x divide-[var(--border-color)]">
            <div class="p-8 text-center group hover:bg-[var(--bg-surface)] transition-colors">
                <div class="text-3xl font-black text-[var(--text-main)] mb-1">15ms</div>
                <div class="text-xs uppercase font-bold text-[var(--text-muted)] tracking-widest">Quote Speed</div>
            </div>
            <div class="p-8 text-center group hover:bg-[var(--bg-surface)] transition-colors">
                <div class="text-3xl font-black text-[var(--text-main)] mb-1">99.9%</div>
                <div class="text-xs uppercase font-bold text-[var(--text-muted)] tracking-widest">Uptime</div>
            </div>
            <div class="p-8 text-center group hover:bg-[var(--bg-surface)] transition-colors">
                <div class="text-3xl font-black text-[var(--text-main)] mb-1">10+</div>
                <div class="text-xs uppercase font-bold text-[var(--text-muted)] tracking-widest">Carriers</div>
            </div>
            <div class="p-8 text-center group hover:bg-[var(--bg-surface)] transition-colors">
                <div class="text-3xl font-black text-[var(--text-main)] mb-1">24/7</div>
                <div class="text-xs uppercase font-bold text-[var(--text-muted)] tracking-widest">Support</div>
            </div>
        </div>
    </div>

    <section id="features" class="py-32 px-6 md:px-12 max-w-[1400px] mx-auto">
        <div class="mb-20 max-w-2xl">
            <h2 class="heading-lg mb-6">Engineered for Scale.</h2>
            <p class="text-xl text-[var(--text-body)]">
                We've stripped away the complexity of logistics. No more spreadsheets, no more phone calls. Just pure, automated shipping infrastructure.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-6 grid-rows-[auto_auto]">
            
            <div class="md:col-span-4 p-10 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] hover:border-[var(--brand-primary)] transition-colors group relative overflow-hidden">
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-lg flex items-center justify-center text-2xl mb-6 shadow-sm">
                        📊
                    </div>
                    <h3 class="text-2xl font-bold mb-3">Instant Rate Calculation</h3>
                    <p class="text-[var(--text-body)] max-w-sm">
                        Compare apples to apples. Our engine standardizes surcharges, fuel levies, and volumetric weights instantly.
                    </p>
                </div>
                <div class="absolute right-0 bottom-0 w-1/2 h-full opacity-50 mask-image-linear-to-l">
                    <div class="h-full w-full flex items-end gap-2 p-10">
                        <div class="w-full bg-[var(--border-color)] h-[40%] rounded-t group-hover:bg-[var(--brand-primary)] transition-colors duration-500"></div>
                        <div class="w-full bg-[var(--border-color)] h-[70%] rounded-t group-hover:bg-[var(--text-main)] transition-colors duration-700"></div>
                        <div class="w-full bg-[var(--border-color)] h-[55%] rounded-t"></div>
                        <div class="w-full bg-[var(--border-color)] h-[80%] rounded-t"></div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 md:row-span-2 p-10 rounded-2xl bg-[var(--text-main)] text-[var(--bg-body)] relative overflow-hidden group">
                <div class="relative z-10 h-full flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center text-2xl mb-6 backdrop-blur-sm">
                            💳
                        </div>
                        <h3 class="text-2xl font-bold mb-3 text-white">Closed-Loop Wallet</h3>
                        <p class="text-white/60">
                            Control spend with prepaid credits. Eliminate surprise billing and credit checks.
                        </p>
                    </div>
                    <div class="mt-10 p-4 bg-white/5 rounded-xl border border-white/10 backdrop-blur-md">
                        <div class="text-xs uppercase tracking-widest opacity-50 mb-1">Balance</div>
                        <div class="text-3xl font-mono font-bold text-[var(--brand-primary)]">$4,250.00</div>
                    </div>
                </div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-[var(--brand-primary)] opacity-20 blur-[80px] group-hover:opacity-40 transition-opacity duration-500"></div>
            </div>

            <div class="md:col-span-2 p-10 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] hover:border-[var(--brand-primary)] transition-colors">
                <div class="w-12 h-12 bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-lg flex items-center justify-center text-2xl mb-6 shadow-sm">
                    🔌
                </div>
                <h3 class="text-xl font-bold mb-2">Shopify Native</h3>
                <p class="text-[var(--text-body)] text-sm">
                    Two-way sync. Orders flow in, tracking numbers flow out. Zero manual entry.
                </p>
            </div>

            <div class="md:col-span-2 p-10 rounded-2xl bg-[var(--bg-card)] border border-[var(--border-color)] hover:border-[var(--brand-primary)] transition-colors">
                <div class="w-12 h-12 bg-[var(--bg-surface)] border border-[var(--border-color)] rounded-lg flex items-center justify-center text-2xl mb-6 shadow-sm">
                    📍
                </div>
                <h3 class="text-xl font-bold mb-2">Granular Tracking</h3>
                <p class="text-[var(--text-body)] text-sm">
                    Unified status codes. Whether it's TNT or CouriersPlease, "Delivered" means Delivered.
                </p>
            </div>

        </div>
    </section>

    <section class="py-32 px-6">
        <div class="max-w-[1400px] mx-auto bg-[var(--text-main)] rounded-[3rem] p-12 md:p-32 text-center relative overflow-hidden">
            <div class="relative z-10">
                <h2 class="text-5xl md:text-8xl font-black text-white mb-8 tracking-tighter">
                    READY TO <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--brand-primary)] to-orange-400">SHIP?</span>
                </h2>
                <div class="flex flex-col md:flex-row gap-6 justify-center items-center mt-12">
                    <router-link to="/register" class="px-10 py-5 bg-[var(--brand-primary)] text-white text-xl font-bold rounded-full hover:scale-105 transition-transform shadow-[0_0_40px_var(--brand-glow)]">
                        Create Free Account
                    </router-link>
                    <span class="text-white/40 text-sm font-medium">Or contact enterprise sales</span>
                </div>
            </div>
            
            <div class="absolute inset-0 opacity-10 bg-[linear-gradient(#fff_1px,transparent_1px),linear-gradient(90deg,#fff_1px,transparent_1px)] bg-[size:60px_60px]"></div>
        </div>
    </section>

    <footer class="py-12 border-t border-[var(--border-color)] bg-[var(--bg-surface)]">
        <div class="max-w-[1400px] mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2 font-bold text-[var(--text-main)]">
                <div class="w-6 h-6 bg-[var(--text-main)] rounded-sm"></div>
                TIN EXPRESS
            </div>
            <div class="text-sm text-[var(--text-muted)]">
                &copy; {{ new Date().getFullYear() }} Tin Logistics Pty Ltd. ABN 12 345 678 901.
            </div>
        </div>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
/* Custom mask for the bento grid fade out */
.mask-image-linear-to-l {
    mask-image: linear-gradient(to left, black, transparent);
    -webkit-mask-image: linear-gradient(to left, black, transparent);
}
</style>