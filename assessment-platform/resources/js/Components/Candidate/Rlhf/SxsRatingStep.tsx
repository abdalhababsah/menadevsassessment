import { Stack } from "../../verdant/layout/stack";
import { Button } from "../../ui/button";
import { RatingScale } from "../../verdant/form/ratingscale";
import { Textarea } from "../../verdant/form/textarea";
import { useState } from "react";
export function SxsRatingStep({ responseA, responseB, initialRating, initialJustification, onSubmit, busy }: any) {
  const [rating, setRating] = useState(initialRating || 0);
  const [just, setJust] = useState(initialJustification || '');
  return (
    <Stack gap="comfortable">
      <div className="text-center font-medium">Which is better?</div>
      <div className="flex justify-center"><RatingScale value={rating} onChange={setRating} max={7} min={1}/></div>
      <Textarea value={just} onChange={(e:any) => setJust(e.target.value)} placeholder="Justify..." rows={4}/>
      <div className="flex justify-end"><Button variant="verdant" onClick={() => onSubmit(rating, just)} disabled={busy}>Save</Button></div>
    </Stack>
  );
}
