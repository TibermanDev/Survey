<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Response;
use App\Models\SurveyDay;
use App\Services\ReportVault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-pak-andy';

    protected function setUp(): void
    {
        parent::setUp();
        config(['survei.operator_password' => 'pw-panitia']);
    }

    /** Jawaban valid untuk satu hari; $override untuk mengganti jawaban tertentu. */
    private function answers(int $day, array $override = []): array
    {
        $out = [];
        foreach (config("survei.days.$day.questions") as $q) {
            $out[$q['id']] = match ($q['type']) {
                'likert' => 4,
                'choice' => $q['options'][0],
                'multi' => [$q['options'][0]],
                'contact' => 'Tidak',
                'text' => 'Catatan hari '.$day,
            };
        }

        return array_merge($out, $override);
    }

    private function openDays(int ...$days): void
    {
        SurveyDay::whereIn('day', $days)->update(['is_open' => true]);
    }

    private function fillDay(?string $code, int $day, array $extra = [], array $override = []): string
    {
        $this->post('/mulai', $code ? ['mode' => 'lanjut', 'code' => $code] : ['mode' => 'baru'])
            ->assertRedirect('/isi');
        $this->get('/isi')->assertOk()->assertSee("Hari $day");
        $this->post('/isi', $extra + ['answers' => $this->answers($day, $override)])
            ->assertRedirect('/selesai');

        return $code ?? Participant::orderByDesc('id')->value('code');
    }

    private function unlock(string $key = self::KEY)
    {
        $res = $this->post('/laporan/buka', ['key' => $key]);
        $cookie = $res->getCookie('laporan_k');
        if ($cookie) {
            $this->withCookie('laporan_k', $cookie->getValue());
        }

        return $res;
    }

    public function test_alur_lengkap_karyawan_dan_laporan(): void
    {
        // Pak Andy membuat kunci.
        $this->post('/laporan/buat', ['key' => self::KEY, 'key_confirmation' => self::KEY])
            ->assertOk()->assertSee('Kunci berhasil dibuat');

        $this->openDays(1, 2);

        // Hari 1: karyawan baru, dapat kode.
        $code = $this->fillDay(null, 1, ['name' => 'Budi Santoso', 'division' => 'IT Software']);
        $this->get('/selesai')->assertSee($code)->assertSee('Lanjut isi Hari 2');

        // Hari 2 dengan kode, termasuk jawaban lampu merah.
        $this->fillDay($code, 2, [], ['q9' => 'Sering']);

        // Isi database tidak memuat data terbaca.
        foreach (Response::pluck('payload') as $payload) {
            $this->assertStringNotContainsString('Budi', base64_decode($payload));
            $this->assertStringNotContainsString('Catatan', base64_decode($payload));
        }

        // Kunci salah ditolak.
        $this->post('/laporan/buka', ['key' => 'salah-total'])->assertSessionHasErrors('key');

        // Kunci benar membuka laporan.
        $this->unlock()->assertRedirect('/laporan/ringkasan');
        $this->get('/laporan/ringkasan')->assertOk()
            ->assertSee('Budi Santoso')->assertSee('Kesimpulan')->assertSee('lampu merah')->assertSee('Sering')->assertSee('Catatan hari 1');
        $this->get('/laporan/karyawan')->assertOk()->assertSee($code);
        $this->get("/laporan/karyawan/$code")->assertOk()
            ->assertSee('Budi Santoso')->assertSee('Hari 2: Hubungan dengan Atasan')->assertSee('Belum mengisi');

        // Setelah dikunci, laporan tidak bisa dibuka lagi.
        $this->post('/laporan/kunci');
        $this->get('/laporan/ringkasan')->assertRedirect('/laporan');
    }

    public function test_laporan_tanpa_kunci_dialihkan(): void
    {
        app(ReportVault::class)->setup(self::KEY);

        $this->get('/laporan/ringkasan')->assertRedirect('/laporan');
        $this->get('/laporan')->assertOk()->assertSee('Masukkan kunci');
    }

    public function test_panitia_bisa_membuka_hari_sebelum_kunci_dibuat_dengan_kunci_sementara(): void
    {
        $vault = app(ReportVault::class);

        $this->post('/panitia/masuk', ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->post('/panitia/masuk', ['password' => 'pw-panitia'])->assertRedirect('/panitia');

        $this->post('/panitia/hari/1')->assertSessionHas('status');
        $this->assertTrue(SurveyDay::isOpen(1));
        $this->assertTrue($vault->isTemporary());
        $this->assertFalse($vault->hasOwnerKey());
        $this->get('/panitia')->assertOk()->assertSee('kunci sementara');

        // Karyawan mengisi saat masih kunci sementara; jawaban tetap terenkripsi.
        $code = $this->fillDay(null, 1, ['name' => 'Budi Santoso', 'division' => 'IT Software']);
        $this->assertStringNotContainsString('Budi', base64_decode(Response::value('payload')));
        $this->get('/panitia')->assertDontSee('Budi');

        // Kunci sementara yang tersimpan di server bisa membuka jawaban SEBELUM dipindah.
        $tempSecret = (\Illuminate\Support\Facades\Crypt::decryptString(
            \Illuminate\Support\Facades\DB::table('report_vault')->value('temp_secret')
        ));
        $this->assertNotNull($vault->decrypt(Response::value('payload'), $tempSecret));

        // Laporan belum bisa dibuka; Pak Andy diminta membuat kunci.
        $this->get('/laporan')->assertOk()->assertSee('Buat kunci')->assertSee('1 jawaban');

        // Pak Andy membuat kunci: jawaban dipindah, kunci sementara dihapus.
        $this->post('/laporan/buat', ['key' => self::KEY, 'key_confirmation' => self::KEY])->assertOk();
        $this->assertFalse($vault->isTemporary());
        $this->assertNull(\Illuminate\Support\Facades\DB::table('report_vault')->value('temp_secret'));
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('report_vault')->count());

        // Kunci sementara lama tidak bisa lagi membuka jawaban.
        $this->assertNull($vault->decrypt(Response::value('payload'), $tempSecret));

        // Kunci Pak Andy bisa.
        $this->unlock();
        $this->get("/laporan/karyawan/$code")->assertOk()->assertSee('Budi Santoso');

        // Jawaban baru setelah itu memakai kunci Pak Andy.
        SurveyDay::whereKey(2)->update(['is_open' => true]);
        $this->fillDay($code, 2);
        $this->get("/laporan/karyawan/$code")->assertSee('Hari 2: Hubungan dengan Atasan')->assertDontSee('Belum mengisi.</p>\n', false);
        $this->assertNotNull($vault->decrypt(Response::where('day', 2)->value('payload'), $vault->unlock(self::KEY)));
    }

    public function test_hari_yang_sama_tidak_bisa_diisi_dua_kali_dan_hari_tertutup_ditolak(): void
    {
        app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1);
        $code = $this->fillDay(null, 1, ['division' => 'IT Software']);

        $this->post('/mulai', ['mode' => 'lanjut', 'code' => $code])
            ->assertSessionHas('info');

        $this->post('/mulai', ['mode' => 'lanjut', 'code' => 'ZZZZZZ'])->assertSessionHasErrors('code');

        SurveyDay::query()->update(['is_open' => false]);
        $this->post('/mulai', ['mode' => 'baru'])->assertSessionHasErrors('code');
    }

    public function test_hari_pertama_wajib_divisi_dan_jawaban(): void
    {
        app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1);

        $this->post('/mulai', ['mode' => 'baru']);
        $answers = $this->answers(1);
        unset($answers['q1']);

        $this->post('/isi', ['answers' => $answers])->assertSessionHasErrors(['division', 'answers.q1']);

        // Pertanyaan "pilih maksimal 2" menolak 3 pilihan.
        $answers = $this->answers(1, ['q9' => ['Target terlalu tinggi', 'Permintaan mendadak', 'Lainnya']]);
        $this->post('/isi', ['division' => 'IT Software', 'answers' => $answers])->assertSessionHasErrors('answers.q9');
        $this->assertSame(0, Response::count());
    }

    public function test_pilihan_lainnya_wajib_diisi_dan_muncul_di_laporan(): void
    {
        app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1);
        $this->post('/mulai', ['mode' => 'baru']);

        // Pilih "Lainnya" tanpa isian: ditolak.
        $answers = $this->answers(1, ['q9' => ['Lainnya']]);
        $this->post('/isi', ['division' => 'Fleet', 'answers' => $answers])->assertSessionHasErrors('answers.q9_lainnya');

        // Dengan isian: tersimpan.
        $answers['q9_lainnya'] = 'Rapat terlalu banyak';
        $this->post('/isi', ['division' => 'Fleet', 'answers' => $answers])->assertRedirect('/selesai');
        $code = Participant::value('code');

        $this->unlock();
        $this->get("/laporan/karyawan/$code")->assertSee('Rapat terlalu banyak');
        $this->get('/laporan/ringkasan')->assertSee('Rapat terlalu banyak');
    }

    public function test_divisi_yang_tidak_dipilih_tampil_sebagai_tidak_disebutkan(): void
    {
        app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1);
        $code = $this->fillDay(null, 1, ['division' => 'Saya tidak bersedia memilih']);

        $this->unlock();
        $this->get("/laporan/karyawan/$code")->assertSee('Divisi tidak disebutkan')->assertDontSee('Saya tidak bersedia memilih');
        $this->get('/laporan/ringkasan')->assertSee('Divisi tidak disebutkan')->assertDontSee('Saya tidak bersedia memilih');
    }

    public function test_pertanyaan_kesediaan_nama_di_hari_6_wajib_dan_tampil_di_laporan(): void
    {
        app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1, 2, 3, 4, 5, 6);
        $code = $this->fillDay(null, 1, ['name' => 'Dina', 'division' => 'Delivery']);
        foreach ([2, 3, 4, 5] as $d) {
            $this->fillDay($code, $d);
        }

        // Tanpa jawaban kesediaan: ditolak.
        $this->post('/mulai', ['mode' => 'lanjut', 'code' => $code]);
        $answers = $this->answers(6);
        unset($answers['q11']);
        $this->post('/isi', ['answers' => $answers])->assertSessionHasErrors('answers.q11');

        $this->post('/isi', ['answers' => $this->answers(6, ['q11' => 'Boleh dilibatkan, tetapi nama saya hanya diketahui Pak Andy'])])
            ->assertRedirect('/selesai');

        $this->unlock();
        $this->get("/laporan/karyawan/$code")->assertSee('Nama hanya untuk Pak Andy');
    }

    public function test_lupa_kunci_dengan_kode_pemulihan(): void
    {
        $recovery = app(ReportVault::class)->setup(self::KEY);
        $this->openDays(1);
        $this->fillDay(null, 1, ['name' => 'Sari', 'division' => 'IT Software']);

        $this->post('/laporan/lupa', ['recovery' => 'AAAA-BBBB-CCCC-DDDD', 'key' => 'kunci-baru', 'key_confirmation' => 'kunci-baru'])
            ->assertSessionHasErrors('recovery');

        $this->post('/laporan/lupa', ['recovery' => strtolower($recovery), 'key' => 'kunci-baru', 'key_confirmation' => 'kunci-baru'])
            ->assertOk()->assertSee('Kunci baru tersimpan');

        $this->assertNull(app(ReportVault::class)->unlock(self::KEY));
        $this->unlock('kunci-baru')->assertRedirect('/laporan/ringkasan');
        $this->get('/laporan/karyawan')->assertSee('Sari');

        // Kode pemulihan lama tidak berlaku lagi.
        $this->assertNull(app(ReportVault::class)->recover($recovery, 'x-123456'));
    }
}
