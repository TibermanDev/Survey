<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\SurveyDay;
use App\Services\ReportVault;
use Illuminate\Http\Request;

/**
 * Panel panitia: membuka/menutup hari survei dan melihat JUMLAH pengisi.
 * Panitia tidak bisa melihat isi jawaban.
 */
class PanitiaController extends Controller
{
    public function __construct(private ReportVault $vault) {}

    public function index(Request $request)
    {
        if (! $request->session()->get('panitia')) {
            return view('panitia.login');
        }

        $counts = Response::selectRaw('day, count(*) as total')->groupBy('day')->pluck('total', 'day');

        return view('panitia.index', [
            'days' => SurveyDay::orderBy('day')->get(),
            'counts' => $counts,
            'ownerKey' => $this->vault->hasOwnerKey(),
            'temporaryKey' => $this->vault->isTemporary(),
            'vaultCreatedAt' => $this->vault->createdAt(),
            'defaultPassword' => config('survei.operator_password') === 'ganti-password-panitia',
        ]);
    }

    public function login(Request $request)
    {
        $expected = (string) config('survei.operator_password');

        if ($expected === '' || ! hash_equals($expected, (string) $request->input('password'))) {
            return back()->withErrors(['password' => 'Password panitia salah.']);
        }

        $request->session()->regenerate();
        $request->session()->put('panitia', true);

        return redirect()->route('panitia.index');
    }

    public function toggle(Request $request, int $day)
    {
        abort_unless($request->session()->get('panitia'), 403);
        $surveyDay = SurveyDay::findOrFail($day);

        // Jika Pak Andy belum membuat kunci, buat kunci sementara agar jawaban tetap terenkripsi.
        if (! $surveyDay->is_open) {
            $this->vault->ensureTemporary();
        }

        $surveyDay->is_open = ! $surveyDay->is_open;
        $surveyDay->is_open ? $surveyDay->opened_at = now() : $surveyDay->closed_at = now();
        $surveyDay->save();

        return back()->with('status', "Hari $day sekarang ".($surveyDay->is_open ? 'DIBUKA' : 'DITUTUP').'.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('panitia');

        return redirect()->route('panitia.index');
    }
}
