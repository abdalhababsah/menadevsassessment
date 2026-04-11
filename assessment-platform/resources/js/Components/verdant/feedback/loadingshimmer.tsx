import { cn } from "@/lib/utils";
export function LoadingShimmer({ className }: any) {
  return <div className={cn("animate-pulse rounded-md bg-stone-200 dark:bg-stone-800", className)} />;
}
