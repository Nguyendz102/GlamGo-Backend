import { createRouter, createWebHistory } from "vue-router";
import Home from "../components/App.vue";
import About from "../components/About.vue";


const routes = [
    {
        path: "/admin",
        component: Home,
        children: [
            {
                path: "dashboard",
                name: "dashboard",
                component: About,
            },
        ]
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
