<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Models\Role;
use App\Models\User_Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected $authController;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authController = new AuthController();

        // Mock Hash facade
        Hash::shouldReceive('check')->andReturnUsing(function ($value, $hashedValue) {
            return $value === 'correct_password';
        })->byDefault();

        // Mengatur ekspektasi default untuk panggilan statis ke User dan User_Role
        // Ini memastikan bahwa jika ada test yang tidak secara eksplisit memalsukan ini,
        // mereka akan mengembalikan mock yang dapat di-chain tanpa error koneksi.
        // Mock ini akan di-override di setiap test case yang membutuhkan perilaku spesifik.
        User::shouldReceive('where')->andReturn(Mockery::mock('stdClass')->shouldReceive('first')->andReturn(null)->getMock())->byDefault();
        User_Role::shouldReceive('with')->andReturn(Mockery::mock('stdClass')->shouldReceive('where')->andReturn(Mockery::mock('stdClass')->shouldReceive('get')->andReturn(collect())->getMock())->getMock())->byDefault();
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
     * Test login berhasil dengan kredensial yang benar.
     */
    public function test_login_success_with_correct_credentials()
    {
        // Mock objek Role untuk relasi
        $mockRole = Mockery::mock(Role::class);
        $mockRole->name = 'Superadmin';

        // Mock objek User_Role untuk relasi
        $mockUserRole = Mockery::mock(User_Role::class);
        $mockUserRole->role_id = 1;
        $mockUserRole->role = $mockRole; // Menetapkan mock Role ke properti 'role'
        $mockUserRole->roleable_type = null;
        $mockUserRole->roleable_id = null;
        // Mock getAttribute untuk properti 'roleable'
        $mockUserRole->shouldReceive('getAttribute')->with('roleable')->andReturn(null);

        // Mock objek User yang akan ditemukan di database
        $user = Mockery::mock(User::class);
        $user->id = 1;
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = 'hashed_password';
        // Mock properti yang diakses langsung pada objek User
        $user->shouldReceive('__get')->andReturnUsing(function ($key) use ($user) {
            // Ini akan mengembalikan nilai properti yang sudah kita set pada mock User
            return $user->{$key};
        })->byDefault();
        $user->shouldReceive('__set')->andReturnUsing(function ($key, $value) use ($user) {
            $user->{$key} = $value;
        })->byDefault();


        // Mock metode createToken dari model User
        $mockToken = Mockery::mock();
        $mockToken->accessToken = 'mock_access_token';
        $mockToken->plainTextToken = 'mock_plain_text_token';
        $user->shouldReceive('createToken')->with('test@example.com')->andReturn($mockToken);

        // Mock panggilan statis User::where()->first()
        User::shouldReceive('where')
            ->once()
            ->with('email', 'test@example.com')
            ->andReturn(Mockery::mock('stdClass')->shouldReceive('first')->andReturn($user)->getMock());


        // Mock panggilan statis User_Role::with()->where()->get()
        User_Role::shouldReceive('with')
            ->once()
            ->with('role', 'roleable')
            ->andReturn(Mockery::mock('stdClass')->shouldReceive('where')
                ->with('user_id', $user->id)
                ->andReturn(Mockery::mock('stdClass')->shouldReceive('get')->andReturn(collect([$mockUserRole]))->getMock())
                ->getMock());


        $requestData = [
            'email' => 'test@example.com',
            'password' => 'correct_password',
        ];
        $validatedData = [
            'email' => 'test@example.com',
            'password' => 'correct_password',
        ];
        $request = $this->mockRequest($requestData, $validatedData);

        $response = $this->authController->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Login success', $responseData['message']);
        $this->assertEquals($user->id, $responseData['user']['id']);
        $this->assertEquals($user->name, $responseData['user']['name']);
        $this->assertEquals($user->email, $responseData['user']['email']);
        $this->assertArrayHasKey('token', $responseData);
        $this->assertArrayHasKey('roles', $responseData);
        $this->assertEquals([
            [
                'role_id' => 1,
                'role_name' => 'Superadmin',
                'roleable_type' => null,
                'roleable_id' => null,
                'roleable_name' => null,
            ]
        ], $responseData['roles']);
    }

    /**
     * Test login gagal dengan password salah.
     */
    public function test_login_failure_with_incorrect_password()
    {
        $user = Mockery::mock(User::class);
        $user->password = 'hashed_correct_password';
        // Mock properti yang diakses langsung pada objek User
        $user->shouldReceive('__get')->andReturnUsing(function ($key) use ($user) {
            return $user->{$key};
        })->byDefault();
        $user->shouldReceive('__set')->andReturnUsing(function ($key, $value) use ($user) {
            $user->{$key} = $value;
        })->byDefault();


        // Mock panggilan statis User::where()->first() untuk mengembalikan user
        User::shouldReceive('where')
            ->once()
            ->with('email', 'test@example.com')
            ->andReturn(Mockery::mock('stdClass')->shouldReceive('first')->andReturn($user)->getMock());

        Hash::shouldReceive('check')
            ->once()
            ->with('incorrect_password', $user->password)
            ->andReturn(false);

        $requestData = [
            'email' => 'test@example.com',
            'password' => 'incorrect_password',
        ];
        $validatedData = [ // Validasi lolos, tapi cek password gagal
            'email' => 'test@example.com',
            'password' => 'incorrect_password',
        ];
        $request = $this->mockRequest($requestData, $validatedData);

        $response = $this->authController->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'Login Failed',
            'message' => 'The provided credentials are incorrect'
        ]), $response->getContent());
    }

    /**
     * Test login gagal ketika user tidak ditemukan.
     */
    public function test_login_failure_user_not_found()
    {
        // Mock panggilan statis User::where()->first() untuk mengembalikan null (user tidak ditemukan)
        User::shouldReceive('where')
            ->once()
            ->with('email', 'nonexistent@example.com')
            ->andReturn(Mockery::mock('stdClass')->shouldReceive('first')->andReturn(null)->getMock());

        // Pastikan User_Role::with() tidak dipanggil dalam skenario ini
        User_Role::shouldNotReceive('with');

        $requestData = [
            'email' => 'nonexistent@example.com',
            'password' => 'any_password',
        ];
        $validatedData = [ // Validasi lolos, tapi pencarian user gagal
            'email' => 'nonexistent@example.com',
            'password' => 'any_password',
        ];
        $request = $this->mockRequest($requestData, $validatedData);

        $response = $this->authController->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode([
            'status' => 'Login Failed',
            'message' => 'The provided credentials are incorrect'
        ]), $response->getContent());
    }

    /**
     * Test login gagal karena validasi (email kosong).
     */
    public function test_login_validation_failure_missing_email()
    {
        $this->expectException(ValidationException::class);

        // Mock objek Request untuk melempar ValidationException
        $request = $this->mockRequest(
            ['password' => 'any_password'],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The email field is required.']])]))
        );

        $this->authController->login($request);
    }

    /**
     * Test login gagal karena validasi (format email tidak valid).
     */
    public function test_login_validation_failure_invalid_email_format()
    {
        $this->expectException(ValidationException::class);

        // Mock objek Request untuk melempar ValidationException
        $request = $this->mockRequest(
            ['email' => 'invalid-email', 'password' => 'any_password'],
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The email field must be a valid email address.']])]))
        );

        $this->authController->login($request);
    }

    /**
     * Test login gagal karena validasi (password terlalu pendek).
     */
    public function test_login_validation_failure_password_too_short()
    {
        $this->expectException(ValidationException::class);

        // Mock objek Request untuk melempar ValidationException
        $request = $this->mockRequest(
            ['email' => 'test@example.com', 'password' => 'short'], // kurang dari 6 karakter
            [],
            true,
            new ValidationException(Mockery::mock(\Illuminate\Contracts\Validation\Validator::class, ['errors' => Mockery::mock(\Illuminate\Contracts\Support\MessageBag::class, ['all' => ['The password field must be at least 6 characters.']])]))
        );

        $this->authController->login($request);
    }
}
