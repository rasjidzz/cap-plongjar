<?php

namespace Tests\Unit;

use App\Http\Controllers\RoleController;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    protected $roleController;

    protected function setUp(): void
    {
        parent::setUp();

        // Inisialisasi RoleController. Karena konstruktornya memiliki dependensi, kita mock.
        // Asumsi konstruktor RoleController adalah public function __construct(User_Role $userRole, User $user)
        $mockUserRoleModel = Mockery::mock(User_Role::class);
        $mockUserModel = Mockery::mock(User::class);
        $this->roleController = new RoleController($mockUserRoleModel, $mockUserModel);

        // Mengatur ekspektasi default untuk panggilan statis ke model
        // Ini akan di-override di setiap test case yang membutuhkan perilaku spesifik.
        // Penting: Kita tidak lagi membiarkan `find` atau `where` mengembalikan `stdClass` secara default
        // karena itu dapat memicu logika Eloquent yang tidak diinginkan.
        // Setiap panggilan statis harus diatur secara eksplisit di setiap test.
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper untuk memalsukan instance Request, termasuk metode validate-nya.
     *
     * @param array $data Data input request.
     * @param array $validatedData Data yang harus dikembalikan oleh `validate()` (untuk skenario sukses).
     * @param bool $shouldValidate Apakah `validate()` diharapkan dipanggil (true untuk sukses/gagal, false jika validasi dilewati).
     * @param \Exception|null $validationException Jika tidak null, `validate()` akan melempar exception ini.
     * @return Request|\Mockery\MockInterface
     */
    protected function mockRequest(array $data, array $validatedData = [], bool $shouldValidate = true, ?\Exception $validationException = null)
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->andReturn($data);
        foreach ($data as $key => $value) {
            $request->shouldReceive('input')->with($key, Mockery::any())->andReturn($value);
            $request->shouldReceive($key)->andReturn($value); // Untuk akses properti langsung jika digunakan
        }

        if ($shouldValidate) {
            if ($validationException) {
                $request->shouldReceive('validate')->once()->andThrow($validationException);
            } else {
                $request->shouldReceive('validate')->once()->andReturn($validatedData);
            }
        } else {
            // Jika validasi tidak diharapkan dipanggil, pastikan tidak dipanggil.
            $request->shouldNotReceive('validate');
        }

        return $request;
    }

    /**
     * Test assignRole berhasil.
     */
    public function test_assign_role_success()
    {
        // 1. Persiapan Data
        $userId = 1;
        $roleId = 2; // Contoh Role ID
        $roleableId = 10; // Contoh roleable_id
        $roleableType = 'App\\Models\\ProgramStudi'; // Contoh roleable_type

        // Mock User
        $mockUser = Mockery::mock('stdClass'); // Menggunakan stdClass untuk menghindari internal Eloquent
        $mockUser->id = $userId;
        // Mock __get untuk properti id
        $mockUser->shouldReceive('__get')->with('id')->andReturn($userId);

        // Mock Role
        $mockRole = Mockery::mock('stdClass'); // Menggunakan stdClass untuk menghindari internal Eloquent
        $mockRole->id = $roleId;
        // Mock __get untuk properti id
        $mockRole->shouldReceive('__get')->with('id')->andReturn($roleId);


        // 2. Ekspektasi Mock
        // Mock User::find()
        User::shouldReceive('find')->once()->with($userId)->andReturn($mockUser);
        // Mock Role::find()
        Role::shouldReceive('find')->once()->with($roleId)->andReturn($mockRole);

        // Mock rantai User_Role::where()->where()->...->exists()
        $mockExistsQuery = Mockery::mock('stdClass');
        $mockExistsQuery->shouldReceive('exists')->once()->andReturn(false); // Ekspektasi akhir: role belum ada

        $mockWhereRoleableType = Mockery::mock('stdClass');
        $mockWhereRoleableType->shouldReceive('where')->once()->with('roleable_type', $roleableType)->andReturn($mockExistsQuery);

        $mockWhereRoleableId = Mockery::mock('stdClass');
        $mockWhereRoleableId->shouldReceive('where')->once()->with('roleable_id', $roleableId)->andReturn($mockWhereRoleableType);

        $mockWhereRoleId = Mockery::mock('stdClass');
        $mockWhereRoleId->shouldReceive('where')->once()->with('role_id', $roleId)->andReturn($mockWhereRoleableId);

        User_Role::shouldReceive('where')
            ->once()
            ->with('user_id', $userId)
            ->andReturn($mockWhereRoleId);

        // Ekspektasi panggilan create
        User_Role::shouldReceive('create')
            ->once()
            ->with([
                'user_id' => $userId,
                'role_id' => $roleId,
                'roleable_id' => $roleableId,
                'roleable_type' => $roleableType,
            ])
            ->andReturn(Mockery::mock('stdClass')); // Mengembalikan instance mock generik

        // 3. Mock Request
        $requestData = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'roleable_id' => $roleableId,
            'roleable_type' => $roleableType,
        ];
        $request = $this->mockRequest($requestData, $requestData);

        // 4. Aksi
        $response = $this->roleController->assignRole($request);

        // 5. Assertions
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'User Assigned to Role Successfully'
        ]), $response->getContent());
    }

    /**
     * Test assignRole gagal karena User tidak ditemukan.
     */
    public function test_assign_role_user_not_found()
    {
        // 1. Persiapan Data
        $userId = 1;
        $roleId = 2;
        $roleableId = null;
        $roleableType = null;

        // 2. Ekspektasi Mock
        User::shouldReceive('find')->once()->with($userId)->andReturn(null); // User tidak ditemukan
        Role::shouldNotReceive('find'); // Role::find tidak boleh dipanggil
        User_Role::shouldNotReceive('where'); // Panggilan where tidak boleh terjadi
        User_Role::shouldNotReceive('create'); // Panggilan create tidak boleh terjadi

        // 3. Mock Request
        $requestData = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'roleable_id' => $roleableId,
            'roleable_type' => $roleableType,
        ];
        $request = $this->mockRequest($requestData, $requestData);

        // 4. Aksi
        $response = $this->roleController->assignRole($request);

        // 5. Assertions
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'User not Found'
        ]), $response->getContent());
    }

    /**
     * Test assignRole gagal karena Role tidak ditemukan.
     */
    public function test_assign_role_role_not_found()
    {
        // 1. Persiapan Data
        $userId = 1;
        $roleId = 2;
        $roleableId = null;
        $roleableType = null;

        // Mock User
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = $userId;
        $mockUser->shouldReceive('__get')->with('id')->andReturn($userId);

        // 2. Ekspektasi Mock
        User::shouldReceive('find')->once()->with($userId)->andReturn($mockUser);
        Role::shouldReceive('find')->once()->with($roleId)->andReturn(null); // Role tidak ditemukan
        User_Role::shouldNotReceive('where'); // Panggilan where tidak boleh terjadi
        User_Role::shouldNotReceive('create'); // Panggilan create tidak boleh terjadi

        // 3. Mock Request
        $requestData = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'roleable_id' => $roleableId,
            'roleable_type' => $roleableType,
        ];
        $request = $this->mockRequest($requestData, $requestData);

        // 4. Aksi
        $response = $this->roleController->assignRole($request);

        // 5. Assertions
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'Role not Found'
        ]), $response->getContent());
    }

    /**
     * Test assignRole gagal karena Role sudah di-assign.
     */
    public function test_assign_role_already_assigned()
    {
        // 1. Persiapan Data
        $userId = 1;
        $roleId = 2;
        $roleableId = 10;
        $roleableType = 'App\\Models\\ProgramStudi';

        // Mock User
        $mockUser = Mockery::mock('stdClass');
        $mockUser->id = $userId;
        $mockUser->shouldReceive('__get')->with('id')->andReturn($userId);

        // Mock Role
        $mockRole = Mockery::mock('stdClass');
        $mockRole->id = $roleId;
        $mockRole->shouldReceive('__get')->with('id')->andReturn($roleId);


        // 2. Ekspektasi Mock
        User::shouldReceive('find')->once()->with($userId)->andReturn($mockUser);
        Role::shouldReceive('find')->once()->with($roleId)->andReturn($mockRole);

        // Memastikan role sudah di-assign
        $mockExistsQuery = Mockery::mock('stdClass');
        $mockExistsQuery->shouldReceive('exists')->once()->andReturn(true); // Ekspektasi akhir: role sudah ada

        $mockWhereRoleableType = Mockery::mock('stdClass');
        $mockWhereRoleableType->shouldReceive('where')->once()->with('roleable_type', $roleableType)->andReturn($mockExistsQuery);

        $mockWhereRoleableId = Mockery::mock('stdClass');
        $mockWhereRoleableId->shouldReceive('where')->once()->with('roleable_id', $roleableId)->andReturn($mockWhereRoleableType);

        $mockWhereRoleId = Mockery::mock('stdClass');
        $mockWhereRoleId->shouldReceive('where')->once()->with('role_id', $roleId)->andReturn($mockWhereRoleId); // Mengembalikan diri sendiri untuk chain

        // Perbaikan: Pastikan mock where() terakhir mengembalikan rantai yang benar
        $mockWhereRoleId = Mockery::mock('stdClass');
        $mockWhereRoleId->shouldReceive('where')->once()->with('role_id', $roleId)->andReturn(Mockery::mock('stdClass')
            ->shouldReceive('where')->once()->with('roleable_id', $roleableId)
            ->andReturn(Mockery::mock('stdClass')
                ->shouldReceive('where')->once()->with('roleable_type', $roleableType)
                ->andReturn(Mockery::mock('stdClass')
                    ->shouldReceive('exists')->once()->andReturn(true) // Sudah ada
                    ->getMock()
                )->getMock()
            )->getMock()
        );


        User_Role::shouldReceive('where')
            ->once()
            ->with('user_id', $userId)
            ->andReturn($mockWhereRoleId);

        User_Role::shouldNotReceive('create'); // Pastikan create tidak dipanggil

        // 3. Mock Request
        $requestData = [
            'user_id' => $userId,
            'role_id' => $roleId,
            'roleable_id' => $roleableId,
            'roleable_type' => $roleableType,
        ];
        $request = $this->mockRequest($requestData, $requestData);

        // 4. Aksi
        $response = $this->roleController->assignRole($request);

        // 5. Assertions
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'message' => 'User already has this role assigned'
        ]), $response->getContent());
    }

    /**
     * Test assignRole gagal karena validasi (user_id kosong).
     */
    public function test_assign_role_validation_failure_missing_user_id()
    {
        $this->expectException(ValidationException::class);

        // Pastikan model find tidak dipanggil jika validasi gagal
        User::shouldNotReceive('find');
        Role::shouldNotReceive('find');
        User_Role::shouldNotReceive('where');
        User_Role::shouldNotReceive('create');

        $request = $this->mockRequest(
            ['role_id' => 1, 'roleable_id' => null, 'roleable_type' => null],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The user id field is required.']])]))
        );

        $this->roleController->assignRole($request);
    }

    /**
     * Test assignRole gagal karena validasi (role_id kosong).
     */
    public function test_assign_role_validation_failure_missing_role_id()
    {
        $this->expectException(ValidationException::class);

        // Pastikan model find tidak dipanggil jika validasi gagal
        User::shouldNotReceive('find');
        Role::shouldNotReceive('find');
        User_Role::shouldNotReceive('where');
        User_Role::shouldNotReceive('create');

        $request = $this->mockRequest(
            ['user_id' => 1, 'roleable_id' => null, 'roleable_type' => null],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The role id field is required.']])]))
        );

        $this->roleController->assignRole($request);
    }

    /**
     * Test assignRole gagal karena validasi (user_id bukan integer).
     */
    public function test_assign_role_validation_failure_invalid_user_id_type()
    {
        $this->expectException(ValidationException::class);

        // Pastikan model find tidak dipanggil jika validasi gagal
        User::shouldNotReceive('find');
        Role::shouldNotReceive('find');
        User_Role::shouldNotReceive('where');
        User_Role::shouldNotReceive('create');

        $request = $this->mockRequest(
            ['user_id' => 'abc', 'role_id' => 1, 'roleable_id' => null, 'roleable_type' => null],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The user id field must be an integer.']])]))
        );

        $this->roleController->assignRole($request);
    }

    /**
     * Test assignRole gagal karena validasi (role_id bukan integer).
     */
    public function test_assign_role_validation_failure_invalid_role_id_type()
    {
        $this->expectException(ValidationException::class);

        // Pastikan model find tidak dipanggil jika validasi gagal
        User::shouldNotReceive('find');
        Role::shouldNotReceive('find');
        User_Role::shouldNotReceive('where');
        User_Role::shouldNotReceive('create');

        $request = $this->mockRequest(
            ['user_id' => 1, 'role_id' => 'xyz', 'roleable_id' => null, 'roleable_type' => null],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The role id field must be an integer.']])]))
        );

        $this->roleController->assignRole($request);
    }
}
