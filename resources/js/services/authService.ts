// Compatibility exports. New callers import queries/ or mutations/ directly.
export { login, signOut } from "./mutations/authMutation.ts";
export { getSession } from "./queries/authQuery.ts";
