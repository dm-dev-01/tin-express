<template>
  <div class="min-h-screen bg-surface flex flex-col font-sans">
    
    <nav class="w-full py-6 px-8 flex justify-between items-center max-w-7xl mx-auto">
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 bg-primary rounded-lg flex items-center justify-center text-white font-bold">T</div>
        <span class="text-xl font-bold tracking-tight text-primary">TIN EXPRESS</span>
      </div>
      <div class="flex items-center gap-6">
        <router-link to="/login" class="text-sm font-semibold text-text-muted hover:text-primary transition-colors">Sign In</router-link>
        <router-link to="/register" class="bg-primary text-white px-5 py-2.5 rounded-full text-sm font-medium hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">
          Start Shipping
        </router-link>
      </div>
    </nav>

    <main class="flex-1 flex flex-col items-center justify-center text-center px-4 relative overflow-hidden">
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none opacity-40 z-0">
          <div class="absolute top-20 left-20 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
          <div class="absolute top-20 right-20 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
      </div>

      <div class="relative z-10 max-w-4xl mx-auto">
        <div class="inline-flex items-center px-3 py-1 rounded-full border border-blue-100 bg-blue-50 text-blue-600 text-xs font-semibold uppercase tracking-wide mb-6">
          🚀 New: Multi-Carrier API V1
        </div>
        
        <h1 class="text-6xl md:text-7xl font-extrabold text-primary mb-8 tracking-tight leading-tight">
          Logistics for the <br />
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-accent to-purple-600">Modern Era</span>
        </h1>
        
        <p class="text-xl text-text-muted max-w-2xl mx-auto mb-10 leading-relaxed">
          Access enterprise shipping rates from Australia's top couriers. 
          Instant quotes, seamless booking, and real-time tracking in one API-first platform.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link to="/register" class="bg-primary text-white px-8 py-4 rounded-xl text-lg font-semibold shadow-xl shadow-primary/20 hover:bg-primary-hover hover:scale-105 transition-all duration-200">
            Get Started for Free
          </router-link>
          <button class="px-8 py-4 rounded-xl text-lg font-semibold text-primary bg-white border border-slate-200 hover:bg-slate-50 transition-all duration-200">
            View API Docs
          </button>
        </div>

        <div class="mt-16 pt-8 border-t border-slate-100">
            <p class="text-sm text-slate-400 font-medium mb-4">POWERING LOGISTICS FOR</p>
            <div class="flex justify-center gap-8 opacity-50 grayscale">
                <span class="font-bold text-xl">ACME Corp</span>
                <span class="font-bold text-xl">Logistix</span>
                <span class="font-bold text-xl">ShipFast</span>
                <span class="font-bold text-xl">GlobalTrade</span>
            </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style>
/* Simple CSS Animation for the background blobs */
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>
