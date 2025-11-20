import { createRouter, createWebHistory } from "vue-router";
import ListOverview from "../pages/ListOverview.vue";
import ListDetailView from "../pages/ListDetailView.vue";

const routes = [
  {
    path: "/",
    name: "home",
    component: ListOverview,
  },
  {
    path: "/lists/:id",
    name: "list-detail",
    component: ListDetailView,
    props: true,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

export default router;
