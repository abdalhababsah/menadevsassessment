import { Stack } from "../verdant/layout/Stack";
import { Input } from "../verdant/form/Input";
import { IconButton } from "../verdant/interactive/IconButton";
import { PlusIcon, TrashIcon, CheckCircleIcon } from "lucide-react";
import { Checkbox } from "../ui/checkbox";
import { RadioGroup, RadioGroupItem } from "../ui/radio-group";

export interface QuestionOption {
  id?: number;
  text: string;
  is_correct: boolean;
  position?: number;
}

export function QuestionOptionInput({ options, onChange, type = 'single' }: { options: QuestionOption[], onChange: (opts: QuestionOption[]) => void, type?: 'single' | 'multi' }) {
  const addOption = () => onChange([...options, { text: '', is_correct: false }]);
  const removeOption = (index: number) => onChange(options.filter((_, i) => i !== index));
  const updateOption = (index: number, fields: Partial<QuestionOption>) => {
    const next = [...options];
    next[index] = { ...next[index], ...fields } as QuestionOption;
    if (type === 'single' && fields.is_correct) {
      next.forEach((opt, i) => { if (i !== index) opt.is_correct = false; });
    }
    onChange(next);
  };

  return (
    <Stack gap="snug">
      {options.map((option, i) => (
        <div key={i} className="flex items-center gap-3">
          {type === 'single' ? (
             <RadioGroup value={option.is_correct ? 'correct' : ''} onValueChange={() => updateOption(i, { is_correct: true })}>
                <RadioGroupItem value="correct" id={`opt-${i}`} />
             </RadioGroup>
          ) : (
            <Checkbox checked={option.is_correct} onCheckedChange={(checked) => updateOption(i, { is_correct: !!checked })} />
          )}
          <Input 
            value={option.text} 
            onChange={(e) => updateOption(i, { text: e.target.value })} 
            placeholder="Option text..."
            className="flex-1"
          />
          <IconButton icon={<TrashIcon className="w-4 h-4" />} label="Remove" variant="ghost" onClick={() => removeOption(i)} />
        </div>
      ))}
      <button type="button" onClick={addOption} className="text-sm text-verdant-600 font-medium flex items-center gap-1 hover:underline">
        <PlusIcon className="w-4 h-4" /> Add Option
      </button>
    </Stack>
  );
}
