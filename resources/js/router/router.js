import { createRouter, createWebHistory } from "vue-router";
import Home from "../components/Home.vue";
import Dashboard from "../components/Dashboard.vue";
import ListProduct from "../components/Products/ListProduct.vue";
import Category from "../components/Category/Categories.vue";
import ProductAttribute from "../components/Products/ListProductAttribute.vue";
import ProductAttributeValue from "../components/Products/ListProductAttributeValue.vue";
import ArticalCategory from "../components/Artical/Category.vue";
import Artical from "../components/Artical/Artical.vue";
import Coupon from "../components/Coupon/Coupon.vue";
import CouponDetails from "../components/Coupon/Details.vue";
import Banner from "../components/Banner.vue";
import Orders from "../components/Orders/index.vue";
import OrderDetails from "../components/Orders/Details.vue";
import Transactions from "../components/Transactions/Index.vue";
import HistoryPriceProduct from "../components/Transactions/HistoryPriceProduct.vue";
import DetailCategorys from "../components/DetailCategories.vue";


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
            {
                path: "coupon",
                name: "coupon",
                component: Coupon,
            },
            {
                path: "coupon/:id",
                name: "couponDetails",
                component: CouponDetails,
            },
            {
                path: "banner",
                name: "banner",
                component: Banner,
            },
            {
                path: "orders",
                name: "orders",
                component: Orders,
            },
            {
                path: "orders/:id",
                name: "ordersDetails",
                component: OrderDetails,
            },
            {
                path: "transactions",
                name: "transactions",
                component: Transactions,
            },
            {
                path: "history-price",
                name: "history-price",
                component: HistoryPriceProduct,
            },
            {
                path: "detail-category",
                name: "detail-category",
                component: DetailCategorys,
            },
        ]
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
