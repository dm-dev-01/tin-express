import { createRouter, createWebHistory } from 'vue-router';
import PublicHome from './components/Pages/PublicHome.vue';
import Login from './components/Pages/Login.vue';
import Register from './components/Pages/Register.vue';
import DashboardLayout from './components/Layout/DashboardLayout.vue';
import RateCalculator from './components/Rates/RateCalculator.vue';
import TeamSettings from './components/Pages/TeamSettings.vue';
import ShipmentHistory from './components/Pages/ShipmentHistory.vue';
import ShipmentDetails from './components/Pages/ShipmentDetails.vue';
import CompanySettings from './components/Pages/CompanySettings.vue';
import AdminDashboard from './components/Admin/AdminDashboard.vue';
import AdminCompanies from './components/Admin/AdminCompanies.vue';
import AdminCarriers from './components/Admin/AdminCarriers.vue';
import AdminUsers from './components/Admin/AdminUsers.vue';
import AdminShipments from './components/Admin/AdminShipments.vue';
import AdminPromotions from './components/Admin/AdminPromotions.vue';
import SupportIndex from './components/Pages/SupportIndex.vue';
import SupportShow from './components/Pages/SupportShow.vue';
import IntegrationsIndex from './components/Pages/IntegrationsIndex.vue';


const routes = [
    {
        path: '/',
        component: PublicHome
    },
    {
        path: '/login',
        component: Login
    },
    { path: '/register', component: Register },
    {
        path: '/dashboard',
        component: DashboardLayout,
        children: [
            { path: '', component: RateCalculator },
            { path: 'team', component: TeamSettings },
            { path: 'settings', component: CompanySettings },
            { path: 'shipments', component: ShipmentHistory },
            { path: 'shipments/:id', component: ShipmentDetails },
            { path: 'support', component: SupportIndex },
            { path: 'support/:id', component: SupportShow },
            { path: 'integrations', component: IntegrationsIndex },
        ],
        // Simple Navigation Guard: Check if logged in
        beforeEnter: (to, from, next) => {
            if (!localStorage.getItem('auth_token')) {
                next('/login');
            } else {
                next();
            }
        }
    },
    {
        path: '/admin',
        component: DashboardLayout,
        children: [
            { path: '', component: AdminDashboard },        // Default to Overview
            { path: 'companies', component: AdminCompanies },
            { path: 'carriers', component: AdminCarriers },
            { path: 'users', component: AdminUsers },
            { path: 'shipments', component: AdminShipments },
            { path: 'promotions', component: AdminPromotions },
            { path: 'support', component: SupportIndex },
            { path: 'support/:id', component: SupportShow }, // Link Carriers
        ],
        beforeEnter: (to, from, next) => {
            // Check auth token presence
            if (!localStorage.getItem('auth_token')) next('/login');
            // Check role (basic frontend check)
            else if (localStorage.getItem('user_role') !== 'super_admin') next('/dashboard');
            else next();
        }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;