<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end'   => ['nullable', 'date_format:H:i'],

            'note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages()
    {
        return [
            'clock_in.required' => '出勤時間を入力してください。',
            'clock_in.date_format' => '出勤時間を時間形式にしてください。',
            'clock_out.required' => '退勤時間を入力してください。',
            'clock_out.date_format' => '退勤時間を時間形式にしてください。',
            'breaks.*.start.date_format' => '休憩開始時間を時間形式にしてください。',
            'breaks.*.end.date_format'   => '休憩終了時間を時間形式にしてください。',
            'note.required' => '備考を記入してください。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $baseDate = Carbon::parse($this->route('id'))->format('Y-m-d');

            $clockIn = Carbon::parse($baseDate . ' ' . $this->clock_in);
            $clockOut = Carbon::parse($baseDate . ' ' . $this->clock_out);

            if ($clockOut->lte($clockIn)) {
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です。');
                return;
            }

            $validBreaks = [];

            foreach ($this->breaks ?? [] as $index => $break) {

                if (!empty($break['start']) && empty($break['end'])) {
                    $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です。');
                    continue;
                }

                if (empty($break['start']) && !empty($break['end'])) {
                    $validator->errors()->add("breaks.$index.end", '休憩時間が不適切な値です。');
                    continue;
                }

                if (empty($break['start']) && empty($break['end'])) {
                    continue;
                }

                $start = Carbon::parse($baseDate . ' ' . $break['start']);
                $end   = Carbon::parse($baseDate . ' ' . $break['end']);

                $hasError = false;

                if ($start->lt($clockIn)) {
                    $validator->errors()->add("breaks.$index.start", '休憩開始が不適切な値です。');
                    $hasError = true;
                }

                if ($start->gt($clockOut)) {
                    $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です。');
                    $hasError = true;
                }

                if ($end->gt($clockOut)) {
                    $validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です。');
                    $hasError = true;
                }

                if ($end->lte($start)) {
                    $validator->errors()->add("breaks.$index.end", '休憩時間が不適切な値です。');
                    $hasError = true;
                }

                if (!$hasError) {
                    $validBreaks[] = [
                        'index' => $index,
                        'start' => $start,
                        'end'   => $end,
                    ];
                }
            }

            usort($validBreaks, fn($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

            for ($i = 0; $i < count($validBreaks) - 1; $i++) {

                $current = $validBreaks[$i];
                $next    = $validBreaks[$i + 1];

                if ($next['start']->lt($current['end'])) {
                    $validator->errors()->add(
                        "breaks.{$next['index']}.start",
                        '休憩時間が他の休憩と重複しています。'
                    );
                }
            }
        });
    }
}
