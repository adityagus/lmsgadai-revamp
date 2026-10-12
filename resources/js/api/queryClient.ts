import { QueryClient } from '@tanstack/vue-query';

export const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 60 * 1000,
            gcTime: 5 * 60 * 1000,
            retry: false,
            networkMode: 'always',
            refetchOnWindowFocus: false,
        },
        mutations: { retry: false, networkMode: 'always', gcTime: 0 },
    },
});
// Session changes must discard employee-specific results and in-flight reads.

export function clearApiCache() {
    queryClient.clear();
}
