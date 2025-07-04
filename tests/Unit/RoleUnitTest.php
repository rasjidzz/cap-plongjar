<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\RoleController; // Sesuaikan jika ada namespace lain di RoleController
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Http\Request;
use Mockery; // Alias for Mockery

class RoleUnitTest extends TestCase
{
    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan Mockery ditutup setelah setiap test untuk mencegah konflik.
        Mockery::close();

        // Penting: Reset facades untuk setiap test unit.
        // Ini membantu mencegah "A facade root has not been set"
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
    }

    /**
     * Clean up the test environment.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * TC-ADM-03: Admin berhasil memberikan hak akses untuk setiap pengguna.
     * Memastikan Admin dapat memberikan hak ases untuk setiap pengguna.
     * HTTP Response status code = 200 (User Assigned to Role Successfully)
     *
     * @return void
     */
    public function test_assign_role_success(): void
    {
        // 1. Mock Request
        $request = Request::create('/api/v1/roles/assignRole', 'POST', [
            'user_id' => 1,
            'role_id' => 1,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 2. Mock User model and its static `find` method
        $userMock = Mockery::mock(User::class);
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($userMock)
            ->once();

        // 3. Mock Role model and its static `find` method
        $roleMock = Mockery::mock(Role::class);
        Mockery::mock('alias:'.Role::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($roleMock)
            ->once();

        // 4. Mock User_Role model for `where->exists()`
        // Simulate that the role is NOT already assigned
        Mockery::mock('alias:'.User_Role::class)
            ->shouldReceive('where->where->where->where->exists')
            ->andReturn(false)
            ->once();

        // 5. Mock User_Role model for `create()`
        // Simulate successful creation
        Mockery::mock('alias:'.User_Role::class)
            ->shouldReceive('create')
            ->with([
                'user_id' => 1,
                'role_id' => 1,
                'roleable_id' => null,
                'roleable_type' => null,
            ])
            ->andReturn(Mockery::mock(User_Role::class)) // Return a dummy User_Role instance
            ->once();

        // Instantiate the controller (assuming no constructor dependencies or mock them)
        $controller = new RoleController();
        // Jika RoleController memiliki constructor __construct(User_Role $user_role_model), Anda perlu memberikan mock:
        // $userRoleModelMock = Mockery::mock(User_Role::class);
        // $controller = new RoleController($userRoleModelMock);

        // Call the method under test
        $response = $controller->assignRole($request);

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'User Assigned to Role Successfully']),
            $response->getContent()
        );

        // Verify that all mocks were called as expected
        Mockery::close();
    }

    /**
     * TC-ADM-04: Admin gagal memberikan hak akses (User not found).
     * Memastikan Admin gagal memberikan hak akses untuk user yang tidak tersedia (User not found).
     * HTTP Response status code = 401 (User not found)
     *
     * @return void
     */
    public function test_assign_role_failure_user_not_found(): void
    {
        // 1. Mock Request with non-existent user_id
        $request = Request::create('/api/v1/roles/assignRole', 'POST', [
            'user_id' => 999, // Non-existent user ID
            'role_id' => 1,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 2. Mock User model to return null (user not found)
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('find')
            ->with(999)
            ->andReturn(null) // Simulate user not found
            ->once();

        // 3. Ensure other model interactions are NOT called
        Mockery::mock('alias:'.Role::class)->shouldNotReceive('find');
        Mockery::mock('alias:'.User_Role::class)->shouldNotReceive('where->where->where->where->exists');
        Mockery::mock('alias:'.User_Role::class)->shouldNotReceive('create');

        // Instantiate the controller
        $controller = new RoleController();

        // Call the method under test
        $response = $controller->assignRole($request);

        // Assertions
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'User not Found']),
            $response->getContent()
        );

        Mockery::close();
    }

    /**
     * TC-ADM-05: Admin gagal memberikan hak akses (Role not found).
     * Memastikan Admin gagal memberikan hak akses untuk role yang tidak tersedia (Role not found).
     * HTTP Response status code = 401 (Role not found)
     *
     * @return void
     */
    public function test_assign_role_failure_role_not_found(): void
    {
        // 1. Mock Request
        $request = Request::create('/api/v1/roles/assignRole', 'POST', [
            'user_id' => 1,
            'role_id' => 999, // Non-existent role ID
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 2. Mock User model to return a user
        $userMock = Mockery::mock(User::class);
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($userMock)
            ->once();

        // 3. Mock Role model to return null (role not found)
        Mockery::mock('alias:'.Role::class)
            ->shouldReceive('find')
            ->with(999)
            ->andReturn(null) // Simulate role not found
            ->once();

        // 4. Ensure other model interactions are NOT called
        Mockery::mock('alias:'.User_Role::class)->shouldNotReceive('where->where->where->where->exists');
        Mockery::mock('alias:'.User_Role::class)->shouldNotReceive('create');

        // Instantiate the controller
        $controller = new RoleController();

        // Call the method under test
        $response = $controller->assignRole($request);

        // Assertions
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'Role not Found']),
            $response->getContent()
        );

        Mockery::close();
    }

    /**
     * TC-ADM-06: Admin gagal memberikan hak akses (User already has this role assigned).
     * Memastikan Admin gagal memberikan hak akses untuk role yang sudah di assigned (User already has this role assigned).
     * HTTP Response status code = 409 (User already has this role assigned)
     *
     * @return void
     */
    public function test_assign_role_failure_already_assigned(): void
    {
        // 1. Mock Request
        $request = Request::create('/api/v1/roles/assignRole', 'POST', [
            'user_id' => 1,
            'role_id' => 1,
            'roleable_id' => null,
            'roleable_type' => null,
        ]);

        // 2. Mock User model to return a user
        $userMock = Mockery::mock(User::class);
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($userMock)
            ->once();

        // 3. Mock Role model to return a role
        $roleMock = Mockery::mock(Role::class);
        Mockery::mock('alias:'.Role::class)
            ->shouldReceive('find')
            ->with(1)
            ->andReturn($roleMock)
            ->once();

        // 4. Mock User_Role model to simulate that the role IS already assigned
        Mockery::mock('alias:'.User_Role::class)
            ->shouldReceive('where->where->where->where->exists')
            ->andReturn(true) // Simulate already assigned
            ->once();

        // 5. Ensure `create()` is NOT called
        Mockery::mock('alias:'.User_Role::class)->shouldNotReceive('create');

        // Instantiate the controller
        $controller = new RoleController();

        // Call the method under test
        $response = $controller->assignRole($request);

        // Assertions
        $this->assertEquals(409, $response->getStatusCode()); // HTTP 409 Conflict
        $this->assertJsonStringEqualsJsonString(
            json_encode(['message' => 'User already has this role assigned']),
            $response->getContent()
        );

        Mockery::close();
    }
}
