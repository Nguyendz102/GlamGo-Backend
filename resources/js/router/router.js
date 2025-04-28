import { createRouter, createWebHistory } from "vue-router";
import Home from "../components/Home.vue";
import Dashboard from "../components/Dashboard.vue";
import ListProduct from "../components/Products/ListProduct.vue";
import Category from "../components/Category/Categories.vue";
import ProductAttribute from "../components/Products/ListProductAttribute.vue";
import ProductAttributeValue from "../components/Products/ListProductAttributeValue.vue";
import ArticalCategory from "../components/Artical/Category.vue";
import Artical from "../components/Artical/Artical.vue";

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
            {
                path: "product-attribute/:id/:name",
                name: "product-attribute",
                component: ProductAttribute,
                props: true, // Bật props để có thể nhận tham số id trong component
            },
            {
                path: "product-attribute-value/:idProduct/:nameProduct/:idAttribute/:nameAttribute",
                name: "product-attribute-value",
                component: ProductAttributeValue,
                props: true, // Bật props để có thể nhận tham số id trong component
            },
            {
                path: "artical-category",
                name: "articalCategory",
                component: ArticalCategory,
            },
            {
                path: "artical",
                name: "artical",
                component: Artical,
            },
        ]
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
