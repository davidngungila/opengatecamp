<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private function fullSet(): array
    {
        return Role::PERMISSIONS;
    }

    private function secretarySet(): array
    {
        return [
            'members.view', 'members.manage',
            'events.manage', 'events.complete',
            'pledges.manage',
            'communication.send',
            'documents.view', 'documents.manage',
            'reports.view', 'reports.export',
            'audit.view',
        ];
    }

    private function treasurerSet(): array
    {
        return [
            'members.view',
            'pledges.manage',
            'finance.view', 'finance.manage', 'finance.approve',
            'documents.view',
            'reports.view', 'reports.export',
            'audit.view',
        ];
    }

    private function committeeSet(): array
    {
        return [
            'members.view',
            'events.manage',
            'communication.send',
            'documents.view',
            'reports.view',
        ];
    }

    public function up(): void
    {
        $roles = [
            'Super Administrator' => $this->fullSet(),
            'Chairperson' => $this->fullSet(),
            'Secretary' => $this->secretarySet(),
            'Treasurer' => $this->treasurerSet(),
            'Committee Member' => $this->committeeSet(),
        ];

        $ids = [];
        foreach ($roles as $name => $permissions) {
            $ids[$name] = Role::updateOrCreate(
                ['name' => $name],
                ['permissions' => $permissions]
            )->id;
        }

        $map = [
            'Finance Officer' => 'Treasurer',
            'Ministry Leader' => 'Committee Member',
            'Group Leader' => 'Committee Member',
            'Data Entry' => 'Committee Member',
            'Auditor' => 'Committee Member',
            'Administrator' => 'Chairperson',
            'Chaplain' => 'Committee Member',
        ];

        DB::transaction(function () use ($map, $ids) {
            $oldRoles = Role::whereIn('name', array_keys($map))->pluck('id', 'name');

            foreach ($oldRoles as $oldName => $oldId) {
                $newId = $ids[$map[$oldName]];
                DB::table('users')->where('role_id', $oldId)->update(['role_id' => $newId]);
                Role::whereKey($oldId)->delete();
            }
        });
    }

    public function down(): void
    {
        // The migration reconstructs roles; reversal is a manual data task.
    }
};