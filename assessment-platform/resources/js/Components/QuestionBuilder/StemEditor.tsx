import { RichEditor } from "../verdant/form/richeditor";
import { Field } from "../verdant/form/field";
export default function StemEditor({ value, onChange, error }: any) {
  return (
    <Field label="Question Stem" error={error}>
      <RichEditor content={value} onChange={onChange} placeholder="Enter question..." />
    </Field>
  );
}
