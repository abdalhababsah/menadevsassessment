<?php

namespace App\Http\Requests\Admin\Roles;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $validPermissions = collect(PermissionSeeder::PERMISSIONS)->flatten()->toArray();

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($validPermissions)],
        ];
    }
}
