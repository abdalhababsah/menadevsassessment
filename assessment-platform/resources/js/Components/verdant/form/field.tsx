import { Stack } from "../layout/stack";
import { cn } from "@/lib/utils";
export function Field({ label, description, error, children, required }: any) {
  return (
    <Stack gap="tight" className="w-full">
      <div className="flex justify-between items-baseline">
        <label className="text-sm font-medium text-stone-700">{label} {required && <span className="text-destructive">*</span>}</label>
      </div>
      {children}
      {description && <p className="text-xs text-stone-500">{description}</p>}
      {error && <p className="text-xs text-destructive font-medium">{error}</p>}
    </Stack>
  );
}
