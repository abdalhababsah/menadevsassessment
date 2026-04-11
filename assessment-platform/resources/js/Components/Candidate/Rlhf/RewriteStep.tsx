import { Stack } from "../../verdant/layout/stack";
import { Textarea } from "../../verdant/form/textarea";
import { Button } from "../../ui/button";
import { useState } from "react";
export function RewriteStep({ sourceText, initialRewrite, onSubmit, busy }: any) {
  const [rw, setRw] = useState(initialRewrite || sourceText);
  return (
    <Stack gap="comfortable">
      <Textarea value={rw} onChange={(e:any) => setRw(e.target.value)} rows={10}/>
      <div className="flex justify-end"><Button variant="verdant" onClick={() => onSubmit(rw)} disabled={busy}>Submit Rewrite</Button></div>
    </Stack>
  );
}
