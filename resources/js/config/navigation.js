export const navigationMenu = [
    {
        category: "Operations",
        items: [
            { name: "Rate Calculator", path: "/dashboard", icon: "📊" },
            { name: "Shipment History", path: "/dashboard/shipments", icon: "📦" },
        ]
    },
    {
        category: "Management",
        items: [
            { name: "Team Access", path: "/dashboard/team", icon: "👥" },
            { name: "Company Settings", path: "/dashboard/settings", icon: "⚙️" },
            { name: "Integrations Hub", path: "/dashboard/integrations", icon: "🔌" },
        ]
    },
    {
        category: "Help & Support",
        items: [
            { name: "Support Center", path: "/dashboard/support", icon: "🚑" },
        ]
    }
];

export const adminMenu = [
    {
        category: "System Admin",
        items: [
            { name: "Admin Dashboard", path: "/admin", icon: "🛡️" },
            { name: "Companies", path: "/admin/companies", icon: "🏢" },
            { name: "Carriers", path: "/admin/carriers", icon: "🚚" },
            { name: "Shipments", path: "/admin/shipments", icon: "📦" },
            { name: "Users", path: "/admin/users", icon: "👤" },
            { name: "Promotions", path: "/admin/promotions", icon: "🏷️" },
        ]
    }
];