import { Stack } from "../../verdant/Layout/Stack";
import { Checkbox } from "@/components/ui/checkbox";

export function MultiSelectQuestion({ stem, instructions, options, selectedOptionIds, onChange }: any) {
  const toggle = (id: number) => {
    const next = selectedOptionIds.includes(id) 
      ? selectedOptionIds.filter((s: any) => s !== id) 
      : [...selectedOptionIds, id];
    onChange(next);
  };

  return (
    <Stack gap="comfortable">
      <div className="prose prose-stone dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: stem }} />
      {instructions && <div className="text-sm text-stone-500 italic mb-4" dangerouslySetInnerHTML={{ __html: instructions }} />}
      
      <Stack gap="snug">
        {options.map((option: any) => (
          <label key={option.id} className="flex items-center gap-3 p-4 rounded-xl border border-stone-200 hover:border-verdant-500 cursor-pointer transition-all">
            <Checkbox checked={selectedOptionIds.includes(option.id)} onCheckedChange={() => toggle(option.id)} />
            <span className="text-sm text-stone-700">{option.content}</span>
          </label>
        ))}
      </Stack>
    </Stack>
  );
}
