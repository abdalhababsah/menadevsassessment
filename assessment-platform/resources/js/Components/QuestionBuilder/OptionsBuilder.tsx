import { Stack } from "../verdant/layout/stack";
import { Input } from "../verdant/form/input";
import { IconButton } from "../verdant/interactive/iconbutton";
import { TrashIcon, PlusIcon } from "lucide-react";
import { Checkbox } from "../ui/checkbox";
import { RadioGroup, RadioGroupItem } from "../ui/radio-group";

export interface QuestionOption { id?: number; text: string; is_correct: boolean; }
export function QuestionOptionInput({ options, onChange, type = "single" }: any) {
  const add = () => onChange([...options, { text: '', is_correct: false }]);
  const remove = (i: number) => onChange(options.filter((_:any, idx:number) => idx !== i));
  const update = (i: number, f: any) => {
    const next = [...options];
    next[i] = { ...next[i], ...f };
    if (type === 'single' && f.is_correct) next.forEach((o, idx) => { if (i !== idx) o.is_correct = false; });
    onChange(next);
  };
  return (
    <Stack gap="snug">
      {options.map((opt:any, i:number) => (
        <div key={i} className="flex items-center gap-3">
          {type === 'single' ? <RadioGroup value={opt.is_correct ? 'c' : ''} onValueChange={() => update(i, { is_correct: true })}><RadioGroupItem value="c" id={`o-${i}`}/></RadioGroup> : <Checkbox checked={opt.is_correct} onCheckedChange={(c) => update(i, { is_correct: !!c })} />}
          <Input value={opt.text} onChange={(e:any) => update(i, { text: e.target.value })} className="flex-1" />
          <IconButton icon={<TrashIcon className="w-4 h-4"/>} label="Remove" onClick={() => remove(i)} />
        </div>
      ))}
      <button type="button" onClick={add} className="text-sm text-verdant-600 flex items-center gap-1 hover:underline"><PlusIcon className="w-4 h-4"/> Add Option</button>
    </Stack>
  );
}
