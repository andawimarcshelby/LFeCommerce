<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('export reports') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_type' => 'required|in:detail,summary,top_n,per_student',
            'format' => 'required|in:pdf,xlsx',
            'filters' => 'required|array',
            'filters.date_from' => 'required|date',
            'filters.date_to' => 'required|date|after_or_equal:filters.date_from',
            'filters.term_ids' => 'sometimes|array',
            'filters.term_ids.*' => 'integer|exists:terms,id',
            'filters.course_ids' => 'sometimes|array',
            'filters.course_ids.*' => 'integer|exists:courses,id',
            'filters.event_types' => 'sometimes|array',
            'filters.event_types.*' => 'string|in:login,page_view,video_watch,quiz_attempt,assignment_submit,forum_post,course_complete',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'report_type.required' => 'Please select a report type.',
            'format.required' => 'Please select an export format (PDF or Excel).',
            'format.in' => 'Export format must be either PDF or Excel.',
            'filters.date_from.required' => 'Start date is required.',
            'filters.date_to.required' => 'End date is required.',
        ];
    }
}
