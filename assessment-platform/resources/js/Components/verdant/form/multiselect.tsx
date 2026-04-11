import { cn } from "@/lib/utils";
export function MultiSelect({ options, selected, onChange, placeholder = "Select options...", className }: any) {
  // Simple multi-select using multiple select
  return (
    <div className={cn("space-y-2", className)}>
      <select 
        multiple 
        value={selected} 
        onChange={(e) => {
          const vals = Array.from(e.target.selectedOptions, (opt) => opt.value);
          onChange(vals);
        }}
        className="flex w-full rounded-md border border-stone-200 bg-white px-3 py-1 text-sm shadow-sm min-h-[100px]"
      >
        {options.map((opt: any) => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
      </select>
      <p className="text-[10px] text-stone-400 italic">Hold Ctrl/Cmd to select multiple</p>
    </div>
  );
}
