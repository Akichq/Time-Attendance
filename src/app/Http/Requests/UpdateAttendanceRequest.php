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
            'breaks.*.break_start_time' => 'nullable|required_with:breaks.*.break_end_time|date_format:H:i',
            'breaks.*.break_end_time' => 'nullable|required_with:breaks.*.break_end_time|date_format:H:i|after:breaks.*.break_start_time',
            'new_breaks.*.break_start_time' => 'nullable|required_with:new_breaks.*.break_end_time|date_format:H:i',
            'new_breaks.*.break_end_time' => 'nullable|required_with:new_breaks.*.break_start_time|date_format:H:i|after:new_breaks.*.break_start_time',
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
            'breaks.*.break_start_time.required_with' => '休憩開始時間を入力してください',
            'breaks.*.break_end_time.required_with' => '休憩終了時間を入力してください',
            'breaks.*.break_end_time.after' => '休憩時間が不適切な値です',
            'new_breaks.*.break_start_time.required_with' => '休憩開始時間を入力してください',
            'new_breaks.*.break_end_time.required_with' => '休憩終了時間を入力してください',
            'new_breaks.*.break_end_time.after' => '休憩時間が不適切な値です',
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
                foreach ($this->input('breaks') as $index => $break) {
                    if (!empty($break['break_start_time']) && !empty($break['break_end_time'])) {
                        // 休憩開始時間が勤務開始時間より前、または休憩終了時間が勤務終了時間より後の場合
                        if ($break['break_start_time'] < $clockInTime || $break['break_end_time'] > $clockOutTime) {
                            $validator->errors()->add("breaks.{$index}.break_start_time", '休憩時間が勤務時間外です');
                        }
                    }
                }
            }

            // 新しい休憩時間のチェック
            if ($this->input('new_breaks')) {
                foreach ($this->input('new_breaks') as $index => $break) {
                        if (!empty($break['break_start_time']) && !empty($break['break_end_time'])) {
                        // 休憩開始時間が勤務開始時間より前、または休憩終了時間が勤務終了時間より後の場合
                        if ($break['break_start_time'] < $clockInTime || $break['break_end_time'] > $clockOutTime) {
                            $validator->errors()->add("new_breaks.{$index}.break_start_time", '休憩時間が勤務時間外です');
                        }
                    }
                }
            }
        });
    }
}