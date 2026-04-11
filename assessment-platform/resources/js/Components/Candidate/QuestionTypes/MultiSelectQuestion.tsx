import { Stack } from "../../verdant/layout/stack";
import { Checkbox } from "../../ui/checkbox";
export function MultiSelectQuestion({ stem, instructions, options, selectedOptionIds, onChange }: any) {
  const toggle = (id: number) => {
    const next = selectedOptionIds.includes(id) ? selectedOptionIds.filter((s:any) => s !== id) : [...selectedOptionIds, id];
    onChange(next);
  };
  return (
    <Stack gap="comfortable">
      <div dangerouslySetInnerHTML={{ __html: stem }} />
      <Stack gap="snug">
        {options.map((o: any) => (
          <label key={o.id} className="flex items-center gap-3 p-4 border rounded-xl hover:border-verdant-500 cursor-pointer">
            <Checkbox checked={selectedOptionIds.includes(o.id)} onCheckedChange={() => toggle(o.id)} />
            <span className="text-sm">{o.content}</span>
          </label>
        ))}
      </Stack>
    </Stack>
  );
}
