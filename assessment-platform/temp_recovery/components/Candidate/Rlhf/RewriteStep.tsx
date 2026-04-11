import { Stack } from "../../verdant/Layout/Stack";
import { Textarea } from "../../verdant/Form/Textarea";
import { Button } from "@/components/ui/button";
import { useState } from "react";
export function RewriteStep({ selectedSide, sourceText, initialRewrite, onSubmit, busy }: any) {
  const [rewrite, setRewrite] = useState(initialRewrite || sourceText);
  return (
    <Stack gap="comfortable">
      <div className="p-4 rounded-2xl bg-stone-50 border border-stone-200">
        <p className="text-xs font-bold text-stone-400 mb-2">Source (Response {selectedSide.toUpperCase()})</p>
        <p className="text-xs line-clamp-4">{sourceText}</p>
      </div>
      <Textarea value={rewrite} onChange={(e) => setRewrite(e.target.value)} placeholder="Rewrite the response here..." rows={10} />
      <div className="flex justify-end">
         <Button variant="verdant" onClick={() => onSubmit(rewrite)} disabled={busy}>Submit Rewrite</Button>
      </div>
    </Stack>
  );
}
