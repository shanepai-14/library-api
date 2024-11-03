<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'year_level' => 'required|integer|min:1|max:6',
            'department' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
        ];

        // Add unique validation for code on creation
        if ($this->isMethod('post')) {
            $rules['code'] .= '|unique:subjects';
        }

        // Modify unique validation for updates to ignore current record
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['code'] .= '|unique:subjects,code,' . $this->route('subject');
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'code.required' => 'The subject code is required',
            'code.unique' => 'This subject code is already in use',
            'name.required' => 'The subject name is required',
            'year_level.required' => 'The year level is required',
            'year_level.integer' => 'The year level must be a number',
            'year_level.min' => 'The year level must be at least 1',
            'year_level.max' => 'The year level cannot be greater than 6',
            'department.required' => 'The department is required',
            'semester.required' => 'The semester is required',
            'semester.integer' => 'The semester must be a number',
            'semester.min' => 'The semester must be at least 1',
            'semester.max' => 'The semester cannot be greater than 3',
        ];
    }
}