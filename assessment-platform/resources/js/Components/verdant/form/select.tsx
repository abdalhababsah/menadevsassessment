import { cn } from "@/lib/utils";
export function Select({ options, value, onChange, placeholder = "Select...", className }: any) {
  return (
    <select 
      value={value} 
      onChange={(e) => onChange(e.target.value)}
      className={cn("flex h-9 w-full rounded-md border border-stone-200 bg-white px-3 py-1 text-sm shadow-sm", className)}
    >
      <option value="" disabled>{placeholder}</option>
      {options.map((opt: any) => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
    </select>
  );
}
