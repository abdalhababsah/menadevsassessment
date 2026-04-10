import { ChangeEvent } from 'react';

interface PermissionPickerProps {
    permissionGroups: Record<string, string[]>;
    selected: string[];
    onChange: (permissions: string[]) => void;
}

export default function PermissionPicker({ permissionGroups, selected, onChange }: PermissionPickerProps) {
    const toggle = (permission: string) => {
        if (selected.includes(permission)) {
            onChange(selected.filter(p => p !== permission));
        } else {
            onChange([...selected, permission]);
        }
    };

    const toggleGroup = (groupPermissions: string[]) => {
        const allSelected = groupPermissions.every(p => selected.includes(p));
        if (allSelected) {
            onChange(selected.filter(p => !groupPermissions.includes(p)));
        } else {
            const newSelected = new Set([...selected, ...groupPermissions]);
            onChange(Array.from(newSelected));
        }
    };

    return (
        <div className="space-y-4">
            {Object.entries(permissionGroups).map(([group, permissions]) => {
                const allSelected = permissions.every(p => selected.includes(p));
                const someSelected = permissions.some(p => selected.includes(p));

                return (
                    <div key={group} className="rounded-lg border border-gray-200 p-4">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={allSelected}
                                ref={(el) => { if (el) el.indeterminate = someSelected && !allSelected; }}
                                onChange={() => toggleGroup(permissions)}
                                className="rounded border-gray-300 text-indigo-600"
                            />
                            <span className="text-sm font-semibold text-gray-900">{group}</span>
                            <span className="text-xs text-gray-500">
                                ({permissions.filter(p => selected.includes(p)).length}/{permissions.length})
                            </span>
                        </label>
                        <div className="mt-2 ml-6 grid grid-cols-2 gap-2">
                            {permissions.map((permission) => (
                                <label key={permission} className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={selected.includes(permission)}
                                        onChange={() => toggle(permission)}
                                        className="rounded border-gray-300 text-indigo-600"
                                    />
                                    <span className="text-sm text-gray-700">{permission}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
