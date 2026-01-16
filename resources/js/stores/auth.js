import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        company: null,
        role: null,
        isLoaded: false,
    }),

    getters: {
        // Safe accessors that don't crash if user is null
        fullName: (state) => state.user ? `${state.user.first_name} ${state.user.last_name}` : 'Loading...',
        companyName: (state) => state.company?.entity_name || 'Enterprise Account',
        userInitials: (state) => state.user ? state.user.first_name[0] + state.user.last_name[0] : 'TE',
        isSuperAdmin: (state) => state.role === 'super_admin',
    },

    actions: {
        async fetchUser() {
            if (this.isLoaded) return; // Deduplication: Don't fetch if we already have it

            try {
                const response = await axios.get('/api/v1/user');
                this.user = response.data;
                this.company = response.data.company;
                this.role = response.data.role;
                this.isLoaded = true;
            } catch (error) {
                if (error.response?.status === 401) {
                    this.logout(false); // Session expired
                }
            }
        },

        async logout(callApi = true) {
            if (callApi) {
                try { await axios.post('/api/v1/logout'); } catch (e) {}
            }
            
            // Clear State
            this.user = null;
            this.company = null;
            this.role = null;
            this.isLoaded = false;
            
            // Clear LocalStorage (Legacy support)
            localStorage.clear();
            
            router.push('/login');
        }
    }
});