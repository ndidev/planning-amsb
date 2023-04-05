import { createFlatStore } from "./generics/flatStore";
import type { Tiers } from "@app/types";

/**
 * Store tiers.
 */
export const tiers = createFlatStore<Tiers>("tiers");
