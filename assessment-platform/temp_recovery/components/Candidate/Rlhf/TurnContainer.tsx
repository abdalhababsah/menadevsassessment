import { cn } from "@/lib/utils";
export function TurnContainer({ children, turnNumber, current = false }: any) {
  return (
    <div className={cn("relative pl-8 border-l-2", current ? "border-verdant-500" : "border-stone-200")}>
       <div className={cn("absolute -left-[11px] top-0 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white", current ? "bg-verdant-500" : "bg-stone-300")}>
         {turnNumber}
       </div>
       <div className={cn("rounded-3xl p-6 mb-8", current ? "bg-white ring-1 ring-stone-200 shadow-sm" : "opacity-60")}>
         {children}
       </div>
    </div>
  );
}
