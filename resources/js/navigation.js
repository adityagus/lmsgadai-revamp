import { computed, defineComponent, h, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const paths = {
  landingpage:'/', 'sign-in':'/sign-in', overview:'/overview', lms:'/kelas',
  coursesCreate:'/courses/create', coursesEdit:'/courses/:courseId/edit', courseDetail:'/kelas/:id',
  'content-create':'/course/create-contents/:courseId', 'content-update':'/course/update-contents/:courseId/:contentId',
  'content-preview':'/student/kelas/:id', 'content-admin-preview':'/course/:id/preview', studentCourses:'/student/kelas',
  pengumuman:'/pengumuman', formulir:'/formulir', report:'/report/documents', 'report-documents':'/report/documents',
  'information-document-create':'/information-document/create', 'information-document-update':'/information-document/update/:id',
  'detail-pengumuman':'/detail-pengumuman/:id', 'archive-pengumuman':'/archive-pengumuman',
  categories:'/master/categories', 'audit-logs':'/master/audit-logs', detailPage:'/detail-course/:id',
};
export function resolveHref(to) {
  if (typeof to === 'string') return to.startsWith('/') || /^https?:/.test(to) ? to : '/'+to;
  let path = to.path || paths[to.name];
  if (!path) throw new Error('Unknown page: '+to.name);
  path = path.replace(/:([\w]+)/g, (_, key) => {
    if (to.params?.[key] == null) throw new Error('Missing route parameter: '+key);
    return encodeURIComponent(to.params[key]);
  });
  const query = new URLSearchParams();
  Object.entries(to.query || {}).forEach(([key,value]) => {
    if (value != null) (Array.isArray(value) ? value : [value]).forEach(item => query.append(key,item));
  });
  return path+(query.size ? '?'+query : '')+(to.hash || '');
}
export function useRoute() {
  const page = usePage();
  return new Proxy({}, {get(_, key) {
    if (key === 'path') return page.url.split('?')[0].split('#')[0];
    if (key === 'fullPath') return page.url;
    if (key === 'hash') return window.location.hash;
    return page.props.routeContext?.[key] || (key === 'name' ? '' : {});
  }});
}
const visit = (to, replace=false) => new Promise((resolve, reject) => router.visit(resolveHref(to), {
  replace, onSuccess:resolve, onError:reject, onCancel:resolve,
}));
export const useRouter = () => ({
  push: to => visit(to), replace: to => visit(to,true),
  back: () => window.history.back(), go: delta => window.history.go(delta),
  resolve: to => ({href:resolveHref(to)}),
});
export const RouterLink = defineComponent({
  inheritAttrs:false,
  props:{to:{type:[String,Object],required:true},replace:Boolean},
  setup(props,{attrs,slots}) {
    const page = usePage();
    const href = computed(() => resolveHref(props.to));
    return () => h(Link,{...attrs,href:href.value,replace:props.replace,
      class:[attrs.class, page.url.split('?')[0] === href.value.split('?')[0] ? 'router-link-active' : ''],
    }, slots);
  },
});

// Preserve asynchronous save/form guards when an Inertia visit leaves a module.
const guards = new Set();
let replay = false;
let checking = false;
async function canLeave(destination) {
  for (const guard of [...guards]) {
    if (await guard(destination) === false) return false;
  }
  return true;
}
export function installNavigationGuards() {
  let currentEntry = {state:window.history.state,url:window.location.href};
  let replayPop = false;
  router.on('navigate', () => {currentEntry = {state:window.history.state,url:window.location.href};});
  // Native back/forward doesn't emit Inertia's before event. Hold the popstate
  // until the same form/quiz guards have approved leaving the current page.
  window.addEventListener('popstate', event => {
    if (replayPop || !guards.size) return;
    event.stopImmediatePropagation();
    const targetState = event.state;
    const targetUrl = new URL(window.location.href);
    const previous = currentEntry;
    (async () => {
      try {
        if (checking || !await canLeave({path:targetUrl.pathname,fullPath:targetUrl.pathname+targetUrl.search})) {
          window.history.pushState(previous.state,'',previous.url);
          return;
        }
        replayPop = true;
        window.dispatchEvent(new PopStateEvent('popstate',{state:targetState}));
      } finally {replayPop = false;}
    })();
  }, true);
  router.on('before', event => {
    if (replay || !guards.size || event.detail.visit.method !== 'get') return;
    event.preventDefault();
    if (checking) {event.detail.visit.onCancel?.();return;}
    checking = true;
    const pending = event.detail.visit;
    const destination = {path:pending.url.pathname, fullPath:pending.url.pathname+pending.url.search};
    (async () => {
      try {
        if (!await canLeave(destination)) {pending.onCancel?.();return;}
        replay = true;
        router.visit(pending.url.href, pending);
      } finally { replay = false; checking = false; }
    })();
  });
}
export function onBeforeRouteLeave(callback) {
  const route = useRoute();
  const wrapped = to => callback.length >= 3
    ? new Promise(resolve => callback(to, route, resolve))
    : callback(to, route);
  guards.add(wrapped);
  onUnmounted(() => guards.delete(wrapped));
}
