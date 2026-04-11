import { Stack } from "../../verdant/layout/stack";
import { Button } from "../../ui/button";
import { RatingScale } from "../../verdant/form/ratingscale";
import { Field } from "../../verdant/form/field";
import { useState } from "react";
export function EvaluationStep({ side, responseText, criteria, initialValues, onSubmit, busy }: any) {
  const [evals, setEvals] = useState(initialValues || {});
  return (
    <Stack gap="comfortable">
      <div className="p-4 rounded-2xl bg-stone-50 border border-l-4 border-l-stone-900"><p className="text-sm whitespace-pre-wrap">{responseText}</p></div>
      <Stack gap="snug">
        {criteria.map((c: any) => (<Field key={c.id} label={c.label}><RatingScale max={5} value={evals[c.id]} onChange={(v:number) => setEvals({ ...evals, [c.id]: v })}/></Field>))}
        <div className="flex justify-end"><Button variant="verdant" onClick={() => onSubmit(evals)} disabled={busy}>Submit</Button></div>
      </Stack>
    </Stack>
  );
}
