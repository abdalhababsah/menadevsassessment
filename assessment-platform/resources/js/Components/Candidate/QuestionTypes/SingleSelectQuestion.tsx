import { Stack } from "../../verdant/layout/stack";
import { RadioGroup, RadioGroupItem } from "../../ui/radio-group";
export function SingleSelectQuestion({ stem, instructions, options, selectedOptionId, onChange }: any) {
  return (
    <Stack gap="comfortable">
      <div dangerouslySetInnerHTML={{ __html: stem }} />
      <RadioGroup value={String(selectedOptionId)} onValueChange={(v) => onChange(Number(v))}>
        {options.map((o: any) => (
          <label key={o.id} className="flex items-center gap-3 p-4 border rounded-xl hover:border-verdant-500 cursor-pointer">
            <RadioGroupItem value={String(o.id)} id={`o-${o.id}`} />
            <span className="text-sm">{o.content}</span>
          </label>
        ))}
      </RadioGroup>
    </Stack>
  );
}
