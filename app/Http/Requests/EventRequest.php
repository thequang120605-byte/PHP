<?php

namespace App\Http\Requests;

use App\Enums\EventVenue;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:180',
            'description' => 'required|string|max:10000',
            'category_id' => 'required|exists:categories,id',
            'semester_id' => 'nullable|exists:semesters,id',
            'criteria_id' => 'nullable|exists:criterias,id',
            'venue' => ['required', Rule::in(EventVenue::values())],
            'capacity' => 'required|integer|min:1|max:100000',
            'proposed_points' => 'required|integer|min:0|max:20',
            'attendance_rule' => 'nullable|in:end_only',
            'min_minutes_between' => 'nullable|integer|min:0|max:1440',
            'status' => 'required|in:draft,published,cancelled',
            'opens_at' => 'required|date_format:Y-m-d\TH:i',
            'closes_at' => 'required|date_format:Y-m-d\TH:i|after:opens_at',
            'starts_at' => 'required|date_format:Y-m-d\TH:i|after_or_equal:closes_at',
            'ends_at' => 'required|date_format:Y-m-d\TH:i|after:starts_at',
            'dispute_closes_at' => 'nullable|date_format:Y-m-d\TH:i|after:ends_at',
            'poster' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function eventData(): array
    {
        $d = $this->safe()->except('poster');
        foreach (['opens_at', 'closes_at', 'starts_at', 'ends_at'] as $f) {
            $d[$f] = Carbon::createFromFormat('Y-m-d\TH:i', $d[$f], 'Asia/Ho_Chi_Minh')->second(0)->utc();
        }

        if (! empty($d['dispute_closes_at'])) {
            $d['dispute_closes_at'] = Carbon::createFromFormat('Y-m-d\TH:i', $d['dispute_closes_at'], 'Asia/Ho_Chi_Minh')->second(0)->utc();
        } else {
            $d['dispute_closes_at'] = null;
        }

        $d['attendance_rule'] = 'end_only';
        $d['min_minutes_between'] = 0;

        return $d;
    }
}
