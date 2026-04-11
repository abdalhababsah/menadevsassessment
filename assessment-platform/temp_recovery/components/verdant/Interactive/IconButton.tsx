import { Button } from "@/components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

export function IconButton({ icon, label, onClick, variant = "verdant-ghost", size = "icon", className }: any) {
  return (
    <TooltipProvider><Tooltip delayDuration={300}><TooltipTrigger asChild>
      <Button variant={variant} size={size} onClick={onClick} className={cn("rounded-full", className)} aria-label={label}>{icon}</Button>
    </TooltipTrigger><TooltipContent side="top" className="bg-stone-900 text-white border-none text-xs"><p>{label}</p></TooltipContent></Tooltip></TooltipProvider>
  );
}
