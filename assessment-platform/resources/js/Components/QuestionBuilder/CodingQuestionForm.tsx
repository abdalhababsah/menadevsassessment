import { useForm } from "@inertiajs/react";
import { Stack } from "../verdant/layout/stack";
import { Frame } from "../verdant/layout/frame";
import { Button } from "../ui/button";
import StemEditor from "./stemeditor";
import { CodeInput } from "../verdant/form/codeinput";
import { RichEditor } from "../verdant/form/richeditor";
import { Field } from "../verdant/form/field";
export default function CodingQuestionForm({ action, submitUrl, tags, initial, title }: any) {
  const { data, setData, post, put, processing, errors } = useForm({
    stem: initial?.stem || '',
    instructions: initial?.instructions || '',
    difficulty: initial?.difficulty || 'medium',
    points: initial?.points || 10,
    coding_config: initial?.coding_config || { language: 'javascript', starter_code: '' },
  });
  return (
    <form onSubmit={(e) => { e.preventDefault(); action === 'create' ? post(submitUrl) : put(submitUrl); }}>
       <Stack gap="comfortable">
          <Frame surface="base"><Stack gap="cozy"><h3>{title}</h3><StemEditor value={data.stem} onChange={(v:string) => setData('stem', v)} error={errors.stem} /></Stack></Frame>
          <Frame surface="base"><Stack gap="cozy"><h3>Instructions</h3><RichEditor content={data.instructions} onChange={(v:string) => setData('instructions', v)} /></Stack></Frame>
          <Frame surface="base"><Stack gap="cozy"><h3>Starter Code</h3><CodeInput value={data.coding_config.starter_code} onChange={(v:any) => setData('coding_config', { ...data.coding_config, starter_code: v || '' })} /></Stack></Frame>
          <div className="flex justify-end gap-3"><Button variant="verdant" type="submit" disabled={processing}>Save</Button></div>
       </Stack>
    </form>
  );
}
