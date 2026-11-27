<?php

namespace App\Http\Controllers;

use App\Services\ReportSession;
use App\Services\ReportVault;
use App\Services\SurveyReport;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/**
 * Laporan hasil survei. Hanya bisa dibuka dengan kunci pribadi Pak Andy.
 */
class LaporanController extends Controller
{
    private const KEY_RULES = ['required', 'string', 'min:6', 'max:200', 'confirmed'];

    private const KEY_MESSAGES = [
        'key.required' => 'Kunci wajib diisi.',
        'key.min' => 'Kunci minimal 6 karakter.',
        'key.confirmed' => 'Ulangan kunci tidak sama.',
    ];

    public function __construct(
        private ReportVault $vault,
        private ReportSession $session,
        private SurveyReport $report,
    ) {}

    public function index(Request $request)
    {
        if (! $this->vault->hasOwnerKey()) {
            return view('laporan.setup', ['pending' => \App\Models\Response::count()]);
        }

        if ($this->session->secret($request)) {
            return redirect()->route('laporan.summary');
        }

        return view('laporan.unlock');
    }

    public function setup(Request $request)
    {
        if ($this->vault->hasOwnerKey()) {
            return redirect()->route('laporan.index');
        }

        $request->validate(['key' => self::KEY_RULES], self::KEY_MESSAGES);

        $recovery = $this->vault->setup($request->input('key'));
        $this->session->start($request, $this->vault->unlock($request->input('key')));

        return view('laporan.recovery', ['recovery' => $recovery, 'isNew' => true]);
    }

    public function unlock(Request $request)
    {
        $secret = $this->vault->unlock((string) $request->input('key'));

        if ($secret === null) {
            return back()->withErrors(['key' => 'Kunci salah. Coba lagi, atau pilih "Lupa kunci".']);
        }

        $this->session->start($request, $secret);

        return redirect()->route('laporan.summary');
    }

    public function forgotForm()
    {
        return view('laporan.forgot');
    }

    public function forgot(Request $request)
    {
        $request->validate(['recovery' => ['required', 'string'], 'key' => self::KEY_RULES],
            self::KEY_MESSAGES + ['recovery.required' => 'Kode pemulihan wajib diisi.']);

        $newRecovery = $this->vault->recover($request->input('recovery'), $request->input('key'));

        if ($newRecovery === null) {
            return back()->withErrors(['recovery' => 'Kode pemulihan salah.']);
        }

        $this->session->start($request, $this->vault->unlock($request->input('key')));

        return view('laporan.recovery', ['recovery' => $newRecovery, 'isNew' => false]);
    }

    public function summary(Request $request)
    {
        return view('laporan.summary', ['r' => $this->report->build($this->secret($request))]);
    }

    public function people(Request $request)
    {
        return view('laporan.people', ['r' => $this->report->build($this->secret($request))]);
    }

    public function person(Request $request, string $code)
    {
        $r = $this->report->build($this->secret($request));
        abort_unless(isset($r['people'][$code]), 404);

        return view('laporan.person', ['p' => $r['people'][$code]]);
    }

    public function changeKeyForm(Request $request)
    {
        $this->secret($request);

        return view('laporan.change-key');
    }

    public function changeKey(Request $request)
    {
        $secret = $this->secret($request);
        $request->validate(['key' => self::KEY_RULES], self::KEY_MESSAGES);

        $this->vault->changeKey($secret, $request->input('key'));

        return redirect()->route('laporan.summary')->with('status', 'Kunci berhasil diganti. Gunakan kunci baru saat membuka laporan berikutnya.');
    }

    public function lock(Request $request)
    {
        $this->session->end($request);

        return redirect()->route('laporan.index')->with('status', 'Laporan sudah dikunci.');
    }

    private function secret(Request $request): string
    {
        $secret = $this->session->secret($request);

        if ($secret === null) {
            throw new HttpResponseException(
                redirect()->route('laporan.index')->with('status', 'Silakan masukkan kunci untuk membuka laporan.')
            );
        }

        return $secret;
    }
}
