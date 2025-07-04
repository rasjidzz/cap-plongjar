<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Untuk mocking koleksi Eloquent
use Mockery; // Alias for Mockery to make it cleaner

class AuthUnitTest extends TestCase
{
    /**
     * Setup the test environment.
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
        // saat menggunakan Facade di unit test murni.
        // Meskipun ini bukan boot Laravel penuh, ini memungkinkan kita memock Facade.
        // Ini adalah "workaround" umum untuk testing Facade di unit test murni.
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
     * Skenario: Login Admin Berhasil
     * Unit Test untuk memverifikasi logika internal AuthController::login
     * ketika kredensial benar.
     *
     * FR-ADM-01LoginTC-ADM-01Login Admin Berhasil
     * Memastikan Admin dapat login dengan memasukkan credential yang benar.
     *
     * @return void
     */
    public function test_login_success_internal_logic(): void
    {
        // 1. Mock Request
        $request = Request::create('/v1/auth/login', 'POST', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        // 2. Mock User model instance dan perilaku query builder
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $userMock->shouldReceive('getAttribute')->with('name')->andReturn('Test Admin');
        $userMock->shouldReceive('getAttribute')->with('email')->andReturn('admin@example.com');
        $userMock->shouldReceive('getAttribute')->with('password')->andReturn(Hash::make('correct-password'));

        // Mock static method `User::where()->first()`
        // Ini perlu dilakukan pada "alias" dari kelas User
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('where->first')
            ->andReturn($userMock)
            ->once(); // Pastikan dipanggil satu kali

        // 3. Mock Hash Facade
        Hash::shouldReceive('check')
            ->with('correct-password', $userMock->getAttribute('password'))
            ->andReturn(true)
            ->once(); // Pastikan dipanggil satu kali

        // 4. Mock createToken method pada instance User
        $mockToken = (object)['plainTextToken' => 'mock-token-string', 'accessToken' => 'mock-access-token-object'];
        $userMock->shouldReceive('createToken')
                 ->with($userMock->getAttribute('email'))
                 ->andReturn($mockToken)
                 ->once(); // Pastikan dipanggil satu kali

        // 5. Mock Role and User_Role for roles collection
        $roleMock = Mockery::mock(Role::class);
        $roleMock->name = 'Superadmin'; // Langsung set properti

        $userRolePivotMock = Mockery::mock('stdClass'); // Mock pivot object
        $userRolePivotMock->roleable_type = null;
        $userRolePivotMock->roleable_id = null;

        $userRoleMock = Mockery::mock(User_Role::class);
        $userRoleMock->role_id = 1;
        $userRoleMock->role = $roleMock; // Assign the mocked Role
        $userRoleMock->pivot = $userRolePivotMock; // Assign the mocked pivot
        $userRoleMock->roleable = null;

        // Mock query builder untuk User_Role::with()->where()->get()
        $userRoleCollectionMock = Mockery::mock(EloquentCollection::class);
        $userRoleCollectionMock->shouldReceive('map')
                               ->andReturn(collect([
                                    [
                                        'role_id' => 1,
                                        'role_name' => 'Superadmin',
                                        'roleable_type' => null,
                                        'roleable_id' => null,
                                        'roleable_name' => null,
                                    ]
                               ]));

        Mockery::mock('alias:'.User_Role::class)
            ->shouldReceive('with->where->get')
            ->andReturn($userRoleCollectionMock)
            ->once(); // Pastikan dipanggil satu kali

        // Create an instance of the controller
        $controller = new AuthController();

        // Call the method under test
        $response = $controller->login($request);

        // Assertions
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'status' => 'success',
                'message' => 'Login success',
                'token' => $mockToken,
                'user' => [
                    'id' => $userMock->getAttribute('id'),
                    'name' => $userMock->getAttribute('name'),
                    'email' => $userMock->getAttribute('email'),
                ],
                'roles' => [
                    [
                        'role_id' => 1,
                        'role_name' => 'Superadmin',
                        'roleable_type' => null,
                        'roleable_id' => null,
                        'roleable_name' => null,
                    ]
                ]
            ]),
            $response->getContent()
        );

        // Verify that all mocks were called as expected
        Mockery::close();
    }

    /**
     * Skenario: Login Admin Gagal (Password Salah)
     * Unit Test untuk memverifikasi logika internal AuthController::login
     * ketika password salah.
     *
     * FR-ADM-01LoginTC-ADM-02Login Admin Gagal
     * Memastikan Admin dapat login dengan memasukkan credential yang salah.
     *
     * @return void
     */
    public function test_login_failure_incorrect_password_internal_logic(): void
    {
        // 1. Mock Request
        $request = Request::create('/v1/auth/login', 'POST', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        // 2. Mock User model instance dan perilaku query builder
        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('getAttribute')->with('id')->andReturn(1); // Tetap definisikan jika diakses
        $userMock->shouldReceive('getAttribute')->with('email')->andReturn('admin@example.com'); // Tetap definisikan

        Mockery::mock('alias:'.User::class)
            ->shouldReceive('where->first')
            ->andReturn($userMock)
            ->once();

        // 3. Mock Hash Facade untuk mengembalikan false
        Hash::shouldReceive('check')
            ->with('wrong-password', Mockery::any()) // Gunakan Mockery::any() karena kita tidak peduli hash passwordnya apa
            ->andReturn(false)
            ->once();

        // Pastikan createToken tidak dipanggil
        $userMock->shouldNotReceive('createToken');

        // Pastikan User_Role tidak dipanggil
        Mockery::mock('alias:'.User_Role::class)
            ->shouldNotReceive('with->where->get');

        // Create an instance of the controller
        $controller = new AuthController();

        // Call the method under test
        $response = $controller->login($request);

        // Assertions
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'status' => 'Login Failed',
                'message' => 'The provided credentials are incorrect'
            ]),
            $response->getContent()
        );

        Mockery::close();
    }

    /**
     * Skenario: Login Admin Gagal (Email tidak ditemukan)
     * Unit Test untuk memverifikasi logika internal AuthController::login
     * ketika email tidak ditemukan.
     *
     * FR-ADM-01LoginTC-ADM-02Login Admin Gagal
     * Memastikan Admin dapat login dengan memasukkan credential yang salah (dalam hal ini email tidak ada).
     *
     * @return void
     */
    public function test_login_failure_non_existent_email_internal_logic(): void
    {
        // 1. Mock Request
        $request = Request::create('/v1/auth/login', 'POST', [
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        // 2. Mock User::where()->first() untuk mengembalikan null (user tidak ditemukan)
        Mockery::mock('alias:'.User::class)
            ->shouldReceive('where->first')
            ->andReturn(null)
            ->once();

        // 3. Pastikan Hash::check tidak dipanggil jika user null
        Hash::shouldNotReceive('check');

        // Pastikan createToken tidak dipanggil
        Mockery::mock(User::class)
            ->shouldNotReceive('createToken');

        // Pastikan User_Role tidak dipanggil
        Mockery::mock('alias:'.User_Role::class)
            ->shouldNotReceive('with->where->get');


        // Create an instance of the controller
        $controller = new AuthController();

        // Call the method under test
        $response = $controller->login($request);

        // Assertions
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'status' => 'Login Failed',
                'message' => 'The provided credentials are incorrect'
            ]),
            $response->getContent()
        );

        Mockery::close();
    }
}
