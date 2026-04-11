import { Stack } from "../../verdant/Layout/Stack";
import { Button } from "@/components/ui/button";
import { RatingScale } from "../../verdant/Form/RatingScale";
import { Field } from "../../verdant/Form/Field";
import { useState } from "react";
export function EvaluationStep({ side, responseText, criteria, initialValues, onSubmit, busy }: any) {
  const [evals, setEvals] = useState(initialValues || {});
  return (
    <Stack gap="comfortable">
      <div className="p-4 rounded-2xl bg-stone-50 border border-stone-200 border-l-4 border-l-stone-900">
        <p className="text-xs font-bold uppercase tracking-widest text-stone-400 mb-2">Response {side.toUpperCase()}</p>
        <p className="text-sm leading-relaxed text-stone-700 whitespace-pre-wrap">{responseText}</p>
      </div>
      <Stack gap="snug">
        {criteria.map((c: any) => (
          <Field key={c.id} label={c.label} description={c.description}>
             <RatingScale 
               max={5} 
               value={evals[c.id]} 
               onChange={(val:number) => setEvals({ ...evals, [c.id]: val })} 
             />
          </Field>
        ))}
        <div className="flex justify-end">
           <Button variant="verdant" onClick={() => onSubmit(evals)} disabled={busy}>Submit Evaluation</Button>
        </div>
      </Stack>
    </Stack>
  );
}
