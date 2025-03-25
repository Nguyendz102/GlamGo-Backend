import { createRouter, createWebHistory } from "vue-router";
import Home from "../components/Home.vue";
import Dashboard from "../components/Dashboard.vue";
import ListProduct from "../components/Products/ListProduct.vue";
import Category from "../components/Category/Categories.vue";


const routes = [
    {
        path: "/admin",
        component: Home,
        children: [
            {
                path: "",
                redirect: "admin/dashboard"
            },
            {
                path: "dashboard",
                name: "dashboard",
                component: Dashboard,
            },
            {
                path: "category",
                name: "category",
                component: Category,
            },
            {
                path: "products",
                name: "products",
                component: ListProduct,
            },
        ]
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
