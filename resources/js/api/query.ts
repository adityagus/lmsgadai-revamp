import { toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { queryClient } from './queryClient.ts';
// Callable for existing async workflows; .options() also works with useQuery.
type ReactiveArgs<Args extends unknown[]> = {
    [Key in keyof Args]: MaybeRefOrGetter<Args[Key]>;
};

export function defineQuery<Args extends unknown[], Data>(group: string, name: string, request: (args: Args, signal: AbortSignal) => Promise<Data>) {
    const options = (...args: ReactiveArgs<Args>) => ({
        queryKey: [group, name, ...args],
        queryFn: ({ signal }: {
            signal: AbortSignal;
        }) => request(args.map(toValue) as Args, signal),
    });
    const query = async (...args: ReactiveArgs<Args>): Promise<Data> => {
        const data = await queryClient.fetchQuery(options(...args.map(toValue) as Args));
        // Editors may mutate their local copy without corrupting the shared cache.
        return structuredClone(data);
    };
    query.options = options;
    return query;
}
