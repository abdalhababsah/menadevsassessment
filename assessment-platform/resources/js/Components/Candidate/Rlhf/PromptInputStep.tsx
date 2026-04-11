import { Stack } from "../../verdant/layout/stack";
import { Textarea } from "../../verdant/form/textarea";
import { Button } from "../../ui/button";
import { useState } from "react";
export function PromptInputStep({ initialValue, guidelines, onSubmit, busy }: any) {
  const [value, setValue] = useState(initialValue);
  return (
    <Stack gap="comfortable">
       <div className="p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm prose prose-amber"><div dangerouslySetInnerHTML={{ __html: guidelines }} /></div>
       <Textarea value={value} onChange={(e:any) => setValue(e.target.value)} placeholder="Type prompt..." rows={6} />
       <div className="flex justify-end"><Button variant="verdant" onClick={() => onSubmit(value)} disabled={busy}>Send</Button></div>
    </Stack>
  );
}
