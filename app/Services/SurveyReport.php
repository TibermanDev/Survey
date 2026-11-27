<?php

namespace App\Services;

use App\Models\Response;

/**
 * Menyusun laporan dari jawaban yang sudah didekripsi.
 * Hanya dipanggil saat Pak Andy sudah memasukkan kuncinya.
 */
class SurveyReport
{
    public function __construct(private ReportVault $vault) {}

    public static function day(int $day): array
    {
        return config("survei.days.$day");
    }

    /** Kategori skor untuk tampilan: Perlu perhatian (<3), Cukup (3–3,9), Baik (≥4). */
    public static function level(?float $value): array
    {
        if ($value === null) {
            return ['key' => 'none', 'label' => 'Belum ada data', 'pill' => 'bg-stone-100 text-stone-500', 'dot' => 'bg-stone-400', 'bar' => 'bg-stone-300'];
        }

        $v = round($value, 1);

        return match (true) {
            $v < 3 => ['key' => 'bad', 'label' => 'Perlu perhatian', 'pill' => 'bg-red-100 text-red-800', 'dot' => 'bg-red-500', 'bar' => 'bg-red-500'],
            $v < 4 => ['key' => 'mid', 'label' => 'Cukup', 'pill' => 'bg-amber-100 text-amber-800', 'dot' => 'bg-amber-400', 'bar' => 'bg-amber-400'],
            default => ['key' => 'good', 'label' => 'Baik', 'pill' => 'bg-emerald-100 text-emerald-800', 'dot' => 'bg-emerald-500', 'bar' => 'bg-emerald-500'],
        };
    }

    public static function fmt(?float $value): string
    {
        return $value === null ? '—' : number_format(round($value, 1), 1, ',', '');
    }

    /** Skor 1-5 untuk satu jawaban, atau null jika tidak dihitung. */
    public static function scoreOf(array $q, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($q['type'] === 'likert') {
            $score = (int) $value;
        } elseif ($q['type'] === 'choice' && ! empty($q['scored'])) {
            $idx = array_search($value, $q['options'], true);
            if ($idx === false) {
                return null;
            }
            $score = $idx + 1;
        } else {
            return null;
        }

        return ! empty($q['reverse']) ? 6 - $score : $score;
    }

    public static function dayScore(int $day, array $answers): ?float
    {
        $scores = [];
        foreach (self::day($day)['questions'] as $q) {
            $s = self::scoreOf($q, $answers[$q['id']] ?? null);
            if ($s !== null) {
                $scores[] = $s;
            }
        }

        return $scores ? round(array_sum($scores) / count($scores), 2) : null;
    }

    public static function isFlagged(array $q, mixed $value): bool
    {
        return ! empty($q['flag']) && in_array($value, $q['flag'], true);
    }

    public function build(string $secret): array
    {
        $people = [];

        $responses = Response::with('participant')->orderBy('created_at')->get();
        foreach ($responses as $r) {
            $data = $this->vault->decrypt($r->payload, $secret);
            if ($data === null) {
                continue;
            }

            $code = $r->participant->code;
            $people[$code] ??= [
                'code' => $code,
                'name' => null,
                'division' => null,
                'days' => [],
                'flags' => [],
                'contact' => null,
            ];

            if ($r->day === 1) {
                $people[$code]['name'] = $data['name'] ?? null;
                $people[$code]['division'] = $data['division'] ?? null;
            }

            $answers = $data['answers'] ?? [];
            $people[$code]['days'][$r->day] = [
                'answers' => $answers,
                'score' => self::dayScore($r->day, $answers),
                'submitted_at' => $r->created_at,
            ];

            foreach (self::day($r->day)['questions'] as $i => $q) {
                $value = $answers[$q['id']] ?? null;
                if (self::isFlagged($q, $value)) {
                    $people[$code]['flags'][] = ['day' => $r->day, 'no' => $i + 1, 'text' => $q['text'], 'answer' => $value];
                }
                if ($q['type'] === 'contact' && $value === 'Ya') {
                    $people[$code]['contact'] = $answers[$q['id'].'_kontak'] ?? '';
                }
            }
        }

        foreach ($people as &$p) {
            ksort($p['days']);
            $scores = array_filter(array_column($p['days'], 'score'), fn ($s) => $s !== null);
            $p['avg'] = $scores ? round(array_sum($scores) / count($scores), 2) : null;
            $p['division'] ??= 'Tidak diketahui';
        }
        unset($p);

        uasort($people, fn ($a, $b) => strcmp($a['division'].$a['code'], $b['division'].$b['code']));

        $report = [
            'people' => $people,
            'days' => $this->dayStats($people),
            'divisions' => $this->divisionStats($people),
            'flags' => $this->flagList($people),
            'open' => $this->openAnswers($people),
            'choices' => $this->choiceCounts($people),
            'contacts' => array_values(array_filter($people, fn ($p) => $p['contact'] !== null)),
        ];
        $report['conclusion'] = $this->conclusion($report);

        return $report;
    }

