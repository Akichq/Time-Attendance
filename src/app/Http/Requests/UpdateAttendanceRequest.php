<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
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
            'clock_in_time' => 'required|date_format:H:i',
            'clock_out_time' => 'required|date_format:H:i|after:clock_in_time',
            'breaks.*.start' => 'nullable|required_with:breaks.*.end|date_format:H:i',
            'breaks.*.end' => 'nullable|required_with:breaks.*.start|date_format:H:i|after:breaks.*.start',
            'new_breaks.start' => 'nullable|required_with:new_breaks.end|date_format:H:i',
            'new_breaks.end' => 'nullable|required_with:new_breaks.start|date_format:H:i|after:new_breaks.start',
            'remarks' => 'required|string|max:255',
        ];
    }

    /**
     * Get the custom validation messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'clock_in_time.required' => '出勤時間を入力してください',
            'clock_out_time.required' => '退勤時間を入力してください',
            'clock_out_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.required_with' => '休憩開始時間を入力してください',
            'breaks.*.end.required_with' => '休憩終了時間を入力してください',
            'breaks.*.end.after' => '休憩時間が不適切な値です',
            'new_breaks.start.required_with' => '休憩開始時間を入力してください',
            'new_breaks.end.required_with' => '休憩終了時間を入力してください',
            'new_breaks.end.after' => '休憩時間が不適切な値です',
            'remarks.required' => '備考を記入してください',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInTime = $this->input('clock_in_time');
            $clockOutTime = $this->input('clock_out_time');

            // 既存の休憩時間のチェック
            if ($this->input('breaks')) {
                foreach ($this->input('breaks') as $break) {
                    if (isset($break['start']) && isset($break['end'])) {
                        if ($break['start'] < $clockInTime || $break['end'] > $clockOutTime) {
                            $validator->errors()->add('breaks', '休憩時間が勤務時間外です');
                        }
                    }
                }
            }

            // 新しい休憩時間のチェック
            if ($this->input('new_breaks')) {
                $newBreak = $this->input('new_breaks');
                if (!empty($newBreak['start']) && !empty($newBreak['end'])) {
                    if ($newBreak['start'] < $clockInTime || $newBreak['end'] > $clockOutTime) {
                        $validator->errors()->add('new_breaks', '休憩時間が勤務時間外です');
                    }
                }
            }
        });
    }
}
