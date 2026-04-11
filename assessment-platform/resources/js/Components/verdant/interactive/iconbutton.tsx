import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
export function IconButton({ icon, label, className, ...props }: any) {
  return <Button variant="ghost" size="icon" className={cn("rounded-full", className)} aria-label={label} {...props}>{icon}</Button>;
}
