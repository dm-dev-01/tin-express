import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';

const app = createApp({});
const pinia = createPinia();

// --- THEME INITIALIZATION ---
// Check localStorage or System Preference
const savedTheme = localStorage.getItem('theme');
const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

app.use(pinia);
app.use(router);
app.mount('#app');