    /** Kesimpulan dalam kalimat biasa untuk bagian paling atas laporan. */
    private function conclusion(array $r): array
    {
        $items = [];
        $people = $r['people'];
        $finished = count(array_filter($people, fn ($p) => count($p['days']) === 6));
        $items[] = ['tone' => 'info', 'text' => count($people)." karyawan sudah mengisi survei, $finished di antaranya sudah menyelesaikan ke-6 hari."];

        $topics = array_filter($r['days'], fn ($d) => $d['avg'] !== null);
        uasort($topics, fn ($a, $b) => $a['avg'] <=> $b['avg']);
        $bad = array_filter($topics, fn ($d) => self::level($d['avg'])['key'] === 'bad');

        if ($bad) {
            $list = implode(', ', array_map(fn ($d) => "{$d['short']} (".self::fmt($d['avg']).')', $bad));
            $items[] = ['tone' => 'bad', 'text' => "Topik yang perlu perhatian: $list."];
        } elseif ($topics) {
            $items[] = ['tone' => 'good', 'text' => 'Tidak ada topik yang masuk kategori "perlu perhatian".'];
        }

        if (count($topics) > 1) {
            $best = end($topics);
            $items[] = ['tone' => self::level($best['avg'])['key'] === 'good' ? 'good' : 'info', 'text' => "Topik dengan skor terbaik: {$best['short']} (".self::fmt($best['avg']).').'];
        }

        $flagged = count(array_unique(array_column($r['flags'], 'code')));
        $items[] = $flagged
            ? ['tone' => 'bad', 'text' => "$flagged karyawan memberi jawaban lampu merah (misalnya perlakuan tidak pantas atau kondisi mental buruk). Daftarnya ada tepat di bawah."]
            : ['tone' => 'good', 'text' => 'Tidak ada jawaban lampu merah.'];

        $divs = array_filter($r['divisions'], fn ($d) => $d['avg'] !== null);
        if (count($divs) > 1) {
            uasort($divs, fn ($a, $b) => $a['avg'] <=> $b['avg']);
            $name = array_key_first($divs);
            $items[] = ['tone' => self::level($divs[$name]['avg'])['key'] === 'bad' ? 'bad' : 'info', 'text' => "Divisi dengan skor terendah: $name (".self::fmt($divs[$name]['avg']).').'];
        }

        if ($r['contacts']) {
            $items[] = ['tone' => 'info', 'text' => count($r['contacts']).' karyawan bersedia dihubungi langsung oleh Pak Andy.'];
        }

        return $items;
    }

    private function dayStats(array $people): array
    {
        $stats = [];
        foreach (config('survei.days') as $d => $day) {
            $scores = [];
            foreach ($people as $p) {
                if (isset($p['days'][$d]) && $p['days'][$d]['score'] !== null) {
                    $scores[] = $p['days'][$d]['score'];
                }
            }
            $stats[$d] = [
                'title' => $day['title'],
                'short' => $day['short'] ?? $day['title'],
                'count' => count(array_filter($people, fn ($p) => isset($p['days'][$d]))),
                'avg' => $scores ? round(array_sum($scores) / count($scores), 2) : null,
            ];
        }

        return $stats;
    }

    private function divisionStats(array $people): array
    {
        $divs = [];
        foreach ($people as $p) {
            $divs[$p['division']]['count'] = ($divs[$p['division']]['count'] ?? 0) + 1;
            foreach ($p['days'] as $d => $day) {
                if ($day['score'] !== null) {
                    $divs[$p['division']]['scores'][$d][] = $day['score'];
                }
            }
        }

        foreach ($divs as &$div) {
            $div['days'] = [];
            foreach (array_keys(config('survei.days')) as $d) {
                $s = $div['scores'][$d] ?? [];
                $div['days'][$d] = $s ? round(array_sum($s) / count($s), 2) : null;
            }
            $all = array_filter($div['days'], fn ($v) => $v !== null);
            $div['avg'] = $all ? round(array_sum($all) / count($all), 2) : null;
            unset($div['scores']);
        }
        unset($div);
        ksort($divs);

        return $divs;
    }

    private function flagList(array $people): array
    {
        $list = [];
        foreach ($people as $p) {
            foreach ($p['flags'] as $f) {
                $list[] = $f + ['code' => $p['code'], 'name' => $p['name'], 'division' => $p['division']];
            }
        }

        return $list;
    }

    private function openAnswers(array $people): array
    {
        $open = [];
        foreach (config('survei.days') as $d => $day) {
            foreach ($day['questions'] as $q) {
                if ($q['type'] !== 'text') {
                    continue;
                }
                $items = [];
                foreach ($people as $p) {
                    $text = trim((string) ($p['days'][$d]['answers'][$q['id']] ?? ''));
                    if ($text !== '') {
                        $items[] = ['code' => $p['code'], 'division' => $p['division'], 'text' => $text];
                    }
                }
                $open[$d] = ['question' => $q['text'], 'items' => $items];
            }
        }

        return $open;
    }

    /** Hitungan jawaban untuk pertanyaan pilihan (choice & multi). */
    private function choiceCounts(array $people): array
    {
        $out = [];
        foreach (config('survei.days') as $d => $day) {
            foreach ($day['questions'] as $i => $q) {
                if (! in_array($q['type'], ['choice', 'multi', 'contact'])) {
                    continue;
                }
                $options = $q['type'] === 'contact' ? ['Ya', 'Tidak'] : $q['options'];
                $counts = array_fill_keys($options, 0);
                foreach ($people as $p) {
                    $v = $p['days'][$d]['answers'][$q['id']] ?? null;
                    foreach ((array) $v as $one) {
                        if (isset($counts[$one])) {
                            $counts[$one]++;
                        }
                    }
                }
                $out[] = ['day' => $d, 'no' => $i + 1, 'question' => $q, 'counts' => $counts];
            }
        }

        return $out;
    }
}
