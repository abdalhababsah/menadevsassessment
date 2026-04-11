import { Stack } from "../../verdant/Layout/Stack";
import { Button } from "@/components/ui/button";
import { RatingScale } from "../../verdant/Form/RatingScale";
import { Textarea } from "../../verdant/Form/Textarea";
import { useState } from "react";
export function SxsRatingStep({ responseA, responseB, initialRating, initialJustification, onSubmit, busy }: any) {
  const [rating, setRating] = useState(initialRating || 0);
  const [justification, setJustification] = useState(initialJustification || '');
  return (
    <Stack gap="comfortable">
      <div className="grid grid-cols-2 gap-4">
         <div className="p-4 rounded-2xl border border-stone-200 bg-stone-50/50">
            <p className="text-xs font-bold text-stone-400 mb-2">A</p>
            <p className="text-xs line-clamp-6">{responseA}</p>
         </div>
         <div className="p-4 rounded-2xl border border-stone-200 bg-stone-50/50">
            <p className="text-xs font-bold text-stone-400 mb-2">B</p>
            <p className="text-xs line-clamp-6">{responseB}</p>
         </div>
      </div>
      <div className="text-center py-4">
         <p className="text-sm font-medium mb-3">Which response is better?</p>
         <RatingScale value={rating} onChange={setRating} max={7} min={1} />
      </div>
      <Textarea value={justification} onChange={(e) => setJustification(e.target.value)} placeholder="Provide your justification..." rows={4} />
      <div className="flex justify-end">
         <Button variant="verdant" onClick={() => onSubmit(rating, justification)} disabled={busy}>Save Selection</Button>
      </div>
    </Stack>
  );
}
