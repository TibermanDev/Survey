<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Response;
use App\Models\SurveyDay;
use App\Services\ReportVault;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SurveiController extends Controller
{
    /** Opsi yang memunculkan kolom isian tambahan. */
    public const OTHER = 'Lainnya';

    public function __construct(private ReportVault $vault) {}

    public static function hasOther(array $q): bool
    {
        return in_array($q['type'], ['choice', 'multi'], true) && in_array(self::OTHER, $q['options'] ?? [], true);
    }

    public function index()
    {
        return view('survei.index', [
            'anyOpen' => SurveyDay::where('is_open', true)->exists(),
        ]);
    }

    /** Karyawan memasukkan kode (lanjut) atau mulai baru dari Hari 1. */
    public function start(Request $request)
    {
        if ($request->input('mode') === 'baru') {
            if (! SurveyDay::isOpen(1)) {
                return back()->withErrors(['code' => 'Pengisian Hari 1 sedang ditutup, jadi belum bisa memulai kode baru. Silakan hubungi panitia.']);
            }
            $request->session()->put('survei', ['code' => null, 'day' => 1]);

            return redirect()->route('survei.form');
        }

        $code = Participant::normalizeCode($request->input('code'));
        $participant = $code !== '' ? Participant::where('code', $code)->first() : null;

        if (! $participant) {
            return back()->withInput()->withErrors(['code' => 'Kode tidak ditemukan. Periksa lagi penulisannya. Jika Anda lupa kode, pilih "Mulai dari Hari 1".']);
        }

        $day = $this->nextOpenDay($participant);
        if ($day === null) {
            $message = $participant->nextDay() === null
                ? 'Anda sudah menyelesaikan seluruh survei (Hari 1–6). Terima kasih banyak!'
                : 'Belum ada hari survei baru yang dibuka untuk Anda. Silakan coba lagi saat survei hari berikutnya dibuka.';

            return back()->withInput()->with('info', $message);
        }

        $request->session()->put('survei', ['code' => $participant->code, 'day' => $day]);

        return redirect()->route('survei.form');
    }

    public function form(Request $request)
    {
        $state = $request->session()->get('survei');
        if (! $state || ! SurveyDay::isOpen($state['day'])) {
            return redirect()->route('survei.index')->with('info', $state ? 'Pengisian hari ini sudah ditutup.' : null);
        }

        return view('survei.form', [
            'dayNumber' => $state['day'],
            'day' => config("survei.days.{$state['day']}"),
            'code' => $state['code'],
            'isNew' => $state['code'] === null,
        ]);
    }

    public function submit(Request $request)
    {
        $state = $request->session()->get('survei');
        if (! $state || ! SurveyDay::isOpen($state['day'])) {
            return redirect()->route('survei.index')->with('info', 'Pengisian hari ini sudah ditutup.');
        }

        $dayNumber = $state['day'];
        $day = config("survei.days.$dayNumber");
        $isNew = $state['code'] === null;

        [$rules, $attributes] = $this->rules($day, $isNew, $request);
        $data = $request->validate($rules, $this->messages(), $attributes);

        $payload = ['answers' => []];
        if ($isNew) {
            $payload['name'] = trim((string) ($data['name'] ?? '')) ?: null;
            $payload['division'] = $data['division'];
        }
        foreach ($day['questions'] as $q) {
            $value = $data['answers'][$q['id']] ?? null;
            $payload['answers'][$q['id']] = $q['type'] === 'likert' ? (int) $value : $value;
            if ($q['type'] === 'contact') {
                $payload['answers'][$q['id'].'_kontak'] = trim((string) ($data['answers'][$q['id'].'_kontak'] ?? ''));
            }
            if (self::hasOther($q)) {
                $payload['answers'][$q['id'].'_lainnya'] = in_array(self::OTHER, (array) $value, true)
                    ? trim((string) ($data['answers'][$q['id'].'_lainnya'] ?? ''))
                    : '';
            }
        }

        try {
            $participant = DB::transaction(function () use ($state, $isNew, $dayNumber, $payload) {
                // Enkripsi di dalam transaksi agar tidak bentrok saat kunci sementara
                // sedang dipindah ke kunci Pak Andy.
                $encrypted = $this->vault->encrypt($payload);

                $participant = $isNew
                    ? Participant::create(['code' => Participant::generateCode()])
                    : Participant::where('code', $state['code'])->firstOrFail();

                Response::create([
                    'participant_id' => $participant->id,
                    'day' => $dayNumber,
                    'payload' => $encrypted,
                ]);

                return $participant;
            });
        } catch (UniqueConstraintViolationException) {
            $request->session()->forget('survei');

            return redirect()->route('survei.index')->with('info', "Hari $dayNumber untuk kode ini sudah pernah diisi.");
        }

        $request->session()->forget('survei');
        $request->session()->put('survei_selesai', ['code' => $participant->code, 'day' => $dayNumber]);

        return redirect()->route('survei.done');
    }

    public function done(Request $request)
    {
        $state = $request->session()->get('survei_selesai');
        if (! $state) {
            return redirect()->route('survei.index');
        }

        $participant = Participant::where('code', $state['code'])->first();

        return view('survei.done', [
            'code' => $state['code'],
            'dayNumber' => $state['day'],
            'showHelp' => ! empty(config("survei.days.{$state['day']}.show_help")),
            'nextDay' => $participant ? $this->nextOpenDay($participant) : null,
            'finished' => $participant && $participant->nextDay() === null,
        ]);
    }

    /** Hari terkecil yang belum diisi dan sedang dibuka. */
    private function nextOpenDay(Participant $participant): ?int
    {
        $done = $participant->responses()->pluck('day')->map(fn ($d) => (int) $d)->all();
        $open = SurveyDay::where('is_open', true)->orderBy('day')->pluck('day')->map(fn ($d) => (int) $d);

        return $open->first(fn ($d) => ! in_array($d, $done, true));
    }

    private function rules(array $day, bool $isNew, Request $request): array
    {
        $rules = [];
        $attributes = [];

        if ($isNew) {
            $rules['name'] = ['nullable', 'string', 'max:100'];
            $rules['division'] = ['required', Rule::in(config('survei.divisions'))];
            $attributes['name'] = 'nama';
            $attributes['division'] = 'divisi';
        }

        foreach ($day['questions'] as $i => $q) {
            $key = "answers.{$q['id']}";
            $attributes[$key] = 'pertanyaan no. '.($i + 1);

            $rules[$key] = match ($q['type']) {
                'likert' => ['required', 'integer', 'between:1,5'],
                'choice' => ['required', Rule::in($q['options'])],
                'multi' => array_merge(['required', 'array', 'min:1'], isset($q['max']) ? ['max:'.$q['max']] : []),
                'contact' => ['required', 'in:Ya,Tidak'],
                'text' => ['nullable', 'string', 'max:2000'],
            };

            if ($q['type'] === 'multi') {
                $rules["$key.*"] = [Rule::in($q['options'])];
            }
            if (self::hasOther($q)) {
                $otherChosen = in_array(self::OTHER, (array) $request->input($key), true);
                $rules["{$key}_lainnya"] = [Rule::requiredIf($otherChosen), 'nullable', 'string', 'max:200'];
                $attributes["{$key}_lainnya"] = 'isian "Lainnya" pada pertanyaan no. '.($i + 1);
            }
            if ($q['type'] === 'contact') {
                $rules["{$key}_kontak"] = ['nullable', 'string', 'max:150'];
                $attributes["{$key}_kontak"] = 'kontak';
            }
        }

        return [$rules, $attributes];
    }

    private function messages(): array
    {
        return [
            'required' => ':Attribute wajib dijawab.',
            'in' => 'Pilihan pada :attribute tidak valid.',
            'integer' => 'Pilihan pada :attribute tidak valid.',
            'between' => 'Pilihan pada :attribute tidak valid.',
            'array' => 'Pilihan pada :attribute tidak valid.',
            'min' => ':Attribute wajib dipilih minimal satu.',
            'max' => ['string' => ':Attribute terlalu panjang.', 'array' => 'Pada :attribute, pilih maksimal :max jawaban.'],
            'string' => ':Attribute tidak valid.',
        ];
    }
}
