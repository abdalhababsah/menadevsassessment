import { Stack } from "../../verdant/layout/stack";
import { CodeInput } from "../../verdant/form/codeinput";
export function CodingQuestion({ stem, config, value, onChange }: any) {
  return (
    <Stack gap="comfortable">
      <div dangerouslySetInnerHTML={{ __html: stem }} />
      <CodeInput value={value.code} onChange={(c:any) => onChange({ ...value, code: c || '' })} language={value.language} height="500px" />
    </Stack>
  );
}
