import { MutationObserver } from '@tanstack/vue-query';
import type { MutationObserverOptions } from '@tanstack/vue-query';
import { queryClient } from './queryClient.ts';
import type { ApiError } from '../services/types/common.ts';
// Variables are an argument tuple so existing multi-argument APIs keep working.

export function defineMutation<Args extends unknown[], Data>(group: string, name: string, request: (...args: Args) => Promise<Data>, invalidates: readonly string[] = [group]) {
    const options = (): MutationObserverOptions<Data, ApiError, Args> => ({
        mutationKey: [group, name],
        mutationFn: (args: Args) => request(...args),
        onSuccess: async () => {
            // Cancel reads started before the write before marking related data stale.
            await Promise.all(invalidates.map(key => queryClient.cancelQueries({ queryKey: [key] })));
            await Promise.all(invalidates.map(key => queryClient.invalidateQueries({ queryKey: [key] })));
        },
    });
    const mutation = (...args: Args): Promise<Data> => new MutationObserver<Data, ApiError, Args>(queryClient, options()).mutate(args);
    mutation.options = options;
    return mutation;
}
