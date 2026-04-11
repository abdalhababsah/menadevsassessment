import { Stack } from "../../verdant/Layout/Stack";
import { Frame } from "../../verdant/Layout/Frame";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";

export function SingleSelectQuestion({ stem, instructions, options, selectedOptionId, onChange }: any) {
  return (
    <Stack gap="comfortable">
      <div className="prose prose-stone dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: stem }} />
      {instructions && <div className="text-sm text-stone-500 italic mb-4" dangerouslySetInnerHTML={{ __html: instructions }} />}
      
      <RadioGroup value={String(selectedOptionId)} onValueChange={(val) => onChange(Number(val))}>
        <Stack gap="snug">
          {options.map((option: any) => (
            <label key={option.id} className="flex items-center gap-3 p-4 rounded-xl border border-stone-200 hover:border-verdant-500 cursor-pointer transition-all">
              <RadioGroupItem value={String(option.id)} id={`opt-${option.id}`} />
              <span className="text-sm text-stone-700">{option.content}</span>
            </label>
          ))}
        </Stack>
      </RadioGroup>
    </Stack>
  );
}
