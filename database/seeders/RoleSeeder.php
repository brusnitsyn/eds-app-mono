<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleScope;
use App\Models\Scope;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Scope::truncate();
        Role::truncate();
        RoleScope::truncate();

        $scopes = [
            [ 'name' => 'LOGGED_IN', 'description' => '' ],

            [ 'name' => 'CAN_CREATE_STAFF', 'description' => 'Разрешает пользователю добавлять персону' ],
            [ 'name' => 'CAN_READ_STAFF', 'description' => 'Разрешает пользователю просматривать персону' ],
            [ 'name' => 'CAN_UPDATE_STAFF', 'description' => 'Разрешает пользователю редактировать персону' ],
            [ 'name' => 'CAN_DELETE_STAFF', 'description' => 'Разрешает пользователю удалять персону' ],

            [ 'name' => 'CAN_CREATE_JOURNALS', 'description' => 'Доступ к созданию журналов' ],
            [ 'name' => 'CAN_READ_JOURNALS', 'description' => 'Доступ к просмотру журналов' ],
            [ 'name' => 'CAN_UPDATE_JOURNALS', 'description' => 'Доступ к редактированию журналов' ],
            [ 'name' => 'CAN_DELETE_JOURNALS', 'description' => 'Доступ к удалению журналов' ],
        ];

        $roles = [
            [ 'name' => 'Администратор', 'slug' => 'ROLE_ADMIN' ],
            [ 'name' => 'Специалист МИС', 'slug' => 'ROLE_HELPER_MIS' ],
        ];

        $roleScopes = [
            [ 'role_id' => 1, 'scope_id' => 1 ],
            [ 'role_id' => 1, 'scope_id' => 2 ],
            [ 'role_id' => 1, 'scope_id' => 3 ],
            [ 'role_id' => 1, 'scope_id' => 4 ],
            [ 'role_id' => 1, 'scope_id' => 5 ],
            [ 'role_id' => 1, 'scope_id' => 6 ],
            [ 'role_id' => 1, 'scope_id' => 7 ],
            [ 'role_id' => 1, 'scope_id' => 8 ],
            [ 'role_id' => 1, 'scope_id' => 9 ],

            [ 'role_id' => 2, 'scope_id' => 1 ],
            [ 'role_id' => 2, 'scope_id' => 3 ],
            [ 'role_id' => 2, 'scope_id' => 7 ],
        ];

        foreach ($scopes as $scope) {
            Scope::create($scope);
        }

        foreach ($roles as $role) {
            Role::create($role);
        }

        foreach ($roleScopes as $roleScope) {
            RoleScope::create($roleScope);
        }
    }
}
