import { Stack } from "../../verdant/Layout/Stack";
import { CodeInput } from "../../verdant/Form/CodeInput";

export function CodingQuestion({ stem, instructions, config, value, onChange }: any) {
  return (
    <Stack gap="comfortable">
      <div className="prose prose-stone dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: stem }} />
      {instructions && <div className="prose prose-stone dark:prose-invert text-sm" dangerouslySetInnerHTML={{ __html: instructions }} />}
      
      <CodeInput 
        value={value.code} 
        onChange={(code:any) => onChange({ ...value, code: code || '' })} 
        language={value.language} 
        height="500px"
      />
    </Stack>
  );
}
