import { Stack } from "../../verdant/Layout/Stack";
import { Textarea } from "../../verdant/Form/Textarea";
import { Button } from "@/components/ui/button";
import { useState } from "react";
export function PromptInputStep({ initialValue, guidelines, onSubmit, busy }: any) {
  const [value, setValue] = useState(initialValue);
  return (
    <Stack gap="comfortable">
       <div className="p-4 bg-amber-50 border border-amber-100 rounded-xl text-sm prose prose-amber text-amber-900">
         <h4 className="text-amber-900 mb-2">Guidelines</h4>
         <div dangerouslySetInnerHTML={{ __html: guidelines }} />
       </div>
       <Textarea value={value} onChange={(e) => setValue(e.target.value)} placeholder="Type your prompt here..." rows={6} />
       <div className="flex justify-end">
         <Button variant="verdant" onClick={() => onSubmit(value)} disabled={busy}>Send Prompt</Button>
       </div>
    </Stack>
  );
}
