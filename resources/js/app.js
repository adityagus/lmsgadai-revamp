import '../css/app.css';
import { createApp, h, watch } from 'vue';
import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query';
import AppLayout from './layouts/AppLayout.vue';
import { RouterLink, useRoute, useRouter, installNavigationGuards } from './navigation';
import pages from './pageRegistry';
createInertiaApp({
  title: title => `${title} · LMS Gadai Mulia`,
  resolve: async name => {
    const loader = pages[name];
    if (!loader) throw new Error(`Page not found: ${name}`);
    const component = (await loader()).default;
    component.layout = (render, page) => page.props.fullscreen ? page : render(AppLayout, {}, () => page);
    return component;
  },
  setup({el,App,props,plugin}) {
    const app = createApp({render:()=>h(App,props)});
    app.use(plugin).use(VueQueryPlugin,{queryClient:new QueryClient()});
    app.component('RouterLink',RouterLink);
    Object.defineProperty(app.config.globalProperties,'$route',{get:()=>useRoute()});
    Object.defineProperty(app.config.globalProperties,'$router',{get:()=>useRouter()});
    app.mixin({mounted() {
      if(this.$root !== this) return;
      const page = usePage();
      watch(()=>page.props.csrfToken, token=>{
        const meta=document.querySelector('meta[name="csrf-token"]');
        if(meta && token) meta.content=token;
      },{immediate:true});
    }});
    installNavigationGuards();
    app.mount(el);
  },
  progress:{color:'#662FFF'},
});
