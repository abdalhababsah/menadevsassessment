import { RichEditor } from "../verdant/form/RichEditor";
import { Field } from "../verdant/form/Field";

export default function StemEditor({ value, onChange, error }: any) {
  return (
    <Field label="Question Stem" error={error}>
      <RichEditor 
        content={value} 
        onChange={onChange} 
        placeholder="Enter the question text here..."
      />
    </Field>
  );
}
