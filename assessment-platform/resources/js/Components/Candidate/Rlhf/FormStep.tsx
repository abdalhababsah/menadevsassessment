import { Stack } from "../../verdant/layout/stack";
import { Button } from "../../ui/button";
import { Input } from "../../verdant/form/input";
import { Field } from "../../verdant/form/field";
export function FormStep({ title, description, fields, initialValues, onSubmit, busy }: any) {
  return (
    <Stack gap="comfortable">
      <div><h3 className="text-xl font-display text-slate-900">{title}</h3><p className="text-sm text-slate-500">{description}</p></div>
      <form onSubmit={(e) => { e.preventDefault(); onSubmit({}); }}>
        <Stack gap="snug">
          {fields.map((f: any) => (<Field key={f.id} label={f.label}><Input placeholder={f.placeholder} defaultValue={initialValues?.[f.id]} /></Field>))}
          <Button variant="verdant" type="submit" disabled={busy}>Submit Form</Button>
        </Stack>
      </form>
    </Stack>
  );
}
