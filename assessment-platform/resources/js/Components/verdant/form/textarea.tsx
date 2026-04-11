import { cn } from "@/lib/utils";
export function Textarea({ className, ...props }: any) {
  return <textarea className={cn("flex min-h-[60px] w-full rounded-md border border-stone-200 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-stone-500 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-verdant-500 disabled:cursor-not-allowed disabled:opacity-50", className)} {...props} />;
}
