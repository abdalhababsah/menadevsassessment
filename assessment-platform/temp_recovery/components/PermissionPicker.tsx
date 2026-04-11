import { Stack } from "./verdant/Layout/Stack";
import { Checkbox } from "./ui/checkbox";
import { Label } from "./ui/label";

export default function PermissionPicker({ permissions, selected, onChange }: any) {
  const toggle = (id: number) => {
    const next = selected.includes(id) ? selected.filter((s: any) => s !== id) : [...selected, id];
    onChange(next);
  };

  // Group permissions by prefix (e.g., users.create -> users)
  const groups = permissions.reduce((acc: any, p: any) => {
    const groupName = p.name.split('.')[0] || 'other';
    if (!acc[groupName]) acc[groupName] = [];
    acc[groupName].push(p);
    return acc;
  }, {});

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {Object.entries(groups).map(([name, group]: [string, any]) => (
        <div key={name} className="p-4 border border-stone-100 dark:border-stone-800 rounded-xl bg-stone-50/50">
          <h4 className="font-display text-lg capitalize mb-3 text-stone-900">{name}</h4>
          <Stack gap="snug">
            {group.map((p: any) => (
              <label key={p.id} className="flex items-center gap-2 cursor-pointer">
                <Checkbox checked={selected.includes(p.id)} onCheckedChange={() => toggle(p.id)} />
                <span className="text-sm text-stone-600">{p.name.split('.').slice(1).join(' ') || p.name}</span>
              </label>
            ))}
          </Stack>
        </div>
      ))}
    </div>
  );
}
